<?php

declare(strict_types=1);

namespace Tests\Unit\Usuario;

use App\Domain\Usuario\Contrasena;
use PHPUnit\Framework\TestCase;

final class ContrasenaTest extends TestCase
{
    public function testVerificaLaContrasenaHasheada(): void
    {
        $hash = Contrasena::hash('Clave.Segura1');
        self::assertTrue(Contrasena::verificar('Clave.Segura1', $hash));
        self::assertFalse(Contrasena::verificar('clave.segura1', $hash));
    }

    public function testEsCompatibleConLosHashesDelSistemaHeredado(): void
    {
        // Así hasheaba el controlador heredado: htmlspecialchars antes de password_hash.
        $heredado = password_hash(htmlspecialchars('a&b<"c\'>', ENT_QUOTES, 'UTF-8'), PASSWORD_DEFAULT);
        self::assertTrue(Contrasena::verificar('a&b<"c\'>', $heredado));
        // Y lo que hashea ahora lo verifica el código heredado del mismo modo.
        self::assertTrue(password_verify(htmlspecialchars('a&b<"c\'>', ENT_QUOTES, 'UTF-8'), Contrasena::hash('a&b<"c\'>')));
    }

    public function testUsaCosteDoce(): void
    {
        self::assertSame(12, password_get_info(Contrasena::hash('x'))['options']['cost'] ?? null);
    }
}
