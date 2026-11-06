<?php

declare(strict_types=1);

final class PropietarioController extends Controller
{
    public function index(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        $this->view('propietarios/index', [
            'owners' => (new Propietario($this->pdo))->listar(mb_substr($search, 0, 100)),
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $this->view('propietarios/form', ['owner' => null]);
    }

    public function save(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $this->requirePost();
        $data = $this->ownerData();
        if (!$this->validateOwner($data)) {
            $this->view('propietarios/form', ['owner' => $data]);
            return;
        }

        try {
            (new Propietario($this->pdo))->crear($data);
            $this->flash('success', 'El propietario se registró correctamente.');
            $this->redirect('propietario');
        } catch (PDOException $exception) {
            error_log((string) $exception);
            $message = (string) $exception->getCode() === '23000'
                ? 'El documento o correo ya está registrado.'
                : 'No fue posible guardar el propietario. Inténtalo nuevamente.';
            $this->flash('danger', $message);
            $this->view('propietarios/form', ['owner' => $data]);
        }
    }

    public function edit(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $owner = (new Propietario($this->pdo))->buscarPorId($this->queryId());
        if ($owner === null) {
            $this->flash('danger', 'No se encontró el propietario solicitado.');
            $this->redirect('propietario');
        }
        $this->view('propietarios/form', ['owner' => $owner]);
    }

    public function update(): void
    {
        $this->requireRole('Administrador', 'Recepcionista');
        $this->requirePost();
        $id = $this->postId();
        $data = $this->ownerData();
        $data['id_propietario'] = $id;
        if (!$this->validateOwner($data)) {
            $this->view('propietarios/form', ['owner' => $data]);
            return;
        }

        try {
            (new Propietario($this->pdo))->actualizar($id, $data);
            $this->flash('success', 'Los datos del propietario se actualizaron.');
            $this->redirect('propietario');
        } catch (PDOException $exception) {
            error_log((string) $exception);
            $message = (string) $exception->getCode() === '23000'
                ? 'El documento o correo ya está registrado por otro propietario.'
                : 'No fue posible actualizar el propietario.';
            $this->flash('danger', $message);
            $this->view('propietarios/form', ['owner' => $data + ['id_propietario' => $id]]);
        }
    }

    private function ownerData(): array
    {
        return [
            'tipo_documento' => $this->input('tipo_documento', 3),
            'documento' => $this->input('documento', 20),
            'nombres' => $this->input('nombres', 100),
            'apellidos' => $this->input('apellidos', 100),
            'telefono' => $this->input('telefono', 20),
            'direccion' => $this->nullableInput('direccion', 200),
            'correo' => $this->nullableInput('correo', 150),
        ];
    }

    private function validateOwner(array $data): bool
    {
        if (!in_array($data['tipo_documento'], ['CC', 'CE', 'TI', 'PAS', 'NIT'], true)
            || $data['documento'] === '' || $data['nombres'] === ''
            || $data['apellidos'] === '' || $data['telefono'] === '') {
            $this->flash('danger', 'Completa los campos obligatorios del propietario.');
            return false;
        }
        if ($data['correo'] !== null && filter_var($data['correo'], FILTER_VALIDATE_EMAIL) === false) {
            $this->flash('danger', 'El correo electrónico no tiene un formato válido.');
            return false;
        }
        return true;
    }

    private function nullableInput(string $key, int $maxLength): ?string
    {
        $value = $this->input($key, $maxLength);
        return $value === '' ? null : $value;
    }

    private function queryId(): int
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('El identificador del propietario no es válido.');
        }
        return $id;
    }

    private function postId(): int
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('El identificador del propietario no es válido.');
        }
        return $id;
    }
}
