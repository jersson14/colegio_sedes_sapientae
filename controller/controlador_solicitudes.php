<?php
require_once __DIR__ . '/_seguridad.php';
/**
 * Controlador para procesar solicitudes de información
 * desde el formulario de contacto de la landing page
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Función para sanitizar datos de entrada
function sanitizar_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para validar email
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Teléfono de cualquier país: 6 a 15 dígitos (utilitario/regional.php)
require_once __DIR__ . '/../utilitario/regional.php';
function validar_telefono($telefono) {
    return telefono_valido($telefono);
}

// Procesar solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Método no permitido'
    ));
    exit;
}

try {
    // Obtener datos del formulario
    $datos_raw = file_get_contents('php://input');
    $datos = json_decode($datos_raw, true);
    
    // Si no vienen como JSON, intentar obtener de POST normal
    if (!$datos) {
        $datos = $_POST;
    }
    
    // Anti-spam (Fase 8D): el campo trampa debe venir vacío y el formulario debe haber estado abierto al menos 3 s.
    // A un robot se le responde "éxito" sin guardar nada, para no darle pistas.
    if (trim((string) ($datos['sitio_web'] ?? '')) !== '' || (int) ($datos['abierto_ms'] ?? 0) < 3000) {
        echo json_encode(array('status' => 'success', 'message' => '¡Gracias por tu interés! Hemos recibido tu solicitud.'));
        exit;
    }
    foreach (array('nombre' => 150, 'email' => 150, 'telefono' => 20, 'mensaje' => 1000) as $campo => $max) {
        if (isset($datos[$campo]) && mb_strlen((string) $datos[$campo], 'UTF-8') > $max) {
            echo json_encode(array('status' => 'error', 'message' => 'El campo ' . $campo . ' es demasiado largo (máximo ' . $max . ' caracteres)'));
            exit;
        }
    }

    // Validar que existan todos los campos requeridos
    $campos_requeridos = array('nombre', 'email', 'telefono', 'nivel', 'mensaje');
    $errores = array();
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($datos[$campo]) || empty(trim($datos[$campo]))) {
            $errores[] = "El campo " . ucfirst($campo) . " es requerido";
        }
    }
    
    if (!empty($errores)) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Faltan campos requeridos',
            'errors' => $errores
        ));
        exit;
    }
    
    // Sanitizar datos
    $nombre = sanitizar_input($datos['nombre']);
    $email = sanitizar_input($datos['email']);
    $telefono = sanitizar_input($datos['telefono']);
    $nivel = sanitizar_input($datos['nivel']);
    $mensaje = sanitizar_input($datos['mensaje']);
    
    // Validaciones específicas
    if (strlen($nombre) < 3) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'El nombre debe tener al menos 3 caracteres'
        ));
        exit;
    }
    
    if (!validar_email($email)) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'El email no es válido'
        ));
        exit;
    }
    
    if (!validar_telefono($telefono)) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Ingrese un teléfono válido (6 a 15 dígitos)'
        ));
        exit;
    }
    
    if (!in_array($nivel, array('inicial', 'primaria', 'secundaria'))) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'El nivel seleccionado no es válido'
        ));
        exit;
    }
    
    if (strlen($mensaje) < 10) {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'El mensaje debe tener al menos 10 caracteres'
        ));
        exit;
    }
    
    // Cargar el modelo
    require_once '../model/model_solicitudes.php';
    $solicitud_model = new Solicitud_Model();
    
    // Máximo 3 solicitudes por hora desde la misma IP (el control por email se evita cambiando de email)
    if ($solicitud_model->Solicitudes_Recientes_IP($_SERVER['REMOTE_ADDR'] ?? '') >= 3) {
        echo json_encode(array('status' => 'warning', 'message' => 'Ya recibimos varias solicitudes desde tu conexión. Intenta más tarde o llámanos.'));
        exit;
    }

    // Verificar si el email ya envió una solicitud recientemente (anti-spam)
    if ($solicitud_model->Email_Reciente($email)) {
        echo json_encode(array(
            'status' => 'warning',
            'message' => 'Ya has enviado una solicitud recientemente. Te contactaremos pronto.'
        ));
        exit;
    }
    
    // Preparar datos para guardar
    $datos_solicitud = array(
        'nombre' => $nombre,
        'email' => $email,
        'telefono' => $telefono,
        'nivel' => $nivel,
        'mensaje' => $mensaje
    );
    
    // Registrar la solicitud
    $resultado = $solicitud_model->Registrar_Solicitud($datos_solicitud);
    
    if ($resultado['status'] === 'success') {
        // Intentar enviar notificación por email (opcional)
        $solicitud_model->Enviar_Notificacion_Email($datos_solicitud);
        
        echo json_encode(array(
            'status' => 'success',
            'message' => '¡Gracias por tu interés! Hemos recibido tu solicitud y nos pondremos en contacto contigo pronto.',
            'id_solicitud' => $resultado['id_solicitud']
        ));
    } else {
        echo json_encode($resultado);
    }
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Error al procesar la solicitud. Por favor, intenta nuevamente.'
    ));
    error_log('controlador_solicitudes: ' . $e->getMessage());
}
?>
