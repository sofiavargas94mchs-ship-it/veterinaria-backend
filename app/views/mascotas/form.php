<?php
$pageTitle = 'Registrar paciente';
$activePage = 'mascotas';
?>
<div class="form-page">
    <a class="back-link" href="index.php?controller=mascota">← Volver a pacientes</a>
    <section class="panel form-panel">
        <div class="panel-heading"><div><span class="eyebrow">PACIENTE NUEVO</span><h2>Datos de la mascota</h2><p class="muted">Vincula al paciente con su propietario y su raza.</p></div></div>
        <?php if ($owners === [] || $breeds === []): ?>
            <div class="alert alert-danger"><?= $owners === [] ? 'Registra primero un propietario.' : 'No hay razas configuradas en el catálogo.' ?></div>
        <?php else: ?>
        <form method="post" action="index.php?controller=mascota&action=save" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" maxlength="80" value="<?= e($pet['nombre'] ?? '') ?>" required></div>
            <div class="field"><label for="propietario_id">Propietario *</label><select id="propietario_id" name="propietario_id" required><option value="">Selecciona un propietario</option><?php foreach ($owners as $owner): ?><option value="<?= e($owner['id_propietario']) ?>" <?= (string) ($pet['propietario_id'] ?? '') === (string) $owner['id_propietario'] ? 'selected' : '' ?>><?= e($owner['nombres'] . ' ' . $owner['apellidos'] . ' · ' . $owner['documento']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="raza_id">Especie y raza *</label><select id="raza_id" name="raza_id" required><option value="">Selecciona una raza</option><?php foreach ($breeds as $breed): ?><option value="<?= e($breed['id_raza']) ?>" <?= (string) ($pet['raza_id'] ?? '') === (string) $breed['id_raza'] ? 'selected' : '' ?>><?= e($breed['especie'] . ' · ' . $breed['nombre']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="sexo">Sexo *</label><select id="sexo" name="sexo" required><option value="">Selecciona</option><option <?= ($pet['sexo'] ?? '') === 'Macho' ? 'selected' : '' ?>>Macho</option><option <?= ($pet['sexo'] ?? '') === 'Hembra' ? 'selected' : '' ?>>Hembra</option></select></div>
            <div class="field"><label for="fecha_nacimiento">Fecha de nacimiento</label><input id="fecha_nacimiento" name="fecha_nacimiento" type="date" value="<?= e($pet['fecha_nacimiento'] ?? '') ?>"></div>
            <div class="field"><label for="color">Color</label><input id="color" name="color" maxlength="50" value="<?= e($pet['color'] ?? '') ?>"></div>
            <div class="field field-full"><label for="microchip">Microchip</label><input id="microchip" name="microchip" maxlength="30" value="<?= e($pet['microchip'] ?? '') ?>"></div>
            <div class="form-actions field-full"><a class="button button-light" href="index.php?controller=mascota">Cancelar</a><button class="button button-primary" type="submit">Registrar paciente</button></div>
        </form>
        <?php endif; ?>
    </section>
</div>
