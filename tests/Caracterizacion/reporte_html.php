<?php

/**
 * Ejecuta un reporte de view/MPDF/REPORTE/ por línea de comandos y escribe en la salida, en JSON, el
 * HTML que el reporte entrega a mPDF (en lugar del PDF). Lo usa tools/caracterizar_reportes.php.
 *
 * Uso: php reporte_html.php <reporte.php> <rol> <usu_id> 'codigo=38&fecha=…' (parámetros GET)
 * Requiere COLEGIO_ENV apuntando a la configuración de prueba (fecha congelada).
 */

declare(strict_types=1);

namespace Mpdf {
    /** Sustituye a mPDF: registra lo que el reporte le pide en lugar de generar el PDF. */
    class Mpdf
    {
        /** @var array<string, mixed> */
        public static array $registro = ['config' => [], 'html' => [], 'pie' => [], 'cabecera' => []];

        /** @param array<string, mixed> $config */
        public function __construct(array $config = [])
        {
            self::$registro['config'] = $config;
        }

        public function SetHTMLFooter(string $html): void
        {
            self::$registro['pie'][] = $html;
        }

        public function SetHTMLHeader(string $html): void
        {
            self::$registro['cabecera'][] = $html;
        }

        public function WriteHTML(string $html, int $modo = 0): void
        {
            self::$registro['html'][] = $html;
        }

        public function Output(string $nombre = '', string $destino = ''): string
        {
            echo "\n@@REPORTE@@" . json_encode(self::$registro + ['archivo' => $nombre], JSON_UNESCAPED_UNICODE);
            return '';
        }

        /** Cualquier otra opción de mPDF (SetTitle, AddPage…) se registra sin efecto. */
        public function __call(string $metodo, array $argumentos): mixed
        {
            self::$registro['otros'][] = $metodo;
            return null;
        }
    }

    class MpdfException extends \Exception
    {
    }
}

namespace {
    [, $reporte, $rol, $usuario, $parametros] = $argv + [null, '', 'ADMINISTRADOR', '9', ''];
    parse_str((string) $parametros, $_GET);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    // Sesión ya iniciada: core/sesion.php no la vuelve a abrir y el guard ve el rol pedido.
    session_save_path(sys_get_temp_dir());
    session_start();
    require_once __DIR__ . '/../../core/tenant.php';
    $_SESSION = [
        'S_ID' => (string) $usuario, 'S_ROL' => (string) $rol, 'S_DNI' => '', 'csrf_token' => 'x',
        'ultima_actividad' => time(), 'S_TENANT' => tenant_actual()->slug,
    ];

    $carpeta = __DIR__ . '/../../view/MPDF/REPORTE';
    chdir($carpeta); // los reportes cargan '../conexion.php' relativo a su carpeta
    require $carpeta . '/' . basename((string) $reporte);
}
