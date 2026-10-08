<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Asistencia\Asistencia;
use App\Repositories\PdoAsistenciaRepositorio;

/** PdoAsistenciaRepositorio contra los SP reales (migración 20261014000000 incluida). */
final class AsistenciaRepositorioTest extends BaseDatosTestCase
{
    private PdoAsistenciaRepositorio $repo;

    protected function setUp(): void
    {
        parent::setUp();
        if ((int) $this->pdo->query('SELECT COUNT(*) FROM matricula WHERE id_matricula = 40')->fetchColumn() === 0) {
            self::markTestSkipped('Requiere los datos de prueba (DatosPrueba).');
        }
        $this->pdo->exec('SET SESSION FOREIGN_KEY_CHECKS = 1');
        $this->pdo->exec("SET SESSION timestamp = UNIX_TIMESTAMP('2025-12-26 12:00:00')");
        $this->repo = new PdoAsistenciaRepositorio($this->pdo);
    }

    /** @return list<array<string, mixed>> */
    private function delDia(string $fecha): array
    {
        $q = $this->pdo->prepare('SELECT * FROM asistencia WHERE id_matricula = 40 AND fecha = ?');
        $q->execute([$fecha]);
        return $q->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function testUnDiaPasadoNoSeDuplicaYGuardaSuMes(): void
    {
        $dia = Asistencia::nueva(['id_matri' => 40, 'fecha' => '2025-11-03', 'esta' => 'PRESENTE', 'obse' => '']);
        self::assertSame(0, $this->repo->registrar([$dia]));
        self::assertSame(1, $this->repo->registrar([$dia]), 'ya existía');
        $filas = $this->delDia('2025-11-03');
        self::assertCount(1, $filas);
        self::assertSame('11', (string) $filas[0]['mes'], 'el mes de la asistencia, no el del registro (diciembre)');
    }

    public function testUnErrorAMitadNoDejaElAulaAMedias(): void
    {
        // Conexión propia, sin la transacción de la prueba: así la del repositorio es la que manda.
        $propia = new \PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s', getenv('DB_HOST') ?: 'localhost', getenv('DB_PORT') ?: '3306', getenv('DB_NAME')),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        );
        $repo = new PdoAsistenciaRepositorio($propia);
        try {
            $repo->registrar([
                Asistencia::nueva(['id_matri' => 40, 'fecha' => '2025-11-04', 'esta' => 'PRESENTE']),
                Asistencia::nueva(['id_matri' => 999999, 'fecha' => '2025-11-04', 'esta' => 'PRESENTE']),
            ]);
            self::fail('Se esperaba un error de clave foránea');
        } catch (\PDOException) {
            $quedo = (int) $propia->query("SELECT COUNT(*) FROM asistencia WHERE id_matricula = 40 AND fecha = '2025-11-04'")->fetchColumn();
            $propia->exec("DELETE FROM asistencia WHERE id_matricula = 40 AND fecha = '2025-11-04'"); // por si falla
            self::assertSame(0, $quedo, 'la primera se deshizo junto con la que falló');
        }
    }

    public function testEditarNoCambiaLaFechaYReportaLaInexistente(): void
    {
        $this->repo->registrar([Asistencia::nueva(['id_matri' => 40, 'fecha' => '2025-11-05', 'esta' => 'AUSENTE'])]);
        $id = (int) $this->delDia('2025-11-05')[0]['id_asistencia'];
        self::assertTrue($this->repo->editar(Asistencia::edicion(['id_asis' => $id, 'fecha' => '2025-11-06', 'esta' => 'JUSTIFICADO', 'obse' => 'Cita'])));
        $fila = $this->delDia('2025-11-05');
        self::assertSame('JUSTIFICADO', $fila[0]['estado'] ?? null);
        self::assertSame([], $this->delDia('2025-11-06'));
        self::assertFalse($this->repo->editar(Asistencia::edicion(['id_asis' => 99999999, 'esta' => 'PRESENTE'])));
    }

    public function testEliminaElDiaDelAula(): void
    {
        $this->repo->registrar([Asistencia::nueva(['id_matri' => 40, 'fecha' => '2025-11-07', 'esta' => 'PRESENTE'])]);
        $this->repo->eliminarDelDia('2025-11-07', 5);
        self::assertSame([], $this->delDia('2025-11-07'));
    }
}
