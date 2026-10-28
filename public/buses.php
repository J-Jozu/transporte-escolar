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

require_once __DIR__ . '/../controllers/BusesController.php';

function escaparBus($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function nombreCompletoBus(?array $conductor): string
{
    if (!$conductor) {
        return 'Sin asignar';
    }

    $nombre = trim(
        ($conductor['nombre'] ?? '') . ' ' .
        ($conductor['apellido'] ?? '')
    );

    return $nombre !== '' ? $nombre : 'Sin asignar';
}

$mensaje = $_SESSION['mensaje_bus'] ?? '';
$tipoMensaje = $_SESSION['tipo_mensaje_bus'] ?? 'success';

unset($_SESSION['mensaje_bus'], $_SESSION['tipo_mensaje_bus']);

// Registrar bus y redirigir para evitar envíos duplicados.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['accion'] ?? '') === 'eliminar') {
            eliminarBus((string) ($_POST['id_bus'] ?? ''));

            $_SESSION['mensaje_bus'] = 'Bus eliminado correctamente.';
            $_SESSION['tipo_mensaje_bus'] = 'success';
        } elseif (($_POST['accion'] ?? '') === 'actualizar') {
            actualizarBus(
                (string) ($_POST['id_bus'] ?? ''),
                $_POST
            );

            $_SESSION['mensaje_bus'] = 'Bus actualizado correctamente.';
            $_SESSION['tipo_mensaje_bus'] = 'success';
        } else {
            registrarBus($_POST);

            $_SESSION['mensaje_bus'] = 'Bus registrado correctamente.';
            $_SESSION['tipo_mensaje_bus'] = 'success';
        }
    } catch (InvalidArgumentException $e) {
        $_SESSION['mensaje_bus'] = $e->getMessage();
        $_SESSION['tipo_mensaje_bus'] = 'danger';
    } catch (Throwable $e) {
        error_log('Error en gestión de buses: ' . $e->getMessage());

        $_SESSION['mensaje_bus'] =
            'No se pudo completar la operación. Verifica los datos e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje_bus'] = 'danger';
    }

    header('Location: buses.php');
    exit;
}

$buses = [];
$conductoresDisponibles = [];
$conteoEstudiantesPorBus = [];
$errorCarga = '';

try {
    $buses = listarBuses();
    $conductoresDisponibles = listarConductoresDisponibles();
    $conteoEstudiantesPorBus = listarConteoEstudiantesPorBus();
} catch (Throwable $e) {
    error_log('Error al cargar gestión de buses: ' . $e->getMessage());
    $errorCarga = 'No se pudieron cargar los datos. Recarga la página o inténtalo más tarde.';
}

$totalBuses = count($buses);
$busesActivos = count(array_filter(
    $buses,
    fn($bus) => !empty($bus['estado'])
));
$busesInactivos = $totalBuses - $busesActivos;

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

    <title>Gestión de Buses | MIRUTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/buses.css">
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

            <a href="conductores.php" class="nav-link">
                <i class="bi bi-person-badge-fill"></i>
                <span>Conductores</span>
            </a>

            <a href="buses.php" class="nav-link active" aria-current="page">
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
                    <strong><?= escaparBus($nombreUsuario) ?></strong>
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

        <section class="dashboard-content buses-content">

            <div class="page-heading">
                <div>
                    <h2>Gestión de Buses</h2>
                    <p>Registra los buses y administra sus conductores asignados.</p>
                </div>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-<?= escaparBus($tipoMensaje) ?> alert-dismissible fade show"
                     role="alert">
                    <?= escaparBus($mensaje) ?>
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
                    <?= escaparBus($errorCarga) ?>
                </div>
            <?php endif; ?>

            <div class="buses-summary">
                <article class="bus-summary-card">
                    <div class="bus-summary-icon">
                        <i class="bi bi-bus-front"></i>
                    </div>
                    <div>
                        <span>Total de buses</span>
                        <strong><?= $totalBuses ?></strong>
                    </div>
                </article>

                <article class="bus-summary-card">
                    <div class="bus-summary-icon active-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <span>Buses activos</span>
                        <strong><?= $busesActivos ?></strong>
                    </div>
                </article>

                <article class="bus-summary-card">
                    <div class="bus-summary-icon inactive-icon">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <span>Buses inactivos</span>
                        <strong><?= $busesInactivos ?></strong>
                    </div>
                </article>
            </div>

            <!-- Formulario -->
            <section class="bus-panel">
                <div class="bus-panel-heading">
                    <div>
                        <h5><i class="bi bi-plus-circle"></i> Registrar bus</h5>
                        <p>Completa los datos del vehículo.</p>
                    </div>
                </div>

                <form method="POST" action="buses.php" class="bus-form">
                    <div class="bus-field">
                        <label for="placa">Placa *</label>
                        <input
                            type="text"
                            id="placa"
                            name="placa"
                            class="form-control"
                            maxlength="8"
                            required
                            autocomplete="off"
                            placeholder="Ej. ABC123"
                        >
                    </div>

                    <div class="bus-field">
                        <label for="marca">Marca</label>
                        <input
                            type="text"
                            id="marca"
                            name="marca"
                            class="form-control"
                            maxlength="20"
                            placeholder="Ej. Toyota"
                        >
                    </div>

                    <div class="bus-field">
                        <label for="modelo">Modelo</label>
                        <input
                            type="text"
                            id="modelo"
                            name="modelo"
                            class="form-control"
                            maxlength="20"
                            placeholder="Ej. Hiace"
                        >
                    </div>

                    <div class="bus-field">
                        <label for="capacidad">Capacidad *</label>
                        <input
                            type="number"
                            id="capacidad"
                            name="capacidad"
                            class="form-control"
                            min="1"
                            max="20"
                            step="1"
                            required
                            placeholder="Ej. 15"
                        >
                    </div>

                    <div class="bus-field bus-conductor-field">
                        <label for="id_conductor">Conductor</label>
                        <select
                            id="id_conductor"
                            name="id_conductor"
                            class="form-select"
                        >
                            <option value="">Sin asignar</option>

                            <?php foreach ($conductoresDisponibles as $conductor): ?>
                                <option value="<?= escaparBus($conductor['id_conductor']) ?>">
                                    <?= escaparBus(
                                        trim(
                                            $conductor['nombre'] . ' ' .
                                            $conductor['apellido']
                                        )
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (empty($conductoresDisponibles)): ?>
                            <small class="text-muted">
                                No hay conductores disponibles para asignar.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="bus-field">
                        <label for="estado">Estado *</label>
                        <select
                            id="estado"
                            name="estado"
                            class="form-select"
                            required
                        >
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="bus-form-actions">
                        <button type="reset" class="btn btn-light">
                            Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Registrar bus
                        </button>
                    </div>
                </form>
            </section>

            <!-- Tabla de buses -->
            <section class="bus-panel bus-list-panel">
                <div class="bus-panel-heading bus-list-heading">
                    <div>
                        <h5><i class="bi bi-list-ul"></i> Buses registrados</h5>
                        <p>Consulta la información y asignación de cada vehículo.</p>
                    </div>

                    <div class="bus-search">
                        <i class="bi bi-search"></i>
                        <input
                            type="search"
                            id="buscarBus"
                            class="form-control"
                            placeholder="Buscar bus..."
                            aria-label="Buscar bus"
                        >
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table bus-table align-middle">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Capacidad</th>
                                <th>Conductor asignado</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="tablaBuses">
                        <?php if (empty($buses)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    No hay buses registrados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($buses as $bus): ?>
                                <?php
                                    $conductor = $bus['conductor'] ?? null;
                                    $conductorNombre = nombreCompletoBus(
                                        is_array($conductor) ? $conductor : null
                                    );
                                    $activo = !empty($bus['estado']);
                                ?>
                                <tr
                                    class="fila-bus-detalle"
                                    tabindex="0"
                                    role="button"
                                    aria-label="Ver detalles del bus <?= escaparBus($bus['placa']) ?>"
                                    data-registro="<?= escaparBus(
                                        json_encode(
                                            $bus,
                                            JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE
                                        ) ?: '{}'
                                    ) ?>"
                                >
                                    <td>
                                        <strong><?= escaparBus($bus['placa']) ?></strong>
                                    </td>
                                    <td><?= escaparBus($bus['marca'] ?? '—') ?></td>
                                    <td><?= escaparBus($bus['modelo'] ?? '—') ?></td>
                                    <td><?= escaparBus($bus['capacidad']) ?></td>
                                    <td>
                                        <?php if ($conductorNombre === 'Sin asignar'): ?>
                                            <span class="bus-unassigned">
                                                <i class="bi bi-person-dash"></i>
                                                Sin asignar
                                            </span>
                                        <?php else: ?>
                                            <?= escaparBus($conductorNombre) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($activo): ?>
                                            <span class="bus-status status-active">
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="bus-status status-inactive">
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger btn-eliminar-registro"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEliminarBus"
                                            data-id="<?= escaparBus($bus['id_bus']) ?>"
                                            data-nombre="<?= escaparBus($bus['placa']) ?>"
                                            data-estudiantes-asignados="<?= (int) ($conteoEstudiantesPorBus[$bus['id_bus']] ?? 0) ?>"
                                            title="Eliminar bus"
                                            aria-label="Eliminar bus"
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

                <div id="sinResultadosBuses" class="text-center py-3 d-none">
                    No se encontraron buses con esa búsqueda.
                </div>
            </section>

        </section>
    </main>
</div>

<div
    class="modal fade"
    id="modalDetalleBus"
    tabindex="-1"
    aria-labelledby="tituloModalDetalleBus"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalDetalleBus">Detalle del bus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="datosDetalleBus" class="row g-3"></div>
                <form id="formEditarBus" action="buses.php" method="POST" hidden>
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id_bus" id="editarIdBus">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="editarPlacaBus">Placa *</label>
                            <input class="form-control text-uppercase" id="editarPlacaBus" name="placa" maxlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarMarcaBus">Marca</label>
                            <input class="form-control" id="editarMarcaBus" name="marca" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarModeloBus">Modelo</label>
                            <input class="form-control" id="editarModeloBus" name="modelo" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarCapacidadBus">Capacidad *</label>
                            <input class="form-control" type="number" id="editarCapacidadBus" name="capacidad" min="1" step="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editarEstadoBus">Estado *</label>
                            <select class="form-select" id="editarEstadoBus" name="estado" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <p class="form-text mb-0 mt-3">
                        El identificador del bus y el conductor asignado no se pueden editar aquí.
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="editarBusBtn">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
                <button type="button" class="btn btn-light" id="cancelarEdicionBus" hidden>Cancelar</button>
                <button type="submit" form="formEditarBus" class="btn btn-success" id="guardarBusBtn" hidden>
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<div
    class="modal fade"
    id="modalEliminarBus"
    tabindex="-1"
    aria-labelledby="tituloModalEliminarBus"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalEliminarBus">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Eliminar bus
                </h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Cerrar"
                ></button>
            </div>

            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar el bus con placa
                <strong id="placaBusEliminar"></strong>?
                <p id="avisoEstudiantesAsignados" class="text-warning small mt-2 mb-0" hidden>
                    Hay estudiantes asignados a este bus. Si continúas, se les quitará
                    la asignación del bus («Sin asignar») antes de eliminarlo.
                </p>
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

                <form method="POST" action="buses.php">
                    <input type="hidden" name="accion" value="eliminar">
                    <input
                        type="hidden"
                        name="id_bus"
                        id="idBusEliminar"
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
    // Menú lateral adaptable.
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('menuToggle');
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

    // Búsqueda de buses.
    const buscarBus = document.getElementById('buscarBus');
    const filasBuses = document.querySelectorAll('#tablaBuses tr');
    const sinResultados = document.getElementById('sinResultadosBuses');

    if (buscarBus) {
        buscarBus.addEventListener('input', () => {
            const termino = buscarBus.value.toLocaleLowerCase().trim();
            let visibles = 0;

            filasBuses.forEach((fila) => {
                const esFilaVacia = fila.querySelector('td[colspan]');
                if (esFilaVacia) return;

                const coincide = fila.textContent
                    .toLocaleLowerCase()
                    .includes(termino);

                fila.style.display = coincide ? '' : 'none';

                if (coincide) visibles++;
            });

            if (sinResultados) {
                sinResultados.classList.toggle(
                    'd-none',
                    visibles > 0 || filasBuses.length === 0 ||
                    document.querySelector('#tablaBuses td[colspan]') !== null
                );
            }
        });
    }

    // Evitar capacidades no enteras o menores que uno.
    const capacidad = document.getElementById('capacidad');

    if (capacidad) {
        capacidad.addEventListener('input', () => {
            capacidad.value = capacidad.value.replace(/[^0-9]/g, '');
        });
    }

    // Normalizar la placa a mayúsculas.
    const placa = document.getElementById('placa');

    if (placa) {
        placa.addEventListener('input', () => {
            placa.value = placa.value.toLocaleUpperCase();
        });
    }

    const modalDetalleBus = document.getElementById('modalDetalleBus');
    const datosDetalleBus = document.getElementById('datosDetalleBus');
    const formEditarBus = document.getElementById('formEditarBus');
    let registroBusActual = null;
    const etiquetasBus = {
        id_bus: 'Identificador',
        placa: 'Placa',
        marca: 'Marca',
        modelo: 'Modelo',
        capacidad: 'Capacidad',
        id_conductor: 'Identificador del conductor',
        conductor: 'Conductor asignado',
        estado: 'Estado'
    };

    function mostrarDetalleBus(registro) {
        datosDetalleBus.replaceChildren();

        Object.entries(registro).forEach(([campo, valor]) => {
            const columna = document.createElement('div');
            columna.className = 'col-md-6';

            const etiqueta = document.createElement('strong');
            etiqueta.className = 'd-block text-secondary small';
            etiqueta.textContent = etiquetasBus[campo] || campo.replaceAll('_', ' ');

            const contenido = document.createElement('span');
            contenido.className = 'd-block';
            if (campo === 'estado') {
                contenido.textContent = valor === true || valor === 'true' || valor === 1 || valor === '1'
                    ? 'Activo'
                    : 'Inactivo';
            } else if (campo === 'conductor' && !valor) {
                contenido.textContent = 'Sin asignar';
            } else if (valor && typeof valor === 'object') {
                contenido.textContent = Object.values(valor).filter(Boolean).join(' ') || 'Sin asignar';
            } else {
                contenido.textContent = valor === null || valor === '' ? '—' : String(valor);
            }

            columna.append(etiqueta, contenido);
            datosDetalleBus.append(columna);
        });
    }

    function cargarFormularioBus(registro) {
        document.getElementById('editarIdBus').value = registro.id_bus || '';
        document.getElementById('editarPlacaBus').value = registro.placa || '';
        document.getElementById('editarMarcaBus').value = registro.marca || '';
        document.getElementById('editarModeloBus').value = registro.modelo || '';
        document.getElementById('editarCapacidadBus').value = registro.capacidad || '';
        document.getElementById('editarEstadoBus').value =
            registro.estado === true || registro.estado === 'true' ||
            registro.estado === 1 || registro.estado === '1' ? '1' : '0';
    }

    function cambiarModoEdicionBus(editar) {
        datosDetalleBus.hidden = editar;
        formEditarBus.hidden = !editar;
        document.getElementById('editarBusBtn').hidden = editar;
        document.getElementById('cancelarEdicionBus').hidden = !editar;
        document.getElementById('guardarBusBtn').hidden = !editar;
    }

    if (modalDetalleBus) {
        modalDetalleBus.addEventListener('show.bs.modal', (evento) => {
            const fila = evento.relatedTarget;
            if (!fila) return;

            registroBusActual = JSON.parse(fila.dataset.registro);
            document.getElementById('tituloModalDetalleBus').textContent =
                `Bus: ${registroBusActual.placa || ''}`;
            mostrarDetalleBus(registroBusActual);
            cargarFormularioBus(registroBusActual);
            cambiarModoEdicionBus(false);
        });

        modalDetalleBus.addEventListener('hidden.bs.modal', () => {
            cambiarModoEdicionBus(false);
        });

        document.getElementById('editarBusBtn').addEventListener('click', () => {
            cambiarModoEdicionBus(true);
        });

        document.getElementById('cancelarEdicionBus').addEventListener('click', () => {
            cargarFormularioBus(registroBusActual);
            cambiarModoEdicionBus(false);
        });

        const abrirDetalleBus = (fila) => {
            bootstrap.Modal.getOrCreateInstance(modalDetalleBus).show(fila);
        };
        const tablaBuses = document.getElementById('tablaBuses');

        tablaBuses.addEventListener('click', (evento) => {
            const fila = evento.target.closest('.fila-bus-detalle');
            if (!fila || evento.target.closest('button, a, input, select, textarea')) return;
            abrirDetalleBus(fila);
        });

        tablaBuses.addEventListener('keydown', (evento) => {
            if (
                evento.target.classList.contains('fila-bus-detalle') &&
                (evento.key === 'Enter' || evento.key === ' ')
            ) {
                evento.preventDefault();
                abrirDetalleBus(evento.target);
            }
        });
    }

    const modalBus = document.getElementById('modalEliminarBus');

    if (modalBus) {
        modalBus.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;

            if (!boton) return;

            document.getElementById('idBusEliminar').value =
                boton.dataset.id;

            document.getElementById('placaBusEliminar').textContent =
                boton.dataset.nombre;

            const estudiantesAsignados =
                Number(boton.dataset.estudiantesAsignados) > 0;
            document.getElementById('avisoEstudiantesAsignados').hidden =
                !estudiantesAsignados;
        });
    }
</script>

</body>
</html>