<?php

declare(strict_types=1);

abstract class Controller
{
    public function __construct(protected PDO $pdo)
    {
    }

    protected function view(string $template, array $data = [], bool $withLayout = true): void
    {
        $templatePath = ROOT_PATH . '/app/views/' . $template . '.php';
        if (!is_file($templatePath)) {
            throw new RuntimeException('No se encontró la vista solicitada.');
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        if ($withLayout) {
            require ROOT_PATH . '/app/views/layouts/main.php';
            return;
        }

        echo $content;
    }

    protected function requireRole(string ...$roles): void
    {
        $user = $_SESSION['user'] ?? null;
        if (!is_array($user) || !in_array($user['rol'] ?? '', $roles, true)) {
            http_response_code(403);
            throw new RuntimeException('No tienes permisos para realizar esta acción.');
        }
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            throw new RuntimeException('Método no permitido.');
        }

        $sentToken = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!is_string($sentToken) || !is_string($sessionToken) || !hash_equals($sessionToken, $sentToken)) {
            http_response_code(419);
            throw new RuntimeException('La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.');
        }
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function redirect(string $controller, string $action = 'index', array $params = []): never
    {
        $query = http_build_query(['controller' => $controller, 'action' => $action] + $params);
        header('Location: index.php?' . $query);
        exit;
    }

    protected function input(string $key, int $maxLength = 500): string
    {
        $value = $_POST[$key] ?? '';
        if (!is_string($value)) {
            return '';
        }

        return trim(mb_substr($value, 0, $maxLength));
    }
}
