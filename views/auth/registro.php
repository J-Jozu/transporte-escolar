
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Transporte Escolar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body>

<div class="register-layout">

    <!-- PANEL IZQUIERDO: ESPACIO PARA LA IMAGEN -->
    <aside class="image-panel">
        <div class="image-content">

            <div class="brand">
                <i class="bi bi-bus-front-fill"></i>
                <span>Transporte Escolar</span>
            </div>

            <div class="image-placeholder">
                <!-- Reemplaza este bloque por una imagen cuando la tengas -->
                <i class="bi bi-bus-front"></i>
                <p>Imagen del transporte escolar</p>
                <small>Espacio reservado para una imagen institucional</small>
            </div>

            <div class="image-message">
                <h1>Un viaje seguro comienza aquí</h1>
                <p>
                    Gestiona el transporte de tus estudiantes
                    de manera sencilla, organizada y segura.
                </p>
            </div>

            <div class="image-footer">
                <i class="bi bi-shield-check"></i>
                Seguridad y tranquilidad para tu familia
            </div>

        </div>
    </aside>

    <!-- PANEL DERECHO: FORMULARIO -->
    <main class="form-panel">

        <div class="register-card">

            <header class="form-heading">
                <div class="mobile-brand">
                    <i class="bi bi-bus-front-fill"></i>
                    Transporte Escolar
                </div>

                <span class="section-label">REGISTRO DE USUARIO</span>
                <h2>Crear una cuenta</h2>
                <p>Completa tus datos para comenzar.</p>
            </header>

            <?php if (!empty($resultadoRegistro)): ?>
                <div
                    id="mensaje"
                    class="alert alert-<?= htmlspecialchars($resultadoRegistro['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                    role="alert"
                >
                    <?= htmlspecialchars($resultadoRegistro['mensaje'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <div id="mensaje" class="alert d-none" role="alert"></div>
            <?php endif; ?>

            <form id="registroForm" method="POST" action="registro.php">

                <div class="form-grid">

                    <!-- NOMBRE -->
                    <div class="form-field">
                        <label for="nombre">Nombre</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                class="form-control"
                                minlength="2"
                                maxlength="30"
                                pattern="[\p{L} ]{2,30}"
                                title="Ingresa entre 2 y 30 letras. Puedes incluir tildes, ñ y espacios."
                                autocomplete="given-name"
                                placeholder="Nombre"
                                required
                            >
                        </div>
                    </div>

                    <!-- APELLIDO -->
                    <div class="form-field">
                        <label for="apellido">Apellido</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input
                                type="text"
                                id="apellido"
                                name="apellido"
                                class="form-control"
                                minlength="2"
                                maxlength="30"
                                pattern="[\p{L} ]{2,30}"
                                title="Ingresa entre 2 y 30 letras. Puedes incluir tildes, ñ y espacios."
                                autocomplete="family-name"
                                placeholder="Apellido"
                                required
                            >
                        </div>
                    </div>

                    <!-- CORREO -->
                    <div class="form-field">
                        <label for="correo">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                class="form-control"
                                maxlength="100"
                                autocomplete="email"
                                placeholder="correo@gmail.com"
                                required
                            >
                        </div>
                    </div>

                    <!-- TELÉFONO -->
                    <div class="form-field">
                        <label for="telefono">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-telephone"></i>
                            </span>
                            <input
                                type="text"
                                id="telefono"
                                name="telefono"
                                class="form-control"
                                inputmode="numeric"
                                minlength="8"
                                maxlength="8"
                                pattern="[0-9]{8}"
                                title="El teléfono debe contener exactamente 8 dígitos."
                                autocomplete="tel"
                                placeholder="########"
                                required
                            >
                        </div>
                    </div>

                    <!-- CONTRASEÑA -->
                    <div class="form-field">
                        <label for="password">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                minlength="8"
                                maxlength="20"
                                autocomplete="new-password"
                                required
                            >
                            <button class="btn btn-outline-secondary toggle-password"
                                    type="button" data-target="password"
                                    aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- CONFIRMAR CONTRASEÑA -->
                    <div class="form-field">
                        <label for="confirmar_password">
                            Confirmar contraseña
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input
                                type="password"
                                id="confirmar_password"
                                name="confirmar_password"
                                class="form-control"
                                minlength="8"
                                maxlength="20"
                                autocomplete="new-password"
                                required
                            >
                            <button class="btn btn-outline-secondary toggle-password"
                                    type="button"
                                    data-target="confirmar_password"
                                    aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <div class="info-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>La cuenta se registrará como acudiente.</span>
                </div>

                <button type="submit" class="btn btn-register">
                    <i class="bi bi-person-check-fill"></i>
                    Crear cuenta
                </button>

            </form>

            <div class="login-link">
                ¿Ya tienes una cuenta?
                <a href="iniciar-sesion.php">Inicia sesión</a>
            </div>

            <footer class="form-footer">
                Sistema de Transporte Escolar · Panamá
            </footer>

        </div>

    </main>

</div>

<script>
    const form = document.getElementById('registroForm');
    const mensaje = document.getElementById('mensaje');

    document.querySelectorAll('.toggle-password').forEach(boton => {
        boton.addEventListener('click', () => {
            const campo = document.getElementById(boton.dataset.target);
            const mostrar = campo.type === 'password';

            campo.type = mostrar ? 'text' : 'password';

            boton.innerHTML = mostrar
                ? '<i class="bi bi-eye-slash"></i>'
                : '<i class="bi bi-eye"></i>';

            boton.setAttribute(
                'aria-label',
                mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'
            );
        });
    });

    form.addEventListener('submit', event => {
        const password = document.getElementById('password').value;
        const confirmar = document.getElementById(
            'confirmar_password'
        ).value;

        if (password !== confirmar) {
            event.preventDefault();

            mensaje.className = 'alert alert-danger';
            mensaje.textContent = 'Las contraseñas no coinciden.';
            mensaje.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
        // Si coinciden, el formulario se envía a PHP.
    });

    const camposLetras = ['nombre', 'apellido'];
    camposLetras.forEach(id => {
        const campo = document.getElementById(id);

        campo.addEventListener('keydown', event => {
            // Permitir teclas de control, navegación y edición.
            if (
                event.ctrlKey ||
                event.metaKey ||
                event.altKey ||
                event.key.length > 1
            ) {
                return;
            }

            // Permitir solo letras Unicode y espacios.
            if (!/^[\p{L} ]$/u.test(event.key)) {
                event.preventDefault();
            }
        });

        // Evitar caracteres no permitidos al pegar texto.
        campo.addEventListener('input', () => {
            campo.value = campo.value.replace(/[^\p{L} ]/gu, '');
        });
    });

    const telefono = document.getElementById('telefono');
    telefono.addEventListener('keydown', event => {
        if (
            event.ctrlKey ||
            event.metaKey ||
            event.altKey ||
            event.key.length > 1
        ) {
            return;
        }

        if (!/^[0-9]$/.test(event.key)) {
            event.preventDefault();
        }
    });

    telefono.addEventListener('input', () => {
        telefono.value = telefono.value.replace(/[^0-9]/g, '').slice(0, 8);
    });
    
</script>

</body>
</html>
