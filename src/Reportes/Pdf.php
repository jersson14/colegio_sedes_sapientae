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
        if ($titulo !== null) {
            $mpdf->SetTitle($titulo);
        }
        if ($pie !== null) {
            $mpdf->SetHTMLFooter($pie);
        }
        $mpdf->WriteHTML($html);
        $archivo === '' ? $mpdf->Output() : $mpdf->Output($archivo, 'I');
    }

    /** Sin datos que mostrar: 404 en lugar de un PDF vacío o con avisos de PHP. */
    public static function sinDatos(): never
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('No hay datos para este reporte.');
    }
}
