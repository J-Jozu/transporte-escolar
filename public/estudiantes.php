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

require_once __DIR__ . '/../controllers/EstudiantesController.php';

function escaparEstudiante($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function nombreCompletoEstudiante(array $estudiante): string
{
    return trim(implode(' ', array_filter([
        $estudiante['nombre'] ?? '',
        $estudiante['segundo_nombre'] ?? '',
        $estudiante['apellido'] ?? '',
        $estudiante['segundo_apellido'] ?? ''
    ], fn($parte) => trim((string) $parte) !== '')));
}

function nombreAcudienteEstudiante($acudiente): string
{
    if (!is_array($acudiente)) {
        return 'Sin acudiente';
    }

    $nombre = trim(
        ($acudiente['nombre'] ?? '') . ' ' .
        ($acudiente['apellido'] ?? '')
    );

    return $nombre !== '' ? $nombre : 'Sin acudiente';
}

function descripcionBusEstudiante($bus): string
{
    if (!is_array($bus) || empty($bus['placa'])) {
        return 'Sin asignar';
    }

    return trim(
        $bus['placa'] . ' ' .
        ($bus['marca'] ?? '') . ' ' .
        ($bus['modelo'] ?? '')
    );
}

$mensaje = $_SESSION['mensaje_estudiante'] ?? '';
$tipoMensaje = $_SESSION['tipo_mensaje_estudiante'] ?? 'success';

unset(
    $_SESSION['mensaje_estudiante'],
    $_SESSION['tipo_mensaje_estudiante']
);

/*
 * Procesar eliminación.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'actualizar') {
        try {
            actualizarEstudiante(
                (string) ($_POST['id_estudiante'] ?? ''),
                $_POST
            );

            $_SESSION['mensaje_estudiante'] =
                'Estudiante actualizado correctamente.';
            $_SESSION['tipo_mensaje_estudiante'] = 'success';
        } catch (InvalidArgumentException $e) {
            $_SESSION['mensaje_estudiante'] = $e->getMessage();
            $_SESSION['tipo_mensaje_estudiante'] = 'danger';
        } catch (Throwable $e) {
            error_log(
                'Error al actualizar estudiante: ' . $e->getMessage()
            );

            $_SESSION['mensaje_estudiante'] =
                'No fue posible actualizar el estudiante. Verifica los datos e inténtalo nuevamente.';
            $_SESSION['tipo_mensaje_estudiante'] = 'danger';
        }

        header('Location: estudiantes.php');
        exit;
    }

    if (($_POST['accion'] ?? '') === 'eliminar') {
        try {
            eliminarEstudiante(
                (string) ($_POST['id_estudiante'] ?? '')
            );

            $_SESSION['mensaje_estudiante'] =
                'Estudiante eliminado correctamente.';
            $_SESSION['tipo_mensaje_estudiante'] = 'success';
        } catch (Throwable $e) {
            error_log(
                'Error al eliminar estudiante: ' . $e->getMessage()
            );

            $_SESSION['mensaje_estudiante'] =
                'No se pudo eliminar el estudiante. Puede tener registros relacionados que impiden la eliminación.';
            $_SESSION['tipo_mensaje_estudiante'] = 'danger';
        }

        header('Location: estudiantes.php');
        exit;
    }
}

/*
 * Cargar listado.
 */
$estudiantes = [];
$busesDisponibles = [];
$errorCarga = '';

try {
    $estudiantes = listarEstudiantes();
    $busesDisponibles = listarBusesParaEstudiantes();
} catch (Throwable $e) {
    error_log('Error al cargar estudiantes: ' . $e->getMessage());

    $errorCarga =
        'No se pudieron cargar los estudiantes. Inténtalo nuevamente.';
}

/*
 * Crear opciones únicas para el filtro de grado.
 */
$grados = [];

foreach ($estudiantes as $estudiante) {
    $grado = trim((string) ($estudiante['grado'] ?? ''));

    if ($grado !== '') {
        $grados[$grado] = $grado;
    }
}

natcasesort($grados);

/*
 * Crear opciones únicas para el filtro de bus.
 */
$busesFiltro = [];

foreach ($estudiantes as $estudiante) {
    $bus = $estudiante['bus'] ?? null;

    if (is_array($bus) && !empty($bus['id_bus'])) {
        $busesFiltro[$bus['id_bus']] = [
            'id' => $bus['id_bus'],
            'placa' => $bus['placa'] ?? 'Sin placa'
        ];
    }
}

uasort($busesFiltro, fn($a, $b) =>
    strnatcasecmp($a['placa'], $b['placa'])
);

$totalEstudiantes = count($estudiantes);

$nombreUsuario = trim(
    ($_SESSION['nombre'] ?? '') . ' ' .
    ($_SESSION['apellido'] ?? '')
);

if ($nombreUsuario === '') {
    $nombreUsuario = $_SESSION['correo'] ?? 'Administrador';
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestión de Estudiantes | MIRUTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/estudiantes.css">
</head>

<body>
<div class="app-shell">

    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <img
                src="assets/img/logo-negro.png"
                alt="Logo del transporte escolar"
                class="logo-sistema"
            >
            <span>
                <strong>MIRUTA</strong>
                <small>Colegio Nova Aura</small>
            </span>
        </a>

        <div class="sidebar-label">MENÚ PRINCIPAL</div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>

            <a href="estudiantes.php" class="nav-link active" aria-current="page">
                <i class="bi bi-people-fill"></i>
                <span>Estudiantes</span>
            </a>

            <a href="conductores.php" class="nav-link">
                <i class="bi bi-person-badge-fill"></i>
                <span>Conductores</span>
            </a>

            <a href="buses.php" class="nav-link">
                <i class="bi bi-bus-front"></i>
                <span>Buses</span>
            </a>

            <a href="rutas.php" class="nav-link">
                <i class="bi bi-signpost-2-fill"></i>
                <span>Rutas</span>
            </a>

            <a href="paradas.php" class="nav-link">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Paradas</span>
            </a>

            <a href="asistencias.php" class="nav-link">
                <i class="bi bi-clipboard-check-fill"></i>
                <span>Control de asistencia</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <i class="bi bi-shield-check"></i>
            <span>Sistema administrativo</span>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-area">

       <header class="topbar">
            <div class="topbar-left">
                <button
                    type="button"
                    class="menu-toggle"
                    id="menuToggle"
                    aria-label="Abrir menú"
                >
                    <i class="bi bi-list"></i>
                </button>

                <div>
                    <h1>Sistema de Gestión y Seguimiento</h1>
                    <p>del Transporte Escolar</p>
                </div>
            </div>

            <div class="topbar-user">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>

                <div class="user-details">
                    <strong><?= escaparEstudiante($nombreUsuario) ?></strong>
                    <small>Administrador</small>
                </div>

                <a
                    href="cerrar-sesion.php"
                    class="logout-button"
                    title="Cerrar sesión"
                    aria-label="Cerrar sesión"
                >
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Salir</span>
                </a>
            </div>
        </header>

        <section class="dashboard-content estudiantes-content">

            <div class="page-heading">
                <div>
                    <h2>Gestión de Estudiantes</h2>
                    <p>Consulta los estudiantes registrados y sus asignaciones de transporte.</p>
                </div>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div
                    class="alert alert-<?= escaparEstudiante($tipoMensaje) ?> alert-dismissible fade show"
                    role="alert"
                >
                    <?= escaparEstudiante($mensaje) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Cerrar"
                    ></button>
                </div>
            <?php endif; ?>

            <?php if ($errorCarga !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= escaparEstudiante($errorCarga) ?>
                </div>
            <?php endif; ?>

            <div class="estudiantes-summary">
                <div class="estudiante-summary-card">
                    <div class="estudiante-summary-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <span>Total de estudiantes</span>
                        <strong><?= $totalEstudiantes ?></strong>
                    </div>
                </div>
            </div>

            <section class="estudiantes-panel">

                <div class="estudiantes-panel-heading">
                    <div>
                        <h5>
                            <i class="bi bi-list-ul"></i>
                            Estudiantes registrados
                        </h5>
                        <p>Busca, filtra o elimina un registro.</p>
                    </div>
                </div>

                <!-- Búsqueda y filtros -->
                <div class="estudiantes-filtros">

                    <div class="estudiante-busqueda">
                        <label for="buscarEstudiante">Buscar estudiante</label>
                        <div class="estudiante-input-icon">
                            <i class="bi bi-search"></i>
                            <input
                                type="search"
                                id="buscarEstudiante"
                                class="form-control"
                                placeholder="Nombre, identificación, grado o bus..."
                            >
                        </div>
                    </div>

                    <div class="estudiante-filtro">
                        <label for="filtroGrado">Filtrar por grado</label>
                        <select id="filtroGrado" class="form-select">
                            <option value="">Todos los grados</option>

                            <?php foreach ($grados as $grado): ?>
                                <option value="<?= escaparEstudiante($grado) ?>">
                                    <?= escaparEstudiante($grado) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="estudiante-filtro">
                        <label for="filtroBus">Filtrar por bus</label>
                        <select id="filtroBus" class="form-select">
                            <option value="">Todos los buses</option>
                            <option value="sin-asignar">Sin bus asignado</option>

                            <?php foreach ($busesFiltro as $busFiltro): ?>
                                <option value="<?= escaparEstudiante($busFiltro['id']) ?>">
                                    <?= escaparEstudiante($busFiltro['placa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="estudiante-filtro-reset">
                        <button
                            type="button"
                            id="limpiarFiltrosEstudiantes"
                            class="btn btn-light"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Limpiar
                        </button>
                    </div>

                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table estudiantes-table align-middle">
                        <thead>
                            <tr>
                                <th>Nombre completo</th>
                                <th>Identificación</th>
                                <th>Grado</th>
                                <th>Acudiente</th>
                                <th>Bus asignado</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="tablaEstudiantes">

                        <?php if (empty($estudiantes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    No hay estudiantes registrados.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($estudiantes as $estudiante): ?>
                                <?php
                                    $nombreCompleto =
                                        nombreCompletoEstudiante($estudiante);

                                    $acudiente =
                                        nombreAcudienteEstudiante(
                                            $estudiante['acudiente'] ?? null
                                        );

                                    $bus = $estudiante['bus'] ?? null;

                                    $busDescripcion =
                                        descripcionBusEstudiante($bus);

                                    $busId = is_array($bus)
                                        ? ($bus['id_bus'] ?? '')
                                        : '';

                                    $busSinAsignar = empty($busId);

                                    $activo = !empty($estudiante['estado']);
                                ?>

                                <tr
                                    data-grado="<?= escaparEstudiante($estudiante['grado'] ?? '') ?>"
                                    data-bus="<?= escaparEstudiante($busSinAsignar ? 'sin-asignar' : $busId) ?>"
                                    class="fila-estudiante-detalle"
                                    tabindex="0"
                                    role="button"
                                    aria-label="Ver detalles de <?= escaparEstudiante($nombreCompleto) ?>"
                                    data-registro="<?= escaparEstudiante(
                                        json_encode(
                                            $estudiante,
                                            JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE
                                        ) ?: '{}'
                                    ) ?>"
                                >
                                    <td>
                                        <strong>
                                            <?= escaparEstudiante($nombreCompleto) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= escaparEstudiante(
                                            $estudiante['identificacion'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= escaparEstudiante(
                                            $estudiante['grado'] ?? 'Sin grado'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= escaparEstudiante($acudiente) ?>
                                    </td>

                                    <td>
                                        <?php if ($busSinAsignar): ?>
                                            <span class="estudiante-sin-bus">
                                                <i class="bi bi-bus-front"></i>
                                                Sin asignar
                                            </span>
                                        <?php else: ?>
                                            <?= escaparEstudiante($busDescripcion) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($activo): ?>
                                            <span class="estudiante-estado estado-activo">
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="estudiante-estado estado-inactivo">
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger btn-eliminar-estudiante"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEliminarEstudiante"
                                            data-id="<?= escaparEstudiante($estudiante['id_estudiante']) ?>"
                                            data-nombre="<?= escaparEstudiante($nombreCompleto) ?>"
                                            title="Eliminar estudiante"
                                            aria-label="Eliminar estudiante"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>

                <div
                    id="sinResultadosEstudiantes"
                    class="text-center py-3 d-none"
                >
                    No se encontraron estudiantes con esos criterios.
                </div>

            </section>
        </section>
    </main>
</div>

<div
    class="modal fade"
    id="modalDetalleEstudiante"
    tabindex="-1"
    aria-labelledby="tituloModalDetalleEstudiante"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalDetalleEstudiante">Detalle del estudiante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="datosDetalleEstudiante" class="row g-3"></div>
                <form id="formEditarEstudiante" action="estudiantes.php" method="POST" hidden>
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id_estudiante" id="editarIdEstudiante">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="editarNombreEstudiante">Primer nombre *</label>
                            <input class="form-control" id="editarNombreEstudiante" name="nombre" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarSegundoNombreEstudiante">Segundo nombre</label>
                            <input class="form-control" id="editarSegundoNombreEstudiante" name="segundo_nombre" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarApellidoEstudiante">Primer apellido *</label>
                            <input class="form-control" id="editarApellidoEstudiante" name="apellido" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarSegundoApellidoEstudiante">Segundo apellido</label>
                            <input class="form-control" id="editarSegundoApellidoEstudiante" name="segundo_apellido" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarIdentificacionEstudiante">Identificación *</label>
                            <input class="form-control" id="editarIdentificacionEstudiante" name="identificacion" maxlength="30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarGradoEstudiante">Grado *</label>
                            <input class="form-control" id="editarGradoEstudiante" name="grado" maxlength="30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarBusEstudiante">Bus asignado</label>
                            <select class="form-select" id="editarBusEstudiante" name="id_bus">
                                <option value="">Sin asignar</option>
                                <?php foreach ($busesDisponibles as $busDisponible): ?>
                                    <?php
                                    $etiquetaBus = trim(
                                        ($busDisponible['placa'] ?? '') . ' ' .
                                        ($busDisponible['marca'] ?? '') . ' ' .
                                        ($busDisponible['modelo'] ?? '')
                                    );
                                    ?>
                                    <option value="<?= escaparEstudiante($busDisponible['id_bus']) ?>">
                                        <?= escaparEstudiante($etiquetaBus) ?>
                                        <?= empty($busDisponible['estado']) ? '(Inactivo)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarEstadoEstudiante">Estado *</label>
                            <select class="form-select" id="editarEstadoEstudiante" name="estado" required>
                                <option value="true">Activo</option>
                                <option value="false">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <p class="form-text mb-0 mt-3">
                        No se pueden editar el identificador, el acudiente ni el código QR.
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="editarEstudianteBtn">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-light" id="cancelarEdicionEstudiante" hidden>Cancelar</button>
                <button type="submit" form="formEditarEstudiante" class="btn btn-success" id="guardarEstudianteBtn" hidden>
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div
    class="modal fade"
    id="modalEliminarEstudiante"
    tabindex="-1"
    aria-labelledby="tituloModalEliminarEstudiante"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="tituloModalEliminarEstudiante"
                >
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Eliminar estudiante
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar al estudiante
                <strong id="nombreEstudianteEliminar"></strong>?

                <p class="text-secondary small mt-2 mb-0">
                    Esta acción no se puede deshacer.
                </p>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal"
                >
                    Cancelar
                </button>

                <form method="POST" action="estudiantes.php">
                    <input type="hidden" name="accion" value="eliminar">

                    <input
                        type="hidden"
                        name="id_estudiante"
                        id="idEstudianteEliminar"
                    >

                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>
                        Sí, eliminar
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const modalDetalleEstudiante = document.getElementById('modalDetalleEstudiante');
    const datosDetalleEstudiante = document.getElementById('datosDetalleEstudiante');
    const formEditarEstudiante = document.getElementById('formEditarEstudiante');
    let registroEstudianteActual = null;

    const etiquetasEstudiante = {
        id_estudiante: 'Identificador',
        nombre: 'Primer nombre',
        segundo_nombre: 'Segundo nombre',
        apellido: 'Primer apellido',
        segundo_apellido: 'Segundo apellido',
        identificacion: 'Identificación',
        grado: 'Grado',
        id_acudiente: 'Identificador del acudiente',
        acudiente: 'Acudiente',
        id_bus: 'Identificador del bus',
        bus: 'Bus asignado',
        codigo_qr: 'Código QR',
        estado: 'Estado',
        fecha_registro: 'Fecha de registro'
    };

    function mostrarDetalleEstudiante(registro) {
        datosDetalleEstudiante.replaceChildren();

        Object.entries(registro).forEach(([campo, valor]) => {
            const columna = document.createElement('div');
            columna.className = 'col-md-6';

            const etiqueta = document.createElement('strong');
            etiqueta.className = 'd-block text-secondary small';
            etiqueta.textContent =
                etiquetasEstudiante[campo] || campo.replaceAll('_', ' ');

            const contenido = document.createElement('span');
            contenido.className = 'd-block';
            if (campo === 'estado') {
                contenido.textContent =
                    valor === true || valor === 'true' || valor === 1 || valor === '1'
                        ? 'Activo'
                        : 'Inactivo';
            } else if (campo === 'bus' && !valor) {
                contenido.textContent = 'Sin asignar';
            } else if (valor && typeof valor === 'object') {
                contenido.textContent =
                    Object.values(valor).filter(Boolean).join(' ') || 'Sin asignar';
            } else {
                contenido.textContent =
                    valor === null || valor === '' ? '—' : String(valor);
            }

            columna.append(etiqueta, contenido);
            datosDetalleEstudiante.append(columna);
        });
    }

    function cargarFormularioEstudiante(registro) {
        document.getElementById('editarIdEstudiante').value =
            registro.id_estudiante || '';
        document.getElementById('editarNombreEstudiante').value =
            registro.nombre || '';
        document.getElementById('editarSegundoNombreEstudiante').value =
            registro.segundo_nombre || '';
        document.getElementById('editarApellidoEstudiante').value =
            registro.apellido || '';
        document.getElementById('editarSegundoApellidoEstudiante').value =
            registro.segundo_apellido || '';
        document.getElementById('editarIdentificacionEstudiante').value =
            registro.identificacion || '';
        document.getElementById('editarGradoEstudiante').value =
            registro.grado || '';
        document.getElementById('editarBusEstudiante').value =
            registro.id_bus || registro.bus?.id_bus || '';
        document.getElementById('editarEstadoEstudiante').value =
            registro.estado === true || registro.estado === 'true' ||
            registro.estado === 1 || registro.estado === '1'
                ? 'true'
                : 'false';
    }

    function cambiarModoEdicionEstudiante(editar) {
        datosDetalleEstudiante.hidden = editar;
        formEditarEstudiante.hidden = !editar;
        document.getElementById('editarEstudianteBtn').hidden = editar;
        document.getElementById('cancelarEdicionEstudiante').hidden = !editar;
        document.getElementById('guardarEstudianteBtn').hidden = !editar;
    }

    if (modalDetalleEstudiante) {
        modalDetalleEstudiante.addEventListener('show.bs.modal', (evento) => {
            const fila = evento.relatedTarget;
            if (!fila) return;

            registroEstudianteActual = JSON.parse(fila.dataset.registro);
            document.getElementById('tituloModalDetalleEstudiante').textContent =
                `Estudiante: ${[
                    registroEstudianteActual.nombre,
                    registroEstudianteActual.segundo_nombre,
                    registroEstudianteActual.apellido,
                    registroEstudianteActual.segundo_apellido
                ].filter(Boolean).join(' ')}`;
            mostrarDetalleEstudiante(registroEstudianteActual);
            cargarFormularioEstudiante(registroEstudianteActual);
            cambiarModoEdicionEstudiante(false);
        });

        modalDetalleEstudiante.addEventListener('hidden.bs.modal', () => {
            cambiarModoEdicionEstudiante(false);
        });

        document.getElementById('editarEstudianteBtn').addEventListener('click', () => {
            cambiarModoEdicionEstudiante(true);
        });

        document.getElementById('cancelarEdicionEstudiante').addEventListener('click', () => {
            cargarFormularioEstudiante(registroEstudianteActual);
            cambiarModoEdicionEstudiante(false);
        });

        const abrirDetalleEstudiante = (fila) => {
            bootstrap.Modal.getOrCreateInstance(modalDetalleEstudiante).show(fila);
        };
        const tablaEstudiantes = document.getElementById('tablaEstudiantes');

        tablaEstudiantes.addEventListener('click', (evento) => {
            const fila = evento.target.closest('.fila-estudiante-detalle');
            if (!fila || evento.target.closest('button, a, input, select, textarea')) return;
            abrirDetalleEstudiante(fila);
        });

        tablaEstudiantes.addEventListener('keydown', (evento) => {
            if (
                evento.target.classList.contains('fila-estudiante-detalle') &&
                (evento.key === 'Enter' || evento.key === ' ')
            ) {
                evento.preventDefault();
                abrirDetalleEstudiante(evento.target);
            }
        });
    }

    // Menú lateral: conserva el comportamiento usado en las otras pantallas.
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebar && sidebarToggle && sidebarOverlay) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            sidebarOverlay.classList.toggle('overlay-visible');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('sidebar-open');
            sidebarOverlay.classList.remove('overlay-visible');
        });
    }

    // Modal de eliminación.
    const modalEliminarEstudiante = document.getElementById(
        'modalEliminarEstudiante'
    );

    if (modalEliminarEstudiante) {
        modalEliminarEstudiante.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;

            if (!boton) return;

            document.getElementById('idEstudianteEliminar').value =
                boton.dataset.id;

            document.getElementById('nombreEstudianteEliminar').textContent =
                boton.dataset.nombre;
        });
    }

    // Búsqueda y filtros combinados.
    const buscarEstudiante = document.getElementById('buscarEstudiante');
    const filtroGrado = document.getElementById('filtroGrado');
    const filtroBus = document.getElementById('filtroBus');
    const limpiarFiltros = document.getElementById(
        'limpiarFiltrosEstudiantes'
    );

    const filasEstudiantes = [
        ...document.querySelectorAll('#tablaEstudiantes tr')
    ];

    const sinResultados = document.getElementById(
        'sinResultadosEstudiantes'
    );

    function filtrarEstudiantes() {
        const texto = buscarEstudiante.value
            .toLocaleLowerCase()
            .trim();

        const gradoSeleccionado = filtroGrado.value;
        const busSeleccionado = filtroBus.value;

        let visibles = 0;
        let hayFilasDeDatos = false;

        filasEstudiantes.forEach((fila) => {
            // Ignorar la fila informativa cuando no hay registros.
            if (fila.querySelector('td[colspan]')) {
                return;
            }

            hayFilasDeDatos = true;

            const contenido = fila.textContent.toLocaleLowerCase();
            const coincideTexto = contenido.includes(texto);
            const coincideGrado =
                gradoSeleccionado === '' ||
                fila.dataset.grado === gradoSeleccionado;

            const coincideBus =
                busSeleccionado === '' ||
                fila.dataset.bus === busSeleccionado;

            const mostrar =
                coincideTexto && coincideGrado && coincideBus;

            fila.style.display = mostrar ? '' : 'none';

            if (mostrar) {
                visibles++;
            }
        });

        if (sinResultados) {
            sinResultados.classList.toggle(
                'd-none',
                !hayFilasDeDatos || visibles > 0
            );
        }
    }

    buscarEstudiante.addEventListener('input', filtrarEstudiantes);
    filtroGrado.addEventListener('change', filtrarEstudiantes);
    filtroBus.addEventListener('change', filtrarEstudiantes);

    limpiarFiltros.addEventListener('click', () => {
        buscarEstudiante.value = '';
        filtroGrado.value = '';
        filtroBus.value = '';
        filtrarEstudiantes();
    });
</script>

</body>
</html>