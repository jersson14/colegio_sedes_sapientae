<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

/**
 * Hash y verificación de contraseñas, compatibles con las ya guardadas.
 *
 * El sistema heredado aplica htmlspecialchars() a la contraseña ANTES de hashearla y antes de
 * verificarla (p. ej. «a&b» se guarda como hash de «a&amp;b»). Las cuentas existentes dependen de
 * eso: hashear la contraseña tal cual dejaría fuera a quien tenga & < > " ' en su clave. Por eso
 * se conserva la normalización, pero en un único sitio.
 */
final class Contrasena
{
    private const COSTE = 12;

    public static function hash(string $plana): string
    {
        return password_hash(self::normalizar($plana), PASSWORD_DEFAULT, ['cost' => self::COSTE]);
    }

    public static function verificar(string $plana, string $hash): bool
    {
        return password_verify(self::normalizar($plana), $hash);
    }

    private static function normalizar(string $plana): string
    {
        return htmlspecialchars($plana, ENT_QUOTES, 'UTF-8');
    }
}
