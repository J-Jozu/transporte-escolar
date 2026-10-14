
<?php

// Configuración de Supabase
define('SUPABASE_URL', 'https://gwvpltylnxlkuciojvlq.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_adVwcqrfmvGUZFK9XOnudQ_Iol5T2et');

/**
 * Realiza solicitudes a la API REST de Supabase.
 */
function supabaseRequest(
    string $tabla,
    string $metodo = 'GET',
    ?array $datos = null,
    string $filtros = ''
): array {

    $url = SUPABASE_URL . '/rest/v1/' . $tabla . $filtros;

    $ch = curl_init($url);

    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    if ($datos !== null) {
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($datos)
        );
    }

    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($respuesta === false) {
        throw new RuntimeException(
            'Error de conexión: ' . $error
        );
    }

    $resultado = json_decode($respuesta, true);

    if ($codigo < 200 || $codigo >= 300) {
        throw new RuntimeException(
            'Error de Supabase (' . $codigo . '): ' . $respuesta
        );
    }

    return is_array($resultado) ? $resultado : [];
}
