<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Institucion\PdoConfiguracionRepositorio;
use App\Institucion\TipoInstitucion;

/** SP_GUARDAR_CONFIGURACION y la tabla configuracion (migración 20261028000000, Fase 5.1). */
final class ConfiguracionInstitucionTest extends BaseDatosTestCase
{
    public function testGuardaLeeYVuelveAlValorPorDefecto(): void
    {
        $this->pdo->exec('DELETE FROM configuracion');
        $repo = new PdoConfiguracionRepositorio($this->pdo);

        $repo->guardar(['institucion.tipo' => 'instituto', 'evaluacion.nota_minima' => '12']);
        $c = $repo->cargar();
        self::assertSame(TipoInstitucion::Instituto, $c->tipo());
        self::assertSame(12, $c->notaMinima());
        self::assertSame('SEMESTRE', $c->tipoPeriodo(), 'lo no guardado sigue el tipo');

        $repo->guardar(['evaluacion.nota_minima' => '']);
        self::assertSame(13, $repo->cargar()->notaMinima(), 'vacío = valor por defecto del instituto');
        self::assertSame(['institucion.tipo'], $this->pdo->query('SELECT clave FROM configuracion')->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function testUnValorInvalidoNoGuardaNada(): void
    {
        $this->pdo->exec('DELETE FROM configuracion');
        $repo = new PdoConfiguracionRepositorio($this->pdo);
        try {
            $repo->guardar(['periodo.tipo' => 'SEMESTRE', 'evaluacion.nota_minima' => '99']);
            self::fail('debía rechazarse');
        } catch (\InvalidArgumentException) {
            self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM configuracion')->fetchColumn(), 'ni siquiera lo válido del mismo envío');
        }
    }
}
