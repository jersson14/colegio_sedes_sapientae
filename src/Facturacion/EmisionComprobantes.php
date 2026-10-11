<?php

declare(strict_types=1);

namespace App\Facturacion;

use PDO;

/**
 * Emisión de comprobantes de la institución con su cuenta de Nubefact: de un ingreso válido a su boleta o
 * factura, consulta de los que quedaron sin respuesta, reintento de los rechazados y anulación.
 */
final class EmisionComprobantes
{
    /** Desde este total, la boleta exige identificar al cliente (SUNAT). */
    public const TOPE_BOLETA_SIN_DOCUMENTO = 700.0;

    public const RESULTADOS_RESERVA = [
        2 => 'Ese ingreso ya tiene un comprobante.',
        0 => 'El ingreso no existe, está anulado o es de monto cero.',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly ClienteNubefact $cliente,
        private readonly ConfiguracionNubefact $cfg,
    ) {
    }

    /**
     * Los ingresos válidos sin comprobante, con el cliente sugerido (el apoderado si lo hay; si no, el alumno).
     *
     * @return list<array<string, mixed>>
     */
    public static function pendientes(PDO $pdo, int $limite = 300): array
    {
        $consulta = $pdo->query(
            "SELECT i.id_ingreso, i.created_at AS fecha, i.cantidad, i.monto,
                    COALESCE(CONCAT(pp.concepto, IF(pe.mes IS NULL, '', CONCAT(' ', pe.mes))), ind.nombre, 'INGRESO') AS concepto,
                    a.alum_dni, CONCAT_WS(' ', a.alum_nombre, a.alum_apepat, a.alum_apemat) AS alumno, a.alum_direccion,
                    pa.Dni_papa, pa.Datos_papa, pa.Dni_mama, pa.Datos_mama
               FROM ingresos i
               LEFT JOIN pago_pensiones pp ON pp.id_pago_pension = i.id_pago_pension
               LEFT JOIN pensiones pe ON pe.id_pensiones = pp.id_pension
               LEFT JOIN matricula m ON m.id_matricula = pp.id_matri
               LEFT JOIN alumnos a ON a.Id_alumno = m.id_alumno
               LEFT JOIN padres pa ON pa.id_papas = (SELECT MIN(x.id_papas) FROM padres x WHERE x.id_alu = a.Id_alumno)
               LEFT JOIN indicadores ind ON ind.id_indicadores = i.id_indicador
              WHERE i.estado = 'VALIDO' AND i.monto > 0
                AND NOT EXISTS (SELECT 1 FROM comprobantes_electronicos c WHERE c.id_ingreso = i.id_ingreso AND c.estado <> 'ANULADO')
              ORDER BY i.id_ingreso DESC LIMIT " . max(1, min($limite, 1000))
        );
        $filas = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $f) {
            [$doc, $nombre] = match (true) {
                preg_match('/^\d{8}$/', (string) $f['Dni_papa']) === 1 => [$f['Dni_papa'], $f['Datos_papa']],
                preg_match('/^\d{8}$/', (string) $f['Dni_mama']) === 1 => [$f['Dni_mama'], $f['Datos_mama']],
                preg_match('/^\d{8}$/', (string) $f['alum_dni']) === 1 => [$f['alum_dni'], $f['alumno']],
                default => ['', ''],
            };
            $filas[] = [
                'id_ingreso' => (int) $f['id_ingreso'],
                'fecha' => $f['fecha'],
                'monto' => $f['monto'],
                'descripcion' => self::descripcion((string) $f['concepto'], $f['alumno'] !== null && $f['alumno'] !== '' ? (string) $f['alumno'] : null),
                'cliente_doc' => (string) $doc,
                'cliente_nombre' => (string) $nombre,
                'cliente_direccion' => (string) ($f['alum_direccion'] ?? ''),
            ];
        }
        return $filas;
    }

    private static function descripcion(string $concepto, ?string $alumno): string
    {
        return mb_substr(mb_strtoupper(trim($concepto) . ($alumno !== null ? ' - ' . trim($alumno) : ''), 'UTF-8'), 0, 250);
    }

    /** @return list<array<string, mixed>> lo más reciente primero */
    public static function historial(PDO $pdo, int $limite = 300): array
    {
        return $pdo->query(
            'SELECT id, id_ingreso, tipo, serie, numero, cliente_tipo_doc, cliente_doc, cliente_nombre, descripcion, total, estado,
                    enlace, enlace_pdf, enlace_xml, sunat_descripcion, error, motivo_anulacion, created_at
               FROM comprobantes_electronicos ORDER BY id DESC LIMIT ' . max(1, min($limite, 1000))
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valida los datos del cliente según el tipo: factura con RUC (11 dígitos) y dirección; boleta con DNI
     * (8), RUC, carné de extranjería (4) o sin documento («-», solo por debajo del tope de la SUNAT).
     *
     * @param array<string, string> $c tipo_doc, doc, nombre, direccion, email
     * @return array{tipo_doc: string, doc: string, nombre: string, direccion: string, email: string}
     * @throws \InvalidArgumentException
     */
    public static function validarCliente(int $tipo, array $c, float $total): array
    {
        $tipoDoc = trim($c['tipo_doc'] ?? '');
        $doc = trim($c['doc'] ?? '');
        $nombre = mb_strtoupper(trim($c['nombre'] ?? ''), 'UTF-8');
        $direccion = trim($c['direccion'] ?? '');
        $email = trim($c['email'] ?? '');
        if ($tipo === 1 && ($tipoDoc !== '6' || preg_match('/^(10|15|17|20)\d{9}$/', $doc) !== 1)) {
            throw new \InvalidArgumentException('La factura exige el RUC del cliente (11 dígitos).');
        }
        if ($tipo === 1 && $direccion === '') {
            throw new \InvalidArgumentException('La factura exige la dirección del cliente.');
        }
        $formatos = ['1' => '/^\d{8}$/', '6' => '/^(10|15|17|20)\d{9}$/', '4' => '/^[A-Z0-9]{8,12}$/i', '-' => '/^-?$/'];
        if (!isset($formatos[$tipoDoc]) || preg_match($formatos[$tipoDoc], $doc) !== 1) {
            throw new \InvalidArgumentException('El documento del cliente no es válido (DNI de 8 dígitos, RUC de 11 o carné de extranjería).');
        }
        if ($tipoDoc === '-') {
            if ($total >= self::TOPE_BOLETA_SIN_DOCUMENTO) {
                throw new \InvalidArgumentException('Desde S/ 700 la boleta exige el documento del cliente.');
            }
            $doc = '-';
            $nombre = $nombre !== '' ? $nombre : 'CLIENTES VARIOS';
        }
        if ($nombre === '' || mb_strlen($nombre) > 200) {
            throw new \InvalidArgumentException('Falta el nombre o la razón social del cliente.');
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('El correo del cliente no es válido.');
        }
        return ['tipo_doc' => $tipoDoc, 'doc' => $doc, 'nombre' => $nombre, 'direccion' => mb_substr($direccion, 0, 250), 'email' => mb_substr($email, 0, 250)];
    }

    /**
     * Reserva el número y lo envía a Nubefact.
     *
     * @param array<string, string> $cliente
     * @return array{codigo: int, mensaje: string, comprobante: ?array<string, mixed>}
     * @throws \InvalidArgumentException si los datos del cliente no son válidos
     */
    public function emitir(int $idIngreso, int $tipo, array $cliente, ?int $usuId): array
    {
        if ($tipo !== 1 && $tipo !== 2) {
            throw new \InvalidArgumentException('Tipo de comprobante inválido.');
        }
        $ingreso = array_values(array_filter(self::pendientes($this->pdo, 1000), static fn (array $p): bool => $p['id_ingreso'] === $idIngreso))[0] ?? null;
        if ($ingreso === null) {
            return ['codigo' => 0, 'mensaje' => self::RESULTADOS_RESERVA[0] . ' O ya tiene comprobante.', 'comprobante' => null];
        }
        $c = self::validarCliente($tipo, $cliente, (float) $ingreso['monto']);
        $reserva = $this->pdo->prepare('CALL SP_RESERVAR_COMPROBANTE(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $reserva->execute([$idIngreso, $tipo, $tipo === 1 ? $this->cfg->serieFactura : $this->cfg->serieBoleta,
            $c['tipo_doc'], $c['doc'], $c['nombre'], $c['direccion'], $c['email'], $ingreso['descripcion'], $usuId]);
        $fila = $reserva->fetch(PDO::FETCH_ASSOC);
        $reserva->closeCursor();
        if (!is_array($fila) || (int) $fila['codigo'] !== 1) {
            $codigo = is_array($fila) ? (int) $fila['codigo'] : 0;
            return ['codigo' => $codigo, 'mensaje' => self::RESULTADOS_RESERVA[$codigo] ?? 'No se pudo reservar el número.', 'comprobante' => null];
        }
        return $this->enviar((int) $fila['id']);
    }

    /**
     * Uno rechazado (ERROR), con los datos del cliente corregidos, se reenvía con el mismo número.
     *
     * @param array<string, string> $cliente
     * @return array{codigo: int, mensaje: string, comprobante: ?array<string, mixed>}
     */
    public function reintentar(int $id, array $cliente): array
    {
        $actual = $this->buscar($id);
        if ($actual === null || $actual['estado'] !== 'ERROR') {
            return ['codigo' => 0, 'mensaje' => 'Solo se reintenta un comprobante rechazado.', 'comprobante' => $actual];
        }
        $c = self::validarCliente((int) $actual['tipo'], $cliente, (float) $actual['total']);
        $corregir = $this->pdo->prepare('CALL SP_CORREGIR_COMPROBANTE(?, ?, ?, ?, ?, ?)');
        $corregir->execute([$id, $c['tipo_doc'], $c['doc'], $c['nombre'], $c['direccion'], $c['email']]);
        $corregir->closeCursor();
        return $this->enviar($id);
    }

    /**
     * Pregunta a Nubefact por uno sin respuesta (PENDIENTE) o aún no aceptado por la SUNAT (EMITIDO).
     *
     * @return array{codigo: int, mensaje: string, comprobante: ?array<string, mixed>}
     */
    public function consultar(int $id): array
    {
        $c = $this->buscar($id);
        if ($c === null || !in_array($c['estado'], ['PENDIENTE', 'EMITIDO'], true)) {
            return ['codigo' => 0, 'mensaje' => 'Solo se consulta un comprobante pendiente o emitido.', 'comprobante' => $c];
        }
        try {
            $respuesta = $this->cliente->enviar($this->cfg->ruta, $this->cfg->token, DocumentoNubefact::consulta((int) $c['tipo'], (string) $c['serie'], (int) $c['numero']));
        } catch (FalloConexionNubefact $e) {
            return ['codigo' => 0, 'mensaje' => $e->getMessage(), 'comprobante' => $c];
        }
        // Si Nubefact no lo tiene (nunca llegó), se reenvía con el mismo número.
        if (isset($respuesta['errors']) && $c['estado'] === 'PENDIENTE') {
            return $this->enviar($id);
        }
        $this->guardarResultado($id, DocumentoNubefact::resultado($respuesta));
        return ['codigo' => 1, 'mensaje' => 'Consultado.', 'comprobante' => $this->buscar($id)];
    }

    /** @return array{codigo: int, mensaje: string, comprobante: ?array<string, mixed>} */
    public function anular(int $id, string $motivo): array
    {
        $motivo = trim($motivo);
        $c = $this->buscar($id);
        if ($c === null || !in_array($c['estado'], ['EMITIDO', 'ACEPTADO'], true)) {
            return ['codigo' => 0, 'mensaje' => 'Solo se anula un comprobante emitido.', 'comprobante' => $c];
        }
        if (mb_strlen($motivo) < 5) {
            return ['codigo' => 0, 'mensaje' => 'Indica el motivo de la anulación.', 'comprobante' => $c];
        }
        try {
            $respuesta = $this->cliente->enviar(
                $this->cfg->ruta,
                $this->cfg->token,
                DocumentoNubefact::anulacion((int) $c['tipo'], (string) $c['serie'], (int) $c['numero'], mb_strtoupper($motivo, 'UTF-8'))
            );
        } catch (FalloConexionNubefact $e) {
            return ['codigo' => 0, 'mensaje' => $e->getMessage(), 'comprobante' => $c];
        }
        if (isset($respuesta['errors'])) {
            return ['codigo' => 0, 'mensaje' => 'Nubefact no aceptó la anulación: ' . DocumentoNubefact::resultado($respuesta)['error'], 'comprobante' => $c];
        }
        $anular = $this->pdo->prepare('CALL SP_ANULAR_COMPROBANTE(?, ?)');
        $anular->execute([$id, mb_substr(mb_strtoupper($motivo, 'UTF-8'), 0, 100)]);
        $anular->closeCursor();
        return ['codigo' => 1, 'mensaje' => 'Comprobante anulado.', 'comprobante' => $this->buscar($id)];
    }

    /** @return array{codigo: int, mensaje: string, comprobante: ?array<string, mixed>} */
    private function enviar(int $id): array
    {
        $c = $this->buscar($id);
        if ($c === null) {
            return ['codigo' => 0, 'mensaje' => 'No existe el comprobante.', 'comprobante' => null];
        }
        $cantidad = (int) $this->pdo->query('SELECT cantidad FROM ingresos WHERE id_ingreso = ' . (int) $c['id_ingreso'])->fetchColumn();
        /** @var array{tipo: int|string, serie: string, numero: int|string, cliente_tipo_doc: string, cliente_doc: string, cliente_nombre: string, cliente_direccion: string, cliente_email: string, descripcion: string, total: float|string} $c */
        $documento = DocumentoNubefact::comprobante($c, $this->cfg, $cantidad, new \DateTimeImmutable('today'));
        try {
            $respuesta = $this->cliente->enviar($this->cfg->ruta, $this->cfg->token, $documento);
        } catch (FalloConexionNubefact $e) {
            return ['codigo' => 3, 'mensaje' => $e->getMessage() . ' El comprobante quedó pendiente: consúltalo en unos minutos.', 'comprobante' => $c];
        }
        $resultado = DocumentoNubefact::resultado($respuesta);
        $this->guardarResultado($id, $resultado);
        $numero = "{$c['serie']}-{$c['numero']}";
        return $resultado['estado'] === 'ERROR'
            ? ['codigo' => 2, 'mensaje' => "Nubefact rechazó el $numero: {$resultado['error']}", 'comprobante' => $this->buscar($id)]
            : ['codigo' => 1, 'mensaje' => "Comprobante $numero emitido" . ($resultado['estado'] === 'ACEPTADO' ? ' y aceptado por la SUNAT.' : '.'),
                'comprobante' => $this->buscar($id)];
    }

    /** @param array{estado: string, enlace: ?string, pdf: ?string, xml: ?string, hash: ?string, sunat: ?string, error: ?string} $r */
    private function guardarResultado(int $id, array $r): void
    {
        $guardar = $this->pdo->prepare('CALL SP_RESULTADO_COMPROBANTE(?, ?, ?, ?, ?, ?, ?, ?)');
        $guardar->execute([$id, $r['estado'], $r['enlace'], $r['pdf'], $r['xml'], $r['hash'], $r['sunat'], $r['error']]);
        $guardar->closeCursor();
    }

    /** @return ?array<string, mixed> */
    private function buscar(int $id): ?array
    {
        $consulta = $this->pdo->prepare('SELECT * FROM comprobantes_electronicos WHERE id = ?');
        $consulta->execute([$id]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        return is_array($fila) ? $fila : null;
    }
}
