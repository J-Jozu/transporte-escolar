<?php

if (!isset($dashboard) || !is_array($dashboard)) {
    header('Location: ../../public/dashboard.php');
    exit;
}

function escaparDashboard($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function nombreEstudianteDashboard(array $fila): string
{
    $estudiante = $fila['estudiantes'] ?? [];

    if (!is_array($estudiante)) {
        return 'Estudiante no disponible';
    }

    $nombre = trim(
        ($estudiante['nombre'] ?? '') . ' ' .
        ($estudiante['apellido'] ?? '')
    );

    return $nombre !== '' ? $nombre : 'Estudiante no disponible';
}

function fechaDashboard(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }

    $timestamp = strtotime($fecha);

    return $timestamp !== false
        ? date('d/m/Y H:i', $timestamp)
        : '—';
}

function eventoDashboard(string $evento): string
{
    $eventos = [
        'subio_al_bus' => 'Subió al bus',
        'llego_a_la_escuela' => 'Llegó a la escuela',
        'bajo_del_bus' => 'Bajó del bus'
    ];

    return $eventos[$evento] ?? 'Actividad registrada';
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

    <title>Dashboard | MIRUTA</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>

<div class="app-shell">

    <!-- Menú lateral -->
    <aside class="sidebar" id="sidebar">

        <a href="dashboard.php" class="sidebar-brand">
            <img src="assets/img/logo-negro.png" alt="Logo del transporte escolar" class="logo-sistema">
            <span>
                <strong>MIRUTA</strong>
                <small>Colegio Nova Aura</small>
            </span>
        </a>

        <div class="sidebar-label">MENÚ PRINCIPAL</div>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-link active">
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

    <!-- Área principal -->
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
                    <strong><?= escaparDashboard($nombreUsuario) ?></strong>
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

        <main class="dashboard-content">

            <div class="page-heading">
                <div>
                    <h2>Dashboard</h2>
                    <p>Resumen general del transporte escolar.</p>
                </div>

                <span class="current-date">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d/m/Y') ?>
                </span>
            </div>

            <?php if ($dashboard['error']): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No fue posible cargar toda la información.
                    Comprueba los permisos de Supabase e inténtalo de nuevo.
                </div>
            <?php endif; ?>

            <!-- Tarjetas estadísticas -->
            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-icon icon-students">
                        <i class="bi bi-people-fill"></i>
                    </div>

                    <div class="stat-info">
                        <span>Total de estudiantes</span>
                        <strong>
                            <?= number_format($dashboard['totalEstudiantes']) ?>
                        </strong>
                        <small>Estudiantes registrados</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon icon-drivers">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>

                    <div class="stat-info">
                        <span>Total de conductores</span>
                        <strong>
                            <?= number_format($dashboard['totalConductores']) ?>
                        </strong>
                        <small>Conductores registrados</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon icon-buses">
                        <i class="bi bi-bus-front-fill"></i>
                    </div>

                    <div class="stat-info">
                        <span>Total de buses</span>
                        <strong>
                            <?= number_format($dashboard['totalBuses']) ?>
                        </strong>
                        <small>Buses registrados</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon icon-routes">
                        <i class="bi bi-signpost-2-fill"></i>
                    </div>

                    <div class="stat-info">
                        <span>Total de rutas</span>
                        <strong>
                            <?= number_format($dashboard['totalRutas']) ?>
                        </strong>
                        <small>Rutas registradas</small>
                    </div>
                </article>

            </section>

            <!-- Actividades y llegadas -->
            <section class="dashboard-panels">

                <article class="content-panel activities-panel">

                    <div class="panel-heading">
                        <div class="panel-title-icon blue-title-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>

                        <div>
                            <h3>Actividades recientes</h3>
                            <p>Últimos eventos registrados</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table dashboard-table">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Actividad</th>
                                    <th>Parada</th>
                                    <th>Fecha y hora</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (empty($dashboard['actividades'])): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <span>No hay actividades registradas.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dashboard['actividades'] as $actividad): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= escaparDashboard(
                                                    nombreEstudianteDashboard($actividad)
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <span class="event-badge">
                                                <?= escaparDashboard(
                                                    eventoDashboard(
                                                        $actividad['tipo_evento'] ?? ''
                                                    )
                                                ) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php
                                            $parada = $actividad['paradas'] ?? [];
                                            echo escaparDashboard(
                                                is_array($parada)
                                                    ? ($parada['nombre'] ?? '—')
                                                    : '—'
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?= escaparDashboard(
                                                fechaDashboard(
                                                    $actividad['fecha_hora'] ?? null
                                                )
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </article>

                <article class="content-panel arrivals-panel">

                    <div class="panel-heading">
                        <div class="panel-title-icon green-title-icon">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>

                        <div>
                            <h3>Últimas llegadas</h3>
                            <p>Estudiantes que llegaron a la escuela</p>
                        </div>
                    </div>

                    <div class="arrivals-list">

                    <?php if (empty($dashboard['ultimasLlegadas'])): ?>

                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <span>No hay llegadas registradas.</span>
                        </div>

                    <?php else: ?>

                        <?php foreach ($dashboard['ultimasLlegadas'] as $llegada): ?>

                            <?php
                            $parada = $llegada['paradas'] ?? [];
                            $nombreParada = is_array($parada)
                                ? ($parada['nombre'] ?? 'Parada no disponible')
                                : 'Parada no disponible';
                            ?>

                            <div class="arrival-item">

                                <div class="arrival-check">
                                    <i class="bi bi-check-lg"></i>
                                </div>

                                <div class="arrival-info">
                                    <strong>
                                        <?= escaparDashboard(
                                            nombreEstudianteDashboard($llegada)
                                        ) ?>
                                    </strong>

                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        <?= escaparDashboard($nombreParada) ?>
                                    </span>

                                    <small>
                                        <?= escaparDashboard(
                                            fechaDashboard(
                                                $llegada['fecha_hora'] ?? null
                                            )
                                        ) ?>
                                    </small>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

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

<script>
const sidebar = document.getElementById('sidebar');
const menuToggle = document.getElementById('menuToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('sidebar-open');
    sidebarOverlay.classList.toggle('overlay-visible');
});

sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('sidebar-open');
    sidebarOverlay.classList.remove('overlay-visible');
});
</script>

</body>
</html>
