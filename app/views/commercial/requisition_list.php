<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-journal-check text-primary me-2"></i>
            <?= ($viewData['is_purchasing'] ?? false) ? 'Bandeja Global de Requisiciones (Compras)' : 'Mis Requisiciones Internas' ?>
        </h2>
    </div>

    <!-- 🔍 BARRA DE FILTROS AVANZADA -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <div class="row g-3">
                <!-- 1. Búsqueda General (Folio o Cliente) -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Buscar (Folio / Cliente)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="filter_search" class="form-control" placeholder="Escribe folio o cliente...">
                    </div>
                </div>

                <!-- 2. Filtro por Estatus -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Estatus</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="Borrador">Borrador</option>
                        <option value="Aprobada">Aprobada</option>
                        <option value="Convertida">Convertida a Compra</option>
                    </select>
                </div>

                <!-- 3. Filtro por Periodo Predefinido -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Periodo</label>
                    <select id="filter_period" class="form-select form-select-sm">
                        <option value="this_month" selected>Este mes</option>
                        <option value="last_3_months">Últimos 3 meses</option>
                        <option value="this_year">Este año</option>
                        <option value="all">Todas</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>

                <!-- 4. Fecha Desde -->
                <div class="col-md-2 date-custom-container d-none">
                    <label class="form-label small fw-bold text-muted mb-1">Desde</label>
                    <input type="date" id="filter_date_from" class="form-control form-control-sm">
                </div>

                <!-- 5. Fecha Hasta -->
                <div class="col-md-2 date-custom-container d-none">
                    <label class="form-label small fw-bold text-muted mb-1">Hasta</label>
                    <input type="date" id="filter_date_to" class="form-control form-control-sm">
                </div>

                <!-- 6. Botón de Limpiar Filtros -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" id="clear_filters_btn" class="btn btn-outline-secondary btn-sm w-100" title="Restaurar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE REQUISICIONES -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="requisitions_table">
                    <thead class="table-light">
                        <tr>
                            <th>Folio Req.</th>
                            <th>Cotización Origen</th>
                            <th>Fecha</th>
                            <th>Cliente Destino</th>
                            <th>Estatus</th>
                            <th>Solicitante</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requisitions) && is_array($requisitions)): ?>
                            <?php foreach ($requisitions as $req): ?>
                                <tr data-status="<?= htmlspecialchars($req['status'] ?? 'Borrador') ?>" data-date="<?= htmlspecialchars(substr($req['quotation_date'], 0, 10)) ?>">
                                    <td><strong class="font-monospace text-primary"><?= htmlspecialchars($req['folio']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark font-monospace"><?= htmlspecialchars($req['quotation_folio']) ?></span></td>
                                    <td><?= htmlspecialchars($req['quotation_date']) ?></td>
                                    <td><span class="badge bg-success">Cliente</span> <?= htmlspecialchars($req['customer_name'] ?? 'N/D') ?></td>
                                    <td>
                                        <?php
                                        $currentStatus = $req['status'] ?? 'Borrador';
                                        $badgeBg = 'bg-secondary';
                                        $statusLabel = $currentStatus;

                                        if ($currentStatus === 'Borrador') {
                                            $badgeBg = 'bg-warning text-dark';
                                        } elseif ($currentStatus === 'Enviada') {
                                            $badgeBg = 'bg-info text-dark';
                                        } elseif ($currentStatus === 'Aprobada') {
                                            $badgeBg = 'bg-success';
                                        } elseif ($currentStatus === 'Cancelada' || $currentStatus === 'Rechazada') {
                                            $badgeBg = 'bg-danger';
                                        } else {
                                            $badgeBg = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeBg ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>

                                        <?php if (!empty($req['replacement_id'])): ?>
                                            <a href="index.php?page=view_requisition_detail&id=<?= $req['replacement_id'] ?>" class="d-block fs-8 text-primary text-decoration-none mt-1" title="Ir a la versión sustituta">
                                                <i class="bi bi-box-arrow-up-right"></i> Ver nueva versión
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(($req['firstname'] ?? '') . ' ' . ($req['apellidos'] ?? '')) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=view_requisition_detail&id=<?= $req['id'] ?>" class="btn btn-outline-primary" title="Ver Detalle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="no_results_row">
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2"></i><br>
                                    No hay requisiciones registradas.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="empty_search_msg" class="text-center text-muted py-4 d-none">
                    <i class="bi bi-search fs-2"></i><br>
                    No se encontraron requisiciones que coincidan con los filtros aplicados.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT DE FILTRADO Y PERIODOS -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('filter_search');
        const statusSelect = document.getElementById('filter_status');
        const periodSelect = document.getElementById('filter_period');
        const dateFromInput = document.getElementById('filter_date_from');
        const dateToInput = document.getElementById('filter_date_to');
        const dateContainers = document.querySelectorAll('.date-custom-container');
        const clearBtn = document.getElementById('clear_filters_btn');

        const tableRows = document.querySelectorAll('#requisitions_table tbody tr:not(#no_results_row)');
        const emptyMsg = document.getElementById('empty_search_msg');

        function getDateRangeByPeriod(period) {
            const now = new Date();
            let startDate = null;
            let endDate = new Date().toISOString().split('T')[0];

            if (period === 'this_month') {
                startDate = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            } else if (period === 'last_3_months') {
                const d = new Date();
                d.setMonth(d.getMonth() - 3);
                startDate = d.toISOString().split('T')[0];
            } else if (period === 'this_year') {
                startDate = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
            } else if (period === 'all') {
                startDate = null;
                endDate = null;
            }
            return {
                startDate,
                endDate
            };
        }

        function handlePeriodChange() {
            const val = periodSelect.value;
            if (val === 'custom') {
                dateContainers.forEach(el => el.classList.remove('d-none'));
            } else {
                dateContainers.forEach(el => el.classList.add('d-none'));
                const range = getDateRangeByPeriod(val);
                if (dateFromInput) dateFromInput.value = range.startDate || '';
                if (dateToInput) dateToInput.value = range.endDate || '';
            }
            filterTable();
        }

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedStatus = statusSelect.value;
            const currentPeriod = periodSelect.value;

            let dateFrom = dateFromInput.value;
            let dateTo = dateToInput.value;

            if (currentPeriod !== 'custom') {
                const range = getDateRangeByPeriod(currentPeriod);
                dateFrom = range.startDate;
                dateTo = range.endDate;
            }

            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const rowDate = row.getAttribute('data-date');

                const matchesSearch = rowText.includes(searchTerm);
                let matchesStatus = selectedStatus === "" || rowStatus === selectedStatus;
                let matchesDate = true;

                if (rowDate) {
                    if (dateFrom && rowDate < dateFrom) matchesDate = false;
                    if (dateTo && rowDate > dateTo) matchesDate = false;
                }

                if (matchesSearch && matchesStatus && matchesDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && tableRows.length > 0) {
                emptyMsg.classList.remove('d-none');
            } else {
                emptyMsg.classList.add('d-none');
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (statusSelect) statusSelect.addEventListener('change', filterTable);
        if (periodSelect) periodSelect.addEventListener('change', handlePeriodChange);
        if (dateFromInput) dateFromInput.addEventListener('change', filterTable);
        if (dateToInput) dateToInput.addEventListener('change', filterTable);

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                statusSelect.value = '';
                periodSelect.value = 'this_month';
                handlePeriodChange();
            });
        }

        handlePeriodChange();
    });
</script>