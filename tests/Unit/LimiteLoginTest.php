<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../core/limite_login.php';

/**
 * Límite de intentos de login y de formularios públicos (H-07, H-14).
 * El estado se guarda en LOGIN_LIMITE_DIR (directorio temporal de tests/bootstrap.php).
 */
final class LimiteLoginTest extends TestCase
{
    protected function setUp(): void
    {
        foreach (glob(limite_dir() . '/*.json') ?: [] as $f) {
            unlink($f);
        }
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
    }

    public function testSinFallosNoHayBloqueo(): void
    {
        self::assertSame(0, limite_bloqueo_restante('ana'));
    }

    public function testCuatroFallosTodaviaPermitenIntentar(): void
    {
        for ($i = 0; $i < LIMITE_FALLOS_USUARIO - 1; $i++) {
            limite_registrar_fallo('ana');
        }
        self::assertSame(0, limite_bloqueo_restante('ana'));
    }

    public function testElQuintoFalloBloqueaQuinceMinutos(): void
    {
        for ($i = 0; $i < LIMITE_FALLOS_USUARIO; $i++) {
            limite_registrar_fallo('ana');
        }
        $restante = limite_bloqueo_restante('ana');
        self::assertGreaterThan(LIMITE_BLOQUEO_BASE - 5, $restante);
        self::assertLessThanOrEqual(LIMITE_BLOQUEO_BASE, $restante);
    }

    public function testElUsuarioNoDistingueMayusculas(): void
    {
        for ($i = 0; $i < LIMITE_FALLOS_USUARIO; $i++) {
            limite_registrar_fallo($i % 2 ? 'ANA' : 'ana');
        }
        self::assertGreaterThan(0, limite_bloqueo_restante('Ana'));
    }

    public function testOtroUsuarioDeLaMismaIpNoQuedaBloqueado(): void
    {
        for ($i = 0; $i < LIMITE_FALLOS_USUARIO; $i++) {
            limite_registrar_fallo('ana');
        }
        self::assertSame(0, limite_bloqueo_restante('beto'));
    }

    public function testElMismoUsuarioDesdeOtraIpNoQuedaBloqueado(): void
    {
        for ($i = 0; $i < LIMITE_FALLOS_USUARIO; $i++) {
            limite_registrar_fallo('ana');
        }
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        self::assertSame(0, limite_bloqueo_restante('ana'));
    }

    public function testLaReincidenciaDuplicaElBloqueo(): void
    {
        for ($ronda = 0; $ronda < 2; $ronda++) {
            for ($i = 0; $i < LIMITE_FALLOS_USUARIO; $i++) {
                limite_registrar_fallo('ana');
            }
        }
        self::assertGreaterThan(LIMITE_BLOQUEO_BASE, limite_bloqueo_restante('ana'));
    }

    public function testUnLoginCorrectoReiniciaElContador(): void
    {
        for ($i = 0; $i < LIMITE_FALLOS_USUARIO - 1; $i++) {
            limite_registrar_fallo('ana');
        }
        limite_registrar_exito('ana');
        limite_registrar_fallo('ana');
        self::assertSame(0, limite_bloqueo_restante('ana'));
    }

    public function testMuchosUsuariosDesdeUnaIpBloqueanLaIp(): void
    {
        for ($i = 0; $i < LIMITE_FALLOS_IP; $i++) {
            limite_registrar_fallo('usuario' . $i);
        }
        self::assertGreaterThan(0, limite_bloqueo_restante('cualquiera'));
    }

    public function testLimitePublicoPermiteHastaElMaximo(): void
    {
        for ($i = 0; $i < 5; $i++) {
            self::assertTrue(limite_publico('solicitud', 5, 3600), "envío $i");
        }
        self::assertFalse(limite_publico('solicitud', 5, 3600));
    }

    public function testLimitePublicoSeparaAcciones(): void
    {
        for ($i = 0; $i < 5; $i++) {
            limite_publico('solicitud', 5, 3600);
        }
        self::assertTrue(limite_publico('otra_accion', 5, 3600));
    }
}
