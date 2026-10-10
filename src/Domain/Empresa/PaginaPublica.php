<?php

declare(strict_types=1);

namespace App\Domain\Empresa;

/**
 * Textos de la página pública de una institución (landing.php, Fase 4.7). Se guardan como JSON en
 * empresa.emp_pagina y se escapan al mostrarse, no al guardarse. Una sección sin contenido no se
 * muestra: la página nunca inventa cifras ni valores que el colegio no escribió.
 *
 * En el formulario las listas van una por línea; las de dos partes, separadas por «|»:
 *   niveles:  «Educación Primaria | Formación académica sólida…»
 *   cifras:   «500 | Estudiantes activos»
 */
final class PaginaPublica
{
    private const MAX = ['lema' => 80, 'bienvenida' => 250, 'nosotrosTitulo' => 120, 'nosotrosTexto' => 3000, 'horario' => 120];

    /**
     * @param list<string> $caracteristicas
     * @param list<array{nombre: string, texto: string}> $niveles
     * @param list<array{nombre: string, texto: string}> $valores
     * @param list<array{valor: int, etiqueta: string}> $cifras
     */
    public function __construct(
        public readonly string $lema = '',
        public readonly string $bienvenida = '',
        public readonly string $nosotrosTitulo = '',
        public readonly string $nosotrosTexto = '',
        public readonly array $caracteristicas = [],
        public readonly array $niveles = [],
        public readonly array $valores = [],
        public readonly array $cifras = [],
        public readonly string $horario = '',
    ) {
        foreach (self::MAX as $campo => $max) {
            if (mb_strlen($this->{$campo}) > $max) {
                throw new \InvalidArgumentException("«{$campo}» admite hasta $max caracteres.");
            }
        }
        self::limite($caracteristicas, 8, 'características');
        self::limite($niveles, 6, 'niveles');
        self::limite($valores, 8, 'valores');
        self::limite($cifras, 4, 'cifras');
    }

    /** @param array<mixed> $lista */
    private static function limite(array $lista, int $max, string $nombre): void
    {
        if (count($lista) > $max) {
            throw new \InvalidArgumentException("Hasta $max $nombre.");
        }
    }

    /** @param array<string, mixed> $f campos del formulario «Personalizar» */
    public static function desdeFormulario(array $f): self
    {
        $texto = static fn (string $c): string => trim(str_replace("\r\n", "\n", (string) ($f[$c] ?? '')));
        return new self(
            $texto('lema'),
            $texto('bienvenida'),
            $texto('nosotros_titulo'),
            $texto('nosotros_texto'),
            array_map(static fn (string $l): string => self::corto($l, 120, 'característica'), self::lineas($texto('caracteristicas'))),
            self::pares($texto('niveles'), 'nivel'),
            self::pares($texto('valores'), 'valor'),
            array_map(static function (string $l): array {
                [$valor, $etiqueta] = array_map('trim', explode('|', $l, 2)) + [1 => ''];
                if (preg_match('/^\d{1,7}$/', $valor) !== 1 || $etiqueta === '') {
                    throw new \InvalidArgumentException("Cifra inválida: «{$l}». Formato: 500 | Estudiantes activos");
                }
                return ['valor' => (int) $valor, 'etiqueta' => self::corto($etiqueta, 40, 'cifra')];
            }, self::lineas($texto('cifras'))),
            $texto('horario'),
        );
    }

    /** @return list<string> */
    private static function lineas(string $texto): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $texto)), static fn (string $l): bool => $l !== ''));
    }

    /** @return list<array{nombre: string, texto: string}> */
    private static function pares(string $texto, string $que): array
    {
        return array_map(static function (string $l) use ($que): array {
            [$nombre, $detalle] = array_map('trim', explode('|', $l, 2)) + [1 => ''];
            return ['nombre' => self::corto($nombre, 60, $que), 'texto' => self::corto($detalle, 300, $que)];
        }, self::lineas($texto));
    }

    private static function corto(string $texto, int $max, string $que): string
    {
        if (mb_strlen($texto) > $max) {
            throw new \InvalidArgumentException("Cada $que admite hasta $max caracteres: «" . mb_substr($texto, 0, 30) . '…»');
        }
        return $texto;
    }

    /** @return array<string, string> los mismos campos del formulario, para editarlos */
    public function aFormulario(): array
    {
        $pares = static fn (array $l): string => implode("\n", array_map(static fn (array $p): string => trim($p['nombre'] . ' | ' . $p['texto'], ' |'), $l));
        return [
            'lema' => $this->lema,
            'bienvenida' => $this->bienvenida,
            'nosotros_titulo' => $this->nosotrosTitulo,
            'nosotros_texto' => $this->nosotrosTexto,
            'caracteristicas' => implode("\n", $this->caracteristicas),
            'niveles' => $pares($this->niveles),
            'valores' => $pares($this->valores),
            'cifras' => implode("\n", array_map(static fn (array $c): string => $c['valor'] . ' | ' . $c['etiqueta'], $this->cifras)),
            'horario' => $this->horario,
        ];
    }

    public function json(): string
    {
        return (string) json_encode(get_object_vars($this), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** Lo guardado en emp_pagina; null si no hay nada o no se puede leer (se usa la página por defecto). */
    public static function desdeJson(?string $json): ?self
    {
        $d = $json !== null && $json !== '' ? json_decode($json, true) : null;
        if (!is_array($d)) {
            return null;
        }
        try {
            return new self(
                (string) ($d['lema'] ?? ''),
                (string) ($d['bienvenida'] ?? ''),
                (string) ($d['nosotrosTitulo'] ?? ''),
                (string) ($d['nosotrosTexto'] ?? ''),
                array_values(array_map('strval', (array) ($d['caracteristicas'] ?? []))),
                self::paresGuardados($d['niveles'] ?? []),
                self::paresGuardados($d['valores'] ?? []),
                array_values(array_map(static fn ($c): array => ['valor' => (int) ($c['valor'] ?? 0), 'etiqueta' => (string) ($c['etiqueta'] ?? '')], (array) ($d['cifras'] ?? []))),
                (string) ($d['horario'] ?? ''),
            );
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /** @return list<array{nombre: string, texto: string}> */
    private static function paresGuardados(mixed $lista): array
    {
        return array_values(array_map(static fn ($p): array => ['nombre' => (string) ($p['nombre'] ?? ''), 'texto' => (string) ($p['texto'] ?? '')], (array) $lista));
    }
}
