<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Carga database/seeders/datos_prueba.sql: dataset de piloto ANONIMIZADO (507 filas, 36 tablas).
 * Contraseña de todos los usuarios: Prueba.2026 (usuarioN, con N = usu_id).
 *
 * ¡BORRA todas las tablas antes de insertar! Por eso exige dos condiciones:
 *   1. El nombre de la BD contiene "prueba", "test" o "ci".
 *   2. La variable de entorno PERMITIR_DATOS_PRUEBA=1.
 *
 * Uso: PERMITIR_DATOS_PRUEBA=1 vendor/bin/phinx seed:run -s DatosPrueba
 */
final class DatosPrueba extends AbstractSeed
{
    public function run(): void
    {
        $bd = (string) $this->getAdapter()->getOption('name');
        if (!preg_match('/(prueba|test|ci)/i', $bd) || getenv('PERMITIR_DATOS_PRUEBA') !== '1') {
            throw new RuntimeException(
                "Negado: los datos de prueba BORRAN la base \"$bd\". Solo en bases cuyo nombre contenga "
                . '"prueba", "test" o "ci", y con PERMITIR_DATOS_PRUEBA=1.'
            );
        }
        $this->execute("SET SESSION sql_mode = ''");
        // Las horas del dataset son de Perú: las columnas TIMESTAMP se guardan en UTC según la zona de la
        // sesión, y la aplicación las lee en -05:00 (APP_ZONA_HORARIA). Con la zona del servidor (UTC en el CI)
        // saldrían cinco horas antes.
        $this->execute("SET SESSION time_zone = '-05:00'");
        foreach (preg_split('/;\R/', (string) file_get_contents(__DIR__ . '/datos_prueba.sql')) ?: [] as $sql) {
            $sql = trim(preg_replace('/^--.*$/m', '', $sql) ?? '');
            if ($sql !== '') {
                $this->execute($sql);
            }
        }
    }
}
