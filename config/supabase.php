<?php
// Configuración de Supabase
define('SUPABASE_URL', 'https://gwvpltylnxlkuciojvlq.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_adVwcqrfmvGUZFK9XOnudQ_Iol5T2et');
/**
 * Renueva el token de acceso usando el refresh_token.
 */
function renovarSesionSupabase(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException(
            'La sesión PHP no está iniciada.'
        );
    }

    $refreshToken = $_SESSION['refresh_token'] ?? '';

    if ($refreshToken === '') {
        throw new RuntimeException(
            'No existe un refresh_token. Cierra sesión e inicia sesión nuevamente.'
        );
    }

    $url = rtrim(SUPABASE_URL, '/') .
        '/auth/v1/token?grant_type=refresh_token';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'refresh_token' => $refreshToken
        ]),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($respuesta === false) {
        throw new RuntimeException(
            'Error al renovar la sesión: ' . $error
        );
    }

    $resultado = json_decode($respuesta, true);

    if (
        $codigo < 200 ||
        $codigo >= 300 ||
        empty($resultado['access_token']) ||
        empty($resultado['refresh_token'])
    ) {
        // El refresh token puede haber caducado o haber sido invalidado.
        unset(
            $_SESSION['access_token'],
            $_SESSION['refresh_token']
        );

        throw new RuntimeException(
            'La sesión de Supabase ya no se puede renovar. Cierra sesión e inicia sesión nuevamente.'
        );
    }

    // Supabase puede entregar un refresh_token nuevo.
    $_SESSION['access_token'] = $resultado['access_token'];
    $_SESSION['refresh_token'] = $resultado['refresh_token'];

    return $resultado['access_token'];
}

/**
 * Ejecuta una solicitud REST a Supabase.
 */
function supabaseRequest(
    string $tabla,
    string $metodo = 'GET',
    ?array $datos = null,
    string $filtros = '',
    ?string $accessToken = null
): array {

    $url = rtrim(SUPABASE_URL, '/') .
        '/rest/v1/' . $tabla . $filtros;

    // Usa el token indicado o el token del usuario autenticado.
    $token = $accessToken
        ?? ($_SESSION['access_token'] ?? SUPABASE_KEY);

    $esSesionUsuario = $accessToken !== null
        || !empty($_SESSION['access_token']);

    // La solicitud se reintenta una sola vez si expira el JWT.
    for ($intento = 0; $intento < 2; $intento++) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . SUPABASE_KEY,
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
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

        // Si el JWT venció, renueva la sesión y vuelve a intentar.
        $jwtExpirado = $codigo === 401
            && is_array($resultado)
            && ($resultado['message'] ?? '') === 'JWT expired';

        if (
            $jwtExpirado &&
            $intento === 0 &&
            $esSesionUsuario &&
            !empty($_SESSION['refresh_token'])
        ) {
            $token = renovarSesionSupabase();
            continue;
        }

        if ($codigo < 200 || $codigo >= 300) {
            throw new RuntimeException(
                'Error de Supabase (' . $codigo . '): ' . $respuesta
            );
        }

        return is_array($resultado) ? $resultado : [];
    }

    throw new RuntimeException(
        'No fue posible completar la solicitud a Supabase.'
    );
}
