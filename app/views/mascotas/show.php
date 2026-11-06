<?php
$pageTitle = 'Expediente clínico';
$activePage = 'mascotas';
?>
<div class="page-toolbar">
    <a class="back-link" href="index.php?controller=mascota">← Volver a pacientes</a>
    <span class="badge <?= $pet['activo'] ? 'badge-completada' : 'badge-cancelada' ?>"><?= $pet['activo'] ? 'Paciente activo' : 'Paciente inactivo' ?></span>
</div>
<section class="patient-hero panel">
    <span class="avatar avatar-large avatar-pet"><?= e(strtoupper(substr($pet['nombre'], 0, 1))) ?></span>
    <div class="patient-main"><span class="eyebrow"><?= e($pet['especie']) ?> · <?= e($pet['raza']) ?></span><h2><?= e($pet['nombre']) ?></h2><p><?= e($pet['sexo']) ?><?= $pet['fecha_nacimiento'] ? ' · Nacimiento ' . e(date('d/m/Y', strtotime($pet['fecha_nacimiento']))) : '' ?></p></div>
    <div class="patient-owner"><span class="muted">PROPIETARIO</span><strong><?= e($pet['propietario_nombres'] . ' ' . $pet['propietario_apellidos']) ?></strong><span><?= e($pet['telefono']) ?></span><span><?= e($pet['documento']) ?></span></div>
</section>
<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">HISTORIAL MÉDICO</span><h2>Evoluciones clínicas <span class="count-pill"><?= count($history) ?></span></h2></div></div>
    <?php if ($history === []): ?>
        <div class="empty-state"><strong>Este paciente aún no tiene evoluciones</strong><span>Las consultas registradas aparecerán en esta sección.</span></div>
    <?php else: ?>
        <div class="timeline"><?php foreach ($history as $entry): ?>
            <article class="timeline-item">
                <span class="timeline-dot"></span>
                <div class="timeline-head"><div><strong><?= e(date('d/m/Y · H:i', strtotime($entry['fecha_atencion']))) ?></strong><small><?= e($entry['veterinario']) ?></small></div><?php if ($entry['peso_kg']): ?><span class="count-pill"><?= e($entry['peso_kg']) ?> kg</span><?php endif; ?></div>
                <div class="history-grid"><div><span class="eyebrow">SÍNTOMAS</span><p><?= nl2br(e($entry['sintomas'])) ?></p></div><div><span class="eyebrow">DIAGNÓSTICO</span><p><?= nl2br(e($entry['diagnostico'])) ?></p></div><div><span class="eyebrow">TRATAMIENTO</span><p><?= nl2br(e($entry['tratamiento'])) ?></p></div></div>
                <?php if ($entry['observaciones']): ?><p class="history-note"><?= nl2br(e($entry['observaciones'])) ?></p><?php endif; ?>
                <?php if ($entry['proxima_revision']): ?><span class="muted">Próxima revisión: <?= e(date('d/m/Y', strtotime($entry['proxima_revision']))) ?></span><?php endif; ?>
            </article>
        <?php endforeach; ?></div>
    <?php endif; ?>
</section>
<?php if ($_SESSION['user']['rol'] === 'Veterinario'): ?>
<section class="panel form-panel">
    <div class="panel-heading"><div><span class="eyebrow">ATENCIÓN MÉDICA</span><h2>Registrar evolución</h2><p class="muted">Al seleccionar una vacuna, su fecha de refuerzo se calcula según el catálogo.</p></div></div>
    <form method="post" action="index.php?controller=mascota&action=history" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="mascota_id" value="<?= e($pet['id_mascota']) ?>">
        <div class="field"><label for="cita_id">Cita relacionada</label><select id="cita_id" name="cita_id"><option value="">Atención sin cita</option><?php foreach ($appointments as $appointment): ?><option value="<?= e($appointment['id_cita']) ?>">#<?= e($appointment['id_cita']) ?> · <?= e(date('d/m/Y H:i', strtotime($appointment['fecha_hora']))) ?> · <?= e($appointment['motivo']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="vacuna_id">Vacuna aplicada (opcional)</label><select id="vacuna_id" name="vacuna_id"><option value="">No se aplicó vacuna</option><?php foreach ($vaccines as $vaccine): ?><option value="<?= e($vaccine['id_vacuna']) ?>"><?= e($vaccine['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="peso_kg">Peso (kg)</label><input id="peso_kg" name="peso_kg" type="number" step="0.01" min="0.01"></div>
        <div class="field"><label for="temperatura_c">Temperatura (°C)</label><input id="temperatura_c" name="temperatura_c" type="number" step="0.1" min="0.1"></div>
        <div class="field"><label for="lote">Lote de vacuna</label><input id="lote" name="lote" maxlength="40"></div>
        <div class="field"><label for="proxima_revision">Próxima revisión</label><input id="proxima_revision" name="proxima_revision" type="date"></div>
        <div class="field field-full"><label for="sintomas">Síntomas *</label><textarea id="sintomas" name="sintomas" rows="3" required></textarea></div>
        <div class="field field-full"><label for="diagnostico">Diagnóstico *</label><textarea id="diagnostico" name="diagnostico" rows="3" required></textarea></div>
        <div class="field field-full"><label for="tratamiento">Tratamiento *</label><textarea id="tratamiento" name="tratamiento" rows="3" required></textarea></div>
        <div class="field field-full"><label for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" rows="2"></textarea></div>
        <div class="form-actions field-full"><button class="button button-primary" type="submit">Guardar evolución</button></div>
    </form>
</section>
<?php endif; ?>
