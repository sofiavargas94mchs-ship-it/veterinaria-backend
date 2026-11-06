<?php

declare(strict_types=1);

final class AuthController extends Controller
{
    public function login(): void
    {
        if (!empty($_SESSION['user']['id_usuario'])) {
            $this->redirect('dashboard');
        }

        $this->view('auth/login', [], false);
    }

    public function authenticate(): void
    {
        // index.php inicia la sesión antes de cargar controladores o producir salida.
        $this->requirePost();

        $emailInput = $_POST['correo'] ?? $_POST['email'] ?? '';
        $email = is_string($emailInput)
            ? filter_var(trim($emailInput), FILTER_VALIDATE_EMAIL)
            : false;
        $password = $_POST['password'] ?? '';
        $role = $this->input('rol', 30);
        if (
            !$email
            || !is_string($password)
            || $password === ''
            || !in_array($role, ['Administrador', 'Recepcionista', 'Veterinario'], true)
        ) {
            $this->flash('danger', 'Ingresa un correo y una contraseña válidos.');
            $this->redirect('auth', 'login');
        }

        $userModel = new Usuario($this->pdo);
        $user = $userModel->authenticate($email, $password, $role);
        if ($user === null) {
            $this->flash('danger', 'Las credenciales no son válidas para el rol seleccionado.');
            $this->redirect('auth', 'login');
        }

        $userModel->updateLastAccess((int) $user['id_usuario']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id_usuario'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['apellido'] = $user['apellido'];
        $_SESSION['correo'] = $user['correo'];
        $_SESSION['user'] = $user;
        unset($_SESSION['csrf_token']);
        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        $this->requirePost();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        header('Location: index.php?controller=auth&action=login');
        exit;
    }
}
