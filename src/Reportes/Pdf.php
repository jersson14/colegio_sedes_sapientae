<?php

declare(strict_types=1);

namespace App\Reportes;

/**
 * Genera y envía un reporte PDF con mPDF (vendor propio en view/MPDF/vendor). Cada reporte conserva
 * su configuración de página; esto solo reúne lo que todos repetían.
 */
final class Pdf
{
    /** Configuración A4 que usan la mayoría de los reportes. */
    public const A4 = [
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 16,
        'margin_bottom' => 16,
        'margin_header' => 9,
        'margin_footer' => 9,
    ];

    /**
     * @param array<string, mixed> $config
     * @param string $archivo nombre sugerido; vacío = el que ponga mPDF
     */
    public static function enviar(string $html, array $config, string $archivo = '', ?string $pie = null, ?string $titulo = null): void
    {
        require_once __DIR__ . '/../../view/MPDF/vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf($config);
        // Fase 4B.3: los PDF de una institución en prueba llevan marca de agua.
        require_once __DIR__ . '/../../core/comercial.php';
        if (comercial_condiciones()->esPrueba()) {
            $mpdf->SetWatermarkText('VERSIÓN DE PRUEBA', 0.08);
            $mpdf->showWatermarkText = true;
        }
        if ($titulo !== null) {
            $mpdf->SetTitle($titulo);
        }
        if ($pie !== null) {
            $mpdf->SetHTMLFooter($pie);
        }
        $mpdf->WriteHTML($html);
        $archivo === '' ? $mpdf->Output() : $mpdf->Output($archivo, 'I');
    }

    /**
     * «src» de una imagen subida (el logo de la institución) para mPDF, que la lee del disco. Desde la
     * Fase 4.6 puede estar en el almacén de la institución: entonces la ruta física. Si no, la ruta
     * relativa de siempre desde view/MPDF/REPORTE/ (los archivos de antes de la Fase 4).
     */
    public static function imagen(string $rutaBd): string
    {
        require_once __DIR__ . '/../../core/subidas.php';
        $fisica = $rutaBd !== '' ? subida_ubicar($rutaBd, dirname($rutaBd)) : null;
        $almacen = realpath(almacen_raiz());
        return $fisica !== null && $almacen !== false && str_starts_with($fisica, $almacen . DIRECTORY_SEPARATOR)
            ? $fisica
            : '../../../' . $rutaBd;
    }

    /** Sin datos que mostrar: 404 en lugar de un PDF vacío o con avisos de PHP. */
    public static function sinDatos(): never
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('No hay datos para este reporte.');
    }
}
