<?php
/**
 * Controlador para procesar solicitudes de información
 * desde el formulario de contacto de la landing page
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

// Función para validar teléfono (9 dígitos)
function validar_telefono($telefono) {
    $telefono_limpio = preg_replace('/[^0-9]/', '', $telefono);
    return strlen($telefono_limpio) === 9;
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
            'message' => 'El teléfono debe tener 9 dígitos'
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
        'message' => 'Error al procesar la solicitud. Por favor, intenta nuevamente.',
        'error_detail' => $e->getMessage()
    ));
}
?>
