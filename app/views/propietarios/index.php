<?php
$pageTitle = 'Propietarios';
$activePage = 'propietarios';
?>
<div class="page-toolbar">
    <div><p class="muted">Administra la información de los clientes y sus pacientes.</p></div>
    <a class="button button-primary" href="index.php?controller=propietario&action=create">＋ Nuevo propietario</a>
</div>
<section class="panel">
    <div class="panel-heading">
        <div><span class="eyebrow">DIRECTORIO</span><h2>Todos los propietarios <span class="count-pill"><?= count($owners) ?></span></h2></div>
        <form class="search-form" method="get" action="index.php">
            <input type="hidden" name="controller" value="propietario">
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre o documento" aria-label="Buscar propietarios">
            <button class="button button-light" type="submit">Buscar</button>
        </form>
    </div>
    <?php if ($owners === []): ?>
        <div class="empty-state"><strong>No se encontraron propietarios</strong><span>Prueba otra búsqueda o registra un propietario.</span></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Propietario</th><th>Documento</th><th>Contacto</th><th>Pacientes</th><th>Registro</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($owners as $owner): ?>
                    <tr>
                        <td><span class="table-person"><span class="avatar avatar-small"><?= e(strtoupper(substr($owner['nombres'], 0, 1) . substr($owner['apellidos'], 0, 1))) ?></span><span><strong><?= e($owner['nombres'] . ' ' . $owner['apellidos']) ?></strong><small><?= e($owner['correo'] ?? 'Sin correo') ?></small></span></span></td>
                        <td><?= e($owner['tipo_documento']) ?> · <?= e($owner['documento']) ?></td>
                        <td><?= e($owner['telefono']) ?></td>
                        <td><span class="count-pill"><?= e($owner['total_mascotas']) ?></span></td>
                        <td><?= e(date('d/m/Y', strtotime($owner['fecha_registro']))) ?></td>
                        <td><a class="text-link" href="index.php?controller=propietario&action=edit&id=<?= e($owner['id_propietario']) ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
