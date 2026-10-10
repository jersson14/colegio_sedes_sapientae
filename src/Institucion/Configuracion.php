<?php

declare(strict_types=1);

namespace App\Institucion;

/**
 * Configuración académica de una institución (Fase 5.1): lo que cambia entre un colegio y un instituto sin
 * bifurcar el código. Cada clave tiene un valor por defecto según el tipo de institución; la tabla
 * `configuracion` solo guarda lo que la institución cambió.
 *
 * Los valores por defecto de institutos y CETPRO siguen la práctica habitual (escala vigesimal con nota
 * mínima 13, semestres, créditos, sin apoderado); se validan con el piloto (5.10) y se pueden cambiar sin
 * tocar código.
 */
final class Configuracion
{
    /** clave => [tipo, valores permitidos | [mínimo, máximo]] */
    public const CLAVES = [
        'institucion.tipo' => ['enum', ['COLEGIO', 'INSTITUTO', 'CETPRO']],
        'periodo.tipo' => ['enum', ['BIMESTRE', 'TRIMESTRE', 'CUATRIMESTRE', 'SEMESTRE']],
        'evaluacion.nota_minima' => ['entero', [0, 20]],
        'evaluacion.ponderacion' => ['enum', ['SIMPLE', 'POR_CREDITOS']],
        // Desde qué nota una unidad desaprobada tiene evaluación de recuperación (igual a la mínima = sin recuperación).
        'evaluacion.recuperacion_desde' => ['entero', [0, 20]],
        'apoderado.obligatorio' => ['booleano', []],
        'matricula.modo' => ['enum', ['POR_AULA', 'POR_UNIDAD']],
    ];

    private const POR_DEFECTO = [
        'COLEGIO' => [
            'periodo.tipo' => 'BIMESTRE',
            'evaluacion.nota_minima' => '11',
            'evaluacion.ponderacion' => 'SIMPLE',
            'evaluacion.recuperacion_desde' => '11',
            'apoderado.obligatorio' => '1',
            'matricula.modo' => 'POR_AULA',
        ],
        'INSTITUTO' => [
            'periodo.tipo' => 'SEMESTRE',
            'evaluacion.nota_minima' => '13',
            'evaluacion.ponderacion' => 'POR_CREDITOS',
            'evaluacion.recuperacion_desde' => '10',
            'apoderado.obligatorio' => '0',
            'matricula.modo' => 'POR_UNIDAD',
        ],
        'CETPRO' => [
            'periodo.tipo' => 'SEMESTRE',
            'evaluacion.nota_minima' => '13',
            'evaluacion.ponderacion' => 'POR_CREDITOS',
            'evaluacion.recuperacion_desde' => '10',
            'apoderado.obligatorio' => '0',
            'matricula.modo' => 'POR_UNIDAD',
        ],
    ];

    /** Periodos por año según su tipo (un cuatrimestre son cuatro meses: tres por año). */
    private const PERIODOS = ['BIMESTRE' => 4, 'TRIMESTRE' => 3, 'CUATRIMESTRE' => 3, 'SEMESTRE' => 2];

    /** @var array<string, string> */
    private readonly array $guardado;

    /** @param array<string, string> $guardado lo que hay en la tabla (claves desconocidas o inválidas se ignoran) */
    public function __construct(array $guardado = [])
    {
        $validos = [];
        foreach ($guardado as $clave => $valor) {
            try {
                $normalizado = self::validar((string) $clave, (string) $valor);
            } catch (\InvalidArgumentException) {
                continue; // un valor corrupto no debe impedir entrar: se usa el de por defecto
            }
            if ($normalizado !== null) {
                $validos[(string) $clave] = $normalizado;
            }
        }
        $this->guardado = $validos;
    }

    /**
     * Normaliza un valor para guardarlo; null = volver al de por defecto.
     *
     * @throws \InvalidArgumentException con un motivo legible
     */
    public static function validar(string $clave, ?string $valor): ?string
    {
        if (!isset(self::CLAVES[$clave])) {
            throw new \InvalidArgumentException("Opción de configuración desconocida: «{$clave}».");
        }
        if ($valor === null || trim($valor) === '') {
            return null;
        }
        $valor = trim($valor);
        [$tipo, $regla] = self::CLAVES[$clave];
        return match ($tipo) {
            'enum' => in_array(strtoupper($valor), $regla, true)
                ? strtoupper($valor)
                : throw new \InvalidArgumentException("«{$clave}» admite: " . implode(', ', $regla) . '.'),
            'entero' => preg_match('/^\d{1,3}$/', $valor) === 1 && (int) $valor >= $regla[0] && (int) $valor <= $regla[1]
                ? (string) (int) $valor
                : throw new \InvalidArgumentException("«{$clave}» debe ser un número entre {$regla[0]} y {$regla[1]}."),
            // booleano
            default => in_array(strtolower($valor), ['1', 'si', 'sí', 'true'], true) ? '1'
                : (in_array(strtolower($valor), ['0', 'no', 'false'], true) ? '0'
                : throw new \InvalidArgumentException("«{$clave}» admite sí o no.")),
        };
    }

    public function tipo(): TipoInstitucion
    {
        return TipoInstitucion::from($this->guardado['institucion.tipo'] ?? 'COLEGIO');
    }

    public function valor(string $clave): string
    {
        if ($clave === 'institucion.tipo') {
            return $this->tipo()->value;
        }
        return $this->guardado[$clave] ?? self::POR_DEFECTO[$this->tipo()->value][$clave]
            ?? throw new \InvalidArgumentException("Opción de configuración desconocida: «{$clave}».");
    }

    public function esPorDefecto(string $clave): bool
    {
        return !isset($this->guardado[$clave]);
    }

    public function tipoPeriodo(): string
    {
        return $this->valor('periodo.tipo');
    }

    public function cantidadPeriodos(): int
    {
        return self::PERIODOS[$this->tipoPeriodo()];
    }

    /** «Bimestre», «Semestre»… para los textos de la interfaz. */
    public function etiquetaPeriodo(): string
    {
        return ucfirst(strtolower($this->tipoPeriodo()));
    }

    public function notaMinima(): int
    {
        return (int) $this->valor('evaluacion.nota_minima');
    }

    /** Desde qué nota hay recuperación (nunca por encima de la mínima: sería un rango vacío al revés). */
    public function recuperacionDesde(): int
    {
        return min((int) $this->valor('evaluacion.recuperacion_desde'), $this->notaMinima());
    }

    public function ponderaPorCreditos(): bool
    {
        return $this->valor('evaluacion.ponderacion') === 'POR_CREDITOS';
    }

    public function apoderadoObligatorio(): bool
    {
        return $this->valor('apoderado.obligatorio') === '1';
    }

    public function matriculaPorUnidad(): bool
    {
        return $this->valor('matricula.modo') === 'POR_UNIDAD';
    }

    /** @return array<string, bool|int|string> lo que necesita el JavaScript del panel */
    public function paraInterfaz(): array
    {
        return [
            'tipo' => $this->tipo()->value,
            'tipoPeriodo' => $this->tipoPeriodo(),
            'etiquetaPeriodo' => $this->etiquetaPeriodo(),
            'cantidadPeriodos' => $this->cantidadPeriodos(),
            'notaMinima' => $this->notaMinima(),
            'recuperacionDesde' => $this->recuperacionDesde(),
            'apoderadoObligatorio' => $this->apoderadoObligatorio(),
            'matriculaPorUnidad' => $this->matriculaPorUnidad(),
        ];
    }

    /** @return array<string, array{valor: string, porDefecto: bool}> */
    public function todo(): array
    {
        $todo = [];
        foreach (array_keys(self::CLAVES) as $clave) {
            $todo[$clave] = ['valor' => $this->valor($clave), 'porDefecto' => $this->esPorDefecto($clave)];
        }
        return $todo;
    }
}
