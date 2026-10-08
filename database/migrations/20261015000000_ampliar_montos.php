<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Montos: de DECIMAL(5,2) (máximo S/ 999.99) a DECIMAL(10,2) (hasta S/ 99 999 999.99), en las
 * columnas y en los parámetros de los procedimientos que los reciben. Antes, un monto mayor se
 * recortaba a 999.99 sin aviso; desde la migración de matrícula se rechazaba. Decisión del
 * responsable: ampliar.
 *
 * Ampliar no pierde datos. down() vuelve a (5,2) solo si ningún monto guardado lo supera (si no,
 * se recortaría): en ese caso se detiene.
 */
final class AmpliarMontos extends AbstractMigration
{
    use RecreaProcedimientos;

    private const COLUMNAS = [
        'egresos' => ['monto'],
        'ingresos' => ['monto'],
        'matricula' => ['pago_admi', 'pago_alu_nuevo', 'pago_matricula'],
        'pago_pensiones' => ['sub_total'],
        'pensiones' => ['precio', 'mora'],
    ];

    private const PROCEDIMIENTOS = [
        'SP_MODIFICAR_EGRESOS', 'SP_MODIFICAR_INGRESOS', 'SP_MODIFICAR_MATRICULA', 'SP_MODIFICAR_PAGO_PENSION',
        'SP_MODIFICAR_PENSIONES', 'SP_REGISTRAR_DETALLE_PENSION_PAGO', 'SP_REGISTRAR_EGRESOS', 'SP_REGISTRAR_INGRESOS',
        'SP_REGISTRAR_MATRICULA', 'SP_REGISTRAR_PENSIONES',
    ];

    public function up(): void
    {
        $this->cambiar('DECIMAL(10,2)', '/decimal\s*\(\s*5\s*,\s*2\s*\)/i');
    }

    public function down(): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                $fila = $this->fetchRow("SELECT COUNT(*) AS n FROM `$tabla` WHERE ABS(`$columna`) > 999.99");
                if ((int) ($fila['n'] ?? 0) > 0) {
                    throw new RuntimeException("No se puede volver a DECIMAL(5,2): $tabla.$columna tiene montos mayores a 999.99");
                }
            }
        }
        $this->cambiar('DECIMAL(5,2)', '/decimal\s*\(\s*10\s*,\s*2\s*\)/i');
    }

    private function cambiar(string $tipo, string $patronAnterior): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            $cambios = implode(', ', array_map(static fn (string $c): string => "MODIFY `$c` $tipo NULL DEFAULT NULL", $columnas));
            $this->execute("ALTER TABLE `$tabla` $cambios");
        }
        foreach (self::PROCEDIMIENTOS as $nombre) {
            $sql = $this->definicionActual($nombre);
            // Solo la firma: el cuerpo de estos SP no declara montos (los DECIMAL(10,2) de los
            // reportes SP_LISTAR_DIFERENCIA* ya tenían el tamaño nuevo y no se tocan).
            $inicio = strpos($sql, "\n");
            $firma = $inicio === false ? $sql : substr($sql, 0, $inicio);
            $nueva = (string) preg_replace($patronAnterior, $tipo, $firma);
            if ($nueva === $firma) {
                throw new RuntimeException("$nombre no tiene parámetros con el tipo esperado");
            }
            $this->recrear($nombre, $nueva . substr($sql, strlen($firma)));
        }
    }
}
