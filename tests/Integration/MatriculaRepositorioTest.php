<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Matricula\CuentaNueva;
use App\Domain\Matricula\DatosMatricula;
use App\Domain\Matricula\ResultadoRegistro;
use App\Repositories\PdoMatriculaRepositorio;
use Tests\Unit\Matricula\DatosMatriculaTest;

/**
 * PdoMatriculaRepositorio contra los SP reales (migración 20261012000000 incluida).
 * Alumno 20: NUEVO y sin matrícula. Alumno 6: matrícula 31 en el año 5. Matrícula 40: con notas.
 */
final class MatriculaRepositorioTest extends BaseDatosTestCase
{
    private const COBRA = 22;
    private PdoMatriculaRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->valor("SELECT COUNT(*) FROM alumnos WHERE Id_alumno = 20 AND tipo_alum = 'NUEVO'") === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec("SET SESSION timestamp = UNIX_TIMESTAMP('2025-12-26 12:00:00')");
        $this->repo = new PdoMatriculaRepositorio($this->pdo);
    }

    private function valor(string $sql, int|string ...$params): mixed
    {
        $q = $this->pdo->prepare($sql);
        $q->execute($params);
        $v = $q->fetchColumn();
        $q->closeCursor();
        return $v;
    }

    private static function datos(array $cambios = []): DatosMatricula
    {
        return DatosMatricula::desdeFormulario(DatosMatriculaTest::formulario($cambios));
    }

    private static function cuenta(string $usuario = 'nuevo20.it'): CuentaNueva
    {
        return CuentaNueva::desdeFormulario(DatosMatriculaTest::formulario(['usu' => $usuario]));
    }

    private function matriculaDe(int $alumno, int $anio): int
    {
        return (int) $this->valor('SELECT id_matricula FROM matricula WHERE id_alumno = ? AND `id_año` = ?', $alumno, $anio);
    }

    public function testRegistraConCuentaPagosEIngresosANombreDeQuienCobra(): void
    {
        self::assertSame(ResultadoRegistro::Registrada, $this->repo->registrar(20, self::datos(), self::cuenta(), self::COBRA));
        $m = $this->matriculaDe(20, 5);
        self::assertSame(3, (int) $this->valor('SELECT COUNT(*) FROM pago_pensiones WHERE id_matri = ?', $m));
        self::assertSame(3, (int) $this->valor('SELECT COUNT(*) FROM ingresos i JOIN pago_pensiones p
            ON p.id_pago_pension = i.id_pago_pension WHERE p.id_matri = ? AND i.id_user = ?', $m, self::COBRA));
        self::assertSame('NUEVO20.IT', $this->valor('SELECT u.usu_usuario FROM usuario u JOIN matricula m ON m.usu_id = u.usu_id WHERE m.id_matricula = ?', $m));
    }

    public function testUnUsuarioOcupadoRespondeTresSinCrearNada(): void
    {
        $usuarios = (int) $this->valor('SELECT COUNT(*) FROM usuario');
        self::assertSame(ResultadoRegistro::UsuarioOcupado, $this->repo->registrar(20, self::datos(), self::cuenta('USUARIO9'), self::COBRA));
        self::assertSame($usuarios, (int) $this->valor('SELECT COUNT(*) FROM usuario'));
        self::assertSame(0, $this->matriculaDe(20, 5));
    }

    public function testAlumnoInexistenteYDuplicadoEnElAnio(): void
    {
        self::assertSame(ResultadoRegistro::Invalida, $this->repo->registrar(999999, self::datos(), self::cuenta(), self::COBRA));
        self::assertSame(ResultadoRegistro::YaMatriculado, $this->repo->registrar(6, self::datos(), self::cuenta('otro.it'), self::COBRA));
    }

    public function testModificarNoPermiteRepetirAnioNiCambiaElAlumno(): void
    {
        $this->repo->registrar(6, self::datos(['año' => '2']), self::cuenta('x.it'), self::COBRA);
        $otra = $this->matriculaDe(6, 2);
        self::assertSame(2, $this->repo->modificar($otra, self::datos(['año' => '5'])), 'el alumno ya está en el año 5');
        self::assertSame(1, $this->repo->modificar($otra, self::datos(['año' => '2', 'proce' => 'Otro'])));
        self::assertSame(6, (int) $this->valor('SELECT id_alumno FROM matricula WHERE id_matricula = ?', $otra));
        self::assertSame(0, $this->repo->modificar(999999, self::datos()));
    }

    public function testNoEliminaConNotasNiConIngresosValidos(): void
    {
        self::assertSame(2, $this->repo->eliminar(40), 'tiene notas y asistencias');
        $this->repo->registrar(20, self::datos(), self::cuenta(), self::COBRA);
        self::assertSame(2, $this->repo->eliminar($this->matriculaDe(20, 5)), 'admisión y matrícula cobradas');
        self::assertSame(0, $this->repo->eliminar(999999));
    }

    public function testAnuladosLosIngresosSeEliminaYElAlumnoVuelveANuevoSinCuenta(): void
    {
        $this->repo->registrar(20, self::datos(), self::cuenta(), self::COBRA);
        $m = $this->matriculaDe(20, 5);
        $usuario = (int) $this->valor('SELECT usu_id FROM matricula WHERE id_matricula = ?', $m);
        $this->pdo->prepare("UPDATE ingresos i JOIN pago_pensiones p ON p.id_pago_pension = i.id_pago_pension
            SET i.estado = 'ANULADO' WHERE p.id_matri = ?")->execute([$m]);

        self::assertSame(1, $this->repo->eliminar($m));
        self::assertSame('NUEVO', $this->valor('SELECT tipo_alum FROM alumnos WHERE Id_alumno = 20'));
        self::assertSame(0, (int) $this->valor('SELECT COUNT(*) FROM usuario WHERE usu_id = ?', $usuario));
        self::assertSame(
            ResultadoRegistro::Registrada,
            $this->repo->registrar(20, self::datos(), self::cuenta(), self::COBRA),
            'se puede volver a matricular con el mismo usuario'
        );
    }

    public function testConOtraMatriculaElAlumnoSigueAntiguoYConservaSuCuenta(): void
    {
        $this->repo->registrar(20, self::datos(['admi' => '0', 'matri' => '0']), self::cuenta(), self::COBRA);
        $this->repo->registrar(20, self::datos(['año' => '2', 'admi' => '0', 'matri' => '0']), self::cuenta(), self::COBRA);
        $usuario = (int) $this->valor('SELECT usu_id FROM matricula WHERE id_matricula = ?', $this->matriculaDe(20, 2));
        self::assertSame(1, $this->repo->eliminar($this->matriculaDe(20, 2)), 'ingresos de monto 0');
        self::assertSame('ANTIGUO', $this->valor('SELECT tipo_alum FROM alumnos WHERE Id_alumno = 20'));
        self::assertSame(1, (int) $this->valor('SELECT COUNT(*) FROM usuario WHERE usu_id = ?', $usuario));
    }
}
