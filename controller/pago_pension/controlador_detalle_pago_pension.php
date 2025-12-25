<?php
    require '../../model/model_pago_pension.php';
    $MPP = new Modelo_Pago_Pension(); //Instanciamos
    $id_matri = htmlspecialchars($_POST['id_matri'], ENT_QUOTES, 'UTF-8');
    $concepto = htmlspecialchars($_POST['concepto'], ENT_QUOTES, 'UTF-8');
    $id_pension = htmlspecialchars($_POST['id_pension'], ENT_QUOTES, 'UTF-8');
    $monto = htmlspecialchars($_POST['monto'], ENT_QUOTES, 'UTF-8');
    
    $array_id = explode(",", $id_matri);
    $array_concepto = explode(",", $concepto);
    $array_pension = explode(",", $id_pension);
    $array_subtotal = explode(",", $monto);
    
    $response = 1; // Suponemos éxito por defecto
    
    for ($i = 0; $i < count($array_id); $i++) {
        $consulta = $MPP->Registrar_detalle_Pago_Pension($array_id[$i], $array_concepto[$i], $array_pension[$i], $array_subtotal[$i]);
        if ($consulta == 2) {
            $response = 2; // Cambiamos el estado si existe algún duplicado
            break; // Salimos si ya existe la pensión
        }
    }
    
    echo $response;
?>