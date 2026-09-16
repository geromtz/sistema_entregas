<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-spreadsheet text-primary"></i> Bandeja de Cotizaciones</h2>
        <a href="index.php?page=create_quotation" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nueva Cotización
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Cliente / Prospecto</th>
                            <th>Atención a</th>
                            <th>Total</th>
                            <th>Estatus</th>
                            <th>Vendedor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($quotations) && is_array($quotations)): ?>
                            <?php foreach ($quotations as $quot): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($quot['folio']) ?></strong></td>
                                    <td><?= htmlspecialchars($quot['quotation_date']) ?></td>
                                    <td>
                                        <?php if (!empty($quot['customer_name'])): ?>
                                            <span class="badge bg-success">Cliente</span> <?= htmlspecialchars($quot['customer_name']) ?>
                                        <?php elseif (!empty($quot['prospect_name'])): ?>
                                            <span class="badge bg-info text-dark">Prospecto</span> <?= htmlspecialchars($quot['prospect_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($quot['attention_to']) ?></td>
                                    <td><strong>$<?= number_format($quot['total'], 2) ?> <?= htmlspecialchars($quot['currency']) ?></strong></td>
                                    <td>
                                        <?php 
                                            $badgeBg = 'bg-secondary';
                                            if ($quot['id_status'] == 1) $badgeBg = 'bg-warning text-dark'; // Borrador
                                            if ($quot['id_status'] == 2) $badgeBg = 'bg-info text-dark';    // Enviada
                                            if ($quot['id_status'] == 3) $badgeBg = 'bg-success';          // Aprobada
                                            if ($quot['id_status'] == 4) $badgeBg = 'bg-danger';           // Rechazada
                                            if ($quot['id_status'] == 5) $badgeBg = 'bg-primary';          // Convertida
                                        ?>
                                        <span class="badge <?= $badgeBg ?>">
                                            <?php
                                                $statuses = [1 => 'Borrador', 2 => 'Enviada', 3 => 'Aprobada', 4 => 'Rechazada', 5 => 'Convertida'];
                                                echo $statuses[$quot['id_status']] ?? 'Desconocido';
                                            ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($quot['firstname'] . ' ' . $quot['apellidos']) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=edit_quotation&id=<?= $quot['id'] ?>" class="btn btn-outline-secondary" title="Ver / Editar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($quot['id_status'] == 3): // Si está aprobada, mostrar opción de convertir a orden ?>
                                                <a href="index.php?page=convert_quotation&id=<?= $quot['id'] ?>" class="btn btn-outline-success" title="Convertir a Pedido">
                                                    <i class="bi bi-arrow-right-square"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2"></i><br>
                                    No hay cotizaciones registradas en tu equipo de trabajo.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>