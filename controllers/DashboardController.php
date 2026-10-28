<?php

require_once __DIR__ . '/../config/supabase.php';

/**
 * Consulta Supabase usando el token del usuario autenticado.
 * Para los conteos solicita únicamente una fila y lee
 * el total real desde Content-Range.
 */
function consultarDashboard(
    string $tabla,
    string $consulta,
    string $token,
    bool $soloConteo = false
): array {
    $url = rtrim(SUPABASE_URL, '/') .
        '/rest/v1/' . $tabla . $consulta;

    $encabezados = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Prefer: count=exact'
    ];

    if ($soloConteo) {
        $encabezados[] = 'Range: 0-0';
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => $encabezados,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tamanoEncabezados = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($respuesta === false) {
        throw new RuntimeException(
            'Error de conexión: ' . $error
        );
    }

    $encabezadosRespuesta = substr(
        $respuesta,
        0,
        $tamanoEncabezados
    );

    $cuerpo = substr($respuesta, $tamanoEncabezados);

    if ($codigo < 200 || $codigo >= 300) {
        throw new RuntimeException(
            'Supabase respondió HTTP ' . $codigo . ': ' . $cuerpo
        );
    }

    $datos = json_decode($cuerpo, true);

    if (!is_array($datos)) {
        $datos = [];
    }

    $total = null;

    if (preg_match(
        '/^Content-Range:\s*[^\/]+\/(\d+|\*)/im',
        $encabezadosRespuesta,
        $coincidencia
    )) {
        if ($coincidencia[1] !== '*') {
            $total = (int) $coincidencia[1];
        }
    }

    return [
        'datos' => $datos,
        'total' => $total
    ];
}

function cargarDashboard(): array
{
    $token = $_SESSION['access_token'];

    $dashboard = [
        'totalEstudiantes' => 0,
        'totalConductores' => 0,
        'totalBuses' => 0,
        'totalRutas' => 0,
        'actividades' => [],
        'ultimasLlegadas' => [],
        'error' => false
    ];

    try {
        $estudiantes = consultarDashboard(
            'estudiantes',
            '?select=id_estudiante',
            $token,
            true
        );

        $conductores = consultarDashboard(
            'conductores',
            '?select=id_conductor',
            $token,
            true
        );

        $buses = consultarDashboard(
            'buses',
            '?select=id_bus',
            $token,
            true
        );

        $rutas = consultarDashboard(
            'rutas',
            '?select=id_ruta',
            $token,
            true
        );

        $dashboard['totalEstudiantes'] = $estudiantes['total'] ?? 0;
        $dashboard['totalConductores'] = $conductores['total'] ?? 0;
        $dashboard['totalBuses'] = $buses['total'] ?? 0;
        $dashboard['totalRutas'] = $rutas['total'] ?? 0;

        $actividades = consultarDashboard(
            'asistencias',
            '?select=id_asistencia,tipo_evento,fecha_hora,' .
            'estudiantes(nombre,apellido),paradas(nombre)' .
            '&order=fecha_hora.desc&limit=8',
            $token
        );

        $dashboard['actividades'] = $actividades['datos'];

        $llegadas = consultarDashboard(
            'asistencias',
            '?select=id_asistencia,fecha_hora,' .
            'estudiantes(nombre,apellido),paradas(nombre)' .
            '&tipo_evento=eq.llego_a_la_escuela' .
            '&order=fecha_hora.desc&limit=5',
            $token
        );

        $dashboard['ultimasLlegadas'] = $llegadas['datos'];

    } catch (Throwable $e) {
        error_log(
            'Error al cargar el dashboard: ' . $e->getMessage()
        );

        $dashboard['error'] = true;
    }

    return $dashboard;
}
