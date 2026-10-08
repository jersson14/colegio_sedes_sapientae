<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

require_once __DIR__ . '/../esquema/esquema.php';
require_once __DIR__ . '/../esquema/RecreaProcedimientos.php';

/**
 * Fase 3, módulo usuario: defectos de la edición de cuentas (los descubrió tests/E2E/flujos.mjs §8).
 *
 *  1. SP_MODIFICAR_USUARIO recibía el usuario como VARCHAR(20): un nombre más largo se truncaba
 *     sin aviso y la cuenta ya no podía entrar con el nombre que escribió el administrador.
 *  2. Permitía renombrar una cuenta al nombre de otra: dos cuentas con el mismo usuario. Ahora
 *     devuelve 2 sin tocar nada (comparación sin distinguir mayúsculas, como el login).
 *  3. SP_MODIFICAR_USUARIO_ESTATUS aceptaba cualquier texto: el ENUM en modo permisivo guardaba ''
 *     y la cuenta quedaba en un estado que el login trata como activo. Ahora se rechaza.
 *
 * Ambos devuelven 1 al aplicar el cambio. down() restaura las definiciones originales.
 */
final class CorregirModificarUsuario extends AbstractMigration
{
    use RecreaProcedimientos;

    public function up(): void
    {
        $this->recrear('SP_MODIFICAR_USUARIO', self::MODIFICAR);
        $this->recrear('SP_MODIFICAR_USUARIO_ESTATUS', self::ESTATUS);
    }

    public function down(): void
    {
        foreach (['SP_MODIFICAR_USUARIO', 'SP_MODIFICAR_USUARIO_ESTATUS'] as $nombre) {
            $this->recrear($nombre, esquema_procedimiento_original($nombre));
        }
    }

    private const MODIFICAR = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_USUARIO`(IN `ID` INT, IN `USUARIO` VARCHAR(250), IN `ROL` INT, IN `EMAIL` VARCHAR(255))
BEGIN
    IF EXISTS (SELECT 1 FROM usuario WHERE usuario.usu_usuario = USUARIO AND usuario.usu_id <> ID) THEN
        SELECT 2;
    ELSE
        UPDATE usuario SET
            usuario.usu_usuario = USUARIO,
            usuario.rol_id = ROL,
            usuario.usu_email = EMAIL
        WHERE usuario.usu_id = ID;
        SELECT 1;
    END IF;
END
SQL;

    private const ESTATUS = <<<'SQL'
CREATE PROCEDURE `SP_MODIFICAR_USUARIO_ESTATUS`(IN `ID` INT, IN `ESTATUS` VARCHAR(20))
BEGIN
    IF ESTATUS NOT IN ('ACTIVO', 'INACTIVO') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Estado de usuario no válido';
    END IF;
    UPDATE usuario SET usuario.usu_estatus = ESTATUS WHERE usuario.usu_id = ID;
    SELECT 1;
END
SQL;
}
