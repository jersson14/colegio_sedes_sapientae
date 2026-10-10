<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Domain\Empresa\ColorInstitucion;
use App\Domain\Empresa\PaginaPublica;
use PDO;

/**
 * Cómo se presenta la institución en el acceso, el panel y la página pública (Fase 4, hito 4.7).
 * Las rutas son relativas a la raíz del proyecto (cada vista les antepone lo suyo).
 *
 * - Modo único: las imágenes son las de img/ (la marca de la instalación, como siempre).
 * - Modo múltiple: el logo que cada colegio sube en «Empresa»; si no subió, una marca neutra. Nunca
 *   las imágenes de img/, que son de la instalación original.
 * En los dos: nombre, contacto, color y textos de la página pública salen de la «empresa» de su base.
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
        public readonly ?ColorInstitucion $color = null,
        public readonly ?PaginaPublica $pagina = null,
        public readonly string $email = '',
        public readonly string $telefono = '',
        public readonly string $direccion = '',
    ) {
    }

    /**
     * @param ?PDO $pdo base de la institución; null si no se pudo abrir (se usa lo de por defecto)
     * @param \Closure(string): bool $logoExiste si la ruta guardada en «empresa» es un archivo de la institución
     */
    public static function deLaInstitucion(?PDO $pdo, ModoTenant $modo, \Closure $logoExiste): self
    {
        $e = [];
        if ($pdo !== null) {
            try {
                // SELECT *: una base aún sin migrar (sin emp_color/emp_pagina) también se presenta.
                $fila = $pdo->query('SELECT * FROM empresa ORDER BY empresa_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
                $e = is_array($fila) ? $fila : [];
            } catch (\PDOException) {
                // Sin la tabla (base a medio crear): la marca por defecto, nunca un error en el acceso.
            }
        }
        $texto = static fn (string $c): string => trim((string) ($e[$c] ?? ''));
        $nombre = $texto('emp_razon') !== '' ? $texto('emp_razon') : self::NOMBRE_POR_DEFECTO;
        try {
            $color = ColorInstitucion::desdeTexto($texto('emp_color'));
        } catch (\InvalidArgumentException) {
            $color = null;
        }
        $resto = [$color, PaginaPublica::desdeJson($e['emp_pagina'] ?? null), $texto('emp_email'), $texto('emp_telefono'), $texto('emp_direccion')];

        if ($modo === ModoTenant::Unico) {
            return new self($nombre, 'img/logo1.png', 'img/logo.jpeg', 'img/icono.jpeg', 'img/fondo.jpeg', ...$resto);
        }
        // El logo está en el almacén (exige sesión): se sirve por un endpoint público sin parámetros que
        // solo entrega el logo de esta institución. «v» cambia con el archivo para no ver uno en caché.
        $logo = $texto('emp_logo');
        $logo = $logo !== '' && $logoExiste($logo)
            ? self::LOGO_PUBLICO . '?v=' . substr(hash('sha256', $logo), 0, 10)
            : self::NEUTRA;
        return new self($nombre, $logo, $logo, $logo, null, ...$resto);
    }
}
