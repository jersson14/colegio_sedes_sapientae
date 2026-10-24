<?php
// Prueba unitaria de nota_normalizada() / nota_aprobada() con la escala de la BD de prueba (Perú: 0-20, AD/A/B/C).
require __DIR__ . '/../utilitario/regional.php';
$casos = ['15' => '15', '15.5' => null, '21' => null, '-1' => null, 'ad' => 'AD', ' b ' => 'B', 'X' => null, '' => null, '11,0' => '11', '020' => '20'];
$fallas = 0;
foreach ($casos as $in => $esperado) {
    $r = nota_normalizada($in);
    if ($r !== $esperado) { $fallas++; echo "FALLA [$in] => " . var_export($r, true) . "\n"; }
}
foreach (['11' => true, '10' => false, 'B' => true, 'C' => false, 'AD' => true] as $in => $esp) {
    if (nota_aprobada($in) !== $esp) { $fallas++; echo "FALLA aprobada [$in]\n"; }
}
if (descripcion_escala() !== '0 a 20 (aprueba con 11) o AD, A, B, C') { $fallas++; echo 'FALLA descripción: ' . descripcion_escala() . "\n"; }
echo "fallas=$fallas\n";
