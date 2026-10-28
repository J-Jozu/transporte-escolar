
<?php

require_once __DIR__ . '/../config/supabase.php';

/**
 * Obtiene el token de sesión si el inicio de sesión lo guardó.
 */
function obtenerTokenConductor(): ?string
{
    return $_SESSION['access_token']
        ?? $_SESSION['supabase_access_token']
        ?? null;
}

/**
 * Consulta todos los conductores.
 */
function listarConductores(): array
{
    return supabaseRequest(
        'conductores',
        'GET',
        null,
        '?select=*&order=apellido.asc,nombre.asc',
        obtenerTokenConductor()
    );
}

/**
 * Obtiene los identificadores de conductores asignados a buses.
 */
function listarIdsConductoresAsignados(): array
{
    $buses = supabaseRequest(
        'buses',
        'GET',
        null,
        '?select=id_conductor',
        obtenerTokenConductor()
    );

    return array_values(array_filter(
        array_map(
            static fn($bus) => $bus['id_conductor'] ?? null,
            $buses
        ),
        static fn($idConductor) => $idConductor !== null
    ));
}

/**
 * Registra un conductor nuevo.
 */
function registrarConductor(array $datos): void
{
    $camposObligatorios = [
        'nombre' => 'El primer nombre',
        'apellido' => 'El primer apellido',
        'numero_licencia' => 'El número de licencia',
        'telefono' => 'El teléfono'
    ];

    foreach ($camposObligatorios as $campo => $etiqueta) {
        if (trim($datos[$campo] ?? '') === '') {
            throw new InvalidArgumentException(
                $etiqueta . ' es obligatorio.'
            );
        }
    }

    $datos['nombre'] = trim($datos['nombre']);
    $datos['segundo_nombre'] = trim($datos['segundo_nombre'] ?? '');
    $datos['apellido'] = trim($datos['apellido']);
    $datos['segundo_apellido'] = trim($datos['segundo_apellido'] ?? '');
    $datos['numero_licencia'] = trim($datos['numero_licencia']);
    $datos['telefono'] = trim($datos['telefono']);
    $datos['tipo_sangre'] = trim($datos['tipo_sangre'] ?? '');

    $tiposSangre = [
        '', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'
    ];

    if (!in_array($datos['tipo_sangre'], $tiposSangre, true)) {
        throw new InvalidArgumentException(
            'Selecciona un tipo de sangre válido.'
        );
    }

    $estado = $datos['estado'] ?? 'true';

    if (!in_array($estado, ['true', 'false'], true)) {
        throw new InvalidArgumentException(
            'Selecciona un estado válido.'
        );
    }

    $nuevoConductor = [
        'nombre' => $datos['nombre'],
        'segundo_nombre' => $datos['segundo_nombre'] ?: null,
        'apellido' => $datos['apellido'],
        'segundo_apellido' => $datos['segundo_apellido'] ?: null,
        'numero_licencia' => $datos['numero_licencia'],
        'telefono' => $datos['telefono'],
        'tipo_sangre' => $datos['tipo_sangre'] ?: null,
        'estado' => $estado === 'true'
    ];

    supabaseRequest(
        'conductores',
        'POST',
        $nuevoConductor,
        '',
        obtenerTokenConductor()
    );
}

function actualizarConductor(string $idConductor, array $datos): void
{
    if ($idConductor === '') {
        throw new InvalidArgumentException(
            'No se indicó el conductor que se desea actualizar.'
        );
    }

    $nombre = trim($datos['nombre'] ?? '');
    $segundoNombre = trim($datos['segundo_nombre'] ?? '');
    $apellido = trim($datos['apellido'] ?? '');
    $segundoApellido = trim($datos['segundo_apellido'] ?? '');
    $licencia = trim($datos['numero_licencia'] ?? '');
    $telefono = trim($datos['telefono'] ?? '');
    $tipoSangre = trim($datos['tipo_sangre'] ?? '');
    $estado = $datos['estado'] ?? '';

    foreach ([
        'nombre' => $nombre,
        'segundo nombre' => $segundoNombre,
        'apellido' => $apellido,
        'segundo apellido' => $segundoApellido
    ] as $etiqueta => $valor) {
        if ($valor !== '' && !preg_match('/^[\p{L} ]{2,30}$/u', $valor)) {
            throw new InvalidArgumentException(
                'El ' . $etiqueta . ' debe tener entre 2 y 30 letras y espacios.'
            );
        }
    }

    if ($nombre === '' || $apellido === '') {
        throw new InvalidArgumentException(
            'El primer nombre y el primer apellido son obligatorios.'
        );
    }

    if (!preg_match('/^[a-zA-Z0-9-]{12,13}$/', $licencia)) {
        throw new InvalidArgumentException(
            'La licencia debe tener entre 12 y 13 letras, números o guiones.'
        );
    }

    if (!preg_match('/^[0-9]{8}$/', $telefono)) {
        throw new InvalidArgumentException(
            'El teléfono debe contener exactamente 8 dígitos.'
        );
    }

    if (!in_array($tipoSangre, ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
        throw new InvalidArgumentException(
            'Selecciona un tipo de sangre válido.'
        );
    }

    if (!in_array($estado, ['true', 'false'], true)) {
        throw new InvalidArgumentException(
            'Selecciona un estado válido.'
        );
    }

    supabaseRequest(
        'conductores',
        'PATCH',
        [
            'nombre' => $nombre,
            'segundo_nombre' => $segundoNombre ?: null,
            'apellido' => $apellido,
            'segundo_apellido' => $segundoApellido ?: null,
            'numero_licencia' => $licencia,
            'telefono' => $telefono,
            'tipo_sangre' => $tipoSangre ?: null,
            'estado' => $estado === 'true'
        ],
        '?id_conductor=eq.' . rawurlencode($idConductor),
        obtenerTokenConductor()
    );
}

function eliminarConductor(string $idConductor): void
{
    if ($idConductor === '') {
        throw new InvalidArgumentException(
            'No se indicó el conductor que se desea eliminar.'
        );
    }

    $token = obtenerTokenConductor();

    supabaseRequest(
        'buses',
        'PATCH',
        [
            'id_conductor' => null,
            'estado' => false
        ],
        '?id_conductor=eq.' . rawurlencode($idConductor),
        $token
    );

    supabaseRequest(
        'conductores',
        'DELETE',
        null,
        '?id_conductor=eq.' . rawurlencode($idConductor),
        $token
    );
}
