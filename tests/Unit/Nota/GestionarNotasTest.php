<?php

declare(strict_types=1);

namespace Tests\Unit\Nota;

use App\Domain\Nota\EdicionNota;
use App\Repositories\NotaRepositorio;
use App\Services\GestionarNotas;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GestionarNotasTest extends TestCase
{
    /** @var NotaRepositorio&object{registradas: int, editadas: list<int>} */
    private NotaRepositorio $repo;
    private GestionarNotas $servicio;

    protected function setUp(): void
    {
        $this->repo = new class () implements NotaRepositorio {
            public int $registradas = 0;
            /** @var list<int> */
            public array $editadas = [];

            public function registrar(array $notas): int
            {
                return $this->registradas = count($notas);
            }

            public function registrarDePadres(array $notas): int
            {
                return count($notas);
            }

            public function editar(EdicionNota $edicion): bool
            {
                $this->editadas[] = $edicion->id;
                return $edicion->id !== 404;
            }

            public function editarDePadres(EdicionNota $edicion): bool
            {
                return true;
            }
        };
        $this->servicio = new GestionarNotas($this->repo);
    }

    public function testUnRegistroInvalidoImpideGuardarElLoteEntero(): void
    {
        try {
            $this->servicio->registrar([
                ['id_matri' => 40, 'perio' => 12, 'cri' => 1, 'nota' => '15'],
                ['id_matri' => 40, 'perio' => 12, 'cri' => 8, 'nota' => 'XYZ'],
            ]);
            self::fail('Se esperaba InvalidArgumentException');
        } catch (InvalidArgumentException) {
            self::assertSame(0, $this->repo->registradas);
        }
    }

    public function testLaEdicionInformaCadaFalloSinDetenerseYSinDatosDeLaBd(): void
    {
        $r = $this->servicio->editar([
            ['id_nota_bole' => 1, 'nota' => '15', 'conclusiones' => ''],
            ['id_nota_bole' => 2, 'nota' => 'XYZ', 'conclusiones' => ''],
            ['id_nota_bole' => 404, 'nota' => '10', 'conclusiones' => ''],
            ['id_nota_bole' => 3, 'nota' => 'A', 'conclusiones' => 'ok'],
        ]);
        self::assertSame(2, $r['actualizadas']);
        self::assertSame([1, 404, 3], $this->repo->editadas, 'la nota inválida no llega a la BD');
        self::assertCount(2, $r['errores']);
        self::assertStringStartsWith('Registro 2:', $r['errores'][0]);
        self::assertSame('No se encontró la nota con ID 404', $r['errores'][1]);
    }
}
