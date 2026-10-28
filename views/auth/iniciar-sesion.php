<?php
$mensajeLogin = $mensajeLogin ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Transporte Escolar</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
<div class="register-layout">

    <!-- Panel izquierdo -->
    <section class="image-panel">
        <div class="image-content">

            <div class="brand">
                <img src="assets/img/logo-negro.png" alt="Logo del transporte escolar" class="logo-sistema">
                <span>MIRUTA</span>
            </div>
            <div class="image-placeholder">
                <img
                    src="assets/img/transporte-escolar.jpg"
                    alt="Transporte escolar"
                    class="transport-image"
                >
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
    </section>

    <!-- Panel derecho -->
    <main class="form-panel">
        <div class="register-card login-card">

            <div class="form-heading">
                <span class="heading-icon">
                    <i class="bi bi-person-circle"></i>
                </span>

                <h1>Iniciar sesión</h1>
                <p>Ingresa tus credenciales para continuar.</p>
            </div>

            <?php if (!empty($mensajeLogin)): ?>
                <div
                    class="alert alert-<?= htmlspecialchars(
                        $mensajeLogin['tipo'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    role="alert"
                >
                    <?= htmlspecialchars(
                        $mensajeLogin['mensaje'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="iniciar-sesion.php">

                <div class="mb-3">
                    <label for="correo" class="form-label">
                        Correo electrónico
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>

                        <input
                            type="email"
                            class="form-control"
                            id="correo"
                            name="correo"
                            maxlength="100"
                            placeholder="ejemplo@correo.com"
                            autocomplete="email"
                            value="<?= htmlspecialchars(
                                $_POST['correo'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="mb-2">
                    <label for="password" class="form-label">
                        Contraseña
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            class="btn btn-outline-secondary password-toggle"
                            type="button"
                            id="togglePassword"
                            aria-label="Mostrar contraseña"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 register-button">
                    Iniciar sesión
                    <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>

        </div>
    </main>

</div>

<script>
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', () => {
    const mostrar = passwordInput.type === 'password';

    passwordInput.type = mostrar ? 'text' : 'password';

    togglePassword.innerHTML = mostrar
        ? '<i class="bi bi-eye-slash"></i>'
        : '<i class="bi bi-eye"></i>';

    togglePassword.setAttribute(
        'aria-label',
        mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'
    );
});
</script>

</body>
</html>