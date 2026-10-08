<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Tarea\Actividad;
use App\Repositories\TareaRepositorio;
use App\Support\AlmacenDocumentos;
use App\Support\Lote;
use App\Support\Texto;
use InvalidArgumentException;

/**
 * Tareas (con sus documentos) y exámenes. La pertenencia (curso del docente, entrega del alumno) la
 * comprueban los controladores con core/pertenencia.php antes de llamar aquí.
 *
 * Con archivos: se valida, se guarda en una carpeta nueva, se actualiza la BD y solo entonces se borra
 * la carpeta anterior (antes se borraba primero: si la BD fallaba, se perdían los archivos). Si la BD
 * no acepta el cambio, se borra la carpeta nueva.
 */
final class GestionarTareas
{
    private const LARGO_TEMA_TAREA = 150; // parámetro de SP_REGISTRAR_TAREA
    private const ESTADOS_EXAMEN = ['PENDIENTE', 'REALIZADO'];

    public function __construct(
        private readonly TareaRepositorio $tareas,
        private readonly AlmacenDocumentos $documentos,
    ) {
    }

    /**
     * @param array<string, mixed> $post
     * @return int 1 publicada, 2 ya hay una con ese tema hoy
     * @throws InvalidArgumentException
     */
    public function publicar(array $post): int
    {
        $tarea = Actividad::desdeFormulario($post, self::LARGO_TEMA_TAREA);
        $carpeta = $this->documentos->guardar($this->documentos->validar(), 'tarea_alumnos_');
        $resultado = $this->tareas->publicar($tarea, $carpeta);
        if ($resultado !== 1) {
            $this->documentos->borrar($carpeta);
        }
        return $resultado;
    }

    /**
     * @param array<string, mixed> $post
     * @param string $carpetaActual la de la BD (core/pertenencia.php), no la del formulario
     * @throws InvalidArgumentException
     */
    public function modificar(array $post, string $carpetaActual): int
    {
        $tarea = Actividad::desdeFormulario($post, self::LARGO_TEMA_TAREA);
        $id = (string) ($post['id'] ?? '');
        $nuevos = $this->documentos->validar();
        if ($nuevos === []) {
            return $this->tareas->modificar($id, $tarea, $carpetaActual);
        }
        $carpeta = $this->documentos->guardar($nuevos);
        $resultado = $this->tareas->modificar($id, $tarea, $carpeta);
        $this->documentos->borrar($resultado === 1 ? $carpetaActual : $carpeta);
        return $resultado;
    }

    /** @return bool false si tiene entregas enviadas o calificadas */
    public function eliminar(string $id): bool
    {
        $carpeta = $this->tareas->carpetaDeTarea($id);
        $eliminada = $this->tareas->eliminar($id);
        if ($eliminada && $carpeta !== null) {
            $this->documentos->borrar($carpeta); // antes los archivos quedaban en el disco
        }
        return $eliminada;
    }

    /** @throws InvalidArgumentException si el estado no es FINALIZADO (lo único que hace el panel) */
    public function cambiarEstado(string $id, mixed $estado): bool
    {
        if (strtoupper(trim((string) $estado)) !== 'FINALIZADO') {
            throw new InvalidArgumentException('Una tarea solo se puede finalizar');
        }
        return $this->tareas->finalizar($id);
    }

    /**
     * Entrega (o reemplazo) del alumno. Sin archivos no hay entrega.
     *
     * @param string $carpetaActual la de la BD (core/pertenencia.php)
     * @return bool false si venció, finalizó o ya está calificada
     * @throws InvalidArgumentException
     */
    public function entregar(mixed $detalle, string $carpetaActual, bool $reemplazo): bool
    {
        $id = Lote::idPositivo($detalle, 'entrega');
        $nuevos = $this->documentos->validar();
        if ($nuevos === []) {
            throw new InvalidArgumentException('Adjunte el archivo de la tarea');
        }
        $carpeta = $this->documentos->guardar($nuevos, 'tarea_alumnos_');
        $entregada = $this->tareas->entregar($id, $carpeta, $reemplazo);
        $this->documentos->borrar($entregada ? $carpetaActual : $carpeta);
        return $entregada;
    }

    /**
     * Calificación vigesimal entera (columna INT).
     *
     * @throws InvalidArgumentException
     */
    public function calificar(mixed $detalle, mixed $nota, mixed $observacion): bool
    {
        $valor = filter_var(trim((string) $nota), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 20]]);
        if ($valor === false) {
            throw new InvalidArgumentException('La calificación va de 0 a 20');
        }
        $observacion = Texto::deFormulario($observacion);
        Texto::exigirLargo('observación', $observacion, 255);
        return $this->tareas->calificar(Lote::idPositivo($detalle, 'entrega'), $valor, $observacion);
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public function registrarExamen(array $post): int
    {
        return $this->tareas->registrarExamen(Actividad::desdeFormulario($post, 255));
    }

    /**
     * @param array<string, mixed> $post
     * @throws InvalidArgumentException
     */
    public function modificarExamen(array $post): int
    {
        return $this->tareas->modificarExamen((string) ($post['id'] ?? ''), Actividad::desdeFormulario($post, 255));
    }

    /** @throws InvalidArgumentException */
    public function estadoExamen(string $id, mixed $estado): bool
    {
        $estado = strtoupper(trim((string) $estado));
        if (!in_array($estado, self::ESTADOS_EXAMEN, true)) {
            throw new InvalidArgumentException('Estado de examen no válido');
        }
        return $this->tareas->estadoExamen($id, $estado);
    }

    public function eliminarExamen(string $id): void
    {
        $this->tareas->eliminarExamen($id);
    }
}
