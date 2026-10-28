<?php

require_once __DIR__ . '/../config/supabase.php';

function obtenerTokenBus(): ?string
{
    return $_SESSION['access_token'] ?? null;
}

/**
 * Lista los buses y los datos de sus conductores.
 */
function listarBuses(): array
{
    $token = obtenerTokenBus();

    $respuesta = supabaseRequest(
        'buses?select=*,conductor:conductores(nombre,apellido)'
        . '&order=placa.asc',
        'GET',
        null,
        '',
        $token
    );

    return $respuesta;
}

/**
 * Cuenta estudiantes asignados a cada bus.
 */
function listarConteoEstudiantesPorBus(): array
{
    $estudiantes = supabaseRequest(
        'estudiantes',
        'GET',
        null,
        '?select=id_bus',
        obtenerTokenBus()
    );

    $conteos = [];

    foreach ($estudiantes as $estudiante) {
        $idBus = $estudiante['id_bus'] ?? null;
        if ($idBus !== null) {
            $conteos[$idBus] = ($conteos[$idBus] ?? 0) + 1;
        }
    }

    return $conteos;
}

/**
 * Devuelve únicamente los conductores que no están
 * asignados a ningún bus.
 */
function listarConductoresDisponibles(): array
{
    $token = obtenerTokenBus();

    $conductores = supabaseRequest(
        'conductores?select=id_conductor,nombre,apellido'
        . '&order=apellido.asc,nombre.asc',
        'GET',
        null,
        '',
        $token
    );

    $buses = supabaseRequest(
        'buses?select=id_conductor',
        'GET',
        null,
        '',
        $token
    );

    $conductoresAsignados = [];

    foreach ($buses as $bus) {
        if (!empty($bus['id_conductor'])) {
            $conductoresAsignados[] = $bus['id_conductor'];
        }
    }

    return array_values(array_filter(
        $conductores,
        function ($conductor) use ($conductoresAsignados) {
            return !in_array(
                $conductor['id_conductor'],
                $conductoresAsignados,
                true
            );
        }
    ));
}

/**
 * Registra un bus.
 */
function registrarBus(array $datos): void
{
    $token = obtenerTokenBus();

    $placa = strtoupper(trim($datos['placa'] ?? ''));
    $marca = trim($datos['marca'] ?? '');
    $modelo = trim($datos['modelo'] ?? '');
    $capacidadTexto = trim((string) ($datos['capacidad'] ?? ''));
    $idConductor = trim($datos['id_conductor'] ?? '');
    $estado = $datos['estado'] ?? '1';

    if ($placa === '') {
        throw new InvalidArgumentException(
            'La placa del bus es obligatoria.'
        );
    }

    if (!ctype_digit($capacidadTexto) || (int) $capacidadTexto <= 0) {
        throw new InvalidArgumentException(
            'La capacidad debe ser un número entero mayor que cero.'
        );
    }

    if (!in_array($estado, ['0', '1'], true)) {
        throw new InvalidArgumentException(
            'El estado seleccionado no es válido.'
        );
    }

    // Si no se seleccionó un conductor, se envía NULL a Supabase.
    $idConductor = $idConductor === '' ? null : $idConductor;

    // Verificar que el conductor seleccionado exista y siga disponible.
    if ($idConductor !== null) {
        $conductores = supabaseRequest(
            'conductores?select=id_conductor'
            . '&id_conductor=eq.' . rawurlencode($idConductor),
            'GET',
            null,
            '',
            $token
        );

        if (empty($conductores)) {
            throw new InvalidArgumentException(
                'El conductor seleccionado no existe.'
            );
        }

        $busesAsignados = supabaseRequest(
            'buses?select=id_bus'
            . '&id_conductor=eq.' . rawurlencode($idConductor),
            'GET',
            null,
            '',
            $token
        );

        if (!empty($busesAsignados)) {
            throw new InvalidArgumentException(
                'Este conductor ya está asignado a otro bus.'
            );
        }
    }

    $nuevoBus = [
        'placa' => $placa,
        'marca' => $marca !== '' ? $marca : null,
        'modelo' => $modelo !== '' ? $modelo : null,
        'capacidad' => (int) $capacidadTexto,
        'id_conductor' => $idConductor,
        'estado' => $estado === '1'
    ];

    supabaseRequest(
        'buses',
        'POST',
        $nuevoBus,
        '',
        $token
    );
}

function actualizarBus(string $idBus, array $datos): void
{
    if ($idBus === '') {
        throw new InvalidArgumentException(
            'No se indicó el bus que se desea actualizar.'
        );
    }

    $placa = strtoupper(trim($datos['placa'] ?? ''));
    $marca = trim($datos['marca'] ?? '');
    $modelo = trim($datos['modelo'] ?? '');
    $capacidadTexto = trim((string) ($datos['capacidad'] ?? ''));
    $estado = $datos['estado'] ?? '';

    if ($placa === '' || strlen($placa) > 8) {
        throw new InvalidArgumentException(
            'La placa es obligatoria y no puede superar 8 caracteres.'
        );
    }

    if (strlen($marca) > 20 || strlen($modelo) > 20) {
        throw new InvalidArgumentException(
            'La marca y el modelo no pueden superar 20 caracteres.'
        );
    }

    if (!ctype_digit($capacidadTexto) || (int) $capacidadTexto <= 0) {
        throw new InvalidArgumentException(
            'La capacidad debe ser un número entero mayor que cero.'
        );
    }

    if (!in_array($estado, ['0', '1'], true)) {
        throw new InvalidArgumentException(
            'El estado seleccionado no es válido.'
        );
    }

    supabaseRequest(
        'buses',
        'PATCH',
        [
            'placa' => $placa,
            'marca' => $marca !== '' ? $marca : null,
            'modelo' => $modelo !== '' ? $modelo : null,
            'capacidad' => (int) $capacidadTexto,
            'estado' => $estado === '1'
        ],
        '?id_bus=eq.' . rawurlencode($idBus),
        obtenerTokenBus()
    );
}

function eliminarBus(string $idBus): void
{
    if ($idBus === '') {
        throw new InvalidArgumentException(
            'No se indicó el bus que se desea eliminar.'
        );
    }

    $token = obtenerTokenBus();
    $estudiantesAsignados = supabaseRequest(
        'estudiantes',
        'GET',
        null,
        '?select=id_estudiante&id_bus=eq.' . rawurlencode($idBus),
        $token
    );

    if (!empty($estudiantesAsignados)) {
        supabaseRequest(
            'estudiantes',
            'PATCH',
            ['id_bus' => null],
            '?id_bus=eq.' . rawurlencode($idBus),
            $token
        );
    }

    supabaseRequest(
        'buses',
        'DELETE',
        null,
        '?id_bus=eq.' . rawurlencode($idBus),
        $token
    );
}