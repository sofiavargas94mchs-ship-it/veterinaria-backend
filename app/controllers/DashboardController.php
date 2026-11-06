<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $_SESSION['user'];
        $dashboard = new Dashboard($this->pdo);
        $this->view('dashboard/index', [
            'metrics' => $dashboard->metricas($user['rol'], (int) $user['id_usuario']),
            'appointments' => $dashboard->citasHoy($user['rol'], (int) $user['id_usuario']),
        ]);
    }
}
