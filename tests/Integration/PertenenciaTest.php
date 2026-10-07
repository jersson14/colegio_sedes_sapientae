<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\RespuestaError;

/**
 * Pertenencia del dato (IDOR) contra el esquema real. Mismo escenario que la
 * verificación manual de la Fase 0:
 *   Estudiante A: usu 90100, matrícula 90010, aula 90005, pago 90901
 *   Estudiante B: usu 90200, matrícula 90020, aula 90006, pago 90902
 *   Docente D1:   usu 90300, curso 90071 en el aula 90005 (tarea 90001, examen 90011, criterio 90801)
 *   Docente D2:   usu 90400, curso 90082 en el aula 90006 (tarea 90002, examen 90022, criterio 90802)
 */
final class PertenenciaTest extends BaseDatosTestCase
{
    private const CARPETA_TAREA_D1 = 'controller/tareas/documentos/tarea_alumnos_1742062671';
    private const CARPETA_TAREA_D2 = 'controller/tareas/documentos/tarea_alumnos_1743114136';
    private const ENVIO_A = 'controller/tareas/documentos/tarea_alumnos_1742062842';
    private const ENVIO_B = 'controller/tareas/documentos/tarea_alumnos_1742062860';

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([[90010, 90001, 90005, 90100], [90020, 90002, 90006, 90200]] as [$m, $al, $au, $u]) {
            $this->insertar('matricula', ['id_matricula' => $m, 'id_alumno' => $al, 'id_aula' => $au, 'usu_id' => $u]);
        }
        $this->insertar('pago_pensiones', ['id_pago_pension' => 90901, 'id_matri' => 90010]);
        $this->insertar('pago_pensiones', ['id_pago_pension' => 90902, 'id_matri' => 90020]);
        foreach ([[90030, 90300, 90001, 90007, 90005, 90071], [90040, 90400, 90002, 90008, 90006, 90082]] as [$doc, $usu, $ad, $asig, $aula, $curso]) {
            $this->insertar('docentes', ['Id_docente' => $doc, 'docente_dni' => (string) ($doc * 1000), 'id_asusuario' => $usu]);
            $this->insertar('asignatura_docente', ['Id_asigdocente' => $ad, 'Id_docente' => $doc]);
            $this->insertar('asignaturas', ['Id_asignatura' => $asig, 'Id_grado' => $aula]);
            $this->insertar(
                'detalle_asignatura_docente',
                ['Id_detalle_asig_docente' => $curso, 'Id_asig_docente' => $ad, 'Id_asignatura' => $asig]
            );
        }
        $this->insertar('tareas', ['id_tarea' => 90001, 'id_detalle_asignatura' => 90071, 'archivo_tarea' => self::CARPETA_TAREA_D1]);
        $this->insertar('tareas', ['id_tarea' => 90002, 'id_detalle_asignatura' => 90082, 'archivo_tarea' => self::CARPETA_TAREA_D2]);
        $this->insertar('detalle_tarea', ['id_detalle_tarea' => 90501, 'id_tarea' => 90001, 'id_matriculado' => 90010, 'archivo_evnio_tarea' => self::ENVIO_A]);
        $this->insertar('detalle_tarea', ['id_detalle_tarea' => 90502, 'id_tarea' => 90002, 'id_matriculado' => 90020, 'archivo_evnio_tarea' => self::ENVIO_B]);
        $this->insertar('examen', ['id_examen' => 90011, 'id_detalle_asignatura' => 90071]);
        $this->insertar('examen', ['id_examen' => 90022, 'id_detalle_asignatura' => 90082]);
        $this->insertar('criterios', ['id_criterio' => 90801, 'id_detalle_asignatura' => 90071]);
        $this->insertar('criterios', ['id_criterio' => 90802, 'id_detalle_asignatura' => 90082]);
    }

    private function denegado(callable $accion): void
    {
        try {
            $accion();
        } catch (RespuestaError $e) {
            self::assertSame(403, $e->getCode());
            return;
        }
        self::fail('Se esperaba 403: el registro no pertenece al usuario');
    }

    private function permitido(callable $accion): void
    {
        $accion();
        $this->addToAssertionCount(1);
    }

    // ---------------- Estudiante

    public function testEstudianteSoloSuMatricula(): void
    {
        $this->comoUsuario('ESTUDIANTE', 90100);
        $this->permitido(fn () => exigir_matricula_propia('90010'));
        $this->denegado(fn () => exigir_matricula_propia('90020'));
    }

    public function testEstudianteSoloSusPagos(): void
    {
        $this->comoUsuario('ESTUDIANTE', 90100);
        $this->permitido(fn () => exigir_pago_propio('90010', '90901'));
        $this->denegado(fn () => exigir_pago_propio('90020', '90902'));
        $this->denegado(fn () => exigir_pago_propio('90010', '90902')); // su matrícula con pago ajeno
    }

    public function testEstudianteSoloSuAula(): void
    {
        $this->comoUsuario('ESTUDIANTE', 90100);
        $this->permitido(fn () => exigir_aula_propia('90005'));
        $this->denegado(fn () => exigir_aula_propia('90006'));
    }

    public function testEstudianteReemplazaSoloSuEnvioYLaCarpetaSaleDeLaBd(): void
    {
        $this->comoUsuario('ESTUDIANTE', 90100);
        self::assertSame(self::ENVIO_A, exigir_envio_propio('90501', 'carpeta-que-dice-el-cliente'));
        $this->denegado(fn () => exigir_envio_propio('90502', 'x'));
    }

    public function testEstudianteDescargaSoloLoAsignadoYLoSuyo(): void
    {
        $this->comoUsuario('ESTUDIANTE', 90100);
        $this->permitido(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1742062671')); // enunciado asignado
        $this->permitido(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1742062842')); // su envío
        $this->denegado(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1742062860'));  // envío de B
        $this->denegado(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1743114136'));  // tarea no asignada
    }

    public function testElIdPropioSaleDeLaSesionSalvoParaElAdministrador(): void
    {
        $this->comoUsuario('ESTUDIANTE', 90100);
        self::assertSame('90100', id_usuario_propio('90200'));
        $this->comoUsuario('ADMINISTRADOR', 1);
        self::assertSame('90200', id_usuario_propio('90200'));
    }

    // ---------------- Docente

    public function testDocenteSoloSusCursos(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        $this->permitido(fn () => exigir_curso_propio('90071'));
        $this->denegado(fn () => exigir_curso_propio('90082'));
    }

    public function testDocenteSoloSusTareasYLaCarpetaSaleDeLaBd(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        self::assertSame(self::CARPETA_TAREA_D1, exigir_tarea_propia('90001', 'lo-que-diga-el-cliente'));
        $this->denegado(fn () => exigir_tarea_propia('90002'));
    }

    public function testDocenteSoloSusExamenes(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        $this->permitido(fn () => exigir_examen_propio('90011'));
        $this->denegado(fn () => exigir_examen_propio('90022'));
    }

    public function testDocenteSoloCalificaEnviosDeSusTareas(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        $this->permitido(fn () => exigir_envio_calificable('90501'));
        $this->denegado(fn () => exigir_envio_calificable('90502'));
    }

    public function testDocenteSoloSusAulasYMatriculas(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        $this->permitido(fn () => exigir_aula_propia('90005'));
        $this->denegado(fn () => exigir_aula_propia('90006'));
        $this->permitido(fn () => exigir_matricula_propia('90010'));
        $this->denegado(fn () => exigir_matricula_propia('90020'));
    }

    public function testDocenteNotasCriterioYAlumnoDebenSerDeSuCurso(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        $this->permitido(fn () => exigir_notas_propias([['id_matri' => 90010, 'cri' => 90801]]));
        $this->denegado(fn () => exigir_notas_propias([['id_matri' => 90020, 'cri' => 90802]])); // criterio ajeno
        $this->denegado(fn () => exigir_notas_propias([['id_matri' => 90020, 'cri' => 90801]])); // alumno ajeno
        $this->denegado(fn () => exigir_notas_propias([                                          // lote mixto
            ['id_matri' => 90010, 'cri' => 90801], ['id_matri' => 90010, 'cri' => 90802],
        ]));
    }

    public function testDocenteDescargaSoloSusTareasYSusEnvios(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        $this->permitido(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1742062671'));
        $this->permitido(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1742062842'));
        $this->denegado(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1743114136'));
        $this->denegado(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1742062860'));
    }

    public function testDocenteSoloVeSuIdDocente(): void
    {
        $this->comoUsuario('DOCENTE', 90300);
        self::assertSame('90030', id_docente_propio('90040'));
    }

    // ---------------- Administrador

    public function testAdministradorSinRestriccionDePertenencia(): void
    {
        $this->comoUsuario('ADMINISTRADOR', 1);
        $this->permitido(fn () => exigir_matricula_propia('90020'));
        $this->permitido(fn () => exigir_tarea_propia('90002'));
        $this->permitido(fn () => exigir_notas_propias([['id_matri' => 90020, 'cri' => 90802]]));
        $this->permitido(fn () => exigir_carpeta_tarea_visible('tarea_alumnos_1742062860'));
    }
}
