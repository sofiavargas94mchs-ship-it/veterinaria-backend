<?php

declare(strict_types=1);

final class MascotaController extends Controller
{
    public function index(): void
    {
        $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        $this->view('mascotas/index', [
            'pets' => (new Mascota($this->pdo))->listar(mb_substr($search, 0, 100)),
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $model = new Mascota($this->pdo);
        $this->view('mascotas/form', [
            'owners' => $model->listarPropietarios(),
            'breeds' => $model->listarRazas(),
            'pet' => null,
        ]);
    }

    public function save(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $this->requirePost();
        $data = [
            'propietario_id' => filter_input(INPUT_POST, 'propietario_id', FILTER_VALIDATE_INT) ?: 0,
            'raza_id' => filter_input(INPUT_POST, 'raza_id', FILTER_VALIDATE_INT) ?: 0,
            'nombre' => $this->input('nombre', 80),
            'fecha_nacimiento' => $this->nullableInput('fecha_nacimiento', 10),
            'sexo' => $this->input('sexo', 10),
            'color' => $this->nullableInput('color', 50),
            'microchip' => $this->nullableInput('microchip', 30),
        ];
        if ($data['propietario_id'] < 1 || $data['raza_id'] < 1 || $data['nombre'] === ''
            || !in_array($data['sexo'], ['Macho', 'Hembra'], true)) {
            $this->flash('danger', 'Completa los campos obligatorios de la mascota.');
            $this->showPetForm($data);
            return;
        }
        if ($data['fecha_nacimiento'] !== null
            && \DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']) === false) {
            $this->flash('danger', 'La fecha de nacimiento no es válida.');
            $this->showPetForm($data);
            return;
        }

        try {
            $petId = (new Mascota($this->pdo))->crear($data);
            $this->flash('success', 'La mascota se registró correctamente.');
            $this->redirect('mascota', 'show', ['id' => $petId]);
        } catch (PDOException $exception) {
            error_log((string) $exception);
            $this->flash('danger', 'No fue posible registrar la mascota. Verifica los datos e inténtalo nuevamente.');
            $this->showPetForm($data);
        }
    }

    public function show(): void
    {
        $id = $this->petId();
        $model = new Mascota($this->pdo);
        $pet = $model->buscarConExpediente($id);
        if ($pet === null) {
            $this->flash('danger', 'No se encontró la mascota solicitada.');
            $this->redirect('mascota');
        }

        $histories = new HistoriaClinica($this->pdo);
        $user = $_SESSION['user'];
        $this->view('mascotas/show', [
            'pet' => $pet,
            'history' => $histories->listarPorMascota($id),
            'vaccines' => $histories->listarVacunasDisponibles($id),
            'appointments' => $user['rol'] === 'Veterinario'
                ? $histories->listarCitasDisponibles($id, (int) $user['id_usuario'])
                : [],
        ]);
    }

    public function history(): void
    {
        $this->requireRole('Veterinario');
        $this->requirePost();
        $petId = $this->postPetId();
        $data = [
            'mascota_id' => $petId,
            'veterinario_id' => (int) $_SESSION['user']['id_usuario'],
            'cita_id' => $this->nullablePostInt('cita_id'),
            'peso_kg' => $this->nullablePostDecimal('peso_kg'),
            'temperatura_c' => $this->nullablePostDecimal('temperatura_c'),
            'sintomas' => $this->input('sintomas', 10000),
            'diagnostico' => $this->input('diagnostico', 10000),
            'tratamiento' => $this->input('tratamiento', 10000),
            'observaciones' => $this->nullableInput('observaciones', 10000),
            'proxima_revision' => $this->nullableInput('proxima_revision', 10),
            'lote' => $this->nullableInput('lote', 40),
        ];
        $vaccineId = filter_input(INPUT_POST, 'vacuna_id', FILTER_VALIDATE_INT) ?: null;

        if ($data['sintomas'] === '' || $data['diagnostico'] === '' || $data['tratamiento'] === '') {
            $this->flash('danger', 'Síntomas, diagnóstico y tratamiento son obligatorios.');
            $this->redirect('mascota', 'show', ['id' => $petId]);
        }

        try {
            (new HistoriaClinica($this->pdo))->registrar($data, $vaccineId);
            $this->flash('success', 'La evolución clínica se guardó correctamente.');
        } catch (Throwable $exception) {
            error_log((string) $exception);
            $this->flash('danger', 'No se pudo guardar la evolución. Verifica la cita o vacuna seleccionada.');
        }
        $this->redirect('mascota', 'show', ['id' => $petId]);
    }

    private function showPetForm(array $pet): void
    {
        $model = new Mascota($this->pdo);
        $this->view('mascotas/form', [
            'owners' => $model->listarPropietarios(),
            'breeds' => $model->listarRazas(),
            'pet' => $pet,
        ]);
    }

    private function petId(): int
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('El identificador de la mascota no es válido.');
        }
        return $id;
    }

    private function postPetId(): int
    {
        $id = filter_input(INPUT_POST, 'mascota_id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('El identificador de la mascota no es válido.');
        }
        return $id;
    }

    private function nullableInput(string $key, int $maxLength): ?string
    {
        $value = $this->input($key, $maxLength);
        return $value === '' ? null : $value;
    }

    private function nullablePostInt(string $key): ?int
    {
        $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
        return $value && $value > 0 ? $value : null;
    }

    private function nullablePostDecimal(string $key): ?string
    {
        $value = $this->input($key, 20);
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value) || (float) $value <= 0) {
            throw new InvalidArgumentException('El peso y la temperatura deben ser valores positivos.');
        }
        return $value;
    }
}
