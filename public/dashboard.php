<?php

date_default_timezone_set('America/Panama');

session_start();

if (
    empty($_SESSION['usuario_id']) ||
    ($_SESSION['rol'] ?? '') !== 'administrador'
) {
    header('Location: iniciar-sesion.php');
    exit;
}

require_once __DIR__ . '/../controllers/DashboardController.php';

$dashboard = cargarDashboard();

require_once __DIR__ . '/../views/dashboard/index.php';
