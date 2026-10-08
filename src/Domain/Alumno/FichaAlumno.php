<?php

declare(strict_types=1);

namespace App\Domain\Alumno;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Datos de un alumno tal como se registran o modifican. Si se construye, es válido: los largos
 * respetan las columnas (antes se truncaban sin aviso) y sexo y fecha son valores que la BD acepta
 * (antes un valor inválido se guardaba vacío o como 0000-00-00).
 */
final class FichaAlumno
{
    private const SEXOS = ['FEMENINO', 'MASCULINO'];

    /** @throws InvalidArgumentException */
    public function __construct(
        public readonly string $dni,
        public readonly string $nombres,
        public readonly string $apellidoPaterno,
        public readonly string $apellidoMaterno,
        public readonly string $sexo,
        public readonly string $fechaNacimiento,
        public readonly string $celular,
        public readonly string $direccion,
        public readonly Padres $padres,
    ) {
        Texto::exigirLargo('DNI', $dni, 8, obligatorio: true);
        Texto::exigirLargo('nombres', $nombres, 100, obligatorio: true);
        Texto::exigirLargo('apellido paterno', $apellidoPaterno, 100, obligatorio: true);
        Texto::exigirLargo('apellido materno', $apellidoMaterno, 100);
        Texto::exigirLargo('celular', $celular, 9);
        Texto::exigirLargo('dirección', $direccion, 255);
        if (!in_array($sexo, self::SEXOS, true)) {
            throw new InvalidArgumentException('Sexo no válido');
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaNacimiento);
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaNacimiento) {
            throw new InvalidArgumentException('Fecha de nacimiento no válida');
        }
    }

    /**
     * Desde el formulario del panel (mismos nombres de campo que js/console_alumnos.js).
     *
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public static function desdeFormulario(array $post): self
    {
        $campo = static fn (string $nombre): string => Texto::deFormulario($post[$nombre] ?? '');
        return new self(
            $campo('dni'),
            $campo('nombre'),
            $campo('apepa'),
            $campo('apema'),
            $campo('sexo'),
            $campo('fechanaci'),
            $campo('telf'),
            $campo('direc'),
            new Padres($campo('dnipa'), $campo('nompa'), $campo('celpa'), $campo('dnima'), $campo('nomma'), $campo('celma')),
        );
    }
}
