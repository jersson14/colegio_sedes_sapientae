<?php

declare(strict_types=1);

namespace App\Facturacion;

/**
 * Los JSON de la API de Nubefact (operaciones generar_comprobante, consultar_comprobante y generar_anulacion)
 * para un comprobante de una sola línea: el concepto del ingreso.
 */
final class DocumentoNubefact
{
    /**
     * @param array{tipo: int|string, serie: string, numero: int|string, cliente_tipo_doc: string, cliente_doc: string, cliente_nombre: string,
     *              cliente_direccion: string, cliente_email: string, descripcion: string, total: float|string} $c
     * @param int $cantidad unidades del ingreso (si el total no se reparte exacto, una sola línea por el total)
     * @return array<string, mixed>
     */
    public static function comprobante(array $c, ConfiguracionNubefact $cfg, int $cantidad, \DateTimeInterface $fecha): array
    {
        $total = round((float) $c['total'], 2);
        $cantidad = max(1, $cantidad);
        if (abs(round($total / $cantidad, 2) * $cantidad - $total) > 0.001) {
            $cantidad = 1;
        }
        $gravado = $cfg->afectacion === 'GRAVADO';
        $base = $gravado ? round($total / (1 + ConfiguracionNubefact::PORCENTAJE_IGV / 100), 2) : $total;
        $igv = round($total - $base, 2);
        $documento = [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => (int) $c['tipo'],
            'serie' => $c['serie'],
            'numero' => (int) $c['numero'],
            'sunat_transaction' => 1,
            'cliente_tipo_de_documento' => $c['cliente_tipo_doc'],
            'cliente_numero_de_documento' => $c['cliente_doc'],
            'cliente_denominacion' => $c['cliente_nombre'],
            'cliente_direccion' => $c['cliente_direccion'],
            'cliente_email' => $c['cliente_email'],
            'fecha_de_emision' => $fecha->format('d-m-Y'),
            'moneda' => 1,
            'porcentaje_de_igv' => ConfiguracionNubefact::PORCENTAJE_IGV,
            'total_gravada' => $gravado ? $base : '',
            'total_exonerada' => $cfg->afectacion === 'EXONERADO' ? $total : '',
            'total_inafecta' => $cfg->afectacion === 'INAFECTO' ? $total : '',
            'total_igv' => $igv,
            'total' => $total,
            'enviar_automaticamente_a_la_sunat' => true,
            'enviar_automaticamente_al_cliente' => $cfg->enviarAlCliente && $c['cliente_email'] !== '',
            'items' => [[
                'unidad_de_medida' => 'ZZ',
                'codigo' => '',
                'descripcion' => $c['descripcion'],
                'cantidad' => $cantidad,
                'valor_unitario' => round($base / $cantidad, 10),
                'precio_unitario' => round($total / $cantidad, 10),
                'subtotal' => $base,
                'tipo_de_igv' => $cfg->tipoIgv(),
                'igv' => $igv,
                'total' => $total,
                'anticipo_regularizacion' => false,
            ]],
        ];
        return $documento;
    }

    /** @return array<string, mixed> */
    public static function consulta(int $tipo, string $serie, int $numero): array
    {
        return ['operacion' => 'consultar_comprobante', 'tipo_de_comprobante' => $tipo, 'serie' => $serie, 'numero' => $numero];
    }

    /** @return array<string, mixed> */
    public static function anulacion(int $tipo, string $serie, int $numero, string $motivo): array
    {
        return ['operacion' => 'generar_anulacion', 'tipo_de_comprobante' => $tipo, 'serie' => $serie, 'numero' => $numero,
            'motivo' => mb_substr($motivo, 0, 100), 'codigo_unico' => ''];
    }

    /**
     * El estado que deja una respuesta de Nubefact a generar o consultar.
     *
     * @param array<string, mixed> $r
     * @return array{estado: string, enlace: ?string, pdf: ?string, xml: ?string, hash: ?string, sunat: ?string, error: ?string}
     */
    public static function resultado(array $r): array
    {
        $texto = static fn (string $c): ?string => isset($r[$c]) && is_scalar($r[$c]) && (string) $r[$c] !== '' ? mb_substr((string) $r[$c], 0, 500) : null;
        if (isset($r['errors'])) {
            $error = is_array($r['errors']) ? implode('; ', array_map('strval', $r['errors'])) : (string) $r['errors'];
            return ['estado' => 'ERROR', 'enlace' => null, 'pdf' => null, 'xml' => null, 'hash' => null, 'sunat' => null,
                'error' => mb_substr($error . (isset($r['codigo']) ? " (código {$r['codigo']})" : ''), 0, 500)];
        }
        return [
            'estado' => ($r['aceptada_por_sunat'] ?? false) === true ? 'ACEPTADO' : 'EMITIDO',
            'enlace' => $texto('enlace'),
            'pdf' => $texto('enlace_del_pdf'),
            'xml' => $texto('enlace_del_xml'),
            'hash' => $texto('codigo_hash'),
            'sunat' => $texto('sunat_description'),
            'error' => null,
        ];
    }
}
