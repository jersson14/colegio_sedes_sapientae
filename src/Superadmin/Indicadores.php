<?php

declare(strict_types=1);

namespace App\Superadmin;

use PDO;

/**
 * Cifras del tablero de superadministrador, todas de la BD maestra (no abre las bases de los colegios):
 * instituciones por estado y tipo, cobros, consumo del asistente de IA y alertas.
 */
final class Indicadores
{
    public function __construct(private readonly PDO $maestro)
    {
    }

    /** @return array<string, int> estado => instituciones (sin las ya borradas) */
    public function porEstado(): array
    {
        $cuenta = array_fill_keys(['PRUEBA', 'ACTIVO', 'MOROSO', 'SUSPENDIDO', 'CANCELADO'], 0);
        foreach ($this->maestro->query('SELECT estado, COUNT(*) AS n FROM tenants WHERE borrado_en IS NULL GROUP BY estado')->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $cuenta[(string) $f['estado']] = (int) $f['n'];
        }
        return $cuenta;
    }

    /** @return array<string, int> tipo => instituciones */
    public function porTipo(): array
    {
        $cuenta = array_fill_keys(['COLEGIO', 'INSTITUTO', 'CETPRO'], 0);
        foreach ($this->maestro->query('SELECT tipo, COUNT(*) AS n FROM tenants WHERE borrado_en IS NULL GROUP BY tipo')->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $cuenta[(string) $f['tipo']] = (int) $f['n'];
        }
        return $cuenta;
    }

    /** @return array{cobrado_mes: float, por_cobrar: float, vencido: float, morosos: int} */
    public function cobros(\DateTimeImmutable $hoy): array
    {
        $consulta = $this->maestro->prepare(
            "SELECT COALESCE(SUM(CASE WHEN estado = 'PAGADA' AND pagada_en >= ? THEN monto END), 0) AS cobrado,
                    COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' AND vencimiento >= ? THEN monto END), 0) AS por_cobrar,
                    COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' AND vencimiento < ? THEN monto END), 0) AS vencido
               FROM facturas"
        );
        $hoyTexto = $hoy->format('Y-m-d');
        $consulta->execute([$hoy->format('Y-m-01'), $hoyTexto, $hoyTexto]);
        $f = (array) $consulta->fetch(PDO::FETCH_ASSOC);
        return [
            'cobrado_mes' => (float) $f['cobrado'],
            'por_cobrar' => (float) $f['por_cobrar'],
            'vencido' => (float) $f['vencido'],
            'morosos' => $this->porEstado()['MOROSO'],
        ];
    }

    /**
     * Lo emitido y lo cobrado en cada uno de los últimos meses (para el gráfico).
     *
     * @return list<array{mes: string, emitido: float, cobrado: float}>
     */
    public function cobrosPorMes(\DateTimeImmutable $hoy, int $meses = 6): array
    {
        $serie = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $serie[$hoy->modify("first day of -$i month")->format('Y-m')] = ['emitido' => 0.0, 'cobrado' => 0.0];
        }
        $desde = array_key_first($serie) . '-01';
        $emitido = $this->maestro->prepare("SELECT DATE_FORMAT(emision, '%Y-%m') AS mes, SUM(monto) AS total FROM facturas
            WHERE estado <> 'ANULADA' AND emision >= ? GROUP BY mes");
        $emitido->execute([$desde]);
        foreach ($emitido->fetchAll(PDO::FETCH_ASSOC) as $f) {
            if (isset($serie[$f['mes']])) {
                $serie[$f['mes']]['emitido'] = (float) $f['total'];
            }
        }
        $cobrado = $this->maestro->prepare("SELECT DATE_FORMAT(pagada_en, '%Y-%m') AS mes, SUM(monto) AS total FROM facturas
            WHERE estado = 'PAGADA' AND pagada_en >= ? GROUP BY mes");
        $cobrado->execute([$desde]);
        foreach ($cobrado->fetchAll(PDO::FETCH_ASSOC) as $f) {
            if (isset($serie[$f['mes']])) {
                $serie[$f['mes']]['cobrado'] = (float) $f['total'];
            }
        }
        $lista = [];
        foreach ($serie as $mes => $v) {
            $lista[] = ['mes' => $mes] + $v;
        }
        return $lista;
    }

    /** @return array{consultas: int, tokens_entrada: int, tokens_salida: int} */
    public function consumoIa(string $mes): array
    {
        $consulta = $this->maestro->prepare('SELECT COALESCE(SUM(consultas), 0), COALESCE(SUM(tokens_entrada), 0), COALESCE(SUM(tokens_salida), 0) FROM consumo_ia WHERE mes = ?');
        $consulta->execute([$mes]);
        [$c, $e, $s] = $consulta->fetch(PDO::FETCH_NUM) ?: [0, 0, 0];
        return ['consultas' => (int) $c, 'tokens_entrada' => (int) $e, 'tokens_salida' => (int) $s];
    }

    /**
     * Lo que pide atención: pruebas por vencer o vencidas, morosos, suspendidos y demos.
     *
     * @return list<array{tipo: string, nivel: string, slug: string, texto: string}>
     */
    public function alertas(\DateTimeImmutable $hoy): array
    {
        $alertas = [];
        $hoyTexto = $hoy->format('Y-m-d');
        $filas = $this->maestro->query(
            "SELECT slug, razon_social, estado, demo, prueba_hasta, suspendido_desde FROM tenants WHERE borrado_en IS NULL AND estado <> 'CANCELADO' ORDER BY slug"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as $f) {
            $slug = (string) $f['slug'];
            if ($f['estado'] === 'PRUEBA' && $f['prueba_hasta'] !== null) {
                $dias = (int) (new \DateTimeImmutable((string) $f['prueba_hasta']))->diff($hoy)->format('%r%a') * -1;
                if ($dias < 0) {
                    $alertas[] = ['tipo' => 'prueba', 'nivel' => 'danger', 'slug' => $slug, 'texto' => "La prueba venció el {$f['prueba_hasta']}: asigna un plan o suspende."];
                } elseif ($dias <= 7) {
                    $alertas[] = ['tipo' => 'prueba', 'nivel' => 'warning', 'slug' => $slug, 'texto' => "La prueba vence en $dias día(s) ({$f['prueba_hasta']})."];
                }
            }
            if ($f['estado'] === 'MOROSO') {
                $alertas[] = ['tipo' => 'moroso', 'nivel' => 'warning', 'slug' => $slug, 'texto' => 'Moroso: no puede dar altas hasta pagar.'];
            }
            if ($f['estado'] === 'SUSPENDIDO') {
                $alertas[] = ['tipo' => 'suspendido', 'nivel' => 'danger', 'slug' => $slug, 'texto' => 'Suspendido desde el ' . substr((string) $f['suspendido_desde'], 0, 10) . '.'];
            }
            if ((int) $f['demo'] === 1) {
                $alertas[] = ['tipo' => 'demo', 'nivel' => 'info', 'slug' => $slug, 'texto' => 'Demo con datos de ejemplo: conviértela en cliente cuando contrate.'];
            }
        }
        $vencidas = $this->maestro->prepare("SELECT t.slug, COUNT(*) AS n, SUM(f.monto) AS monto FROM facturas f JOIN tenants t ON t.id = f.tenant_id
            WHERE f.estado = 'PENDIENTE' AND f.vencimiento < ? GROUP BY t.slug");
        $vencidas->execute([$hoyTexto]);
        foreach ($vencidas->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $alertas[] = ['tipo' => 'cobro', 'nivel' => 'danger', 'slug' => (string) $f['slug'],
                'texto' => "{$f['n']} cobro(s) vencido(s) por " . number_format((float) $f['monto'], 2) . '.'];
        }
        return $alertas;
    }

    /** @return list<array<string, string|null>> */
    public function cobrosDe(string $slug, int $limite = 24): array
    {
        $consulta = $this->maestro->prepare('SELECT f.numero, f.periodo_inicio, f.periodo_fin, f.emision, f.vencimiento, f.monto, f.moneda, f.estado, f.pagada_en
            FROM facturas f JOIN tenants t ON t.id = f.tenant_id WHERE t.slug = ? ORDER BY f.id DESC LIMIT ' . max(1, min($limite, 200)));
        $consulta->execute([$slug]);
        /** @var list<array<string, string|null>> */
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string, string|null>> */
    public function auditoriaDe(string $slug, int $limite = 30): array
    {
        $consulta = $this->maestro->prepare('SELECT fecha, actor, accion, detalle, ip FROM auditoria WHERE tenant = ? ORDER BY id DESC LIMIT ' . max(1, min($limite, 200)));
        $consulta->execute([$slug]);
        /** @var list<array<string, string|null>> */
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /** La última migración del código (para avisar de las bases que no están al día). */
    public static function ultimaMigracion(string $carpeta): string
    {
        $versiones = array_map(static fn (string $f): string => (string) strtok(basename($f), '_'), glob($carpeta . '/*.php') ?: []);
        sort($versiones);
        return (string) end($versiones);
    }
}
