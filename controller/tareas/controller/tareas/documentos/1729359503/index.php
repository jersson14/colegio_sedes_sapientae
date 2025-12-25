<?php
// Establecer el directorio actual
$directorio = __DIR__;

// Abrir el directorio y leer los archivos
if ($handle = opendir($directorio)) {
    echo "<h1>Documentos en la carpeta " . basename($directorio) . "</h1>";
    echo "<ul>";

    // Listar todos los archivos en la carpeta
    while (false !== ($entrada = readdir($handle))) {
        // Ignorar los directorios '.' y '..'
        if ($entrada != "." && $entrada != "..") {
            // Mostrar el archivo con un enlace
            echo "<li><a href=\"$entrada\">$entrada</a></li>";
        }
    }
    echo "</ul>";
    closedir($handle);
} else {
    echo "<p>No se pudo abrir la carpeta.</p>";
}
?>
