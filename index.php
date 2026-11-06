<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

require_once ROOT_PATH . '/app/config/database.php';
require_once ROOT_PATH . '/app/models/Usuario.php';
require_once ROOT_PATH . '/app/models/Propietario.php';
require_once ROOT_PATH . '/app/models/Mascota.php';
require_once ROOT_PATH . '/app/models/Cita.php';
require_once ROOT_PATH . '/app/models/HistoriaClinica.php';
require_once ROOT_PATH . '/app/models/Dashboard.php';
require_once ROOT_PATH . '/app/models/Reporte.php';
require_once ROOT_PATH . '/app/controllers/Controller.php';
require_once ROOT_PATH . '/app/controllers/AuthController.php';
require_once ROOT_PATH . '/app/controllers/DashboardController.php';
require_once ROOT_PATH . '/app/controllers/PropietarioController.php';
require_once ROOT_PATH . '/app/controllers/MascotaController.php';
require_once ROOT_PATH . '/app/controllers/CitaController.php';
require_once ROOT_PATH . '/app/controllers/ReporteController.php';

$routes = [
    'auth' => ['class' => AuthController::class, 'actions' => ['login', 'authenticate', 'logout']],
    'dashboard' => ['class' => DashboardController::class, 'actions' => ['index']],
    'propietario' => ['class' => PropietarioController::class, 'actions' => ['index', 'create', 'save', 'edit', 'update']],
    'mascota' => ['class' => MascotaController::class, 'actions' => ['index', 'create', 'save', 'show', 'history']],
    'cita' => ['class' => CitaController::class, 'actions' => ['index', 'agenda', 'create', 'save', 'state']],
    'reporte' => ['class' => ReporteController::class, 'actions' => ['index']],
];

$controllerName = isset($_GET['controller']) && is_string($_GET['controller'])
    ? $_GET['controller']
    : (isset($_SESSION['user']) ? 'dashboard' : 'auth');
$action = isset($_GET['action']) && is_string($_GET['action'])
    ? $_GET['action']
    : ($controllerName === 'auth' ? 'login' : 'index');

if (!isset($routes[$controllerName]) || !in_array($action, $routes[$controllerName]['actions'], true)) {
    http_response_code(404);
    echo 'La página solicitada no existe.';
    exit;
}

if ($controllerName !== 'auth' && empty($_SESSION['user']['id_usuario'])) {
    header('Location: index.php?controller=auth&action=login');
    exit;
}

try {
    $controllerClass = $routes[$controllerName]['class'];
    $controller = new $controllerClass($pdo);
    $controller->{$action}();
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error</title>';
    echo '<body><main><h1>No fue posible completar la solicitud</h1>';
    echo '<p>Ocurrió un error interno. Inténtalo nuevamente o contacta al administrador.</p>';
    echo '<p><a href="index.php">Volver al inicio</a></p></main></body></html>';
}
