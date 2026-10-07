<?php
    require_once __DIR__ . '/../../core/guard.php';
    require_once __DIR__ . '/../../core/pertenencia.php';
    require '../../model/model_notas.php';
    $MNOTAS = new Modelo_Notas();//Instaciamos
    $id = htmlspecialchars($_POST['id'],ENT_QUOTES,'UTF-8');
    $id = id_usuario_propio($id); // IDOR: la UI siempre envía el id propio

    $consulta = $MNOTAS->Cargar_aulas_por_docente($id);
    echo json_encode($consulta);
 
?>
