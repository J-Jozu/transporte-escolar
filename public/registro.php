
<?php

require_once __DIR__ . '/../controllers/RegistroController.php';

$resultadoRegistro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultadoRegistro = registrarAcudiente();
}

require_once __DIR__ . '/../views/auth/registro.php';
