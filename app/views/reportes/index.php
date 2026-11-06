<?php
$pageTitle = 'Reportes';
$activePage = 'reportes';
$maxIncome = max(array_map(static fn(array $row): float => (float) $row['ingresos'], $monthly ?: [['ingresos' => 1]]));
$maxVaccines = max(array_map(static fn(array $row): int => (int) $row['vacunaciones'], $vaccinations ?: [['vacunaciones' => 1]]));
?>
<div class="page-toolbar"><p class="muted">Indicadores de actividad, ingresos y seguimiento preventivo.</p><span class="report-period">Últimos 12 meses</span></div>
<div class="report-summary">
    <article class="metric-card"><div class="metric-top"><span>Citas atendidas</span><span class="metric-icon blue">✓</span></div><strong class="metric-value"><?= e($attendedLastMonth) ?></strong><span class="metric-note">Durante los últimos 30 días</span></article>
    <article class="metric-card"><div class="metric-top"><span>Veterinarios activos</span><span class="metric-icon mint">♙</span></div><strong class="metric-value"><?= e(count($appointments)) ?></strong><span class="metric-note">Con actividad en la clínica</span></article>
    <article class="metric-card"><div class="metric-top"><span>Vacunas registradas</span><span class="metric-icon gold">+</span></div><strong class="metric-value"><?= e(array_sum(array_map(static fn(array $row): int => (int) $row['vacunaciones'], $vaccinations))) ?></strong><span class="metric-note">En los últimos 12 meses</span></article>
</div>
<div class="report-grid">
    <section class="panel">
        <div class="panel-heading"><div><span class="eyebrow">FINANZAS</span><h2>Ingresos mensuales</h2></div></div>
        <?php if ($monthly === []): ?><div class="empty-state"><strong>Sin facturación en el periodo</strong></div><?php else: ?>
            <div class="bar-chart"><?php foreach ($monthly as $row): $percent = max(3, (int) round(((float) $row['ingresos'] / $maxIncome) * 100)); ?>
                <div class="bar-row"><span class="bar-label"><?= e($row['periodo']) ?></span><div class="bar-track"><span class="bar-fill" style="width: <?= e($percent) ?>%"></span></div><strong class="bar-value">$<?= e(number_format((float) $row['ingresos'], 0, ',', '.')) ?></strong></div>
            <?php endforeach; ?></div>
        <?php endif; ?>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><span class="eyebrow">PREVENCIÓN</span><h2>Vacunaciones por mes</h2></div></div>
        <?php if ($vaccinations === []): ?><div class="empty-state"><strong>Sin vacunaciones en el periodo</strong></div><?php else: ?>
            <div class="bar-chart"><?php foreach ($vaccinations as $row): $percent = max(3, (int) round(((int) $row['vacunaciones'] / $maxVaccines) * 100)); ?>
                <div class="bar-row"><span class="bar-label"><?= e($row['periodo']) ?></span><div class="bar-track"><span class="bar-fill bar-fill-gold" style="width: <?= e($percent) ?>%"></span></div><strong class="bar-value"><?= e($row['vacunaciones']) ?></strong></div>
            <?php endforeach; ?></div>
        <?php endif; ?>
    </section>
</div>
<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">EQUIPO MÉDICO</span><h2>Citas por veterinario</h2></div></div>
    <?php if ($appointments === []): ?><div class="empty-state"><strong>No hay veterinarios para mostrar</strong></div><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Veterinario</th><th>Citas</th><th>Atendidas</th><th>Proporción atendida</th></tr></thead><tbody>
        <?php foreach ($appointments as $row): $total = (int) $row['total_citas']; $done = (int) $row['atendidas']; $percent = $total > 0 ? (int) round($done * 100 / $total) : 0; ?>
            <tr><td class="strong"><?= e($row['veterinario']) ?></td><td><?= e($total) ?></td><td><?= e($done) ?></td><td><div class="progress-inline"><span><i style="width: <?= e($percent) ?>%"></i></span><strong><?= e($percent) ?>%</strong></div></td></tr>
        <?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">CUIDADO PREVENTIVO</span><h2>Próximos refuerzos</h2></div></div>
    <?php if ($upcomingVaccines === []): ?><div class="empty-state"><strong>No hay refuerzos próximos o vencidos</strong></div><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Paciente</th><th>Propietario</th><th>Vacuna</th><th>Próxima dosis</th><th>Estado</th></tr></thead><tbody>
        <?php foreach ($upcomingVaccines as $vaccine): ?><tr><td class="strong"><?= e($vaccine['mascota']) ?></td><td><?= e($vaccine['propietario']) ?></td><td><?= e($vaccine['vacuna']) ?></td><td><?= e(date('d/m/Y', strtotime($vaccine['fecha_proxima_dosis']))) ?></td><td><span class="badge <?= $vaccine['estado'] === 'Vencida' ? 'badge-cancelada' : 'badge-programada' ?>"><?= e($vaccine['estado']) ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
