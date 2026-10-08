<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Alumno\FichaAlumno;
use PDO;
use PDOStatement;

/** Sobre los procedimientos existentes (migración 20261011000000 incluida). */
final class PdoAlumnoRepositorio implements AlumnoRepositorio
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(FichaAlumno $ficha, string $rutaFoto): bool
    {
        return (int) $this->escalar('SP_REGISTRAR_ALUMNOS', [...$this->alumno($ficha, $rutaFoto), ...$this->padres($ficha)]) === 1;
    }

    public function modificar(int $id, FichaAlumno $ficha, string $rutaFoto): bool
    {
        // El SP conserva el parámetro IDPA por compatibilidad, pero ubica a los padres por el alumno.
        $parametros = [$id, ...$this->alumno($ficha, $rutaFoto), 0, ...$this->padres($ficha)];
        return (int) $this->escalar('SP_MODIFICAR_ALUMNOS', $parametros) === 1;
    }

    public function fotoPorId(int $id): ?string
    {
        return $this->foto('Id_alumno', $id);
    }

    public function fotoPorDni(string $dni): ?string
    {
        return $this->foto('alum_dni', $dni);
    }

    public function eliminarPorDni(string $dni): bool
    {
        return (int) $this->escalar('SP_ELIMINAR_ALUMNO', [$dni]) === 1;
    }

    public function cambiarFotoPorDni(string $dni, string $rutaFoto): void
    {
        $this->llamar('SP_MODIFICAR_ESTUDIANTE_FOTO', [$dni, $rutaFoto])->closeCursor();
    }

    /** @return list<string> en el orden de SP_REGISTRAR_ALUMNOS */
    private function alumno(FichaAlumno $f, string $rutaFoto): array
    {
        return [$f->dni, $f->nombres, $f->apellidoPaterno, $f->apellidoMaterno, $f->sexo,
            $f->fechaNacimiento, $f->celular, $f->direccion, $rutaFoto];
    }

    /** @return list<string> */
    private function padres(FichaAlumno $f): array
    {
        $p = $f->padres;
        return [$p->dniPapa, $p->datosPapa, $p->celularPapa, $p->dniMama, $p->datosMama, $p->celularMama];
    }

    private function foto(string $columna, int|string $valor): ?string
    {
        $consulta = $this->pdo->prepare("SELECT alum_fotoperfil FROM alumnos WHERE $columna = ?");
        $consulta->execute([$valor]);
        $foto = $consulta->fetchColumn();
        $consulta->closeCursor();
        return $foto === false ? null : (string) $foto;
    }

    /** @param list<int|string> $parametros */
    private function llamar(string $procedimiento, array $parametros): PDOStatement
    {
        $marcas = implode(', ', array_fill(0, count($parametros), '?'));
        $consulta = $this->pdo->prepare("CALL $procedimiento($marcas)");
        $consulta->execute($parametros);
        return $consulta;
    }

    /** @param list<int|string> $parametros */
    private function escalar(string $procedimiento, array $parametros): mixed
    {
        $consulta = $this->llamar($procedimiento, $parametros);
        $valor = $consulta->fetchColumn();
        $consulta->closeCursor();
        return $valor;
    }
}
