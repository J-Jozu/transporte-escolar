
<?php

require_once __DIR__ . '/../config/supabase.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $resultado = supabaseRequest('rutas', 'GET', null, '?select=id_ruta&limit=1');

    echo "Conexión con Supabase correcta.\n";
    echo "La API respondió correctamente.\n";
    echo "Rutas encontradas: " . count($resultado);

} catch (Throwable $e) {
    echo "No se pudo completar la consulta.\n";
    echo $e->getMessage();
}
