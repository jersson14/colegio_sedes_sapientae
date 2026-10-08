<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Usuario\EstadoUsuario;
use App\Repositories\PdoUsuarioRepositorio;
use PDOException;

/** PdoUsuarioRepositorio contra los SP reales (migración 20261010000000 incluida). */
final class UsuarioRepositorioTest extends BaseDatosTestCase
{
    private PdoUsuarioRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->pdo->query('SELECT COUNT(*) FROM usuario WHERE usu_id = 11')->fetchColumn() === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->repo = new PdoUsuarioRepositorio($this->pdo);
    }

    private function columna(string $columna, int $id): mixed
    {
        return $this->pdo->query("SELECT $columna FROM usuario WHERE usu_id = $id")->fetchColumn();
    }

    public function testBuscaSinDistinguirMayusculasYTraeLosDatosDeLaSesion(): void
    {
        $filas = $this->repo->buscarPorUsuario('USUARIO10');
        self::assertCount(1, $filas);
        foreach (['usu_id', 'usu_contra', 'usu_estatus', 'tipo_rol', 'docente_nombre', 'Docente', 'docente_dni'] as $clave) {
            self::assertArrayHasKey($clave, $filas[0]);
        }
        self::assertSame([], $this->repo->buscarPorUsuario('no-existe'));
    }

    public function testModificaYGuardaNombresLargosSinTruncar(): void
    {
        $largo = str_repeat('N', 40);
        self::assertTrue($this->repo->modificar(11, $largo, 2, 'nuevo@example.com'));
        self::assertSame($largo, $this->columna('usu_usuario', 11));
        self::assertSame('nuevo@example.com', $this->columna('usu_email', 11));
    }

    public function testNoRenombraAlNombreDeOtraCuenta(): void
    {
        self::assertFalse($this->repo->modificar(11, 'USUARIO9', 2, 'x@example.com'));
        self::assertSame('usuario11', $this->columna('usu_usuario', 11));
        self::assertTrue($this->repo->modificar(11, 'usuario11', 2, 'x@example.com'), 'su propio nombre no es duplicado');
    }

    public function testCambiaContrasenaYEstado(): void
    {
        $this->repo->cambiarContrasena(11, 'hash-de-prueba');
        self::assertSame('hash-de-prueba', $this->columna('usu_contra', 11));
        $this->repo->cambiarEstado(11, EstadoUsuario::Inactivo);
        self::assertSame('INACTIVO', $this->columna('usu_estatus', 11));
    }

    public function testElProcedimientoRechazaUnEstadoInvalido(): void
    {
        // Defensa en la BD además de la del servicio: antes se guardaba ''.
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Estado de usuario no válido');
        $this->pdo->prepare('CALL SP_MODIFICAR_USUARIO_ESTATUS(?, ?)')->execute([11, 'CUALQUIERA']);
    }
}
