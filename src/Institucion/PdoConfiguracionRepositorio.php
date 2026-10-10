<?php

declare(strict_types=1);

namespace App\Institucion;

use PDO;

/** La tabla `configuracion` de la base de la institución (Fase 5.1). */
final class PdoConfiguracionRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function cargar(): Configuracion
    {
        try {
            $filas = $this->pdo->query('SELECT clave, valor FROM configuracion')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\PDOException) {
            // Base sin la migración todavía: valores por defecto (de colegio), nunca un error al entrar.
            $filas = [];
        }
        /** @var array<string, string> $filas */
        return new Configuracion($filas);
    }

    /**
     * @param array<string, ?string> $cambios clave => valor (null o vacío = volver al de por defecto)
     * @throws \InvalidArgumentException si alguna clave o valor no es válido (no se guarda nada)
     */
    public function guardar(array $cambios): void
    {
        $normalizados = [];
        foreach ($cambios as $clave => $valor) {
            $normalizados[$clave] = Configuracion::validar((string) $clave, $valor);
        }
        $guardar = $this->pdo->prepare('CALL SP_GUARDAR_CONFIGURACION(?, ?)');
        foreach ($normalizados as $clave => $valor) {
            $guardar->execute([$clave, $valor]);
            $guardar->closeCursor();
        }
    }
}
