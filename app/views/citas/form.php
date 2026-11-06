<?php
$pageTitle = 'Agendar cita';
$activePage = 'citas';
?>
<div class="form-page">
    <a class="back-link" href="index.php?controller=cita&action=agenda">← Volver a la agenda</a>
    <section class="panel form-panel">
        <div class="panel-heading"><div><span class="eyebrow">AGENDA MÉDICA</span><h2>Nueva cita</h2><p class="muted">El sistema valida la disponibilidad del veterinario antes de guardar.</p></div></div>
        <?php if ($pets === [] || $veterinarians === []): ?>
            <div class="alert alert-danger"><?= $pets === [] ? 'Registra primero una mascota activa.' : 'No hay veterinarios activos para asignar.' ?></div>
        <?php else: ?>
        <form method="post" action="index.php?controller=cita&action=save" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field field-full"><label for="mascota_id">Paciente *</label><select id="mascota_id" name="mascota_id" required><option value="">Selecciona un paciente</option><?php foreach ($pets as $pet): ?><option value="<?= e($pet['id_mascota']) ?>"><?= e($pet['mascota'] . ' · ' . $pet['nombres'] . ' ' . $pet['apellidos']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="veterinario_id">Veterinario *</label><select id="veterinario_id" name="veterinario_id" required><option value="">Selecciona un veterinario</option><?php foreach ($veterinarians as $vet): ?><option value="<?= e($vet['id_usuario']) ?>"><?= e($vet['nombre'] . ' ' . $vet['apellido']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="fecha_hora">Fecha y hora *</label><input id="fecha_hora" name="fecha_hora" type="datetime-local" min="<?= e(date('Y-m-d\TH:i')) ?>" required></div>
            <div class="field"><label for="duracion_min">Duración</label><select id="duracion_min" name="duracion_min"><option value="15">15 minutos</option><option value="30" selected>30 minutos</option><option value="45">45 minutos</option><option value="60">60 minutos</option></select></div>
            <div class="field"><label for="motivo">Motivo de consulta *</label><input id="motivo" name="motivo" maxlength="255" placeholder="Ej. Control general" required></div>
            <div class="form-actions field-full"><a class="button button-light" href="index.php?controller=cita&action=agenda">Cancelar</a><button class="button button-primary" type="submit">Confirmar cita</button></div>
        </form>
        <?php endif; ?>
    </section>
</div>
