<?php

session_start();

if (
    !empty($_SESSION['usuario_id']) &&
    ($_SESSION['rol'] ?? '') === 'administrador'
) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../controllers/LoginController.php';

$mensajeLogin = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensajeLogin = iniciarSesion();

    if (!empty($mensajeLogin['redireccion'])) {
        header('Location: ' . $mensajeLogin['redireccion']);
        exit;
    }
}

require_once __DIR__ . '/../views/auth/iniciar-sesion.php';