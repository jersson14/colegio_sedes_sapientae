<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Asistente\HerramientasAsistente;
use App\Asistente\RegistroConsultasIa;
use App\Institucion\Configuracion;
use PDO;

/** Herramientas del asistente de IA (alcance por rol) y registro de consultas (migración 20261103000000). */
final class AsistenteTest extends BaseDatosTestCase
{
    private const TODO = ['desde' => '2000-01-01', 'hasta' => '2100-12-31', 'limite' => 100];

    private function herramientas(string $rol, int $usuId, ?Configuracion $c = null): HerramientasAsistente
    {
        return new HerramientasAsistente($this->pdo, $rol, $usuId, $c ?? new Configuracion());
    }

    /** @return list<string> */
    private function nombres(string $rol, ?Configuracion $c = null): array
    {
        return array_column($this->herramientas($rol, 1, $c)->definiciones(), 'name');
    }

    public function testCadaRolVeSoloSusHerramientas(): void
    {
        self::assertSame(['resumen_institucion', 'pensiones_vencidas', 'asistencia_resumen', 'notas_resumen', 'buscar_alumno', 'catalogo'], $this->nombres('ADMINISTRADOR'));
        self::assertSame(['pensiones_vencidas', 'asistencia_resumen', 'notas_resumen', 'catalogo'], $this->nombres('ESTUDIANTE'));
        self::assertSame(['asistencia_resumen', 'notas_resumen', 'buscar_alumno', 'catalogo'], $this->nombres('DOCENTE'));
        self::assertSame(['resumen_institucion', 'buscar_alumno', 'catalogo'], $this->nombres('SECRETARIA'));
        self::assertContains('record_academico', $this->nombres('SECRETARIA', new Configuracion(['institucion.tipo' => 'INSTITUTO'])), 'en institutos');
        self::assertSame([], $this->nombres('ENFERMERA'));
        $instituto = $this->herramientas('SECRETARIA', 9, new Configuracion(['institucion.tipo' => 'INSTITUTO']));
        self::assertSame(['error' => 'No se encontró al alumno.'], $instituto->ejecutar('record_academico', ['nombre' => 'zzzzzz']));
        self::assertSame(['error' => 'El récord académico por unidades es de institutos.'], $this->herramientas('SECRETARIA', 9)->ejecutar('record_academico', ['nombre' => 'abc']));
        self::assertSame(['error' => 'Herramienta no disponible para este usuario.'], $this->herramientas('SECRETARIA', 9)->ejecutar('pensiones_vencidas', []));
        self::assertSame(['error' => 'Herramienta no disponible para este usuario.'], $this->herramientas('ESTUDIANTE', 9)->ejecutar('resumen_institucion', []));
    }

    public function testElResumenSoloIncluyeCajaParaElAdministrador(): void
    {
        self::assertArrayHasKey('ingresos_del_mes', $this->herramientas('ADMINISTRADOR', 9)->ejecutar('resumen_institucion', []));
        self::assertArrayNotHasKey('ingresos_del_mes', $this->herramientas('SECRETARIA', 9)->ejecutar('resumen_institucion', []));
    }

    public function testElEstudianteSoloVeLoSuyo(): void
    {
        $usuario = (int) $this->pdo->query('SELECT usu_id FROM matricula WHERE usu_id IS NOT NULL ORDER BY id_matricula LIMIT 1')->fetchColumn();
        $consulta = $this->pdo->prepare("SELECT DISTINCT CONCAT_WS(' ', al.alum_nombre, al.alum_apepat, al.alum_apemat) FROM matricula m
            JOIN alumnos al ON al.Id_alumno = m.id_alumno WHERE m.usu_id = ?");
        $consulta->execute([$usuario]);
        $propios = $consulta->fetchAll(PDO::FETCH_COLUMN);
        $h = $this->herramientas('ESTUDIANTE', $usuario);
        foreach (['pensiones_vencidas', 'asistencia_resumen', 'notas_resumen'] as $herramienta) {
            $alumnos = array_unique(array_column($h->ejecutar($herramienta, self::TODO + ['modo' => 'por_alumno'])['detalle'], 'alumno'));
            self::assertSame([], array_diff($alumnos, $propios), "$herramienta no trae datos de otros alumnos");
        }
    }

    public function testElDocenteSoloVeSusAulasYCursos(): void
    {
        $aulas = $this->pdo->query("SELECT DISTINCT au.Grado FROM detalle_asignatura_docente dad
            JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente JOIN docentes d ON d.Id_docente = ad.Id_docente
            JOIN asignaturas a ON a.Id_asignatura = dad.Id_asignatura JOIN aulas au ON au.Id_aula = a.Id_grado WHERE d.id_asusuario = 10")->fetchAll(PDO::FETCH_COLUMN);
        $cursos = $this->pdo->query("SELECT DISTINCT a.nombre_asig FROM detalle_asignatura_docente dad
            JOIN asignatura_docente ad ON ad.Id_asigdocente = dad.Id_asig_docente JOIN docentes d ON d.Id_docente = ad.Id_docente
            JOIN asignaturas a ON a.Id_asignatura = dad.Id_asignatura WHERE d.id_asusuario = 10")->fetchAll(PDO::FETCH_COLUMN);
        $h = $this->herramientas('DOCENTE', 10);
        self::assertSame([], array_diff(array_column($h->ejecutar('asistencia_resumen', self::TODO)['detalle'], 'aula'), $aulas));
        $notas = $h->ejecutar('notas_resumen', self::TODO)['detalle'];
        self::assertSame([], array_diff(array_column($notas, 'curso'), $cursos));
        self::assertSame([], array_diff(array_column($h->ejecutar('buscar_alumno', ['nombre' => 'PRU'])['coincidencias'], 'aula'), $aulas));
        $otro = $this->herramientas('DOCENTE', 999999);
        self::assertSame([], $otro->ejecutar('asistencia_resumen', self::TODO)['detalle'], 'un docente sin cursos no ve a nadie');
    }

    public function testNuncaDevuelveDocumentosNiContacto(): void
    {
        $h = $this->herramientas('ADMINISTRADOR', 9);
        $todo = (string) json_encode([$h->ejecutar('buscar_alumno', ['nombre' => 'PRUEBA']), $h->ejecutar('pensiones_vencidas', self::TODO),
            $h->ejecutar('asistencia_resumen', self::TODO), $h->ejecutar('notas_resumen', self::TODO + ['modo' => 'por_alumno'])]);
        foreach (['dni', 'movil', 'direccion', 'celular', 'email'] as $campo) {
            self::assertStringNotContainsStringIgnoringCase("\"$campo", $todo);
        }
        self::assertDoesNotMatchRegularExpression('/\b\d{8}\b/', $todo, 'ningún número de 8 dígitos (DNI)');
        self::assertSame(['error' => 'Indica al menos 3 letras del nombre.'], $h->ejecutar('buscar_alumno', ['nombre' => 'a']));
    }

    public function testEntradasNoConfiables(): void
    {
        $h = $this->herramientas('ADMINISTRADOR', 9);
        $r = $h->ejecutar('asistencia_resumen', ['desde' => "2025-01-01' OR 1=1 --", 'limite' => 'mucho', 'grado' => ['no', 'texto']]);
        self::assertArrayHasKey('detalle', $r, 'fechas y tipos inválidos se ignoran (valores por defecto)');
        self::assertLessThanOrEqual(100, count($h->ejecutar('notas_resumen', ['limite' => 999999])['detalle']));
        self::assertSame([], $h->ejecutar('buscar_alumno', ['nombre' => '%%%'])['coincidencias'], 'el comodín se busca como texto');
    }

    public function testRegistroYLimites(): void
    {
        $registro = new RegistroConsultasIa($this->pdo);
        $this->pdo->exec('DELETE FROM asistente_consultas');
        $registro->registrar(9, 'ADMINISTRADOR', '¿Cuántos alumnos?', ['resumen_institucion', 'resumen_institucion'], 1200, 80, 'OK');
        $registro->registrar(10, 'DOCENTE', 'x', [], 0, 0, 'ERROR');
        self::assertSame(2, $registro->delMes());
        self::assertSame(1, $registro->delUsuarioEnLaUltimaHora(9));
        self::assertSame('resumen_institucion', $this->pdo->query('SELECT herramientas FROM asistente_consultas WHERE usu_id = 9')->fetchColumn());
        $resumen = $registro->resumenDelMes();
        self::assertSame([2, 1200, 80], [$resumen['consultas'], $resumen['tokens_entrada'], $resumen['tokens_salida']]);
        $this->pdo->exec("UPDATE asistente_consultas SET fecha = NOW() - INTERVAL 400 DAY WHERE usu_id = 10");
        self::assertSame(0, $registro->purgar(10), 'nunca menos de 30 días');
        self::assertSame(1, $registro->purgar(365));
    }
}
