<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Nota\EdicionNota;
use App\Domain\Nota\NotaDePadres;
use App\Domain\Nota\RegistroNota;
use App\Repositories\PdoNotaRepositorio;

/** PdoNotaRepositorio contra los SP reales (migraciones 20261009000000 y 20261013000000 incluidas). */
final class NotaRepositorioTest extends BaseDatosTestCase
{
    private PdoNotaRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->pdo->query('SELECT COUNT(*) FROM matricula WHERE id_matricula = 40')->fetchColumn() === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->repo = new PdoNotaRepositorio($this->pdo);
    }

    private function valor(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    public function testRegistraSoloLasNuevasYGuardaElTextoEscapado(): void
    {
        $nota = RegistroNota::desdeArreglo(['id_matri' => 40, 'perio' => 12, 'cri' => 1, 'nota' => 'ad', 'conclu' => '<b>bien</b>']);
        self::assertSame(1, $this->repo->registrar([$nota]));
        self::assertSame(0, $this->repo->registrar([$nota]), 'la existente no se toca');
        self::assertSame('AD', trim((string) $this->valor('SELECT nota FROM notas WHERE id_matricula = 40 AND id_bimestre = 12 AND id_criterio = 1')));
        self::assertSame('&lt;b&gt;bien&lt;/b&gt;', $this->valor('SELECT conclusiones FROM notas WHERE id_matricula = 40 AND id_bimestre = 12 AND id_criterio = 1'));
    }

    public function testLasNotasDePadresSeActualizanEnLugarDeDuplicarse(): void
    {
        $de = static fn (string $nota): NotaDePadres => NotaDePadres::desdeArreglo(['id_matri' => 40, 'perio' => 12, 'competencia' => 'Puntualidad', 'nota' => $nota]);
        self::assertSame(1, $this->repo->registrarDePadres([$de('A')]));
        self::assertSame(1, $this->repo->registrarDePadres([$de('B')]));
        self::assertSame(1, (int) $this->valor("SELECT COUNT(*) FROM notas_padre WHERE id_matricula = 40 AND id_bimestre = 12 AND criterio = 'Puntualidad'"));
        self::assertSame('B', trim((string) $this->valor("SELECT nota FROM notas_padre WHERE id_matricula = 40 AND id_bimestre = 12 AND criterio = 'Puntualidad'")));
    }

    public function testEditaYReportaLasInexistentes(): void
    {
        $this->repo->registrar([RegistroNota::desdeArreglo(['id_matri' => 40, 'perio' => 12, 'cri' => 1, 'nota' => '10'])]);
        $id = (int) $this->valor('SELECT id_nota_bole FROM notas WHERE id_matricula = 40 AND id_bimestre = 12 AND id_criterio = 1');
        self::assertTrue($this->repo->editar(EdicionNota::delAlumno(['id_nota_bole' => $id, 'nota' => '18', 'conclusiones' => 'Mejoró'])));
        self::assertSame('18', trim((string) $this->valor("SELECT nota FROM notas WHERE id_nota_bole = $id")));
        self::assertFalse($this->repo->editar(EdicionNota::delAlumno(['id_nota_bole' => 99999999, 'nota' => '18', 'conclusiones' => ''])));
    }

    public function testEditaLaNotaDeLosPadresConSuCompetencia(): void
    {
        $this->repo->registrarDePadres([NotaDePadres::desdeArreglo(['id_matri' => 40, 'perio' => 12, 'competencia' => 'Orden', 'nota' => 'C'])]);
        $id = (int) $this->valor("SELECT id_nota_papa FROM notas_padre WHERE id_matricula = 40 AND criterio = 'Orden'");
        self::assertTrue($this->repo->editarDePadres(EdicionNota::deLosPadres(['id_nota_papa' => $id, 'criterio' => 'Orden y limpieza', 'nota' => 'A'])));
        self::assertSame('Orden y limpieza', $this->valor("SELECT criterio FROM notas_padre WHERE id_nota_papa = $id"));
    }
}
