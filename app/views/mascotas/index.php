<?php
$pageTitle = 'Pacientes';
$activePage = 'mascotas';
?>
<div class="page-toolbar">
    <p class="muted">Consulta los pacientes y accede a sus expedientes clínicos.</p>
    <?php if (in_array($_SESSION['user']['rol'], ['Administrador', 'Recepcionista'], true)): ?>
        <a class="button button-primary" href="index.php?controller=mascota&action=create">＋ Registrar paciente</a>
    <?php endif; ?>
</div>
<section class="panel">
    <div class="panel-heading">
        <div><span class="eyebrow">PACIENTES</span><h2>Directorio de mascotas <span class="count-pill"><?= count($pets) ?></span></h2></div>
        <form class="search-form" method="get" action="index.php">
            <input type="hidden" name="controller" value="mascota">
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Mascota, cliente o documento" aria-label="Buscar pacientes">
            <button class="button button-light" type="submit">Buscar</button>
        </form>
    </div>
    <?php if ($pets === []): ?>
        <div class="empty-state"><strong>No hay pacientes registrados</strong><span>Registra una mascota para comenzar a gestionar su expediente.</span></div>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Paciente</th><th>Especie / raza</th><th>Propietario</th><th>Contacto</th><th>Estado</th><th></th></tr></thead>
            <tbody><?php foreach ($pets as $pet): ?>
                <tr>
                    <td><span class="table-person"><span class="avatar avatar-small avatar-pet"><?= e(strtoupper(substr($pet['nombre'], 0, 1))) ?></span><span><strong><?= e($pet['nombre']) ?></strong><small><?= e($pet['sexo']) ?><?= $pet['fecha_nacimiento'] ? ' · ' . e(date('Y', strtotime($pet['fecha_nacimiento']))) : '' ?></small></span></span></td>
                    <td><?= e($pet['especie']) ?><small class="block-muted"><?= e($pet['raza']) ?></small></td>
                    <td><?= e($pet['propietario_nombres'] . ' ' . $pet['propietario_apellidos']) ?></td>
                    <td><?= e($pet['telefono']) ?></td>
                    <td><span class="badge <?= $pet['activo'] ? 'badge-completada' : 'badge-cancelada' ?>"><?= $pet['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td><a class="text-link" href="index.php?controller=mascota&action=show&id=<?= e($pet['id_mascota']) ?>">Ver expediente →</a></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
