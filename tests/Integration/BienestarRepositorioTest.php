<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Comunicado\Comunicado;
use App\Domain\Salud\Atencion;
use App\Domain\Salud\TipoAtencion;
use App\Repositories\PdoBienestarRepositorio;

/** PdoBienestarRepositorio contra los SP reales (migración 20261021000000 incluida). */
final class BienestarRepositorioTest extends BaseDatosTestCase
{
    private PdoBienestarRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->valor('SELECT COUNT(*) FROM matricula WHERE id_matricula = 40') === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->repo = new PdoBienestarRepositorio($this->pdo);
    }

    private function valor(string $sql): mixed
    {
        return $this->pdo->query($sql)->fetchColumn();
    }

    private static function atencion(string $motivo): Atencion
    {
        return Atencion::desdeFormulario(['estu' => '40', 'motivo' => $motivo, 'diagno' => 'D', 'observa' => 'O']);
    }

    public function testCadaProfesionalSoloModificaLasAtencionesDeSuTipo(): void
    {
        $this->repo->registrarAtencion(TipoAtencion::Psicologia, self::atencion('ANSIEDAD IT'), 32);
        $id = (int) $this->valor("SELECT id_atencion FROM atencion_salud WHERE motivo_consulta = 'ANSIEDAD IT'");
        self::assertSame(['PSICOLOGIA', 32], [$this->valor("SELECT tipo_atencion FROM atencion_salud WHERE id_atencion = $id"),
            (int) $this->valor("SELECT id_usuario FROM atencion_salud WHERE id_atencion = $id")]);

        self::assertFalse($this->repo->modificarAtencion(TipoAtencion::Enfermeria, $id, self::atencion('CAMBIADO')), 'la enfermera no la toca');
        self::assertSame('ANSIEDAD IT', $this->valor("SELECT motivo_consulta FROM atencion_salud WHERE id_atencion = $id"));

        self::assertTrue($this->repo->modificarAtencion(TipoAtencion::Psicologia, $id, self::atencion('SEGUIMIENTO')));
        self::assertSame([32, 'SEGUIMIENTO'], [(int) $this->valor("SELECT id_usuario FROM atencion_salud WHERE id_atencion = $id"),
            $this->valor("SELECT motivo_consulta FROM atencion_salud WHERE id_atencion = $id")], 'quien atendió no cambia');
    }

    public function testComunicadoConservaSuAutor(): void
    {
        $datos = static fn (string $titulo): Comunicado => Comunicado::desdeFormulario(['tipo' => 'GENERAL', 'grado' => '5', 'titulo' => $titulo, 'descripcion' => 'x', 'esta' => 'ACTIVO']);
        self::assertSame(1, $this->repo->registrarComunicado($datos('AVISO IT'), 'controller/comunicados/fotos/', 9));
        $id = (int) $this->valor("SELECT id_comunicado FROM comunicados WHERE titulo = 'AVISO IT'");
        self::assertTrue($this->repo->modificarComunicado($id, $datos('AVISO IT 2'), 'controller/comunicados/fotos/'));
        self::assertSame(9, (int) $this->valor("SELECT id_usuario FROM comunicados WHERE id_comunicado = $id"));
        self::assertSame('controller/comunicados/fotos/', $this->repo->imagenDeComunicado($id));
        self::assertTrue($this->repo->eliminarComunicado($id));
        self::assertFalse($this->repo->eliminarComunicado($id));
        self::assertFalse($this->repo->modificarComunicado($id, $datos('X'), ''));
    }
}
