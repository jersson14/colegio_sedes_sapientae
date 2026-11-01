<?php
require_once __DIR__ . '/../_seguridad.php';
require_once __DIR__ . '/../../utilitario/validacion.php';
require_once __DIR__ . '/../../utilitario/archivos.php';
require_once __DIR__ . '/../../model/model_aula_virtual.php';
/**
 * Material de apoyo del aula virtual (Fase 13): archivos, videos y enlaces que el docente publica en un curso.
 *   POST accion=guardar  curso, titulo, descripcion, enlaces (uno por línea), archivos[]   (docente del curso, dirección, auxiliar)
 *   POST accion=eliminar id                                                                  (los mismos)
 *   GET  id=X&archivo=nombre -> abre o descarga el archivo (también el estudiante del aula del curso)
 * La lista sale en controlador_aula_virtual.php (accion=curso).
 */
$M = new Modelo_Aula_Virtual();
$rol = rol_actual();
$usu = usuario_actual_id();
const MATERIAL_DIR = __DIR__ . '/material/';

/** ¿Puede ver el curso? Estudiante: de su aula en ese año. Docente: suyo. Dirección y auxiliar: todos. */
function material_puede_ver($M, $curso, $rol, $usu) {
    if ($rol === 'ESTUDIANTE') {
        $mat = $M->Matricula_Estudiante($usu, $curso['anio']);
        return $mat && (int) $mat['aula'] === (int) $curso['aula'];
    }
    return material_puede_publicar($curso, $rol, $usu);
}

function material_puede_publicar($curso, $rol, $usu) {
    return in_array($rol, ['ADMINISTRADOR', 'AUXILIAR'], true) || ($rol === 'DOCENTE' && (int) $curso['usu_docente'] === (int) $usu);
}

/** Carpeta guardada en la base ("mat_…"); '' si no tiene ese formato, para no tocar otra carpeta. */
function material_carpeta($c) {
    return preg_match('/^mat_\d+$/', (string) $c) ? $c : '';
}

function material_negar($motivo, $mensaje) {
    registrar_acceso_denegado('controller/aula_virtual/controlador_material.php', $motivo);
    denegar(403, $mensaje);
}

// ---------- Abrir o descargar un archivo ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $m = $M->Material((int) ($_GET['id'] ?? 0));
    $curso = $m ? $M->Curso($m['curso']) : null;
    $carpeta = $m ? material_carpeta($m['carpeta']) : '';
    $nombre = basename(str_replace('\\', '/', (string) ($_GET['archivo'] ?? '')));
    $ruta = $carpeta !== '' && $nombre !== '' && $nombre[0] !== '.' ? MATERIAL_DIR . $carpeta . '/' . $nombre : '';
    if (!$curso || $ruta === '' || !is_file($ruta)) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }
    if (!material_puede_ver($M, $curso, $rol, $usu)) {
        registrar_acceso_denegado('controller/aula_virtual/controlador_material.php', 'CURSO_AJENO');
        http_response_code(403);
        exit('No tiene permiso para ver este archivo');
    }
    session_write_close();   // los videos pueden tardar: no bloquear las demás pantallas del usuario
    enviar_archivo($ruta, $nombre);
}

$accion = $_POST['accion'] ?? '';

// ---------- Publicar material ----------
if ($accion === 'guardar') {
    validar_documentos_subidos('archivos', true);
    $curso = $M->Curso(entero_post('curso', 1, 'curso'));
    if (!$curso) {
        denegar(404, 'Curso no encontrado');
    }
    if (!material_puede_publicar($curso, $rol, $usu)) {
        material_negar('CURSO_AJENO', 'Ese curso no está asignado a usted');
    }
    $titulo = texto_post('titulo', 150, true, 'Título');
    $descripcion = texto_post('descripcion', 3000, false, 'Descripción');
    $enlaces = enlaces_video_desde_texto($_POST['enlaces'] ?? '');
    $archivos = $_FILES['archivos'] ?? null;
    $hay_archivos = $archivos && array_filter((array) $archivos['name'], 'strlen');
    if (!$hay_archivos && !$enlaces && $descripcion === '') {
        denegar(400, 'Agregue al menos un archivo, un enlace o una descripción');
    }
    $carpeta = null;
    if ($hay_archivos) {
        if (!is_dir(MATERIAL_DIR)) {
            mkdir(MATERIAL_DIR, 0750, true);
        }
        do {
            $carpeta = 'mat_' . time() . random_int(100000, 999999);
        } while (!@mkdir(MATERIAL_DIR . $carpeta, 0750));   // mkdir falla si ya existe: se prueba otro nombre
        foreach ((array) $archivos['name'] as $i => $original) {
            if ($original === '') {
                continue;
            }
            $nombre = nombre_documento_seguro($original);
            $base = pathinfo($nombre, PATHINFO_FILENAME);
            $ext = pathinfo($nombre, PATHINFO_EXTENSION);
            for ($n = 2; is_file(MATERIAL_DIR . $carpeta . '/' . $nombre); $n++) {   // dos archivos con el mismo nombre
                $nombre = $base . ' (' . $n . ')' . ($ext !== '' ? '.' . $ext : '');
            }
            if (!move_uploaded_file($archivos['tmp_name'][$i], MATERIAL_DIR . $carpeta . '/' . $nombre)) {
                denegar(400, 'Error al guardar el archivo: ' . htmlspecialchars($original, ENT_QUOTES, 'UTF-8'));
            }
        }
    }
    $id = $M->Material_Registrar($curso['id'], $titulo, $descripcion, $carpeta, $enlaces, $usu);
    responder_json(['ok' => true, 'id' => $id]);
}

// ---------- Quitar material ----------
if ($accion === 'eliminar') {
    $m = $M->Material(entero_post('id', 1, 'material'));
    if (!$m) {
        denegar(404, 'Material no encontrado');
    }
    if (!material_puede_publicar($M->Curso($m['curso']), $rol, $usu)) {
        material_negar('CURSO_AJENO', 'Ese curso no está asignado a usted');
    }
    $M->Material_Eliminar($m['id']);
    $carpeta = material_carpeta($m['carpeta']);
    if ($carpeta !== '' && is_dir(MATERIAL_DIR . $carpeta)) {
        foreach (glob(MATERIAL_DIR . $carpeta . '/*') ?: [] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir(MATERIAL_DIR . $carpeta);
    }
    responder_json(['ok' => true]);
}

denegar(400, 'Acción no válida');
