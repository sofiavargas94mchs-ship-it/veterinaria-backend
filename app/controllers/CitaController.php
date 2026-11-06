<?php

declare(strict_types=1);

final class CitaController extends Controller
{
    public function index(): void
    {
        $this->agenda();
    }

    public function agenda(): void
    {
        $date = isset($_GET['fecha']) && is_string($_GET['fecha'])
            ? $_GET['fecha']
            : date('Y-m-d');
        if (\DateTime::createFromFormat('Y-m-d', $date) === false) {
            $date = date('Y-m-d');
        }
        $status = isset($_GET['estado']) && is_string($_GET['estado']) && $_GET['estado'] !== ''
            ? $_GET['estado']
            : null;
        $role = $_SESSION['user']['rol'];
        $userId = (int) $_SESSION['user']['id_usuario'];
        $appointments = (new Cita($this->pdo))->listarAgenda(
            $date,
            $role === 'Veterinario' ? $userId : null,
            $status
        );
        $this->view('citas/index', [
            'appointments' => $appointments,
            'date' => $date,
            'status' => $status ?? '',
        ]);
    }

    public function create(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $this->view('citas/form', [
            'pets' => (new Cita($this->pdo))->listarMascotasActivas(),
            'veterinarians' => (new Usuario($this->pdo))->findActiveVeterinarians(),
        ]);
    }

    public function save(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $this->requirePost();
        $dateTime = $this->input('fecha_hora', 16);
        $parsed = \DateTime::createFromFormat('Y-m-d\TH:i', $dateTime);
        $data = [
            'mascota_id' => filter_input(INPUT_POST, 'mascota_id', FILTER_VALIDATE_INT) ?: 0,
            'veterinario_id' => filter_input(INPUT_POST, 'veterinario_id', FILTER_VALIDATE_INT) ?: 0,
            'creada_por' => (int) $_SESSION['user']['id_usuario'],
            'fecha_hora' => $parsed ? $parsed->format('Y-m-d H:i:s') : '',
            'duracion_min' => filter_input(INPUT_POST, 'duracion_min', FILTER_VALIDATE_INT) ?: 0,
            'motivo' => $this->input('motivo', 255),
        ];
        if (!$parsed || $parsed <= new DateTime()
            || $data['mascota_id'] < 1 || $data['veterinario_id'] < 1 || $data['motivo'] === '') {
            $this->flash('danger', 'Completa los datos de la cita y selecciona una fecha futura válida.');
            $this->redirect('cita', 'create');
        }

        try {
            (new Cita($this->pdo))->agendar($data);
            $this->flash('success', 'La cita quedó agendada.');
            $this->redirect('cita', 'agenda', ['fecha' => $parsed->format('Y-m-d')]);
        } catch (InvalidArgumentException $exception) {
            $this->flash('danger', $exception->getMessage());
            $this->redirect('cita', 'create');
        } catch (PDOException $exception) {
            error_log((string) $exception);
            $this->flash('danger', 'No fue posible agendar la cita. Verifica el horario e inténtalo nuevamente.');
            $this->redirect('cita', 'create');
        }
    }

    public function state(): void
    {
        $this->requireRole('Administrador', 'Recepcionista', 'Veterinario');
        $this->requirePost();
        $id = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
        $status = $this->input('estado', 20);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('El identificador de la cita no es válido.');
        }

        $model = new Cita($this->pdo);
        if ($_SESSION['user']['rol'] === 'Veterinario'
            && $model->veterinarioDeCita($id) !== (int) $_SESSION['user']['id_usuario']) {
            http_response_code(403);
            throw new RuntimeException('Solo puedes actualizar el estado de tus propias citas.');
        }
        $model->cambiarEstado($id, $status);
        $this->flash('success', 'El estado de la cita se actualizó.');
        $date = isset($_POST['fecha']) && is_string($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
        $this->redirect('cita', 'agenda', ['fecha' => $date]);
    }
}
