<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * El esquema creado por la migración inicial es el del sistema.
 */
final class EsquemaTest extends BaseDatosTestCase
{
    private function contar(string $sql): int
    {
        $q = $this->pdo->prepare($sql);
        $q->execute([getenv('DB_NAME')]);
        return (int) $q->fetchColumn();
    }

    public function testTablasDelSistema(): void
    {
        self::assertSame(36, $this->contar(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name <> 'phinxlog'"
        ));
    }

    public function testProcedimientosAlmacenados(): void
    {
        // 254 del esquema inicial + SP_OBTENER/MODIFICAR_PERSONALIZACION (20261022000000, Fase 4.7).
        self::assertSame(256, $this->contar(
            "SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema = ? AND routine_type = 'PROCEDURE'"
        ));
    }

    public function testEventosProgramados(): void
    {
        // Pasan tareas/exámenes vencidos a FINALIZADO/REALIZADO y actualizan alumnos al cerrar el año.
        self::assertSame(4, $this->contar('SELECT COUNT(*) FROM information_schema.events WHERE event_schema = ?'));
    }

    public function testElLoginEncuentraUnUsuarioConContrasenaBcrypt(): void
    {
        $hash = password_hash('clave-de-prueba', PASSWORD_DEFAULT);
        $this->insertar('roles', ['Id_rol' => 90001, 'tipo_rol' => 'DOCENTE']);
        $this->insertar('usuario', ['usu_id' => 90001, 'usu_usuario' => 'prueba_e2e', 'usu_contra' => $hash,
            'usu_estatus' => 'ACTIVO', 'rol_id' => 90001]);
        $this->insertar('docentes', ['Id_docente' => 90001, 'docente_dni' => '90000001', 'id_asusuario' => 90001]);

        $q = $this->pdo->prepare('CALL SP_VERIFICAR_USUARIO(?)');
        $q->execute(['prueba_e2e']);
        $filas = $q->fetchAll(\PDO::FETCH_ASSOC);
        $q->closeCursor();

        self::assertCount(1, $filas);
        self::assertTrue(password_verify('clave-de-prueba', $filas[0]['usu_contra']));
        // sesion_crear() depende de estos nombres de columna (primer SELECT de la UNION).
        foreach (['usu_id', 'usu_usuario', 'docente_nombre', 'Docente', 'tipo_rol', 'docente_fotoperfil',
                  'docente_movil', 'docente_direccion', 'fechana', 'usu_email', 'docente_dni', 'usu_estatus'] as $col) {
            self::assertArrayHasKey($col, $filas[0], "falta la columna $col que usa core/sesion.php");
        }
    }
}
