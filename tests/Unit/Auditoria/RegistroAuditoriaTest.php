<?php

declare(strict_types=1);

namespace Tests\Unit\Auditoria;

use App\Auditoria\RegistroAuditoria;
use PHPUnit\Framework\TestCase;

final class RegistroAuditoriaTest extends TestCase
{
    public function testNuncaGuardaContrasenasNiTokens(): void
    {
        $datos = RegistroAuditoria::datosSeguros(['usu' => 'ana', 'contra' => 'Secreta.1', 'txt_clave_nueva' => 'x', '_csrf' => 'abc', 'api_key' => 'k', 'token' => 't']);
        self::assertSame('{"usu":"ana","contra":"***","txt_clave_nueva":"***","_csrf":"***","api_key":"***","token":"***"}', $datos);
    }

    public function testRecortaValoresLargosYAnidados(): void
    {
        $datos = (string) RegistroAuditoria::datosSeguros(['obs' => str_repeat('á', 300), 'notas' => [['nota' => '15', 'pass' => 'x']]]);
        $json = json_decode(rtrim($datos, '…'), true);
        self::assertSame(201, mb_strlen($json['obs']), '200 caracteres y la elipsis');
        self::assertSame([['nota' => '15', 'pass' => '***']], $json['notas']);
    }

    public function testDeLosArchivosSoloElNombre(): void
    {
        $datos = RegistroAuditoria::datosSeguros([], ['foto' => ['name' => 'yo.png', 'tmp_name' => '/tmp/x'], 'archivos' => ['name' => ['a.pdf', 'b.pdf']]]);
        self::assertSame('{"archivo:foto":"yo.png","archivo:archivos":["a.pdf","b.pdf"]}', $datos);
        self::assertNull(RegistroAuditoria::datosSeguros([]));
    }

    public function testElTotalTieneTope(): void
    {
        $post = [];
        for ($i = 0; $i < 100; $i++) {
            $post["campo$i"] = str_repeat('x', 150);
        }
        self::assertSame(4001, mb_strlen((string) RegistroAuditoria::datosSeguros($post)));
    }

    public function testModuloYAccion(): void
    {
        self::assertSame(['alumnos', 'registrar_alumno'], RegistroAuditoria::moduloYAccion('C:\xampp\htdocs\app\controller\alumnos\controlador_registrar_alumno.php'));
        self::assertSame(['reportes', 'record_academico'], RegistroAuditoria::moduloYAccion('/var/www/view/MPDF/REPORTE/record_academico.php'));
        self::assertSame(['otros', 'x'], RegistroAuditoria::moduloYAccion('/var/www/x.php'));
    }

    public function testLasLecturasRutinariasNoSeAuditanSalvoSiSeDeniegan(): void
    {
        self::assertTrue(RegistroAuditoria::esLecturaRutinaria('listar_alumnos', 200));
        self::assertTrue(RegistroAuditoria::esLecturaRutinaria('traertipo', 200));
        self::assertFalse(RegistroAuditoria::esLecturaRutinaria('listar_alumnos', 403));
        self::assertFalse(RegistroAuditoria::esLecturaRutinaria('registrar_alumno', 200));
    }
}
