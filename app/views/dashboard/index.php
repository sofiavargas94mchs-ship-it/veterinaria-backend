<?php
$pageTitle = 'Resumen';
$activePage = 'dashboard';
$cards = [
    ['label' => 'Pacientes activos', 'value' => $metrics['mascotas'], 'icon' => '♡', 'tone' => 'mint', 'note' => 'Mascotas registradas'],
    ['label' => 'Citas de hoy', 'value' => $metrics['citas_hoy'], 'icon' => '▦', 'tone' => 'blue', 'note' => 'En la agenda diaria'],
    ['label' => 'Ingresos del mes', 'value' => $metrics['ingresos_mes'] === null ? '—' : '$' . number_format($metrics['ingresos_mes'], 0, ',', '.'), 'icon' => '$', 'tone' => 'gold', 'note' => $metrics['ingresos_mes'] === null ? 'Disponible para administración' : 'Facturación emitida'],
    ['label' => 'Vacunas próximas', 'value' => $metrics['vacunas_proximas'], 'icon' => '+', 'tone' => 'rose', 'note' => 'Dentro de los próximos 30 días'],
];
?>
<div class="welcome-row">
    <div><h2>Hola, <?= e($_SESSION['user']['nombre']) ?> <span class="wave">✦</span></h2><p class="muted">Aquí tienes el resumen de actividad de tu clínica.</p></div>
    <a class="button button-primary" href="index.php?controller=cita&action=create">＋ Agendar cita</a>
</div>
<div class="metric-grid">
    <?php foreach ($cards as $card): ?>
        <article class="metric-card">
            <div class="metric-top"><span><?= e($card['label']) ?></span><span class="metric-icon <?= e($card['tone']) ?>"><?= e($card['icon']) ?></span></div>
            <strong class="metric-value"><?= e($card['value']) ?></strong>
            <span class="metric-note"><?= e($card['note']) ?></span>
        </article>
    <?php endforeach; ?>
</div>
<section class="panel">
    <div class="panel-heading">
        <div><span class="eyebrow">AGENDA</span><h2>Citas de hoy</h2></div>
        <a class="text-link" href="index.php?controller=cita&action=agenda">Ver agenda completa <span aria-hidden="true">→</span></a>
    </div>
    <?php if ($appointments === []): ?>
        <div class="empty-state"><span class="empty-icon">▦</span><strong>No hay citas para hoy</strong><span>La agenda está libre por el momento.</span></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Hora</th><th>Paciente</th><th>Propietario</th><th>Veterinario</th><th>Motivo</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td class="strong"><?= e(date('H:i', strtotime($appointment['fecha_hora']))) ?></td>
                        <td><span class="table-person"><span class="avatar avatar-small"><?= e(strtoupper(substr($appointment['mascota'], 0, 1))) ?></span><?= e($appointment['mascota']) ?></span></td>
                        <td><?= e($appointment['propietario']) ?></td>
                        <td><?= e($appointment['veterinario']) ?></td>
                        <td><?= e($appointment['motivo']) ?></td>
                        <td><span class="badge badge-<?= e(strtolower(str_replace(' ', '-', $appointment['estado']))) ?>"><?= e($appointment['estado']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
