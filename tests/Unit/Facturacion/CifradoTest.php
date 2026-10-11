<?php

declare(strict_types=1);

namespace Tests\Unit\Facturacion;

use App\Support\Cifrado;
use PHPUnit\Framework\TestCase;

final class CifradoTest extends TestCase
{
    private static function clave(): string
    {
        return base64_encode(str_repeat('k', 32));
    }

    public function testIdaYVuelta(): void
    {
        $cifrado = Cifrado::desdeClave(self::clave());
        $guardado = $cifrado->cifrar('token-secreto');
        self::assertStringStartsWith('v1:', $guardado);
        self::assertStringNotContainsString('token-secreto', $guardado);
        self::assertNotSame($guardado, $cifrado->cifrar('token-secreto'), 'IV distinto en cada cifrado');
        self::assertSame('token-secreto', $cifrado->descifrar($guardado));
    }

    public function testDetectaLaAlteracion(): void
    {
        $cifrado = Cifrado::desdeClave(self::clave());
        $crudo = (string) base64_decode(substr($cifrado->cifrar('token-secreto'), 3), true);
        $alterado = 'v1:' . base64_encode(substr($crudo, 0, -1) . chr(ord(substr($crudo, -1)) ^ 1));
        $this->expectException(\RuntimeException::class);
        $cifrado->descifrar($alterado);
    }

    public function testOtraClaveNoDescifra(): void
    {
        $guardado = Cifrado::desdeClave(self::clave())->cifrar('token-secreto');
        $this->expectException(\RuntimeException::class);
        Cifrado::desdeClave(base64_encode(str_repeat('z', 32)))->descifrar($guardado);
    }

    public function testSinClaveNoArranca(): void
    {
        $this->expectException(\RuntimeException::class);
        Cifrado::desdeClave('corta');
    }
}
