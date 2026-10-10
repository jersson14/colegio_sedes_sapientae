<?php

declare(strict_types=1);

namespace App\Tenancy;

use PDO;

/**
 * Convierte una demo en cliente (Fase 4B.6): el colegio que probó con datos de ejemplo pasa a una base
 * LIMPIA con su administrador real, en el mismo subdominio. Los datos de la demo (ficticios) se borran, y
 * sus archivos también: nada de la prueba se mezcla con los datos reales.
 */
final class ConversionDemo
{
    /** @param string $almacenes carpeta con los almacenes de las instituciones (ALMACEN_DIR) */
    public function __construct(
        private readonly PDO $servidor,
        private readonly PDO $maestro,
        private readonly AltaInstitucion $alta,
        private readonly string $almacenes,
    ) {
    }

    /**
     * $datos trae los del cliente (razón social, correo, administrador) y el estado con que empieza; su slug
     * debe ser el de la demo. La base nueva se llama como la de la demo con un sufijo de fecha.
     *
     * @return array{clave: string, base: string}
     */
    public function convertir(SolicitudAlta $datos): array
    {
        $demo = $this->maestro->prepare('SELECT base_datos, demo FROM tenants WHERE slug = ? AND borrado_en IS NULL');
        $demo->execute([$datos->slug]);
        $fila = $demo->fetch(PDO::FETCH_ASSOC);
        if (!is_array($fila)) {
            throw new \DomainException("No existe la institución «{$datos->slug}».");
        }
        if ((int) $fila['demo'] !== 1) {
            throw new \DomainException("«{$datos->slug}» no es una demo: sus datos son reales y no se reemplazan.");
        }
        if ($datos->demo) {
            throw new \InvalidArgumentException('La conversión crea una base limpia, no otra demo.');
        }
        $baseDemo = (string) $fila['base_datos'];
        $clave = $this->alta->crearBase($datos);

        $this->maestro->prepare('UPDATE tenants SET base_datos = ?, demo = 0, razon_social = ?, estado = ? WHERE slug = ?')
            ->execute([$datos->baseDatos, $datos->razonSocial, $datos->estado->value, $datos->slug]);
        // Desde aquí el colegio ya usa la base limpia; la de la demo y sus archivos sobran.
        $this->servidor->exec("DROP DATABASE IF EXISTS `$baseDemo`");
        RespaldoInstitucion::borrarCarpeta($this->almacenes . '/' . $datos->slug);
        return ['clave' => $clave, 'base' => $datos->baseDatos];
    }

    /** Nombre de la base del cliente: la de la demo con la fecha, sin pasar de 64 caracteres. */
    public static function baseCliente(string $slug): string
    {
        return substr('sge_' . str_replace('-', '_', $slug), 0, 50) . '_' . date('ymdHis');
    }
}
