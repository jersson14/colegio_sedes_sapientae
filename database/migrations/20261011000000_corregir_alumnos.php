<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo alumnos: defectos que encontró tests/E2E/flujos.mjs §9.
 *
 *  1. SP_MODIFICAR_ALUMNOS actualizaba los padres por el IDPA que manda el formulario: con un id
 *     ajeno se sobrescribían los padres de OTRO alumno. Ahora por id_alu (padres es 1:1 con alumnos);
 *     el parámetro IDPA se conserva para no cambiar la firma, pero ya no se usa.
 *  2. SP_REGISTRAR_ALUMNOS enlazaba los padres con MAX(Id_alumno): con dos registros simultáneos,
 *     los padres de uno quedaban en el otro. Ahora con LAST_INSERT_ID().
 *  3. SP_ELIMINAR_ALUMNO recibía el DNI como INT (comparación numérica contra un CHAR) y, si el
 *     alumno tenía matrícula, la clave foránea lanzaba un error que llegaba como 500: el panel nunca
 *     mostraba su aviso. Ahora compara el DNI como texto y devuelve 0 si tiene matrícula o no existe,
 *     1 si lo eliminó.
 *
 * El largo de los campos (DNI de 8, celular de 9) se valida en App\Domain\Alumno\FichaAlumno.
 */
final class CorregirAlumnos extends AbstractMigration
{
    use RecreaProcedimientos;

    private const PADRES_POR_IDPA = 'WHERE id_papas = IDPA;';
    private const PADRES_POR_ALUMNO = 'WHERE id_alu = ID;';
    private const ULTIMO_POR_MAX = 'SET @ulti:=(SELECT MAX(Id_alumno) AS id FROM alumnos);';
    private const ULTIMO_INSERTADO = 'SET @ulti:=LAST_INSERT_ID();';

    public function up(): void
    {
        $modificar = esquema_procedimiento_original('SP_MODIFICAR_ALUMNOS');
        // Aparece dos veces (rama «mismo DNI» y rama «DNI nuevo»): las dos deben cambiar.
        if (substr_count($modificar, self::PADRES_POR_IDPA) !== 2) {
            throw new RuntimeException('SP_MODIFICAR_ALUMNOS no tiene la forma esperada');
        }
        $this->recrear('SP_MODIFICAR_ALUMNOS', str_replace(self::PADRES_POR_IDPA, self::PADRES_POR_ALUMNO, $modificar));
        $this->recrear('SP_REGISTRAR_ALUMNOS', self::reemplazarUnaVez(
            esquema_procedimiento_original('SP_REGISTRAR_ALUMNOS'),
            self::ULTIMO_POR_MAX,
            self::ULTIMO_INSERTADO
        ));
        $this->recrear('SP_ELIMINAR_ALUMNO', self::ELIMINAR);
    }

    public function down(): void
    {
        foreach (['SP_MODIFICAR_ALUMNOS', 'SP_REGISTRAR_ALUMNOS', 'SP_ELIMINAR_ALUMNO'] as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
    }

    private const ELIMINAR = <<<'SQL'
CREATE PROCEDURE `SP_ELIMINAR_ALUMNO`(IN `DNI` CHAR(8))
BEGIN
    -- Con matrícula no se elimina (antes fallaba la clave foránea y el panel recibía un 500).
    IF EXISTS (SELECT 1 FROM matricula INNER JOIN alumnos ON alumnos.Id_alumno = matricula.id_alumno
               WHERE alumnos.alum_dni = DNI) THEN
        SELECT 0;
    ELSE
        DELETE FROM alumnos WHERE alumnos.alum_dni = DNI;
        SELECT IF(ROW_COUNT() > 0, 1, 0);
    END IF;
END
SQL;
}
