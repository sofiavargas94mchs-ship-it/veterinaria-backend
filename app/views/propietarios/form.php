<?php
$pageTitle = $owner ? 'Editar propietario' : 'Nuevo propietario';
$activePage = 'propietarios';
$isEdit = isset($owner['id_propietario']);
$action = $isEdit ? 'update' : 'save';
?>
<div class="form-page">
    <a class="back-link" href="index.php?controller=propietario">← Volver a propietarios</a>
    <section class="panel form-panel">
        <div class="panel-heading"><div><span class="eyebrow">CLIENTES</span><h2><?= e($pageTitle) ?></h2><p class="muted">Los campos marcados son necesarios para guardar el registro.</p></div></div>
        <form method="post" action="index.php?controller=propietario&action=<?= e($action) ?>" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= e($owner['id_propietario']) ?>"><?php endif; ?>
            <div class="field"><label for="tipo_documento">Tipo de documento *</label><select id="tipo_documento" name="tipo_documento" required>
                <?php foreach (['CC', 'CE', 'TI', 'PAS', 'NIT'] as $type): ?><option value="<?= e($type) ?>" <?= ($owner['tipo_documento'] ?? 'CC') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?>
            </select></div>
            <div class="field"><label for="documento">Número de documento *</label><input id="documento" name="documento" maxlength="20" value="<?= e($owner['documento'] ?? '') ?>" required></div>
            <div class="field"><label for="nombres">Nombres *</label><input id="nombres" name="nombres" maxlength="100" value="<?= e($owner['nombres'] ?? '') ?>" required></div>
            <div class="field"><label for="apellidos">Apellidos *</label><input id="apellidos" name="apellidos" maxlength="100" value="<?= e($owner['apellidos'] ?? '') ?>" required></div>
            <div class="field"><label for="telefono">Teléfono *</label><input id="telefono" name="telefono" type="tel" maxlength="20" value="<?= e($owner['telefono'] ?? '') ?>" required></div>
            <div class="field"><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" maxlength="150" value="<?= e($owner['correo'] ?? '') ?>"></div>
            <div class="field field-full"><label for="direccion">Dirección</label><input id="direccion" name="direccion" maxlength="200" value="<?= e($owner['direccion'] ?? '') ?>"></div>
            <div class="form-actions field-full"><a class="button button-light" href="index.php?controller=propietario">Cancelar</a><button class="button button-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Registrar propietario' ?></button></div>
        </form>
    </section>
</div>
