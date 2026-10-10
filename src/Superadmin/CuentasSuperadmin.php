<?php

declare(strict_types=1);

namespace App\Superadmin;

use PDO;

/**
 * Cuentas del panel de superadministrador (BD maestra). No tienen nada que ver con las de los colegios:
 * otra tabla, otra sesión y otro host. Se crean por consola (tools/crear_superadmin.php).
 */
final class CuentasSuperadmin
{
    public function __construct(private readonly PDO $maestro)
    {
    }

    /** @return array{usuario: string, nombre: string}|null la cuenta si usuario y clave son correctos y está activa */
    public function autenticar(string $usuario, string $clave): ?array
    {
        $consulta = $this->maestro->prepare('SELECT usuario, nombre, clave_hash, activo FROM superadmins WHERE usuario = ?');
        $consulta->execute([$usuario]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        // Se verifica siempre un hash (también si el usuario no existe): mismo tiempo de respuesta.
        $hash = is_array($fila) ? (string) $fila['clave_hash'] : '$2y$12$' . str_repeat('x', 53);
        if (!password_verify($clave, $hash) || !is_array($fila) || (int) $fila['activo'] !== 1) {
            return null;
        }
        $this->maestro->prepare('UPDATE superadmins SET ultimo_acceso = NOW() WHERE usuario = ?')->execute([$usuario]);
        return ['usuario' => (string) $fila['usuario'], 'nombre' => (string) $fila['nombre']];
    }

    /** Sigue activa (una cuenta desactivada pierde la sesión abierta en su siguiente petición). */
    public function activa(string $usuario): bool
    {
        $consulta = $this->maestro->prepare('SELECT activo FROM superadmins WHERE usuario = ?');
        $consulta->execute([$usuario]);
        return (int) $consulta->fetchColumn() === 1;
    }

    /** @return string la contraseña generada (se muestra una vez) */
    public function crear(string $usuario, string $nombre): string
    {
        if (preg_match('/^[a-z0-9._-]{3,60}$/', $usuario) !== 1) {
            throw new \InvalidArgumentException('Usuario: 3 a 60 minúsculas, dígitos, punto, guion o guion bajo.');
        }
        if (trim($nombre) === '') {
            throw new \InvalidArgumentException('El nombre es obligatorio.');
        }
        $clave = self::claveAleatoria();
        $this->maestro->prepare('INSERT INTO superadmins (usuario, nombre, clave_hash) VALUES (?, ?, ?)')
            ->execute([$usuario, trim($nombre), password_hash($clave, PASSWORD_DEFAULT, ['cost' => 12])]);
        return $clave;
    }

    public function desactivar(string $usuario): bool
    {
        $consulta = $this->maestro->prepare('UPDATE superadmins SET activo = 0 WHERE usuario = ?');
        $consulta->execute([$usuario]);
        return $consulta->rowCount() === 1;
    }

    private static function claveAleatoria(): string
    {
        $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $clave = '';
        for ($i = 0; $i < 20; $i++) {
            $clave .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $clave;
    }
}
