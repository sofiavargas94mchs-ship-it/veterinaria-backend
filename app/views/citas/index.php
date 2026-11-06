<?php
$pageTitle = 'Agenda';
$activePage = 'citas';
$statuses = ['Programada', 'En consulta', 'Completada', 'Cancelada'];
?>
<div class="page-toolbar">
    <div><p class="muted">Organiza las consultas y revisa el estado de cada paciente.</p></div>
    <?php if (in_array($_SESSION['user']['rol'], ['Administrador', 'Recepcionista'], true)): ?>
        <a class="button button-primary" href="index.php?controller=cita&action=create">＋ Agendar cita</a>
    <?php endif; ?>
</div>
<section class="panel">
    <div class="panel-heading agenda-heading">
        <div><span class="eyebrow">AGENDA DIARIA</span><h2>Citas programadas</h2></div>
        <form class="filter-form" method="get" action="index.php">
            <input type="hidden" name="controller" value="cita">
            <input type="hidden" name="action" value="agenda">
            <label for="fecha">Fecha</label><input id="fecha" name="fecha" type="date" value="<?= e($date) ?>">
            <label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach ($statuses as $item): ?><option value="<?= e($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select>
            <button class="button button-light" type="submit">Filtrar</button>
        </form>
    </div>
    <?php if ($appointments === []): ?>
        <div class="empty-state"><span class="empty-icon">▦</span><strong>No hay citas con estos filtros</strong><span>Selecciona otra fecha o estado.</span></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Hora</th><th>Paciente</th><th>Propietario</th><th>Veterinario</th><th>Motivo</th><th>Estado</th><th>Actualizar</th></tr></thead>
                <tbody><?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td class="strong"><?= e(date('H:i', strtotime($appointment['fecha_hora']))) ?><small class="block-muted"><?= e($appointment['duracion_min']) ?> min</small></td>
                        <td><a class="table-person text-link" href="index.php?controller=mascota&action=show&id=<?= e($appointment['id_mascota']) ?>"><span class="avatar avatar-small avatar-pet"><?= e(strtoupper(substr($appointment['mascota'], 0, 1))) ?></span><?= e($appointment['mascota']) ?></a></td>
                        <td><?= e($appointment['propietario_nombres'] . ' ' . $appointment['propietario_apellidos']) ?><small class="block-muted"><?= e($appointment['telefono']) ?></small></td>
                        <td><?= e($appointment['veterinario_nombre'] . ' ' . $appointment['veterinario_apellido']) ?></td>
                        <td><?= e($appointment['motivo']) ?></td>
                        <td><span class="badge badge-<?= e(strtolower(str_replace(' ', '-', $appointment['estado']))) ?>"><?= e($appointment['estado']) ?></span></td>
                        <td><form class="inline-form" method="post" action="index.php?controller=cita&action=state">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id_cita" value="<?= e($appointment['id_cita']) ?>">
                            <input type="hidden" name="fecha" value="<?= e($date) ?>">
                            <select name="estado" aria-label="Cambiar estado de la cita #<?= e($appointment['id_cita']) ?>"><?php foreach ($statuses as $item): ?><option value="<?= e($item) ?>" <?= $appointment['estado'] === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select>
                            <button class="button button-small button-light" type="submit">Guardar</button>
                        </form></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
