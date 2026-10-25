<?php
require_once __DIR__ . '/../_seguridad.php';
    require '../../model/model_matriculas.php';

    $MMAT= new Modelo_Matriculas();//Instaciamos
    $id = htmlspecialchars($_POST['id'] ?? '',ENT_QUOTES,'UTF-8');
    // sin aula elegida todavía: lista vacía (antes devolvía un aviso de PHP y la pantalla fallaba al leerlo)
    if ($id === '') { echo '[]'; exit; }
    $consulta = $MMAT->TraerNivel($id);
    echo json_encode($consulta);
   
?>