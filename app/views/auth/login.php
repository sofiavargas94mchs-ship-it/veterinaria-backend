<?php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | VetCare</title>
    <link rel="stylesheet" href="public/css/app.css">
</head>
<body class="login-page">
<main class="login-layout">
    <section class="login-story">
        <a class="brand brand-on-dark" href="index.php">
            <span class="brand-mark">V</span>
            <span>Vet<span class="brand-light">Care</span><small>CLÍNICA VETERINARIA</small></span>
        </a>
        <div class="story-copy">
            <span class="eyebrow eyebrow-light">CUIDADO CON PROPÓSITO</span>
            <h1>Una mejor atención<br>para cada paciente.</h1>
            <p>Organiza la agenda, el expediente clínico y el cuidado preventivo desde un solo lugar.</p>
        </div>
        <div class="story-note"><span class="status-dot"></span> Plataforma de gestión clínica</div>
    </section>
    <section class="login-panel">
        <div class="login-form-wrap">
            <span class="eyebrow">BIENVENIDO DE NUEVO</span>
            <h2>Iniciar sesión</h2>
            <p class="muted">Ingresa tus credenciales para continuar.</p>
            <?php if (is_array($flash)): ?>
                <div class="alert alert-<?= e($flash['type'] ?? 'info') ?>" role="alert"><?= e($flash['message'] ?? '') ?></div>
            <?php endif; ?>
            <form method="post" action="index.php?controller=auth&action=authenticate" class="form-stack">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="correo">Correo electrónico</label>
                <input id="correo" name="correo" type="email" autocomplete="username" placeholder="nombre@clinica.com" required>
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Tu contraseña" required>
                <label for="rol">Perfil de acceso</label>
                <select id="rol" name="rol" required>
                    <option value="Administrador">Administrador</option>
                    <option value="Recepcionista" selected>Recepcionista</option>
                    <option value="Veterinario">Veterinario</option>
                </select>
                <button class="button button-primary button-wide" type="submit">Entrar al sistema <span aria-hidden="true">→</span></button>
            </form>
            <p class="login-footnote">El perfil seleccionado debe coincidir con el rol asignado a tu cuenta.</p>
        </div>
    </section>
</main>
</body>
</html>
