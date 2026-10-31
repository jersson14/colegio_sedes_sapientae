<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require '../../model/model_panel_rol.php';
// Panel de inicio según el rol de la sesión (docente, estudiante, auxiliar, enfermería o psicología)
$M = new Modelo_Panel_Rol();
$comun = $M->Comun();
$usu = (int) usuario_actual_id();
$M->usuario = $usu;
switch (rol_actual()) {
    case 'DOCENTE':    $datos = $M->Docente($usu, $comun); break;
    case 'ESTUDIANTE': $datos = $M->Estudiante($usu, $comun); break;
    case 'AUXILIAR':   $datos = $M->Auxiliar($comun); break;
    case 'ENFERMERA':  $datos = $M->Salud('ENFERMERIA', $comun); break;
    case 'PSICOLOGA':  $datos = $M->Salud('PSICOLOGIA', $comun); break;
    case 'CONTADOR':   $datos = []; break;   // solo el encabezado y el año escolar (su panel contable es aparte)
    default:           denegar(400, 'Este panel no corresponde a su rol');
}
responder_json(['rol' => rol_actual()] + $comun + $datos);
