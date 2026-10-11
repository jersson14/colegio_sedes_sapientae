<?php

declare(strict_types=1);

namespace App\Facturacion;

/**
 * La cuenta de Nubefact de una institución: ruta y token (los da Nubefact en «API - Integración»), series y
 * cómo se trata el IGV. Los servicios educativos suelen estar exonerados del IGV; lo confirma el contador.
 */
final class ConfiguracionNubefact
{
    /** tipo_de_igv de Nubefact por afectación. */
    public const AFECTACIONES = ['EXONERADO' => 8, 'GRAVADO' => 1, 'INAFECTO' => 9];

    public const PORCENTAJE_IGV = 18.0;

    public function __construct(
        public readonly string $ruta,
        public readonly string $token,
        public readonly string $serieBoleta,
        public readonly string $serieFactura,
        public readonly string $afectacion,
        public readonly bool $enviarAlCliente,
    ) {
    }

    /**
     * Solo rutas de la API de Nubefact (https://api.nubefact.com/…): la ruta la escribe el administrador del
     * colegio y el servidor la llama, así que no puede apuntar a otro sitio (SSRF). Para las pruebas,
     * NUBEFACT_HOSTS_PERMITIDOS añade hosts (también por http).
     *
     * @param list<string> $hostsPermitidos «host» o «host:puerto» adicionales
     * @throws \InvalidArgumentException
     */
    public static function validarRuta(string $ruta, array $hostsPermitidos = []): string
    {
        $ruta = trim($ruta);
        $partes = parse_url($ruta);
        $host = strtolower((string) ($partes['host'] ?? ''));
        $conPuerto = $host . (isset($partes['port']) ? ':' . $partes['port'] : '');
        $oficial = ($partes['scheme'] ?? '') === 'https' && $host === 'api.nubefact.com' && !isset($partes['port'])
            && preg_match('#^/api/v1/[A-Za-z0-9-]+/?$#', (string) ($partes['path'] ?? '')) === 1;
        $deprueba = in_array($partes['scheme'] ?? '', ['http', 'https'], true) && $host !== ''
            && (in_array($host, $hostsPermitidos, true) || in_array($conPuerto, $hostsPermitidos, true));
        if (!$oficial && !$deprueba) {
            throw new \InvalidArgumentException('La ruta debe ser la de tu cuenta de Nubefact: https://api.nubefact.com/api/v1/…');
        }
        return $ruta;
    }

    /** @throws \InvalidArgumentException */
    public static function validarSerie(string $serie, string $letra): string
    {
        $serie = strtoupper(trim($serie));
        if (preg_match('/^' . $letra . '[A-Z0-9]{3}$/', $serie) !== 1) {
            throw new \InvalidArgumentException("La serie debe tener 4 caracteres y empezar con {$letra} (p. ej. {$letra}001).");
        }
        return $serie;
    }

    public function tipoIgv(): int
    {
        return self::AFECTACIONES[$this->afectacion] ?? 8;
    }
}
