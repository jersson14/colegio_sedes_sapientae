<?php

declare(strict_types=1);

namespace App\Comercial;

/**
 * Qué peticiones frena el estado o el plan (Fase 4B.2 y 4B.3). Se evalúa en core/guard.php antes de que
 * el controlador haga nada, así que ningún controlador tiene que acordarse de comprobarlo.
 */
final class Restricciones
{
    /** Endpoints que dan de alta algo que consume plan; son los que MOROSO no permite. */
    public const ALTAS = [
        'controller/alumnos/controlador_registrar_alumno.php' => [Recurso::Alumnos],
        'controller/matricula/controlador_registro_matriculas.php' => [Recurso::Usuarios],
        'controller/docentes/controlador_registrar_docente.php' => [Recurso::Usuarios],
        'controller/personal_administrativo/controlador_registrar_personal_administrativo.php' => [Recurso::Usuarios],
        'controller/usuario/controlador_registro_usuario.php' => [Recurso::Usuarios],
    ];

    /** Lo único que una institución suspendida puede pedir. */
    public const EXPORTACION = 'controller/exportacion/controlador_exportar_datos.php';

    /**
     * @param string $ruta endpoint relativo a la raíz del proyecto
     * @param \Closure(Recurso): int $consumo uso actual (solo se calcula si hace falta)
     * @return array{codigo: int, mensaje: string}|null null = se permite
     */
    public static function evaluar(string $ruta, Condiciones $c, \Closure $consumo): ?array
    {
        if ($c->soloExportacion()) {
            return $ruta === self::EXPORTACION
                ? null
                : ['codigo' => 403, 'mensaje' => 'El servicio está suspendido: solo se pueden descargar los datos de la institución.'];
        }
        if (!isset(self::ALTAS[$ruta])) {
            return null;
        }
        if (!$c->permiteAltas()) {
            return ['codigo' => 402, 'mensaje' => 'Hay un pago pendiente: puedes consultar e imprimir, pero no registrar altas ni matrículas hasta regularizarlo.'];
        }
        foreach (self::ALTAS[$ruta] as $recurso) {
            $limite = $c->limite($recurso);
            if ($limite !== null && $consumo($recurso) >= $limite) {
                $plan = $c->plan !== null ? $c->plan->nombre : '';
                return ['codigo' => 402, 'mensaje' => "Se alcanzó el límite de {$limite} {$recurso->etiqueta()} del plan {$plan}. Para ampliarlo, contacta con soporte."];
            }
        }
        return null;
    }
}
