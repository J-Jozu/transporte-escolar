
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

require_once __DIR__ . '/../controllers/ConductoresController.php';

function escaparConductor($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function nombreCompletoConductor(array $conductor): string
{
    $partes = [
        $conductor['nombre'] ?? '',
        $conductor['segundo_nombre'] ?? '',
        $conductor['apellido'] ?? '',
        $conductor['segundo_apellido'] ?? ''
    ];

    return trim(implode(' ', array_filter(
        $partes,
        static fn($parte) => trim((string) $parte) !== ''
    )));
}

$mensaje = $_SESSION['mensaje_conductor'] ?? '';
$tipoMensaje = $_SESSION['tipo_mensaje_conductor'] ?? '';

unset(
    $_SESSION['mensaje_conductor'],
    $_SESSION['tipo_mensaje_conductor']
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'actualizar') {
        try {
            actualizarConductor(
                (string) ($_POST['id_conductor'] ?? ''),
                $_POST
            );

            $_SESSION['mensaje_conductor'] =
                'Conductor actualizado correctamente.';
            $_SESSION['tipo_mensaje_conductor'] = 'exito';
        } catch (InvalidArgumentException $e) {
            $_SESSION['mensaje_conductor'] = $e->getMessage();
            $_SESSION['tipo_mensaje_conductor'] = 'error';
        } catch (Throwable $e) {
            error_log('Error al actualizar conductor: ' . $e->getMessage());

            $_SESSION['mensaje_conductor'] =
                'No fue posible actualizar el conductor. Verifica los datos e inténtalo nuevamente.';
            $_SESSION['tipo_mensaje_conductor'] = 'error';
        }

        header('Location: conductores.php');
        exit;
    }

    if (($_POST['accion'] ?? '') === 'eliminar') {
        try {
            eliminarConductor(
                (string) ($_POST['id_conductor'] ?? '')
            );

            $_SESSION['mensaje_conductor'] =
                'Conductor eliminado correctamente.';
            $_SESSION['tipo_mensaje_conductor'] = 'exito';
        } catch (Throwable $e) {
            error_log('Error al eliminar conductor: ' . $e->getMessage());

            $_SESSION['mensaje_conductor'] =
                'No se pudo eliminar el conductor. Comprueba si tiene otros registros relacionados e inténtalo nuevamente.';
            $_SESSION['tipo_mensaje_conductor'] = 'error';
        }

        header('Location: conductores.php');
        exit;
    }

    try {
        registrarConductor($_POST);

        $_SESSION['mensaje_conductor'] = 'Conductor registrado correctamente.';
        $_SESSION['tipo_mensaje_conductor'] = 'exito';
    } catch (InvalidArgumentException $e) {
        $_SESSION['mensaje_conductor'] = $e->getMessage();
        $_SESSION['tipo_mensaje_conductor'] = 'error';
    } catch (Throwable $e) {
        error_log('Error al registrar conductor: ' . $e->getMessage());

        $_SESSION['mensaje_conductor'] =
            'No fue posible registrar el conductor. Verifica los datos e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje_conductor'] = 'error';
    }

    header('Location: conductores.php');
    exit;
}

$errorCarga = false;
$conductoresAsignados = [];

try {
    $conductores = listarConductores();
    $conductoresAsignados = listarIdsConductoresAsignados();

    if (!is_array($conductores)) {
        $conductores = [];
    }
} catch (Throwable $e) {
    error_log('Error al listar conductores: ' . $e->getMessage());
    $conductores = [];
    $errorCarga = true;
}

$totalConductores = count($conductores);
$conductoresActivos = 0;
$conductoresInactivos = 0;

foreach ($conductores as $conductor) {
    if (($conductor['estado'] ?? false) === true ||
        ($conductor['estado'] ?? '') === 'true' ||
        ($conductor['estado'] ?? '') === 1 ||
        ($conductor['estado'] ?? '') === '1') {
        $conductoresActivos++;
    } else {
        $conductoresInactivos++;
    }
}

$nombreUsuario = trim(
    ($_SESSION['nombre'] ?? '') . ' ' .
    ($_SESSION['apellido'] ?? '')
);

if ($nombreUsuario === '') {
    $nombreUsuario = 'Administrador';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Conductores | MIRUTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/conductores.css">
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

            <a href="estudiantes.php" class="nav-link">
                <i class="bi bi-people-fill"></i>
                <span>Estudiantes</span>
            </a>

            <a href="conductores.php" class="nav-link active" aria-current="page">
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

    <div class="main-area">

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
                    <strong><?= escaparConductor($nombreUsuario) ?></strong>
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

        <main class="dashboard-content conductores-content">

            <div class="page-heading conductores-heading">
                <div>
                    <h2>Gestión de conductores</h2>
                    <p>Registra y consulta los conductores del transporte escolar.</p>
                </div>

                <span class="current-date">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d/m/Y') ?>
                </span>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div
                    class="alert <?= $tipoMensaje === 'exito' ? 'alert-success' : 'alert-danger' ?>"
                    role="alert"
                >
                    <?= escaparConductor($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorCarga): ?>
                <div class="alert alert-warning" role="alert">
                    No fue posible cargar la lista de conductores.
                    Inténtalo nuevamente más tarde.
                </div>
            <?php endif; ?>

            <section class="conductores-resumen">
                <article class="conductor-stat">
                    <div class="conductor-stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <span>Total de conductores</span>
                        <strong><?= number_format($totalConductores) ?></strong>
                    </div>
                </article>

                <article class="conductor-stat">
                    <div class="conductor-stat-icon activo">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <span>Conductores activos</span>
                        <strong><?= number_format($conductoresActivos) ?></strong>
                    </div>
                </article>

                <article class="conductor-stat">
                    <div class="conductor-stat-icon inactivo">
                        <i class="bi bi-person-x-fill"></i>
                    </div>
                    <div>
                        <span>Conductores inactivos</span>
                        <strong><?= number_format($conductoresInactivos) ?></strong>
                    </div>
                </article>
            </section>

            <section class="conductores-layout">

                <!-- Formulario: aproximadamente 25 % -->
                <article class="content-panel conductor-form-panel">
                    <div class="panel-heading">
                        <div class="panel-title-icon blue-title-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <div>
                            <h3>Registrar conductor</h3>
                            <p>Completa los datos del conductor.</p>
                        </div>
                    </div>

                    <form
                        action="conductores.php"
                        method="POST"
                        class="conductor-form"
                    >
                        <div class="conductor-field">
                            <label for="nombre">Primer nombre *</label>
                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                minlength="2"
                                maxlength="30"
                                pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{2,30}"
                                title="Escribe entre 2 y 30 letras. Se permiten tildes, ñ y espacios."
                                autocomplete="given-name"
                                required
                            >
                        </div>

                        <div class="conductor-field">
                            <label for="segundo_nombre">Segundo nombre</label>
                            <input
                                type="text"
                                id="segundo_nombre"
                                name="segundo_nombre"
                                minlength="2"
                                maxlength="30"
                                pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{2,30}"
                                title="Si lo completas, escribe entre 2 y 30 letras."
                            >
                        </div>

                        <div class="conductor-field">
                            <label for="apellido">Primer apellido *</label>
                            <input
                                type="text"
                                id="apellido"
                                name="apellido"
                                minlength="2"
                                maxlength="30"
                                pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{2,30}"
                                title="Escribe entre 2 y 30 letras. Se permiten tildes, ñ y espacios."
                                autocomplete="family-name"
                                required
                            >
                        </div>

                        <div class="conductor-field">
                            <label for="segundo_apellido">Segundo apellido</label>
                            <input
                                type="text"
                                id="segundo_apellido"
                                name="segundo_apellido"
                                minlength="2"
                                maxlength="30"
                                pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{2,30}"
                                title="Si lo completas, escribe entre 2 y 30 letras."
                            >
                        </div>
                            
                        <div class="conductor-field">
                            <label for="numero_licencia">N.º de licencia *</label>
                            <input
                                type="text"
                                id="numero_licencia"
                                name="numero_licencia"
                                minlength="12"
                                maxlength="13"
                                pattern="[A-Za-z0-9-]{12,13}"
                                title="La licencia debe tener entre 12 y 13 caracteres. Solo letras, números y guiones."
                                required
                            >
                        </div>

                        <div class="conductor-field">
                            <label for="telefono">Teléfono *</label>
                            <input
                                type="tel"
                                id="telefono"
                                name="telefono"
                                minlength="8"
                                maxlength="8"
                                pattern="[0-9]{8}"
                                inputmode="numeric"
                                title="El teléfono debe contener exactamente 8 dígitos."
                                autocomplete="tel"
                                required
                            >
                        </div>

                        <div class="conductor-field">
                            <label for="tipo_sangre">Tipo de sangre</label>
                            <select id="tipo_sangre" name="tipo_sangre">
                                <option value="">Seleccionar</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>

                        <div class="conductor-field">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado" required>
                                <option value="true" selected>Activo</option>
                                <option value="false">Inactivo</option>
                            </select>
                        </div>

                        <div class="conductor-form-actions">
                            <button type="reset" class="btn btn-light">
                                Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus me-1"></i>
                                Registrar
                            </button>
                        </div>
                    </form>
                </article>

                <!-- Lista: aproximadamente 75 % -->
                <article class="content-panel conductor-list-panel">
                    <div class="panel-heading conductor-list-heading">
                        <div class="panel-heading-title">
                            <div class="panel-title-icon blue-title-icon">
                                <i class="bi bi-card-list"></i>
                            </div>
                            <div>
                                <h3>Conductores registrados</h3>
                                <p>Consulta los datos de los conductores.</p>
                            </div>
                        </div>

                        <div class="conductor-search">
                            <i class="bi bi-search"></i>
                            <input
                                type="search"
                                id="buscarConductor"
                                placeholder="Buscar conductor..."
                                aria-label="Buscar conductor"
                            >
                        </div>
                    </div>

                    <div class="table-responsive conductor-table-wrap">
                        <table class="table dashboard-table conductor-table">
                            <thead>
                                <tr>
                                    <th>Nombre completo</th>
                                    <th>Licencia</th>
                                    <th>Teléfono</th>
                                    <th>Tipo de sangre</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="listaConductores">
                                <?php if (empty($conductores)): ?>
                                    <tr id="filaSinConductores">
                                        <td colspan="6" class="text-center py-4">
                                            No hay conductores registrados.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($conductores as $conductor): ?>
                                        <?php
                                        $activo = (
                                            ($conductor['estado'] ?? false) === true ||
                                            ($conductor['estado'] ?? '') === 'true' ||
                                            ($conductor['estado'] ?? '') === 1 ||
                                            ($conductor['estado'] ?? '') === '1'
                                        );
                                        ?>
                                        <tr
                                            class="fila-conductor fila-conductor-detalle"
                                            tabindex="0"
                                            role="button"
                                            aria-label="Ver detalles de <?= escaparConductor(nombreCompletoConductor($conductor)) ?>"
                                            data-registro="<?= escaparConductor(
                                                json_encode(
                                                    $conductor,
                                                    JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE
                                                ) ?: '{}'
                                            ) ?>"
                                        >
                                            <td>
                                                <strong>
                                                    <?= escaparConductor(
                                                        nombreCompletoConductor($conductor)
                                                    ) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?= escaparConductor(
                                                    $conductor['numero_licencia'] ?? '—'
                                                ) ?>
                                            </td>
                                            <td>
                                                <?= escaparConductor(
                                                    $conductor['telefono'] ?? '—'
                                                ) ?>
                                            </td>
                                            <td>
                                                <?= escaparConductor(
                                                    $conductor['tipo_sangre'] ?? '—'
                                                ) ?>
                                            </td>
                                            <td>
                                                <span class="conductor-estado <?= $activo ? 'estado-activo' : 'estado-inactivo' ?>">
                                                    <?= $activo ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger btn-eliminar-registro"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEliminarConductor"
                                                        data-id="<?= escaparConductor($conductor['id_conductor']) ?>"
                                                        data-nombre="<?= escaparConductor(
                                                            nombreCompletoConductor($conductor)
                                                        ) ?>"
                                                        data-asignado="<?= in_array(
                                                            (string) $conductor['id_conductor'],
                                                            $conductoresAsignados,
                                                            true
                                                        ) ? 'true' : 'false' ?>"
                                                        title="Eliminar conductor"
                                                        aria-label="Eliminar conductor"
                                                    >
                                                        <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr id="filaSinResultados" hidden>
                                        <td colspan="6" class="text-center py-4">
                                            No se encontraron conductores con esa búsqueda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="conductor-list-footer">
                        <span>
                            Registros: <strong id="totalVisible"><?= $totalConductores ?></strong>
                        </span>
                    </div>
                </article>

            </section>

            <footer class="dashboard-footer">
                Sistema de Gestión y Seguimiento del Transporte Escolar
            </footer>

        </main>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div
    class="modal fade"
    id="modalDetalleConductor"
    tabindex="-1"
    aria-labelledby="tituloModalDetalleConductor"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalDetalleConductor">
                    Detalle del conductor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="datosDetalleConductor" class="row g-3"></div>
                <form id="formEditarConductor" action="conductores.php" method="POST" hidden>
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id_conductor" id="editarIdConductor">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="editarNombre">Primer nombre *</label>
                            <input class="form-control" id="editarNombre" name="nombre" minlength="2" maxlength="30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarSegundoNombre">Segundo nombre</label>
                            <input class="form-control" id="editarSegundoNombre" name="segundo_nombre" minlength="2" maxlength="30">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarApellido">Primer apellido *</label>
                            <input class="form-control" id="editarApellido" name="apellido" minlength="2" maxlength="30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarSegundoApellido">Segundo apellido</label>
                            <input class="form-control" id="editarSegundoApellido" name="segundo_apellido" minlength="2" maxlength="30">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarLicencia">N.º de licencia *</label>
                            <input class="form-control" id="editarLicencia" name="numero_licencia" minlength="12" maxlength="13" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarTelefono">Teléfono *</label>
                            <input class="form-control" id="editarTelefono" name="telefono" inputmode="numeric" minlength="8" maxlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarTipoSangre">Tipo de sangre</label>
                            <select class="form-select" id="editarTipoSangre" name="tipo_sangre">
                                <option value="">Sin especificar</option>
                                <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                                <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarEstadoConductor">Estado</label>
                            <select class="form-select" id="editarEstadoConductor" name="estado" required>
                                <option value="true">Activo</option>
                                <option value="false">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <p class="form-text mb-0 mt-3">
                        El identificador del conductor y el usuario relacionado no se pueden editar aquí.
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="editarConductorBtn">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-light" id="cancelarEdicionConductor" hidden>Cancelar</button>
                <button type="submit" form="formEditarConductor" class="btn btn-success" id="guardarConductorBtn" hidden>
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<div
    class="modal fade"
    id="modalEliminarConductor"
    tabindex="-1"
    aria-labelledby="tituloModalEliminarConductor"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalEliminarConductor">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Eliminar conductor
                </h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                <p id="mensajeEliminarConductor" class="mb-0">
                    ¿Estás seguro de que deseas eliminar a
                    <strong id="nombreConductorEliminar"></strong>?
                </p>
                <p
                    id="avisoConductorAsignado"
                    class="text-warning small mt-2 mb-0"
                    hidden
                >
                    <strong id="nombreConductorAsignado"></strong> está asignado a un bus.
                    Si continúas, el bus
                    quedará como «Sin asignar» y luego se eliminará el conductor.
                    ¿Deseas continuar?
                </p>
                <p id="avisoEliminarSinAsignacion" class="text-secondary small mt-2 mb-0">
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

                <form method="POST" action="conductores.php">
                    <input type="hidden" name="accion" value="eliminar">
                    <input
                        type="hidden"
                        name="id_conductor"
                        id="idConductorEliminar"
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
const sidebar = document.getElementById('sidebar');
const menuToggle = document.getElementById('menuToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (sidebar && menuToggle && sidebarOverlay) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-open');
        sidebarOverlay.classList.toggle('overlay-visible');
    });

    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('sidebar-open');
        sidebarOverlay.classList.remove('overlay-visible');
    });
}

const buscarConductor = document.getElementById('buscarConductor');
const filasConductores = Array.from(
    document.querySelectorAll('.fila-conductor')
);
const filaSinResultados = document.getElementById('filaSinResultados');
const totalVisible = document.getElementById('totalVisible');

function filtrarConductores() {
    const busqueda = buscarConductor.value.trim().toLocaleLowerCase('es');
    let visibles = 0;

    filasConductores.forEach((fila) => {
        const coincide = fila.textContent
            .toLocaleLowerCase('es')
            .includes(busqueda);

        fila.hidden = !coincide;

        if (coincide) {
            visibles++;
        }
    });

    if (filaSinResultados) {
        filaSinResultados.hidden = visibles !== 0;
    }

    totalVisible.textContent = visibles;
}

buscarConductor.addEventListener('input', filtrarConductores);

const modalDetalleConductor = document.getElementById('modalDetalleConductor');
const datosDetalleConductor = document.getElementById('datosDetalleConductor');
const formEditarConductor = document.getElementById('formEditarConductor');
let registroConductorActual = null;

const etiquetasConductor = {
    id_conductor: 'Identificador',
    id_usuario: 'Usuario relacionado',
    nombre: 'Primer nombre',
    segundo_nombre: 'Segundo nombre',
    apellido: 'Primer apellido',
    segundo_apellido: 'Segundo apellido',
    numero_licencia: 'N.º de licencia',
    telefono: 'Teléfono',
    tipo_sangre: 'Tipo de sangre',
    estado: 'Estado',
    fecha_creacion: 'Fecha de creación'
};

function mostrarDetalleConductor(registro) {
    datosDetalleConductor.replaceChildren();

    Object.entries(registro).forEach(([campo, valor]) => {
        const columna = document.createElement('div');
        columna.className = 'col-md-6';

        const etiqueta = document.createElement('strong');
        etiqueta.className = 'd-block text-secondary small';
        etiqueta.textContent = etiquetasConductor[campo] ||
            campo.replaceAll('_', ' ');

        const contenido = document.createElement('span');
        contenido.className = 'd-block';
        if (campo === 'estado') {
            contenido.textContent =
                valor === true || valor === 'true' || valor === 1 || valor === '1'
                    ? 'Activo'
                    : 'Inactivo';
        } else if (valor && typeof valor === 'object') {
            contenido.textContent = Object.values(valor).filter(Boolean).join(' ') || '—';
        } else {
            contenido.textContent = valor === null || valor === '' ? '—' : String(valor);
        }

        columna.append(etiqueta, contenido);
        datosDetalleConductor.append(columna);
    });
}

function cargarFormularioConductor(registro) {
    document.getElementById('editarIdConductor').value = registro.id_conductor || '';
    document.getElementById('editarNombre').value = registro.nombre || '';
    document.getElementById('editarSegundoNombre').value = registro.segundo_nombre || '';
    document.getElementById('editarApellido').value = registro.apellido || '';
    document.getElementById('editarSegundoApellido').value = registro.segundo_apellido || '';
    document.getElementById('editarLicencia').value = registro.numero_licencia || '';
    document.getElementById('editarTelefono').value = registro.telefono || '';
    document.getElementById('editarTipoSangre').value = registro.tipo_sangre || '';
    document.getElementById('editarEstadoConductor').value =
        registro.estado === true || registro.estado === 'true' ||
        registro.estado === 1 || registro.estado === '1' ? 'true' : 'false';
}

function cambiarModoEdicionConductor(editar) {
    datosDetalleConductor.hidden = editar;
    formEditarConductor.hidden = !editar;
    document.getElementById('editarConductorBtn').hidden = editar;
    document.getElementById('cancelarEdicionConductor').hidden = !editar;
    document.getElementById('guardarConductorBtn').hidden = !editar;
}

if (modalDetalleConductor) {
    modalDetalleConductor.addEventListener('show.bs.modal', (evento) => {
        const fila = evento.relatedTarget;
        if (!fila) return;

        registroConductorActual = JSON.parse(fila.dataset.registro);
        document.getElementById('tituloModalDetalleConductor').textContent =
            `Conductor: ${[
                registroConductorActual.nombre,
                registroConductorActual.segundo_nombre,
                registroConductorActual.apellido,
                registroConductorActual.segundo_apellido
            ].filter(Boolean).join(' ')}`;
        mostrarDetalleConductor(registroConductorActual);
        cargarFormularioConductor(registroConductorActual);
        cambiarModoEdicionConductor(false);
    });

    modalDetalleConductor.addEventListener('hidden.bs.modal', () => {
        cambiarModoEdicionConductor(false);
    });

    document.getElementById('editarConductorBtn').addEventListener('click', () => {
        cambiarModoEdicionConductor(true);
    });

    document.getElementById('cancelarEdicionConductor').addEventListener('click', () => {
        cargarFormularioConductor(registroConductorActual);
        cambiarModoEdicionConductor(false);
    });

    const abrirDetalleConductor = (fila) => {
        bootstrap.Modal.getOrCreateInstance(modalDetalleConductor).show(fila);
    };

    document.getElementById('listaConductores').addEventListener('click', (evento) => {
        const fila = evento.target.closest('.fila-conductor-detalle');
        if (!fila || evento.target.closest('button, a, input, select, textarea')) return;
        abrirDetalleConductor(fila);
    });

    document.getElementById('listaConductores').addEventListener('keydown', (evento) => {
        if (
            evento.target.classList.contains('fila-conductor-detalle') &&
            (evento.key === 'Enter' || evento.key === ' ')
        ) {
            evento.preventDefault();
            abrirDetalleConductor(evento.target);
        }
    });
}

const formularioConductor = document.querySelector('.conductor-form');

if (formularioConductor) {
    const camposNombre = [
        'nombre',
        'segundo_nombre',
        'apellido',
        'segundo_apellido'
    ];

    // Nombres y apellidos: solo letras y espacios, máximo 30.
    camposNombre.forEach((id) => {
        const campo = document.getElementById(id);
        if (!campo) return;

        campo.maxLength = 30;

        campo.addEventListener('input', () => {
            campo.value = campo.value
                .replace(/[^\p{L} ]/gu, '')
                .slice(0, 30);
        });

        campo.addEventListener('keydown', (evento) => {
            if (evento.ctrlKey || evento.metaKey || evento.altKey) return;

            if (
                evento.key.length === 1 &&
                !/^[\p{L} ]$/u.test(evento.key)
            ) {
                evento.preventDefault();
            }
        });
    });

    // Licencia: solo letras, números y guiones; máximo 13.
    const licencia = document.getElementById('numero_licencia');

    if (licencia) {
        licencia.maxLength = 13;

        licencia.addEventListener('input', () => {
            licencia.value = licencia.value
                .replace(/[^a-zA-Z0-9-]/g, '')
                .slice(0, 13);
        });

        licencia.addEventListener('keydown', (evento) => {
            if (evento.ctrlKey || evento.metaKey || evento.altKey) return;

            if (
                evento.key.length === 1 &&
                !/^[a-zA-Z0-9-]$/.test(evento.key)
            ) {
                evento.preventDefault();
            }
        });
    }

    // Teléfono: solo números; máximo 8.
    const telefono = document.getElementById('telefono');

    if (telefono) {
        telefono.maxLength = 8;
        telefono.inputMode = 'numeric';

        telefono.addEventListener('input', () => {
            telefono.value = telefono.value
                .replace(/[^0-9]/g, '')
                .slice(0, 8);
        });

        telefono.addEventListener('keydown', (evento) => {
            if (evento.ctrlKey || evento.metaKey || evento.altKey) return;

            if (
                evento.key.length === 1 &&
                !/^[0-9]$/.test(evento.key)
            ) {
                evento.preventDefault();
            }
        });
    }

    // Validar longitudes mínimas y campos obligatorios al enviar.
    formularioConductor.addEventListener('submit', (evento) => {
        const requeridos = [
            { id: 'nombre', min: 2, max: 30, nombre: 'El nombre' },
            { id: 'apellido', min: 2, max: 30, nombre: 'El apellido' },
            { id: 'numero_licencia', min: 12, max: 13, nombre: 'La licencia' },
            { id: 'telefono', min: 8, max: 8, nombre: 'El teléfono' }
        ];

        for (const regla of requeridos) {
            const campo = document.getElementById(regla.id);
            const valor = campo.value.trim();

            if (valor.length < regla.min || valor.length > regla.max) {
                evento.preventDefault();
                campo.setCustomValidity(
                    `${regla.nombre}: debe tener entre ${regla.min} y ${regla.max} caracteres.`
                );
                campo.reportValidity();
                campo.addEventListener('input', () => {
                    campo.setCustomValidity('');
                }, { once: true });
                campo.focus();
                return;
            }
        }
    });

}

const modalConductor = document.getElementById('modalEliminarConductor');

if (modalConductor) {
    modalConductor.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (!boton) return;

        document.getElementById('idConductorEliminar').value =
            boton.dataset.id;
        document.getElementById('nombreConductorEliminar').textContent =
            boton.dataset.nombre;
        document.getElementById('nombreConductorAsignado').textContent =
            boton.dataset.nombre;

        const conductorAsignado = boton.dataset.asignado === 'true';
        document.getElementById('mensajeEliminarConductor').hidden =
            conductorAsignado;
        document.getElementById('avisoConductorAsignado').hidden =
            !conductorAsignado;
        document.getElementById('avisoEliminarSinAsignacion').hidden =
            conductorAsignado;
    });
}

</script>

</body>
</html>
