<?php
// Establecer el directorio actual
$directorio = __DIR__;

// Abrir el directorio y leer los archivos y carpetas
if ($handle = opendir($directorio)) {
    echo "<h1>Documentos</h1>";
    echo "<ul>";

    // Listar todos los archivos y carpetas en el directorio
    while (false !== ($entrada = readdir($handle))) {
        // Ignorar los directorios '.' y '..'
        if ($entrada != "." && $entrada != "..") {
            // Si es una carpeta, mostrarla como un enlace
            if (is_dir($entrada)) {
                echo "<li><a href=\"$entrada/\">$entrada</a></li>";
            } else {
                // Mostrar el archivo con un enlace
                echo "<li><a href=\"$entrada\">$entrada</a></li>";
            }
        }
    }
    echo "</ul>";
    closedir($handle);
} else {
    echo "<p>No se pudo abrir la carpeta.</p>";
}
?>
