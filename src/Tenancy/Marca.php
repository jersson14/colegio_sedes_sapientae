<?php

declare(strict_types=1);

namespace App\Tenancy;

use PDO;

/**
 * Nombre e imágenes con que se presenta la institución en el acceso y en el panel (Fase 4, hito 4.7).
 * Las rutas son relativas a la raíz del proyecto (cada vista les antepone lo suyo).
 *
 * - Modo único: la marca de la instalación son los archivos de img/ (como siempre); el nombre, el de
 *   la empresa en la BD. Una instalación nueva cambia esos archivos o sube su logo en «Empresa».
 * - Modo múltiple: el nombre y el logo de la empresa de cada colegio (módulo «Empresa»); si no subió
 *   logo, una marca neutra. Nunca las imágenes de img/, que son de la instalación original.
 */
final class Marca
{
    public const NEUTRA = 'img/marca_neutra.svg';
    public const NOMBRE_POR_DEFECTO = 'Gestión escolar';
    public const LOGO_PUBLICO = 'controller/archivo/controlador_logo.php';

    public function __construct(
        public readonly string $nombre,
        public readonly string $logoAcceso,
        public readonly string $logoPanel,
        public readonly string $icono,
        public readonly ?string $fondoAcceso,
    ) {
    }

    /**
     * @param ?PDO $pdo base de la institución; null si no se pudo abrir (se usa lo de por defecto)
     * @param \Closure(string): bool $logoExiste si la ruta guardada en «empresa» es un archivo de la institución
     */
    public static function deLaInstitucion(?PDO $pdo, ModoTenant $modo, \Closure $logoExiste): self
    {
        $empresa = ['emp_razon' => '', 'emp_logo' => ''];
        if ($pdo !== null) {
            try {
                $fila = $pdo->query('SELECT emp_razon, emp_logo FROM empresa ORDER BY empresa_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
                $empresa = is_array($fila) ? $fila + $empresa : $empresa;
            } catch (\PDOException) {
                // Sin la tabla (base a medio crear): la marca por defecto, nunca un error en el acceso.
            }
        }
        $nombre = trim((string) $empresa['emp_razon']) !== '' ? trim((string) $empresa['emp_razon']) : self::NOMBRE_POR_DEFECTO;

        if ($modo === ModoTenant::Unico) {
            return new self($nombre, 'img/logo1.png', 'img/logo.jpeg', 'img/icono.jpeg', 'img/fondo.jpeg');
        }
        // El logo está en el almacén (exige sesión): se sirve por un endpoint público sin parámetros que
        // solo entrega el logo de esta institución. «v» cambia con el archivo para no ver uno en caché.
        $logo = (string) $empresa['emp_logo'];
        $logo = $logo !== '' && $logoExiste($logo)
            ? self::LOGO_PUBLICO . '?v=' . substr(hash('sha256', $logo), 0, 10)
            : self::NEUTRA;
        return new self($nombre, $logo, $logo, $logo, null);
    }
}
