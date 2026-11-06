<?php
$currentUser = $_SESSION['user'] ?? [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$name = trim(($currentUser['nombre'] ?? '') . ' ' . ($currentUser['apellido'] ?? ''));
$role = $currentUser['rol'] ?? '';
$initials = strtoupper(substr($currentUser['nombre'] ?? 'V', 0, 1) . substr($currentUser['apellido'] ?? '', 0, 1));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Panel') ?> | VetCare</title>
    <link rel="stylesheet" href="public/css/app.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php?controller=dashboard">
            <span class="brand-mark">V</span>
            <span>Vet<span class="brand-light">Care</span><small>CLÍNICA VETERINARIA</small></span>
        </a>
        <div class="nav-caption">MENÚ PRINCIPAL</div>
        <nav class="side-nav" aria-label="Navegación principal">
            <a href="index.php?controller=dashboard" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">⌂</span> Resumen
            </a>
            <a href="index.php?controller=mascota" class="<?= ($activePage ?? '') === 'mascotas' ? 'active' : '' ?>">
                <span class="nav-icon">♡</span> Pacientes
            </a>
            <?php if (in_array($role, ['Administrador', 'Recepcionista'], true)): ?>
                <a href="index.php?controller=propietario" class="<?= ($activePage ?? '') === 'propietarios' ? 'active' : '' ?>">
                    <span class="nav-icon">♙</span> Propietarios
                </a>
            <?php endif; ?>
            <a href="index.php?controller=cita&action=agenda" class="<?= ($activePage ?? '') === 'citas' ? 'active' : '' ?>">
                <span class="nav-icon">▦</span> Agenda
            </a>
            <?php if ($role === 'Administrador'): ?>
                <a href="index.php?controller=reporte" class="<?= ($activePage ?? '') === 'reportes' ? 'active' : '' ?>">
                    <span class="nav-icon">▤</span> Reportes
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="clinic-status"><span class="status-dot"></span><span>Clínica activa<small>Todos los sistemas operativos</small></span></div>
            <form method="post" action="index.php?controller=auth&action=logout">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="logout-button" type="submit"><span class="nav-icon">↪</span> Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div>
                <span class="eyebrow">CLÍNICA VETERINARIA</span>
                <h1><?= e($pageTitle ?? 'Panel') ?></h1>
            </div>
            <div class="user-chip">
                <span class="avatar"><?= e($initials) ?></span>
                <span><strong><?= e($name) ?></strong><small><?= e($role) ?></small></span>
            </div>
        </header>
        <section class="page-content">
            <?php if (is_array($flash)): ?>
                <div class="alert alert-<?= e($flash['type'] ?? 'info') ?>" role="status">
                    <?= e($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </section>
        <footer class="footer">VetCare <span>·</span> Gestión integral de pacientes y clínica</footer>
    </main>
</div>
</body>
</html>
