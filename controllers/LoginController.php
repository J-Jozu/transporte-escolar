<?php

require_once __DIR__ . '/../config/supabase.php';

function iniciarSesion(): array
{
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        return [
            'tipo' => 'danger',
            'mensaje' => 'Ingresa tu correo y contraseña.'
        ];
    }

    if (
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        mb_strlen($correo, 'UTF-8') > 100
    ) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'Ingresa un correo electrónico válido.'
        ];
    }

    $url = rtrim(SUPABASE_URL, '/') . '/auth/v1/token?grant_type=password';

    $datos = [
        'email' => $correo,
        'password' => $password
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($datos),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($respuesta === false) {
        error_log('Error cURL en inicio de sesión: ' . $error);

        return [
            'tipo' => 'danger',
            'mensaje' => 'No fue posible conectar con el servicio. Inténtalo nuevamente.'
        ];
    }

    $resultado = json_decode($respuesta, true);

    if ($codigo < 200 || $codigo >= 300) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'No se pudo iniciar sesión. Verifica tus credenciales y que tu correo esté confirmado.'
        ];
    }

    if (
        empty($resultado['access_token']) ||
        empty($resultado['refresh_token']) ||
        empty($resultado['user']['id'])
    ) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'El servicio no devolvió los datos de sesión esperados.'
        ];
    }

    try {
        $perfil = supabaseRequest(
            'usuarios',
            'GET',
            null,
            '?id_usuario=eq.' .
                rawurlencode($resultado['user']['id']) .
                '&select=id_usuario,nombre,apellido,correo,rol,estado',
            $resultado['access_token']
        );

        if (empty($perfil)) {
            return [
                'tipo' => 'danger',
                'mensaje' => 'No se encontró el perfil de este usuario.'
            ];
        }

        $usuario = $perfil[0];

        if (empty($usuario['estado'])) {
            return [
                'tipo' => 'danger',
                'mensaje' => 'Esta cuenta se encuentra desactivada.'
            ];
        }

        if ($usuario['rol'] !== 'administrador') {
            return [
                'tipo' => 'danger',
                'mensaje' => 'No tienes permisos para acceder al panel administrativo.'
            ];
        }

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['apellido'] = $usuario['apellido'];
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['access_token'] = $resultado['access_token'];
        $_SESSION['refresh_token'] = $resultado['refresh_token'];

        return [
            'tipo' => 'success',
            'mensaje' => 'Inicio de sesión correcto.',
            'redireccion' => 'dashboard.php'
        ];

    } catch (Throwable $e) {
        error_log('Error al consultar el perfil: ' . $e->getMessage());

        return [
            'tipo' => 'danger',
            'mensaje' => 'No fue posible consultar el perfil del usuario.'
        ];
    }
}