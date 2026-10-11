<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Auditoria\RegistroAuditoria;

/** Auditoría de la institución (H-15, migración 20261101000000). */
final class AuditoriaTest extends BaseDatosTestCase
{
    public function testRegistraYBuscaConFiltros(): void
    {
        $auditoria = new RegistroAuditoria($this->pdo);
        $auditoria->registrar(9, 'usuario9', 'ADMINISTRADOR', 'alumnos', 'registrar_alumno', 200, '127.0.0.1', '{"dni":"70000001"}');
        $auditoria->registrar(10, 'usuario10', 'DOCENTE', 'notas', 'registro_notas', 403, '127.0.0.1', null);

        $todo = $auditoria->buscar([]);
        self::assertSame(['registro_notas', 'registrar_alumno'], array_slice(array_column($todo, 'accion'), 0, 2), 'lo más reciente primero');
        self::assertSame(['registrar_alumno'], array_column($auditoria->buscar(['usuario' => 'usuario9', 'modulo' => 'alumnos']), 'accion'));
        self::assertSame([], $auditoria->buscar(['usuario' => '%']), 'el comodín se busca como texto');
        self::assertSame([], $auditoria->buscar(['hasta' => '2000-01-01']));
        self::assertContains('notas', $auditoria->modulos());
    }

    public function testPurgaSoloLoAntiguoYNuncaMenosDeUnMes(): void
    {
        $auditoria = new RegistroAuditoria($this->pdo);
        $this->pdo->exec('DELETE FROM auditoria_acciones');
        $auditoria->registrar(9, 'usuario9', 'ADMINISTRADOR', 'alumnos', 'registrar_alumno', 200, '', null);
        $this->pdo->exec("INSERT INTO auditoria_acciones (fecha, usuario, modulo, accion, estado) VALUES (NOW() - INTERVAL 800 DAY, 'antiguo', 'alumnos', 'eliminar_alumnos', 200)");

        self::assertSame(0, $auditoria->purgar(5), 'menos de 30 días no borra nada');
        self::assertSame(1, $auditoria->purgar(730));
        self::assertSame(['usuario9'], array_column($auditoria->buscar([]), 'usuario'));
    }
}
