<?php
/**
 * Modelo para gestionar las solicitudes de información
 * desde la landing page del colegio
 */
require_once 'model_conexion.php';

class Solicitud_Model extends conexionBD {
    private $conexion;

    public function __construct() {
        $this->conexion = parent::conexionPDO();
    }

    /**
     * Registrar una nueva solicitud de información
     * @param array $datos - Datos del formulario
     * @return array - Resultado de la operación
     */
    public function Registrar_Solicitud($datos) {
        try {
            $sql = "INSERT INTO solicitudes_informacion 
                    (nombre_completo, email, telefono, nivel_interes, mensaje, ip_registro) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conexion->prepare($sql);
            
            $nombre = trim($datos['nombre']);
            $email = trim(strtolower($datos['email']));
            $telefono = trim($datos['telefono']);
            $nivel = $datos['nivel'];
            $mensaje = trim($datos['mensaje']);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            $stmt->bindParam(1, $nombre);
            $stmt->bindParam(2, $email);
            $stmt->bindParam(3, $telefono);
            $stmt->bindParam(4, $nivel);
            $stmt->bindParam(5, $mensaje);
            $stmt->bindParam(6, $ip);
            
            if ($stmt->execute()) {
                $id_solicitud = $this->conexion->lastInsertId();
                
                return array(
                    'status' => 'success',
                    'message' => 'Solicitud registrada exitosamente',
                    'id_solicitud' => $id_solicitud
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Error al registrar la solicitud'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Error del sistema: ' . $e->getMessage()
            );
        }
    }

    /**
     * Listar todas las solicitudes
     * @param string $estado - Filtrar por estado (opcional)
     * @return array - Lista de solicitudes
     */
    public function Listar_Solicitudes($estado = null) {
        try {
            $sql = "SELECT 
                        id_solicitud,
                        nombre_completo,
                        email,
                        telefono,
                        nivel_interes,
                        mensaje,
                        DATE_FORMAT(fecha_registro, '%d-%m-%Y %H:%i') as fecha_formateada,
                        estado,
                        observaciones
                    FROM solicitudes_informacion";
            
            if ($estado) {
                $sql .= " WHERE estado = ?";
            }
            
            $sql .= " ORDER BY fecha_registro DESC";
            
            if ($estado) {
                $stmt = $this->conexion->prepare($sql);
                $stmt->bindParam(1, $estado);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conexion->query($sql);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $result;
            
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Obtener una solicitud por ID
     * @param int $id - ID de la solicitud
     * @return array|null - Datos de la solicitud
     */
    public function Obtener_Solicitud($id) {
        try {
            $sql = "SELECT * FROM solicitudes_informacion WHERE id_solicitud = ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Actualizar el estado de una solicitud
     * @param int $id - ID de la solicitud
     * @param string $estado - Nuevo estado
     * @param string $observaciones - Observaciones (opcional)
     * @param int $usuario_id - ID del usuario que atiende
     * @return array - Resultado de la operación
     */
    public function Actualizar_Estado($id, $estado, $observaciones = null, $usuario_id = null) {
        try {
            $sql = "UPDATE solicitudes_informacion 
                    SET estado = ?, 
                        observaciones = ?,
                        fecha_atencion = NOW(),
                        atendido_por = ?
                    WHERE id_solicitud = ?";
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(1, $estado);
            $stmt->bindParam(2, $observaciones);
            $stmt->bindParam(3, $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(4, $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return array(
                    'status' => 'success',
                    'message' => 'Estado actualizado correctamente'
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'Error al actualizar el estado'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Error del sistema: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtener estadísticas de solicitudes
     * @return array - Estadísticas
     */
    public function Obtener_Estadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estado = 'CONTACTADO' THEN 1 ELSE 0 END) as contactados,
                        SUM(CASE WHEN estado = 'ATENDIDO' THEN 1 ELSE 0 END) as atendidos,
                        SUM(CASE WHEN nivel_interes = 'inicial' THEN 1 ELSE 0 END) as inicial,
                        SUM(CASE WHEN nivel_interes = 'primaria' THEN 1 ELSE 0 END) as primaria,
                        SUM(CASE WHEN nivel_interes = 'secundaria' THEN 1 ELSE 0 END) as secundaria,
                        SUM(CASE WHEN DATE(fecha_registro) = CURDATE() THEN 1 ELSE 0 END) as hoy,
                        SUM(CASE WHEN YEARWEEK(fecha_registro) = YEARWEEK(NOW()) THEN 1 ELSE 0 END) as esta_semana,
                        SUM(CASE WHEN MONTH(fecha_registro) = MONTH(NOW()) THEN 1 ELSE 0 END) as este_mes
                    FROM solicitudes_informacion";
            
            $stmt = $this->conexion->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Validar email único (evitar spam)
     * @param string $email - Email a validar
     * @return bool - True si ya existe una solicitud reciente
     */
    public function Email_Reciente($email) {
        try {
            // Verificar si hay una solicitud del mismo email en las últimas 24 horas
            $sql = "SELECT COUNT(*) as total 
                    FROM solicitudes_informacion 
                    WHERE email = ? 
                    AND fecha_registro > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
            $stmt = $this->conexion->prepare($sql);
            $email_lower = strtolower(trim($email));
            $stmt->bindParam(1, $email_lower);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Enviar notificación por email (opcional)
     * @param array $datos - Datos de la solicitud
     * @return bool - True si se envió correctamente
     */
    public function Enviar_Notificacion_Email($datos) {
        // Configurar el email del colegio
        $email_destino = "informes@sedessapientiae.edu.pe"; // Cambiar por el email real
        $asunto = "Nueva Solicitud de Información - Landing Page";
        
        $nivel_texto = array(
            'inicial' => 'Educación Inicial',
            'primaria' => 'Educación Primaria',
            'secundaria' => 'Educación Secundaria'
        );
        
        $mensaje = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1e40af; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #333; }
                .value { color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nueva Solicitud de Información</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <span class='label'>Nombre:</span>
                        <span class='value'>{$datos['nombre']}</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Email:</span>
                        <span class='value'>{$datos['email']}</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Teléfono:</span>
                        <span class='value'>{$datos['telefono']}</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Nivel de Interés:</span>
                        <span class='value'>{$nivel_texto[$datos['nivel']]}</span>
                    </div>
                    <div class='field'>
                        <span class='label'>Mensaje:</span>
                        <p class='value'>{$datos['mensaje']}</p>
                    </div>
                    <div class='field'>
                        <span class='label'>Fecha:</span>
                        <span class='value'>" . date('d-m-Y H:i') . "</span>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Landing Page <noreply@sedessapientiae.edu.pe>\r\n";
        
        // Descomentar la siguiente línea para activar el envío de emails
        // return mail($email_destino, $asunto, $mensaje, $headers);
        
        return true; // Por ahora retorna true sin enviar
    }
}
?>
