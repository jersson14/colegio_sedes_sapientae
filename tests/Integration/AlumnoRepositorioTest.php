<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Alumno\FichaAlumno;
use App\Repositories\PdoAlumnoRepositorio;
use Tests\Unit\Alumno\FichaAlumnoTest;

/** PdoAlumnoRepositorio contra los SP reales (migración 20261011000000 incluida). */
final class AlumnoRepositorioTest extends BaseDatosTestCase
{
    private PdoAlumnoRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->pdo->query("SELECT COUNT(*) FROM alumnos WHERE alum_dni = '70000014'")->fetchColumn() === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->repo = new PdoAlumnoRepositorio($this->pdo);
    }

    private static function ficha(string $dni, array $cambios = []): FichaAlumno
    {
        return FichaAlumno::desdeFormulario(FichaAlumnoTest::formulario(['dni' => $dni] + $cambios));
    }

    /** @return array<string, mixed> */
    private function fila(string $sql, array $params = []): array
    {
        $q = $this->pdo->prepare($sql);
        $q->execute($params);
        return (array) $q->fetch(\PDO::FETCH_ASSOC);
    }

    public function testRegistraAlumnoYPadresEnlazadosPorElIdInsertado(): void
    {
        self::assertTrue($this->repo->registrar(self::ficha('79999991'), 'controller/alumnos/fotos/'));
        $a = $this->fila("SELECT a.Id_alumno, a.alum_nombre, p.id_alu, p.Datos_mama FROM alumnos a
            JOIN padres p ON p.id_alu = a.Id_alumno WHERE a.alum_dni = '79999991'");
        self::assertSame('MADRE', $a['Datos_mama']);
        self::assertFalse($this->repo->registrar(self::ficha('79999991'), ''), 'DNI repetido');
    }

    public function testModificaLosPadresDelPropioAlumnoAunqueElFormularioTraigaOtroIdpa(): void
    {
        $id = (int) $this->fila("SELECT Id_alumno FROM alumnos WHERE alum_dni = '70000014'")['Id_alumno'];
        $ajenos = $this->fila("SELECT p.Datos_papa FROM padres p JOIN alumnos a ON a.Id_alumno = p.id_alu WHERE a.alum_dni = '70000001'");
        self::assertTrue($this->repo->modificar($id, self::ficha('70000014', ['nompa' => 'Padre Nuevo']), 'controller/alumnos/fotos/'));
        self::assertSame('PADRE NUEVO', $this->fila('SELECT Datos_papa FROM padres WHERE id_alu = ?', [$id])['Datos_papa']);
        self::assertSame($ajenos, $this->fila("SELECT p.Datos_papa FROM padres p JOIN alumnos a ON a.Id_alumno = p.id_alu WHERE a.alum_dni = '70000001'"));
        self::assertFalse($this->repo->modificar($id, self::ficha('70000001'), ''), 'DNI de otro alumno');
    }

    public function testEliminaSoloSinMatricula(): void
    {
        self::assertFalse($this->repo->eliminarPorDni('70000001'), 'tiene matrícula: 0, no un error de clave foránea');
        self::assertNotNull($this->repo->fotoPorDni('70000001'));
        self::assertTrue($this->repo->eliminarPorDni('70000014'));
        self::assertNull($this->repo->fotoPorDni('70000014'));
        self::assertSame(0, (int) $this->fila("SELECT COUNT(*) n FROM padres p LEFT JOIN alumnos a ON a.Id_alumno = p.id_alu WHERE a.Id_alumno IS NULL")['n'], 'los padres se borran en cascada');
        self::assertFalse($this->repo->eliminarPorDni('79999999'), 'inexistente');
    }

    public function testElDniSeComparaComoTextoNoComoNumero(): void
    {
        // Antes el parámetro era INT: «70000014.0» o «070000014» coincidían numéricamente.
        self::assertFalse($this->repo->eliminarPorDni('070000014'));
        self::assertNotNull($this->repo->fotoPorDni('70000014'));
    }

    public function testCambiaLaFotoPorDni(): void
    {
        $this->repo->cambiarFotoPorDni('70000014', 'controller/alumnos/fotos/IMGx.png');
        self::assertSame('controller/alumnos/fotos/IMGx.png', $this->repo->fotoPorDni('70000014'));
    }
}
