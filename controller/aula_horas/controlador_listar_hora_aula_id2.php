<?php
require '../../model/model_aula_horas.php';
$MAH = new Modelo_aula_Horas();

if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
    $año = htmlspecialchars($_POST['año'], ENT_QUOTES, 'UTF-8');

    $consulta = $MAH->Listar_componentes_horas_aulas($id,$año);

    if ($consulta && is_array($consulta)) {
        echo json_encode([
            "sEcho" => 1,
            "iTotalRecords" => count($consulta),
            "iTotalDisplayRecords" => count($consulta),
            "aaData" => $consulta
        ]);
    } else {
        echo json_encode([
            "sEcho" => 1,
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => []
        ]);
    }
} else {
    error_log("ID no proporcionado o es inválido");
    echo json_encode([
        "sEcho" => 1,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => "ID no proporcionado o es inválido"
    ]);
}

?>
