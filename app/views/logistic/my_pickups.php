<?php
// app/views/logistic/my_pickups.php
?>

<?php
function renderArriboLogistica($arrivalInfo)
{
    if ($arrivalInfo === 'Producto Local') {
        return '<span class="badge arrival-bg bg-primary-subtle text-primary">Producto Local</span>';
    }

    $fechaArribo = DateTime::createFromFormat('d/m/Y', $arrivalInfo);

    if (!$fechaArribo) {
        return '<span class="badge bg-light text-dark border">' . htmlspecialchars($arrivalInfo) . '</span>';
    }

    $fechaArribo->setTime(0, 0, 0);
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);

    $esUrgente = ($fechaArribo < $hoy);
    $badgeClass = $esUrgente ? 'urgente-blink text-danger fw-bold' : 'text-dark';
    $iconClass = $esUrgente ? 'bi-exclamation-triangle-fill' : 'bi-calendar3';

    $html = '<div class="arrival-badge ' . $badgeClass . '">';
    $html .= '<i class="bi ' . $iconClass . ' me-1"></i>' . htmlspecialchars($arrivalInfo);

    if ($esUrgente) {
        $intervalo = $hoy->diff($fechaArribo);
        $diasAtraso = $intervalo->days > 0 ? $intervalo->days : 1;
        $txtDia = ($diasAtraso == 1) ? 'día' : 'días';
        $html .= '<span class="d-block small text-uppercase" style="font-size: 0.65rem;">';
        $html .= "Atrasado $diasAtraso $txtDia</span>";
    }

    $html .= '</div>';
    return $html;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            <i class="bi bi-truck me-2 text-primary"></i>
            Mi Ruta de Recolección
        </h3>
        <span class="badge bg-primary px-3 py-2 shadow-sm">
            <span id="counterDisplay"><?= count($data ?? []) ?></span> Pendientes en mi Ruta
        </span>
    </div>

    <!-- Filtros y Buscador Rápido -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body bg-light border-bottom p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Buscador</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="logisticsSearch" class="form-control border-start-0" placeholder="Proveedor, pedido...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1 text-uppercase">Agrupar por</label>
                    <select id="groupBySelect" class="form-select">
                        <option value="none">Sin agrupar</option>
                        <option value="supplier">Proveedor</option>
                        <option value="type">Tipo</option>
                    </select>
                </div>

                <div class="col-md-5 text-end">
                    <button class="btn btn-outline-secondary" onclick="location.reload()" title="Actualizar Ruta"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Tareas del Chofer -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="mainLogisticsTable">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Origen / Sucursal</th>
                        <th>Arribo / Estatus</th>
                        <th>Pedido</th>
                        <th>Producto</th>
                        <th class="text-center pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No tienes recolecciones pendientes asignadas en este momento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $index => $row):
                            $supplierName = $row['supplier_name'] ?? 'S/N';
                            $isLocal = ($row['arrival_info'] === 'Producto Local');
                            $itemId = $row['item_id'];
                        ?>
                            <tr class="logistics-row"
                                data-supplier="<?= htmlspecialchars($supplierName) ?>"
                                data-type="<?= $isLocal ? 'Local' : 'Foráneo' ?>"
                                data-search-content="<?= strtolower(htmlspecialchars($supplierName . ' ' . $row['system_number'] . ' ' . $row['description_product'])) ?>">

                                <td class="ps-4 text-center fw-bold text-muted" style="width: 50px;">
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($supplierName) ?></div>
                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($row['pickup_location'] ?? 'N/A') ?></small>
                                </td>

                                <td>
                                    <span class="badge <?= $isLocal ? 'bg-primary-subtle text-primary' : 'bg-light text-dark border' ?>">
                                        <?= renderArriboLogistica($row['arrival_info']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="badge bg-light text-dark border">#<?= htmlspecialchars($row['system_number']) ?></div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark"><?= (int)$row['quantity'] ?> pz</div>
                                    <small class="text-dark d-block fw-bold" style="line-height: 1.2; font-size: 0.85rem;">
                                        <?= htmlspecialchars($row['description_product']) ?>
                                    </small>
                                </td>

                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm border rounded bg-white">
                                        <!-- Marcar Recolectado -->
                                        <form method="POST" action="index.php?page=mark_pickup" onsubmit="return confirm('¿Confirmas que has recolectado este ítem?');">
                                            <input type="hidden" name="item_id" value="<?= $itemId ?>">
                                            <button type="submit" class="btn btn-white btn-sm text-success border-end" title="Marcar Recolectado">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <!-- Reportar Incidente -->
                                        <button type="button" class="btn btn-white btn-sm text-warning" onclick="reportIncident(<?= $itemId ?>, '<?= htmlspecialchars($row['description_product']) ?>')" title="Reportar Incidente">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Reporte de Incidentes -->
<div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-octagon-fill text-warning me-2"></i>Reportar Incidente / Motivo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-secondary border-0 mb-3">
                    <small class="text-muted d-block text-uppercase fw-bold mb-1">Producto / Ítem:</small>
                    <span id="incidentProductText" class="fw-bold text-dark">---</span>
                </div>
                <form id="formIncidente">
                    <input type="hidden" name="item_id" id="modal_item_id">

                    <!-- Opciones rápidas de motivos -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Motivo Común:</label>
                        <select id="quickReasonSelect" class="form-select form-select-sm mb-2" onchange="fillComment(this)">
                            <option value="">-- Seleccionar motivo rápido (opcional) --</option>
                            <option value="Falta de tiempo / Ruta excedida en la jornada.">Falta de tiempo / Ruta excedida</option>
                            <option value="Proveedor cerrado o sin personal disponible.">Proveedor cerrado / Ausente</option>
                            <option value="Mercancía no lista para entrega/recolección.">Mercancía no lista</option>
                            <option value="">Otro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Detalle del Problema:</label>
                        <textarea name="comments" id="modal_comments" class="form-control" rows="3" placeholder="Describe brevemente la situación..." style="resize: none;" required></textarea>
                    </div>

                    <!-- Acción opcional: Liberar tarea -->
                    <div class="form-check form-switch bg-light p-2 rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="release_task" id="releaseTaskCheck" value="1" checked>
                        <label class="form-check-label small fw-bold text-dark" for="releaseTaskCheck">
                            Regresar tarea al centro logistico.
                        </label>
                    </div>

                    <div id="mensajeFeedback" class="text-center p-2 rounded d-none mt-2"></div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarIncidente" onclick="guardarIncidenteJS()" class="btn btn-warning btn-sm fw-bold shadow-sm">
                    <i class="bi bi-save me-1"></i> Guardar Reporte
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('logisticsSearch');
        const groupBySelect = document.getElementById('groupBySelect');
        const tableBody = document.querySelector('#mainLogisticsTable tbody');

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (groupBySelect) groupBySelect.addEventListener('change', applyFilters);

        function applyFilters() {
            const term = searchInput.value.toLowerCase();
            const groupBy = groupBySelect.value;
            const rows = Array.from(document.querySelectorAll('.logistics-row'));

            document.querySelectorAll('.group-header').forEach(h => h.remove());

            rows.forEach(row => {
                const content = row.getAttribute('data-search-content') || '';
                if (content.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            if (groupBy !== 'none') {
                const visibleRows = rows.filter(r => r.style.display !== 'none');
                const groups = {};

                visibleRows.forEach(row => {
                    const val = row.getAttribute(`data-${groupBy}`) || 'Otros';
                    if (!groups[val]) groups[val] = [];
                    groups[val].push(row);
                });

                Object.keys(groups).sort().forEach(groupName => {
                    const header = document.createElement('tr');
                    header.className = 'group-header table-secondary align-middle';
                    header.innerHTML = `
                        <td class="ps-4 text-center"><i class="bi bi-collection"></i></td>
                        <td colspan="5" class="py-2 ps-3 small text-uppercase fw-bold text-dark">
                            ${groupName} <span class="badge bg-dark ms-2">${groups[groupName].length}</span>
                        </td>
                    `;
                    tableBody.insertBefore(header, groups[groupName][0]);
                });
            }
            updateCounter();
        }

        function updateCounter() {
            const counter = document.getElementById('counterDisplay');
            if (counter) {
                const visibleCount = document.querySelectorAll('.logistics-row:not([style*="display: none"])').length;
                counter.innerText = visibleCount;
            }
        }
    });

    function reportIncident(id, productName) {
        document.getElementById('modal_item_id').value = id;
        document.getElementById('incidentProductText').innerText = productName;
        const modalEl = new bootstrap.Modal(document.getElementById('incidentModal'));
        modalEl.show();
    }

    // Función auxiliar para rellenar rápido el comentario con el select
    function fillComment(select) {
        if (select.value) {
            document.getElementById('modal_comments').value = select.value;
        }
    }
</script>

<script src="public/assets/js/logistic/saveIncident.js"></script>