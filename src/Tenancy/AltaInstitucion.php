<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Domain\Usuario\Contrasena;
use PDO;

/**
 * Alta de una institución en modo múltiple (Fase 4, hito 4.5):
 * crear la base → migrarla → sembrar el catálogo del sistema → crear el administrador → registrarla
 * en la maestra. El registro va al final: hasta entonces la institución no se puede resolver, así que
 * nadie entra a una base a medio crear. Si un paso falla, se borra la base (solo si la creó este alta).
 */
final class AltaInstitucion
{
    /**
     * Roles con los ids que los procedimientos dan por hechos (SP_REGISTRAR_DOCENTES usa rol 2; el
     * administrador es el 9). Son del sistema, no de cada colegio.
     */
    public const ROLES = [
        1 => ['ESTUDIANTE', 'ROL QUE DA PERMISOS A ESTUDIANTE'],
        2 => ['DOCENTE', 'PERMISOS QUE EL DOCENTE TENDRA'],
        3 => ['AUXILIAR', 'EL AUXILIAR ES LA PERSONA QUE REGISTRA LAS ASISTENCIAS'],
        4 => ['ENFERMERA', 'PERSONA ENCARGADA DE LOS PRIMEROS AUXILIOS'],
        5 => ['PSICOLOGA', 'PERSONA ENCARGADA DE LA SALUD MENTAL DE LOS ESTUDIANTES'],
        9 => ['ADMINISTRADOR', 'ACCESO A TODAS LAS FUNCIONALIDADES DEL SISTEMA'],
    ];

    /**
     * @param PDO $servidor conexión con permisos DDL, sin base seleccionada
     * @param \Closure(string): PDO $conectar abre la base nueva con permisos DDL
     * @param \Closure(string): bool $migrar aplica las migraciones a la base nueva
     */
    public function __construct(
        private readonly PDO $servidor,
        private readonly PDO $maestro,
        private readonly \Closure $conectar,
        private readonly \Closure $migrar,
        private readonly int $diasPrueba = 30,
    ) {
    }

    /** Datos de ejemplo anonimizados (los mismos de las pruebas) para las demos. */
    public const DATOS_DEMO = __DIR__ . '/../../database/seeders/datos_prueba.sql';

    /** @return string la contraseña inicial del administrador (se muestra una vez; no se guarda en claro) */
    public function ejecutar(SolicitudAlta $s): string
    {
        $this->comprobarLibre($s);
        $clave = $this->crearBase($s);
        try {
            // Registro y plan inicial juntos: o queda el colegio completo o nada.
            $this->maestro->beginTransaction();
            try {
                $this->maestro->prepare('INSERT INTO tenants (slug, dominio, razon_social, tipo, base_datos, estado, demo) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$s->slug, $s->dominio, $s->razonSocial, $s->tipo, $s->baseDatos, $s->estado->value, (int) $s->demo]);
                $this->planInicial($s);
                $this->maestro->commit();
            } catch (\Throwable $e) {
                $this->maestro->rollBack();
                throw $e;
            }
            return $clave;
        } catch (\Throwable $e) {
            $this->servidor->exec("DROP DATABASE IF EXISTS `{$s->baseDatos}`");
            throw $e;
        }
    }

    /**
     * Crea y deja lista la base de $s (sin registrarla): migraciones y, según el caso, el catálogo del sistema
     * con su administrador o los datos de demostración. Si algo falla, la base no queda. La usan el alta y
     * la conversión de una demo en cliente.
     *
     * @return string la contraseña inicial del administrador
     */
    public function crearBase(SolicitudAlta $s): string
    {
        $existe = $this->servidor->prepare('SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $existe->execute([$s->baseDatos]);
        if ($existe->fetchColumn() !== false) {
            throw new \DomainException("La base «{$s->baseDatos}» ya existe.");
        }
        $this->servidor->exec("CREATE DATABASE `{$s->baseDatos}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        try {
            if (!($this->migrar)($s->baseDatos)) {
                throw new \RuntimeException('Las migraciones fallaron (detalle arriba).');
            }
            $clave = self::claveInicial();
            $pdo = ($this->conectar)($s->baseDatos);
            $s->demo ? $this->cargarDemo($pdo, $s, $clave) : $this->sembrar($pdo, $s, $clave);
            // Fase 5.1: el tipo vive también en la base de la institución (sus reglas académicas dependen de él).
            (new \App\Institucion\PdoConfiguracionRepositorio($pdo))->guardar(['institucion.tipo' => $s->tipo]);
            return $clave;
        } catch (\Throwable $e) {
            $this->servidor->exec("DROP DATABASE IF EXISTS `{$s->baseDatos}`");
            throw $e;
        }
    }

    /**
     * Demo: los datos de ejemplo, con el nombre y el correo del colegio. Esos datos traen cuentas con una
     * contraseña conocida (está en el repositorio): todas pasan a una contraseña aleatoria que nadie conoce,
     * y el primer administrador del ejemplo pasa a ser el de la demo, con la contraseña generada.
     */
    private function cargarDemo(PDO $pdo, SolicitudAlta $s, string $clave): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET SESSION sql_mode = ''");
        $pdo->exec("SET SESSION time_zone = '-05:00'");
        foreach (preg_split('/;\R/', (string) file_get_contents(self::DATOS_DEMO)) ?: [] as $sql) {
            $sql = trim((string) preg_replace('/^--.*$/m', '', $sql));
            if ($sql !== '') {
                $pdo->exec($sql);
            }
        }
        $pdo->prepare('UPDATE empresa SET emp_razon = ?, emp_email = ? WHERE empresa_id = 1')
            ->execute([mb_strtoupper($s->razonSocial, 'UTF-8'), $s->email]);
        $inservible = $pdo->prepare('UPDATE usuario SET usu_contra = ? WHERE usu_id = ?');
        foreach ($pdo->query('SELECT usu_id FROM usuario')->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $inservible->execute([Contrasena::hash(bin2hex(random_bytes(24))), $id]);
        }
        $admin = (int) $pdo->query('SELECT MIN(usu_id) FROM usuario WHERE rol_id = 9')->fetchColumn();
        $pdo->prepare("UPDATE usuario SET usu_usuario = ?, usu_contra = ?, usu_email = ?, usu_estatus = 'ACTIVO' WHERE usu_id = ?")
            ->execute([$s->adminUsuario, Contrasena::hash($clave), $s->email, $admin]);
    }

    /**
     * Fase 4B: un colegio nuevo empieza con el plan PRUEBA (si la maestra lo tiene) y, si está en PRUEBA,
     * con fecha de fin. Así nunca queda un colegio sin límites por olvido.
     */
    private function planInicial(SolicitudAlta $s): void
    {
        $plan = $this->maestro->query("SELECT id FROM planes WHERE codigo = 'PRUEBA' AND activo = 1")->fetchColumn();
        if ($plan === false) {
            return;
        }
        $this->maestro->prepare('INSERT INTO suscripciones (tenant_id, plan_id, inicio) SELECT id, ?, CURDATE() FROM tenants WHERE slug = ?')
            ->execute([$plan, $s->slug]);
        if ($s->estado === EstadoTenant::Prueba) {
            $this->maestro->prepare('UPDATE tenants SET prueba_hasta = CURDATE() + INTERVAL ? DAY WHERE slug = ?')->execute([$this->diasPrueba, $s->slug]);
        }
    }

    private function comprobarLibre(SolicitudAlta $s): void
    {
        $registro = new PdoRepositorioTenants($this->maestro);
        if ($registro->porSlug($s->slug) !== null) {
            throw new \DomainException("Ya existe una institución «{$s->slug}».");
        }
        if ($s->dominio !== null && $registro->porDominio($s->dominio) !== null) {
            throw new \DomainException("El dominio «{$s->dominio}» ya es de otra institución.");
        }
        $usada = $this->maestro->prepare('SELECT 1 FROM tenants WHERE base_datos = ?');
        $usada->execute([$s->baseDatos]);
        $existe = $this->servidor->prepare('SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
        $existe->execute([$s->baseDatos]);
        if ($usada->fetchColumn() !== false || $existe->fetchColumn() !== false) {
            // Nunca se reutiliza una base existente: podría tener datos de otro colegio.
            throw new \DomainException("La base «{$s->baseDatos}» ya existe.");
        }
    }

    private function sembrar(PDO $pdo, SolicitudAlta $s, string $clave): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('SET NAMES utf8');
        // El mismo sql_mode que la aplicación (Core\Conexion): los procedimientos guardan '' en fechas.
        $pdo->exec("SET SESSION sql_mode = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'");
        $pdo->beginTransaction();
        $rol = $pdo->prepare("INSERT INTO roles (Id_rol, tipo_rol, descripcion, estado, created_at) VALUES (?, ?, ?, 'ACTIVO', NOW())");
        foreach (self::ROLES as $id => [$nombre, $descripcion]) {
            $rol->execute([$id, $nombre, $descripcion]);
        }
        // empresa 1: los procedimientos asignan empresa_id = 1 a toda cuenta nueva.
        $pdo->prepare("INSERT INTO empresa (empresa_id, emp_razon, emp_email, emp_cod, emp_telefono, emp_direccion, emp_logo, created_at)
            VALUES (1, ?, ?, '', '', '', '', NOW())")->execute([mb_strtoupper($s->razonSocial, 'UTF-8'), $s->email]);
        $pdo->commit();

        $admin = $pdo->prepare('CALL SP_REGISTRAR_PERSONAL(?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, 9)');
        $admin->execute([
            $s->adminDni,
            mb_strtoupper($s->adminNombres, 'UTF-8'),
            mb_strtoupper($s->adminApellidos, 'UTF-8'),
            'DIRECTOR',
            '', '', '',
            'controller/personal_administrativo/fotos/VACIO.png',
            $s->adminUsuario,
            Contrasena::hash($clave),
            $s->email,
        ]);
        if ((string) $admin->fetchColumn() !== '1') {
            throw new \RuntimeException('No se pudo crear el administrador.');
        }
        $admin->closeCursor();
    }

    /** 16 caracteres sin ambiguos (0/O, 1/l/I) ni símbolos que el formulario escape. */
    private static function claveInicial(): string
    {
        $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $clave = '';
        for ($i = 0; $i < 16; $i++) {
            $clave .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $clave;
    }
}
