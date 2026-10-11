<?php

declare(strict_types=1);

namespace App\Facturacion;

use App\Support\Cifrado;
use PDO;

/** La cuenta de Nubefact de la institución, en la tabla configuracion (claves facturacion.*). El token, cifrado. */
final class PdoConfiguracionFacturacion
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, string> */
    private function filas(): array
    {
        $consulta = $this->pdo->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'facturacion.%'");
        /** @var array<string, string> */
        return $consulta->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** null si aún no hay ruta y token. */
    public function cargar(Cifrado $cifrado): ?ConfiguracionNubefact
    {
        $f = $this->filas();
        if (($f['facturacion.ruta'] ?? '') === '' || ($f['facturacion.token'] ?? '') === '') {
            return null;
        }
        return new ConfiguracionNubefact(
            $f['facturacion.ruta'],
            $cifrado->descifrar($f['facturacion.token']),
            $f['facturacion.serie_boleta'] ?? 'B001',
            $f['facturacion.serie_factura'] ?? 'F001',
            $f['facturacion.afectacion'] ?? 'EXONERADO',
            ($f['facturacion.enviar_cliente'] ?? '0') === '1',
        );
    }

    /**
     * Para la pantalla: nunca el token, solo si está configurado.
     *
     * @return array<string, mixed>
     */
    public function resumen(): array
    {
        $f = $this->filas();
        $series = $this->pdo->query('SELECT serie, ultimo FROM series_comprobantes')->fetchAll(PDO::FETCH_KEY_PAIR);
        $boleta = $f['facturacion.serie_boleta'] ?? 'B001';
        $factura = $f['facturacion.serie_factura'] ?? 'F001';
        return [
            'ruta' => $f['facturacion.ruta'] ?? '',
            'token_configurado' => ($f['facturacion.token'] ?? '') !== '',
            'serie_boleta' => $boleta,
            'serie_factura' => $factura,
            'ultimo_boleta' => (int) ($series[$boleta] ?? 0),
            'ultimo_factura' => (int) ($series[$factura] ?? 0),
            'afectacion' => $f['facturacion.afectacion'] ?? 'EXONERADO',
            'enviar_cliente' => ($f['facturacion.enviar_cliente'] ?? '0') === '1',
        ];
    }

    /**
     * @param array<string, string> $datos ruta, token (vacío = se conserva), serie_boleta, serie_factura, afectacion,
     *                                     enviar_cliente, ultimo_boleta y ultimo_factura (para continuar una numeración previa)
     * @param list<string> $hostsPermitidos
     * @throws \InvalidArgumentException
     */
    public function guardar(array $datos, Cifrado $cifrado, array $hostsPermitidos = []): void
    {
        $afectacion = strtoupper(trim($datos['afectacion'] ?? 'EXONERADO'));
        if (!isset(ConfiguracionNubefact::AFECTACIONES[$afectacion])) {
            throw new \InvalidArgumentException('Afectación al IGV inválida.');
        }
        $token = trim($datos['token'] ?? '');
        if ($token === '' && !$this->resumen()['token_configurado']) {
            throw new \InvalidArgumentException('Falta el token de Nubefact.');
        }
        $ultimos = [];
        foreach (['boleta', 'factura'] as $tipo) {
            $valor = trim($datos["ultimo_$tipo"] ?? '');
            if ($valor !== '' && preg_match('/^\d{1,8}$/', $valor) !== 1) {
                throw new \InvalidArgumentException("El último número de $tipo debe ser un número.");
            }
            $ultimos[$tipo] = $valor;
        }
        $cambios = [
            'facturacion.ruta' => ConfiguracionNubefact::validarRuta($datos['ruta'] ?? '', $hostsPermitidos),
            'facturacion.serie_boleta' => ConfiguracionNubefact::validarSerie($datos['serie_boleta'] ?? 'B001', 'B'),
            'facturacion.serie_factura' => ConfiguracionNubefact::validarSerie($datos['serie_factura'] ?? 'F001', 'F'),
            'facturacion.afectacion' => $afectacion,
            'facturacion.enviar_cliente' => ($datos['enviar_cliente'] ?? '') === '1' ? '1' : '0',
        ];
        if ($token !== '') {
            if (mb_strlen($token) > 200) {
                throw new \InvalidArgumentException('El token es demasiado largo.');
            }
            $cambios['facturacion.token'] = $cifrado->cifrar($token);
        }
        $guardar = $this->pdo->prepare('CALL SP_GUARDAR_CONFIGURACION(?, ?)');
        foreach ($cambios as $clave => $valor) {
            $guardar->execute([$clave, $valor]);
            $guardar->closeCursor();
        }
        foreach (['boleta' => $cambios['facturacion.serie_boleta'], 'factura' => $cambios['facturacion.serie_factura']] as $tipo => $serie) {
            if ($ultimos[$tipo] !== '') {
                $fijar = $this->pdo->prepare('CALL SP_FIJAR_CORRELATIVO(?, ?)');
                $fijar->execute([$serie, (int) $ultimos[$tipo]]);
                $fijar->closeCursor();
            }
        }
    }
}
