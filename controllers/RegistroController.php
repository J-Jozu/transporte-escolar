
<?php

require_once __DIR__ . '/../config/supabase.php';

function registrarAcudiente(): array
{
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';

    
    // Validar nombre y apellido: entre 2 y 30 letras.
    if (
        mb_strlen($nombre, 'UTF-8') < 2 ||
        mb_strlen($nombre, 'UTF-8') > 30 ||
        !preg_match('/^[\p{L} ]+$/u', $nombre)
    ) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'El nombre debe tener entre 2 y 30 letras'
        ];
    }

    if (
        mb_strlen($apellido, 'UTF-8') < 2 ||
        mb_strlen($apellido, 'UTF-8') > 30 ||
        !preg_match('/^[\p{L} ]+$/u', $apellido)
    ) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'El apellido debe tener entre 2 y 30 letras'
        ];
    }

    // Validar correo: máximo 100 caracteres.
    if (mb_strlen($correo, 'UTF-8') > 100) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'El correo no puede superar los 100 caracteres.'
        ];
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'Ingresa un correo electrónico válido.'
        ];
    }

    // Validar teléfono: exactamente 8 dígitos.
    if (!preg_match('/^[0-9]{8}$/', $telefono)) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'El teléfono debe contener exactamente 8 números.'
        ];
    }

    // Validar contraseña: entre 8 y 20 caracteres.
    $longitudPassword = mb_strlen($password, 'UTF-8');

    if ($longitudPassword < 8 || $longitudPassword > 20) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'La contraseña debe tener entre 8 y 20 caracteres.'
        ];
    }

    if ($password !== $confirmar) {
        return [
            'tipo' => 'danger',
            'mensaje' => 'Las contraseñas no coinciden.'
        ];
    }


    // Solicitud a Supabase Auth
    $url = rtrim(SUPABASE_URL, '/') . '/auth/v1/signup';

    $datos = [
        'email' => $correo,
        'password' => $password,
        'data' => [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono
        ]
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE
        ),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($respuesta === false) {
        error_log('Error cURL en registro: ' . $error);

        return [
            'tipo' => 'danger',
            'mensaje' => 'No fue posible conectar con Supabase. Inténtalo nuevamente.'
        ];
    }

    $resultado = json_decode($respuesta, true);

    if ($codigo < 200 || $codigo >= 300) {
        $mensajeError = $resultado['msg']
            ?? $resultado['message']
            ?? $resultado['error_description']
            ?? 'No fue posible crear la cuenta.';

        return [
            'tipo' => 'danger',
            'mensaje' => $mensajeError
        ];
    }

    return [
        'tipo' => 'success',
        'mensaje' => 'Cuenta creada correctamente. '
    ];
}
