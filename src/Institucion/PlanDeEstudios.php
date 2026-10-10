<?php

declare(strict_types=1);

namespace App\Institucion;

use PDO;

/**
 * Plan de estudios de un instituto (Fase 5.3): programas → módulos formativos → unidades didácticas, y
 * los prerrequisitos entre unidades. Escribe por los procedimientos de la migración 20261029000000.
 *
 * Los ciclos de prerrequisitos (A pide B, B pide A) se descartan aquí: el procedimiento no puede recorrer
 * el grafo en MariaDB 10.4 (ver la migración).
 */
final class PlanDeEstudios
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Todo el plan, anidado, con los prerrequisitos de cada unidad.
     *
     * @return list<array<string, mixed>>
     */
    public function arbol(): array
    {
        $programas = $this->pdo->query('SELECT id_programa, codigo, nombre, estado FROM programas_estudio ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
        $modulos = $this->pdo->query('SELECT id_modulo, id_programa, nombre, orden FROM modulos_formativos ORDER BY orden, nombre')->fetchAll(PDO::FETCH_ASSOC);
        $unidades = $this->pdo->query(
            'SELECT id_unidad, id_modulo, codigo, nombre, periodo_academico, creditos, horas_teoricas, horas_practicas, estado
               FROM unidades_didacticas ORDER BY periodo_academico, codigo'
        )->fetchAll(PDO::FETCH_ASSOC);
        $requisitos = [];
        foreach ($this->pdo->query('SELECT id_unidad, id_requisito FROM prerrequisitos')->fetchAll(PDO::FETCH_NUM) as [$unidad, $requisito]) {
            $requisitos[(int) $unidad][] = (int) $requisito;
        }
        $porModulo = [];
        foreach ($unidades as $u) {
            $u['prerrequisitos'] = $requisitos[(int) $u['id_unidad']] ?? [];
            $porModulo[(int) $u['id_modulo']][] = $u;
        }
        $porPrograma = [];
        foreach ($modulos as $m) {
            $m['unidades'] = $porModulo[(int) $m['id_modulo']] ?? [];
            $porPrograma[(int) $m['id_programa']][] = $m;
        }
        return array_values(array_map(static fn (array $p): array => $p + ['modulos' => $porPrograma[(int) $p['id_programa']] ?? []], $programas));
    }

    /** @throws \InvalidArgumentException|\DomainException */
    public function guardarPrograma(int $id, string $codigo, string $nombre, bool $activo): int
    {
        $codigo = strtoupper(trim($codigo));
        if (preg_match('/^[A-Z0-9][A-Z0-9-]{1,19}$/', $codigo) !== 1 || trim($nombre) === '' || mb_strlen($nombre) > 200) {
            throw new \InvalidArgumentException('Programa: código de 2 a 20 mayúsculas, dígitos o guiones, y nombre de hasta 200 caracteres.');
        }
        return $this->id('CALL SP_GUARDAR_PROGRAMA(?, ?, ?, ?)', [$id, $codigo, trim($nombre), $activo ? 'ACTIVO' : 'INACTIVO'], "El código {$codigo} ya es de otro programa.");
    }

    /** @throws \InvalidArgumentException|\DomainException */
    public function guardarModulo(int $id, int $programa, string $nombre, int $orden): int
    {
        if (trim($nombre) === '' || mb_strlen($nombre) > 200 || $orden < 1 || $orden > 50) {
            throw new \InvalidArgumentException('Módulo: nombre de hasta 200 caracteres y orden de 1 a 50.');
        }
        return $this->id('CALL SP_GUARDAR_MODULO(?, ?, ?, ?)', [$id, $programa, trim($nombre), $orden], 'El programa no existe.');
    }

    /** @throws \DomainException */
    public function guardarUnidad(UnidadDidactica $u): int
    {
        return $this->id(
            'CALL SP_GUARDAR_UNIDAD(?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$u->id, $u->modulo, $u->codigo, $u->nombre, $u->periodoAcademico, $u->creditos, $u->horasTeoricas, $u->horasPracticas, $u->activa ? 'ACTIVO' : 'INACTIVO'],
            "El código {$u->codigo} ya es de otra unidad, o el módulo no existe.",
        );
    }

    /** @param 'programa'|'modulo'|'unidad' $que */
    public function eliminar(string $que, int $id): bool
    {
        $sp = ['programa' => 'SP_ELIMINAR_PROGRAMA', 'modulo' => 'SP_ELIMINAR_MODULO', 'unidad' => 'SP_ELIMINAR_UNIDAD'][$que];
        return $this->llamar("CALL $sp(?)", [$id]) === 1;
    }

    /** @throws \DomainException si es la misma unidad, de otro programa o formaría un ciclo */
    public function agregarPrerrequisito(int $unidad, int $requisito): void
    {
        if ($this->dependeDe($requisito, $unidad)) {
            throw new \DomainException('Ese prerrequisito formaría un ciclo: la unidad elegida ya depende de esta.');
        }
        if ($this->llamar('CALL SP_AGREGAR_PRERREQUISITO(?, ?)', [$unidad, $requisito]) !== 1) {
            throw new \DomainException('Un prerrequisito debe ser otra unidad del mismo programa de estudios.');
        }
    }

    public function quitarPrerrequisito(int $unidad, int $requisito): bool
    {
        return $this->llamar('CALL SP_QUITAR_PRERREQUISITO(?, ?)', [$unidad, $requisito]) === 1;
    }

    /** ¿$unidad necesita (directa o indirectamente) a $requisito? */
    private function dependeDe(int $unidad, int $requisito): bool
    {
        $aristas = [];
        foreach ($this->pdo->query('SELECT id_unidad, id_requisito FROM prerrequisitos')->fetchAll(PDO::FETCH_NUM) as [$u, $r]) {
            $aristas[(int) $u][] = (int) $r;
        }
        $pendientes = [$unidad];
        $vistos = [];
        while ($pendientes !== []) {
            $actual = array_pop($pendientes);
            if ($actual === $requisito) {
                return true;
            }
            if (isset($vistos[$actual])) {
                continue;
            }
            $vistos[$actual] = true;
            array_push($pendientes, ...($aristas[$actual] ?? []));
        }
        return false;
    }

    /** @param list<int|string> $parametros */
    private function id(string $sql, array $parametros, string $siCero): int
    {
        $id = $this->llamar($sql, $parametros);
        if ($id === 0) {
            throw new \DomainException($siCero);
        }
        return $id;
    }

    /** @param list<int|string> $parametros */
    private function llamar(string $sql, array $parametros): int
    {
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        $valor = (int) $consulta->fetchColumn();
        $consulta->closeCursor();
        return $valor;
    }
}
