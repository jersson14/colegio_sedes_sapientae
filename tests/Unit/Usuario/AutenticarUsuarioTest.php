<?php

declare(strict_types=1);

namespace Tests\Unit\Usuario;

use App\Domain\Usuario\Contrasena;
use App\Domain\Usuario\ResultadoLogin;
use App\Services\AutenticarUsuario;
use PHPUnit\Framework\TestCase;

final class AutenticarUsuarioTest extends TestCase
{
    private function servicio(array ...$cuentas): AutenticarUsuario
    {
        $porId = [];
        foreach ($cuentas as $i => $c) {
            $porId[$i + 1] = $c + ['usu_id' => $i + 1, 'usu_estatus' => 'ACTIVO'];
        }
        return new AutenticarUsuario(new UsuarioRepositorioEnMemoria($porId));
    }

    public function testCredencialesCorrectasDevuelvenLaCuenta(): void
    {
        $r = $this->servicio(['usu_usuario' => 'ANA', 'usu_contra' => Contrasena::hash('Clave.1')])->ejecutar('ana', 'Clave.1');
        self::assertSame(ResultadoLogin::Correcto, $r->resultado);
        self::assertSame('ANA', $r->cuenta['usu_usuario'] ?? null);
    }

    public function testContrasenaIncorrectaOUsuarioInexistente(): void
    {
        $s = $this->servicio(['usu_usuario' => 'ANA', 'usu_contra' => Contrasena::hash('Clave.1')]);
        self::assertSame(ResultadoLogin::Incorrecto, $s->ejecutar('ANA', 'otra')->resultado);
        self::assertSame(ResultadoLogin::Incorrecto, $s->ejecutar('NADIE', 'Clave.1')->resultado);
        self::assertNull($s->ejecutar('ANA', 'otra')->cuenta);
    }

    public function testCuentaInactivaNoEntraPeroSoloSiLaClaveEsCorrecta(): void
    {
        $s = $this->servicio(['usu_usuario' => 'ANA', 'usu_contra' => Contrasena::hash('Clave.1'), 'usu_estatus' => 'INACTIVO']);
        self::assertSame(ResultadoLogin::Inactivo, $s->ejecutar('ANA', 'Clave.1')->resultado);
        // Con la clave mal no se revela que la cuenta existe y está inactiva.
        self::assertSame(ResultadoLogin::Incorrecto, $s->ejecutar('ANA', 'otra')->resultado);
    }

    public function testConDatosAntiguosDuplicadosCuentaLaFilaCuyaClaveCoincide(): void
    {
        $s = $this->servicio(
            ['usu_usuario' => 'ANA', 'usu_contra' => Contrasena::hash('primera'), 'usu_estatus' => 'INACTIVO'],
            ['usu_usuario' => 'ANA', 'usu_contra' => Contrasena::hash('segunda')],
        );
        self::assertSame(ResultadoLogin::Correcto, $s->ejecutar('ANA', 'segunda')->resultado);
        self::assertSame(2, $s->ejecutar('ANA', 'segunda')->cuenta['usu_id'] ?? null);
    }
}
