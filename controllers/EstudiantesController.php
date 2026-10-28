<?php

require_once __DIR__ . '/../config/supabase.php';

/**
 * Obtener los estudiantes con sus acudientes y buses.
 */
function listarEstudiantes(): array
{
    $token = $_SESSION['access_token'] ?? null;

    return supabaseRequest(
        'estudiantes?select=*,'
        . 'acudiente:usuarios(nombre,apellido),'
        . 'bus:buses(id_bus,placa,marca,modelo)'
        . '&order=apellido.asc,nombre.asc',
        'GET',
        null,
        '',
        $token
    );
}

/**
 * Obtiene buses disponibles para asignar a estudiantes.
 */
function listarBusesParaEstudiantes(): array
{
    return supabaseRequest(
        'buses',
        'GET',
        null,
        '?select=id_bus,placa,marca,modelo,estado&order=placa.asc',
        $_SESSION['access_token'] ?? null
    );
}

/**
 * Actualiza únicamente los campos editables del estudiante.
 */
function actualizarEstudiante(string $idEstudiante, array $datos): void
{
    if ($idEstudiante === '') {
        throw new InvalidArgumentException(
            'No se indicó el estudiante que se desea actualizar.'
        );
    }

    $nombre = trim($datos['nombre'] ?? '');
    $segundoNombre = trim($datos['segundo_nombre'] ?? '');
    $apellido = trim($datos['apellido'] ?? '');
    $segundoApellido = trim($datos['segundo_apellido'] ?? '');
    $identificacion = trim($datos['identificacion'] ?? '');
    $grado = trim($datos['grado'] ?? '');
    $estado = $datos['estado'] ?? '';
    $idBus = trim($datos['id_bus'] ?? '');

    if (
        $nombre === '' || strlen($nombre) > 100 ||
        $apellido === '' || strlen($apellido) > 100 ||
        strlen($segundoNombre) > 100 ||
        strlen($segundoApellido) > 100 ||
        $identificacion === '' || strlen($identificacion) > 30 ||
        $grado === '' || strlen($grado) > 30
    ) {
        throw new InvalidArgumentException(
            'El primer nombre, primer apellido, identificación y grado son obligatorios; revisa también sus longitudes.'
        );
    }

    if (!in_array($estado, ['true', 'false'], true)) {
        throw new InvalidArgumentException(
            'El estado seleccionado no es válido.'
        );
    }

    if ($idBus !== '') {
        $buses = supabaseRequest(
            'buses',
            'GET',
            null,
            '?select=id_bus&id_bus=eq.' . rawurlencode($idBus),
            $_SESSION['access_token'] ?? null
        );

        if (empty($buses)) {
            throw new InvalidArgumentException(
                'El bus seleccionado no existe.'
            );
        }
    }

    supabaseRequest(
        'estudiantes',
        'PATCH',
        [
            'nombre' => $nombre,
            'segundo_nombre' => $segundoNombre,
            'apellido' => $apellido,
            'segundo_apellido' => $segundoApellido,
            'identificacion' => $identificacion,
            'grado' => $grado,
            'id_bus' => $idBus !== '' ? $idBus : null,
            'estado' => $estado === 'true'
        ],
        '?id_estudiante=eq.' . rawurlencode($idEstudiante),
        $_SESSION['access_token'] ?? null
    );
}

/**
 * Eliminar un estudiante por su UUID.
 */
function eliminarEstudiante(string $idEstudiante): void
{
    $token = $_SESSION['access_token'] ?? null;

    if ($idEstudiante === '') {
        throw new InvalidArgumentException(
            'No se indicó el estudiante que se desea eliminar.'
        );
    }

    supabaseRequest(
        'estudiantes',
        'DELETE',
        null,
        '?id_estudiante=eq.' . rawurlencode($idEstudiante),
        $token
    );
}