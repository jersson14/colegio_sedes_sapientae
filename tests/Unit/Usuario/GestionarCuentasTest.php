<?php

declare(strict_types=1);

namespace Tests\Unit\Usuario;

use App\Domain\Usuario\Contrasena;
use App\Services\GestionarCuentas;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GestionarCuentasTest extends TestCase
{
    private UsuarioRepositorioEnMemoria $repo;
    private GestionarCuentas $cuentas;

    protected function setUp(): void
    {
        $this->repo = new UsuarioRepositorioEnMemoria([
            1 => ['usu_usuario' => 'ANA', 'rol_id' => 2, 'usu_email' => 'a@x.pe', 'usu_estatus' => 'ACTIVO'],
            2 => ['usu_usuario' => 'LUIS', 'rol_id' => 2, 'usu_email' => 'l@x.pe', 'usu_estatus' => 'ACTIVO'],
        ]);
        $this->cuentas = new GestionarCuentas($this->repo);
    }

    public function testModificaLaCuenta(): void
    {
        self::assertTrue($this->cuentas->modificar(1, '  ANA.NUEVA ', 9, 'n@x.pe '));
        self::assertSame('ANA.NUEVA', $this->repo->cuentas[1]['usu_usuario']);
        self::assertSame(9, $this->repo->cuentas[1]['rol_id']);
        self::assertSame('n@x.pe', $this->repo->cuentas[1]['usu_email']);
    }

    public function testNoRenombraAlNombreDeOtraCuenta(): void
    {
        self::assertFalse($this->cuentas->modificar(1, 'luis', 2, 'a@x.pe'));
        self::assertSame('ANA', $this->repo->cuentas[1]['usu_usuario']);
        // Conservar su propio nombre no es un duplicado.
        self::assertTrue($this->cuentas->modificar(1, 'ANA', 2, 'a@x.pe'));
    }

    /** @return iterable<string, array{int, string, int}> */
    public static function datosInvalidos(): iterable
    {
        yield 'id cero' => [0, 'ANA', 2];
        yield 'usuario vacío' => [1, '   ', 2];
        yield 'rol cero' => [1, 'ANA', 0];
        yield 'usuario de 251' => [1, str_repeat('a', 251), 2];
    }

    #[DataProvider('datosInvalidos')]
    public function testRechazaDatosInvalidos(int $id, string $usuario, int $rol): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cuentas->modificar($id, $usuario, $rol, 'a@x.pe');
    }

    public function testAdmiteUsuariosDeHasta250Caracteres(): void
    {
        self::assertTrue($this->cuentas->modificar(1, str_repeat('a', 250), 2, 'a@x.pe'));
    }

    public function testCambiaLaContrasenaGuardandoSoloElHash(): void
    {
        $this->cuentas->cambiarContrasena(1, 'Nueva&Clave');
        $hash = (string) $this->repo->cuentas[1]['usu_contra'];
        self::assertStringNotContainsString('Nueva', $hash);
        self::assertTrue(Contrasena::verificar('Nueva&Clave', $hash));
    }

    public function testNoAceptaUnaContrasenaVacia(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cuentas->cambiarContrasena(1, '');
    }

    public function testCambiaElEstado(): void
    {
        $this->cuentas->cambiarEstado(1, 'inactivo');
        self::assertSame('INACTIVO', $this->repo->cuentas[1]['usu_estatus']);
        $this->cuentas->cambiarEstado(1, 'ACTIVO');
        self::assertSame('ACTIVO', $this->repo->cuentas[1]['usu_estatus']);
    }

    public function testRechazaUnEstadoInvalidoSinTocarLaCuenta(): void
    {
        try {
            $this->cuentas->cambiarEstado(1, 'CUALQUIERA');
            self::fail('Se esperaba InvalidArgumentException');
        } catch (InvalidArgumentException) {
            self::assertSame('ACTIVO', $this->repo->cuentas[1]['usu_estatus']);
        }
    }
}
