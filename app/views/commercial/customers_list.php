<?php
// app/views/commercial/customers_list.php
?>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Razón Social / Comercial</th>
                <th>Facturación (Corte / OC)</th>
                <th class="text-center">Cierres (Mes / Dic)</th>
                <th class="text-center">Parcialidades</th>
                <th class="text-center">Horarios y Días (Logística)</th>
                <th class="text-center">Notas / Info</th>
                <th class="text-end pe-3">Acciones</th>
            </tr>
        </thead>
        <tbody id="customersTableBody">
            <?php if (!empty($customers)): ?>
                <?php foreach ($customers as $client): ?>
                    <?php
                    // Preparar resumen de días permitidos para el popover
                    $diasArr = [];
                    if (!empty($client['allow_monday'])) $diasArr[] = 'Lun';
                    if (!empty($client['allow_tuesday'])) $diasArr[] = 'Mar';
                    if (!empty($client['allow_wednesday'])) $diasArr[] = 'Mié';
                    if (!empty($client['allow_thursday'])) $diasArr[] = 'Jue';
                    if (!empty($client['allow_friday'])) $diasArr[] = 'Vie';
                    if (!empty($client['allow_saturday'])) $diasArr[] = 'Sáb';
                    $diasStr = !empty($diasArr) ? implode(', ', $diasArr) : 'Ninguno';
                    $horarioStr = (!empty($client['delivery_time_start']) && !empty($client['delivery_time_end']))
                        ? substr($client['delivery_time_start'], 0, 5) . ' a ' . substr($client['delivery_time_end'], 0, 5) . ' hrs'
                        : 'Sin horario fijo';
                    ?>
                    <tr class="customer-row" data-search="<?= strtolower(($client['business_name'] ?? '') . ' ' . ($client['company_name'] ?? '')) ?>">

                        <!-- 1. Identidad -->
                        <td class="ps-3">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($client['business_name']) ?></div>
                            <small class="text-muted"><i class="bi bi-shop me-1"></i><?= htmlspecialchars($client['company_name'] ?? 'Sin nombre comercial') ?></small>
                        </td>

                        <!-- 2. Facturación -->
                        <td>
                            <div class="small fw-bold text-primary">Corte: <?= !empty($client['invoice_cutoff_day']) ? 'Día ' . $client['invoice_cutoff_day'] : 'Libre' ?></div>
                            <div class="d-flex gap-1 mt-1">
                                <?php if (!empty($client['requires_po_number'])): ?>
                                    <span class="badge bg-secondary" style="font-size: 0.65rem;" title="Exige Orden de Compra">OC Req.</span>
                                <?php endif; ?>
                                <?php if (!empty($client['requires_remission_first'])): ?>
                                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem;" title="Exige remisión previa">Remisión</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 3. Cierres (Mes / Diciembre) -->
                        <td class="text-center">
                            <div class="small fw-bold"><?= !empty($client['invoice_cutoff_day']) ? 'Día ' . $client['invoice_cutoff_day'] : 'N/D' ?></div>
                            <?php if (!empty($client['december_close_date'])): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1" style="font-size: 0.7rem;">
                                    Dic: <?= htmlspecialchars($client['december_close_date']) ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- 4. Parcialidades -->
                        <td class="text-center">
                            <?php if (isset($client['accepts_partial_invoices'])): ?>
                                <?= $client['accepts_partial_invoices'] ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Sí acepta</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">No (Completo)</span>' ?>
                            <?php else: ?>
                                <span class="text-muted small">N/D</span>
                            <?php endif; ?>
                        </td>

                        <!-- 5. Horarios y Días (Con Popover de Bootstrap) -->
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-info py-0 px-2 text-dark border"
                                data-bs-toggle="popover"
                                data-bs-trigger="focus"
                                data-bs-title="Detalle Logístico de Recepción"
                                data-bs-html="true"
                                data-bs-content="<strong>Días hábiles:</strong> <?= $diasStr ?><br><strong>Horario:</strong> <?= $horarioStr ?>">
                                <i class="bi bi-clock-history text-primary me-1"></i> Ver Horarios
                            </button>
                        </td>

                        <!-- 6. Notas o Instrucciones Especiales -->
                        <td class="text-center">
                            <?php if (!empty($client['special_instructions'])): ?>
                                <button type="button" class="btn btn-sm btn-light text-warning border py-0 px-2"
                                    data-bs-toggle="popover"
                                    data-bs-trigger="focus"
                                    data-bs-title="Instrucciones Especiales"
                                    data-bs-content="<?= htmlspecialchars($client['special_instructions'], ENT_QUOTES) ?>">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Nota
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>

                        <!-- 7. Acciones -->
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-outline-primary shadow-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editCustomerModal"
                                data-id="<?= $client['customer_id'] ?>"
                                data-business="<?= htmlspecialchars($client['business_name'] ?? '', ENT_QUOTES) ?>"
                                data-company="<?= htmlspecialchars($client['company_name'] ?? '', ENT_QUOTES) ?>"
                                data-cutoff="<?= $client['invoice_cutoff_day'] ?? '' ?>"
                                data-december="<?= htmlspecialchars($client['december_close_date'] ?? '', ENT_QUOTES) ?>"
                                data-partial="<?= $client['accepts_partial_invoices'] ?? 1 ?>"
                                data-remission="<?= $client['requires_remission_first'] ?? 0 ?>"
                                data-po="<?= $client['requires_po_number'] ?? 1 ?>"
                                data-mon="<?= $client['allow_monday'] ?? 1 ?>"
                                data-tue="<?= $client['allow_tuesday'] ?? 1 ?>"
                                data-wed="<?= $client['allow_wednesday'] ?? 1 ?>"
                                data-thu="<?= $client['allow_thursday'] ?? 1 ?>"
                                data-fri="<?= $client['allow_friday'] ?? 1 ?>"
                                data-sat="<?= $client['allow_saturday'] ?? 0 ?>"
                                data-sun="<?= $client['allow_sunday'] ?? 0 ?>"
                                data-tstart="<?= $client['delivery_time_start'] ?? '08:00:00' ?>"
                                data-tend="<?= $client['delivery_time_end'] ?? '17:00:00' ?>"
                                data-emails="<?= htmlspecialchars($client['billing_emails'] ?? '', ENT_QUOTES) ?>"
                                data-instructions="<?= htmlspecialchars($client['special_instructions'] ?? '', ENT_QUOTES) ?>">
                                <i class="bi bi-pencil-square"></i> Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-folder2-open display-4 d-block mb-2"></i>
                        No se encontraron clientes registrados o asignados a tu cuenta.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Script indispensable para inicializar los Popovers de Bootstrap -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
</script>



<!-- Modal de Edición Completa del Cliente (Datos, Preferencias y Direcciones) -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="index.php?page=update_customer_full" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6"><i class="bi bi-building-gear me-2"></i>Expediente y Reglas del Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- ID Oculto del Cliente -->
                    <input type="hidden" name="customer_id" id="edit_customer_id">

                    <!-- Navegación por Pestañas (Tabs) -->
                    <ul class="nav nav-tabs mb-3" id="customerTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold small" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab"><i class="bi bi-info-circle me-1"></i> Datos y Preferencias</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold small" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses" type="button" role="tab"><i class="bi bi-geo-alt me-1"></i> Sucursales / Direcciones</button>
                        </li>
                    </ul>

                    <!-- Contenido de las Pestañas -->
                    <div class="tab-content" id="customerTabContent">

                        <!-- PESTAÑA 1: DATOS GENERALES Y PREFERENCIAS -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="text-muted fw-bold small text-uppercase mb-2">Identidad Fiscal y Comercial</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Razón Social (Denominación Legal):</label>
                                    <input type="text" name="business_name" id="edit_business_name" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Nombre Comercial:</label>
                                    <input type="text" name="company_name" id="edit_company_name" class="form-control form-control-sm">
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="text-muted fw-bold small text-uppercase mb-2">Reglas de Negocio, Facturación y Cierres</h6>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Día de Corte de Mes:</label>
                                    <input type="number" name="invoice_cutoff_day" id="edit_invoice_cutoff_day" class="form-control form-control-sm" min="1" max="31" placeholder="Ej. 25">
                                    <div class="form-text" style="font-size: 0.7rem;">Límite para recibir facturas.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Corte / Cierre en Diciembre:</label>
                                    <input type="text" name="december_close_date" id="edit_december_close_date" class="form-control form-control-sm" placeholder="Ej. 15-Dic">
                                    <div class="form-text" style="font-size: 0.7rem;">Fecha límite de fin de año.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">¿Acepta Parcialidades?</label>
                                    <select name="accepts_partial_invoices" id="edit_accepts_partial_invoices" class="form-select form-select-sm">
                                        <option value="1">Sí</option>
                                        <option value="0">No (Exige completo)</option>
                                    </select>
                                </div>

                                <!-- Campos que faltaban por integrar en el modal -->
                                <div class="col-md-4 mt-3">
                                    <label class="form-label fw-bold small">¿Exige Remisión Previa a Factura?</label>
                                    <select name="requires_remission_first" id="edit_requires_remission_first" class="form-select form-select-sm">
                                        <option value="1">Sí (Obligatorio)</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-3">
                                    <label class="form-label fw-bold small text-primary"><i class="bi bi-clock-history me-1"></i> Lógica de Horarios y Días de Recepción (Logística)</label>

                                    <!-- Días de la semana -->
                                    <div class="p-3 bg-light rounded border">
                                        <label class="form-label small fw-bold text-muted d-block mb-2">Días que el cliente SÍ recibe mercancía:</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_monday" id="chk_mon" value="1">
                                                <label class="form-check-small" for="chk_mon">Lunes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_tuesday" id="chk_tue" value="1">
                                                <label class="form-check-small" for="chk_tue">Martes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_wednesday" id="chk_wed" value="1">
                                                <label class="form-check-small" for="chk_wed">Miércoles</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_thursday" id="chk_thu" value="1">
                                                <label class="form-check-small" for="chk_thu">Jueves</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_friday" id="chk_fri" value="1">
                                                <label class="form-check-small" for="chk_fri">Viernes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_saturday" id="chk_sat" value="1">
                                                <label class="form-check-small" for="chk_sat">Sábados</label>
                                            </div>
                                        </div>

                                        <!-- Horarios -->
                                        <div class="row g-2 mt-3 pt-2 border-top">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Hora Inicio Recepción:</label>
                                                <input type="time" name="delivery_time_start" id="edit_time_start" class="form-control form-control-sm" value="08:00">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Hora Límite / Cierre:</label>
                                                <input type="time" name="delivery_time_end" id="edit_time_end" class="form-control form-control-sm" value="17:00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">¿Exige Número de OC?</label>
                                    <select name="requires_po_number" id="edit_requires_po_number" class="form-select form-select-sm">
                                        <option value="1">Sí</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label class="form-label fw-bold small">Correos Electrónicos para Facturación:</label>
                                    <textarea name="billing_emails" id="edit_billing_emails" class="form-control form-control-sm" rows="2" placeholder="facturas@cliente.com, pagos@cliente.com"></textarea>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <label class="form-label fw-bold small">Instrucciones Especiales / Notas:</label>
                                    <textarea name="special_instructions" id="edit_special_instructions" class="form-control form-control-sm" rows="2" placeholder="Notas visibles para logística y facturación..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 2: SUCURSALES Y DIRECCIONES -->
                        <div class="tab-pane fade" id="addresses" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted fw-bold small text-uppercase m-0">Sucursales y Direcciones (<code>customers_address</code>)</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAddressModalForCreate()">
                                    <i class="bi bi-plus-circle me-1"></i> Nueva Sucursal
                                </button>
                            </div>

                            <!-- Contenedor donde se listan las direcciones dinámicamente -->
                            <div id="clientAddressesContainer" class="border rounded p-3 bg-light" style="max-height: 350px; overflow-y: auto;">
                                <span class="text-muted small">Cargando direcciones...</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar Todos los Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sub-Modal para Crear / Editar Dirección Individual -->
<div class="modal fade" id="addressFormModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addressForm" onsubmit="saveAddress(event)">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fs-6" id="addressModalTitle"><i class="bi bi-geo-alt-fill me-2"></i>Gestionar Dirección</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="address_id" id="addr_id">
                    <input type="hidden" name="id_customer" id="addr_id_customer">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nombre de Sucursal (Branch):</label>
                            <input type="text" name="branch" id="addr_branch" class="form-control form-control-sm" placeholder="Ej. PLANTA 2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Link de Google Maps:</label>
                            <input type="url" name="google_maps" id="addr_google_maps" class="form-control form-control-sm" placeholder="https://maps.app.goo.gl/...">
                        </div>
                        <!-- Campo de autocomplegado rápido -->
                        <div class="col-12 bg-light p-2 rounded border mb-2">
                            <label class="form-label fw-bold small text-primary"><i class="bi bi-magic me-1"></i> Autocompletar pegando dirección de Maps:</label>
                            <input type="text" id="quickMapsInput" class="form-control form-control-sm" placeholder="Ej. Vista Rosa 219, Linda Vista, 67123 Guadalupe, N.L.">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Calle:</label>
                            <input type="text" name="street" id="addr_street" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Núm. Ext:</label>
                            <input type="text" name="exterior_number" id="addr_exterior_number" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Núm. Int:</label>
                            <input type="text" name="interior_number" id="addr_interior_number" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Colonia:</label>
                            <input type="text" name="colonia" id="addr_colonia" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Municipio / Ciudad:</label>
                            <input type="text" name="municipio" id="addr_municipio" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Código Postal:</label>
                            <input type="text" name="codigo_postal" id="addr_codigo_postal" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Estado:</label>
                            <input type="text" name="estado" id="addr_estado" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">País:</label>
                            <input type="text" name="pais" id="addr_pais" class="form-control form-control-sm" value="México">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Referencias:</label>
                            <input type="text" name="referencias" id="addr_referencias" class="form-control form-control-sm" placeholder="Entre calles, color de fachada...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar Dirección</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentCustomerId = null;

    // Al abrir el modal principal del cliente
    const editModal = document.getElementById('editCustomerModal');
    editModal?.addEventListener('show.bs.modal', function(event) {
        let button = event.relatedTarget;
        if (!button) return;

        let clientId = button.getAttribute('data-id');
        currentCustomerId = clientId;

        // Función auxiliar segura para rellenar inputs de texto y selects
        function setVal(selector, val) {
            let el = editModal.querySelector(selector);
            if (el) el.value = val !== null && val !== undefined ? val : '';
        }

        // Función auxiliar para marcar o desmarcar checkboxes de los días
        function setCheck(selector, val) {
            let el = editModal.querySelector(selector);
            if (el) el.checked = (val == '1' || val === true || val === 1);
        }

        // 1. Rellenar campos de texto, números y selects
        setVal('#edit_customer_id', clientId);
        setVal('#edit_business_name', button.getAttribute('data-business'));
        setVal('#edit_company_name', button.getAttribute('data-company'));
        setVal('#edit_invoice_cutoff_day', button.getAttribute('data-cutoff'));
        setVal('#edit_december_close_date', button.getAttribute('data-december'));
        setVal('#edit_accepts_partial_invoices', button.getAttribute('data-partial'));
        setVal('#edit_requires_remission_first', button.getAttribute('data-remission'));
        setVal('#edit_requires_po_number', button.getAttribute('data-po'));
        setVal('#edit_billing_emails', button.getAttribute('data-emails'));
        setVal('#edit_special_instructions', button.getAttribute('data-instructions'));
        setVal('#edit_time_start', button.getAttribute('data-tstart'));
        setVal('#edit_time_end', button.getAttribute('data-tend'));

        // 2. Marcar los checkboxes reales de los días de la semana
        setCheck('#chk_mon', button.getAttribute('data-mon'));
        setCheck('#chk_tue', button.getAttribute('data-tue'));
        setCheck('#chk_wed', button.getAttribute('data-wed'));
        setCheck('#chk_thu', button.getAttribute('data-thu'));
        setCheck('#chk_fri', button.getAttribute('data-fri'));
        setCheck('#chk_sat', button.getAttribute('data-sat'));
        setCheck('#chk_sun', button.getAttribute('data-sun'));

        // 3. Cargar las direcciones correspondientes a este cliente
        if (currentCustomerId) {
            loadClientAddresses(currentCustomerId);
        }
    });


    function loadClientAddresses(customerId) {
        let container = document.getElementById('clientAddressesContainer');

        // Diagnóstico en consola para ver si el ID llegó bien
        console.log("Cargando direcciones para el ID de cliente:", customerId);

        if (!customerId) {
            container.innerHTML = '<p class="text-danger small m-0 text-center py-3">Error: ID de cliente no definido.</p>';
            return;
        }

        container.innerHTML = '<span class="text-muted small">Cargando direcciones...</span>';

        fetch('index.php?page=get_customer_addresses&id=' + customerId)
            .then(response => response.json())
            .then(data => {
                // Diagnóstico para ver qué respondió PHP
                console.log("Respuesta del servidor de direcciones:", data);

                if (data.success && data.addresses && data.addresses.length > 0) {
                    let html = '<div class="list-group list-group-flush bg-transparent">';
                    data.addresses.forEach(addr => {
                        let mapsBtn = addr.google_maps ?
                            `<a href="${addr.google_maps}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2 me-1" title="Ver ubicación en Maps"><i class="bi bi-geo-alt-fill"></i> Ver ubicación</a>` : '';

                        html += `<div class="list-group-item bg-transparent d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <strong class="text-primary">${addr.branch || 'Sucursal Principal'}</strong><br>
                                        <small class="text-dark">${addr.street} #${addr.exterior_number} ${addr.interior_number ? 'Int. ' + addr.interior_number : ''}, Col. ${addr.colonia}, ${addr.municipio}, ${addr.estado} (C.P. ${addr.codigo_postal})</small><br>
                                        <small class="text-muted">Referencias: ${addr.referencias || 'Ninguna'}</small>
                                    </div>
                                    <div class="text-end">
                                        ${mapsBtn}
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 me-1" onclick='openAddressModalForEdit(${JSON.stringify(addr)})' title="Editar dirección"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteAddress(${addr.id})" title="Eliminar dirección"><i class="bi bi-trash"></i></button>
                                    </div>
                                 </div>`;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p class="text-muted small m-0 text-center py-3">No hay sucursales registradas para este cliente.</p>';
                }
            })
            .catch(error => {
                console.error("Error en el fetch de direcciones:", error);
                container.innerHTML = '<p class="text-danger small m-0 text-center">Error al cargar las direcciones.</p>';
            });
    }

    function openAddressModalForCreate() {
        document.getElementById('addressForm').reset();
        document.getElementById('addr_id').value = '';
        document.getElementById('addr_id_customer').value = currentCustomerId;
        document.getElementById('addressModalTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Agregar Nueva Sucursal';
        new bootstrap.Modal(document.getElementById('addressFormModal')).show();
    }

    function openAddressModalForEdit(addr) {
        document.getElementById('addressForm').reset();
        document.getElementById('addr_id').value = addr.id;
        document.getElementById('addr_id_customer').value = addr.id_customer;
        document.getElementById('addr_branch').value = addr.branch || '';
        document.getElementById('addr_google_maps').value = addr.google_maps || '';
        document.getElementById('addr_street').value = addr.street || '';
        document.getElementById('addr_exterior_number').value = addr.exterior_number || '';
        document.getElementById('addr_interior_number').value = addr.interior_number || '';
        document.getElementById('addr_colonia').value = addr.colonia || '';
        document.getElementById('addr_municipio').value = addr.municipio || '';
        document.getElementById('addr_codigo_postal').value = addr.codigo_postal || '';
        document.getElementById('addr_estado').value = addr.estado || '';
        document.getElementById('addr_pais').value = addr.pais || 'México';
        document.getElementById('addr_referencias').value = addr.referencias || '';

        document.getElementById('addressModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Editar Sucursal';
        new bootstrap.Modal(document.getElementById('addressFormModal')).show();
    }

    function saveAddress(event) {
        event.preventDefault();
        let formData = new FormData(document.getElementById('addressForm'));

        fetch('index.php?page=save_customer_address', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addressFormModal')).hide();
                    loadClientAddresses(currentCustomerId); // Recargar lista
                } else {
                    alert('Error al guardar la dirección: ' + (data.message || ''));
                }
            });
    }

    function deleteAddress(addressId) {
        if (confirm('¿Estás seguro de eliminar esta dirección?')) {
            let formData = new FormData();
            formData.append('address_id', addressId);

            fetch('index.php?page=delete_customer_address', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadClientAddresses(currentCustomerId);
                    } else {
                        alert('No se pudo eliminar la dirección.');
                    }
                });
        }
    }

    function parseGoogleMapsAddress(rawText) {
        // Objeto inicial con campos vacíos
        let addressData = {
            street: '',
            exterior_number: '',
            colonia: '',
            municipio: '',
            codigo_postal: '',
            estado: ''
        };

        if (!rawText) return addressData;

        // Limpiar saltos de línea y dividir por comas
        let parts = rawText.split(',').map(p => p.trim());

        // 1. Analizar la primera parte (Calle y Número)
        // Ejemplo: "Vista Rosa 219" o "Av. Fundidora No. 501"
        let firstPart = parts[0] || '';
        // Buscar un número al final o después de "No."
        let matchNumber = firstPart.match(/(?:No\.?\s*)?(\d+[\w-]*(?:\s*(?:Int|Interior)\.?\s*\d+)?)$/i);

        if (matchNumber) {
            addressData.exterior_number = matchNumber[1];
            // La calle es todo lo que está antes del número
            addressData.street = firstPart.replace(matchNumber[0], '').trim();
        } else {
            addressData.street = firstPart;
        }

        // 2. Buscar Código Postal (5 dígitos seguidos) en todas las partes
        parts.forEach((part, index) => {
            let cpMatch = part.match(/\b(\d{5})\b/);
            if (cpMatch) {
                addressData.codigo_postal = cpMatch[1];
                // A veces el CP viene junto con el municipio/estado, ej: "67123 Guadalupe"
                let cleanedPart = part.replace(cpMatch[1], '').trim();
                if (cleanedPart) parts[index] = cleanedPart; // Actualizamos la parte sin el CP
                else parts.splice(index, 1); // Si solo era el CP, eliminamos la parte
            }
        });

        // 3. Detectar Estado (ej. N.L., Nuevo León, CDMX, etc.) y limpiar
        const statesList = ['n.l.', 'nuevo león', 'nl', 'cdmx', 'jalisco', 'coahuila', 'chihuahua'];
        parts.forEach((part, index) => {
            let lower = part.toLowerCase();
            if (statesList.some(st => lower.includes(st))) {
                addressData.estado = part;
                parts.splice(index, 1); // Remover el estado del array para analizar el resto
            }
        });

        // 4. Analizar las partes intermedias restantes (Colonia, Municipio)
        // Por lo general, lo que queda en medio suele ser Colonia y Municipio
        if (parts.length >= 3) {
            // Si hay varias partes, la segunda suele ser la colonia y la tercera el municipio
            addressData.colonia = parts[1] || '';
            addressData.municipio = parts[2] || '';
        } else if (parts.length === 2) {
            addressData.colonia = parts[1] || '';
        }

        // Limpiezas extras si la palabra "Col." viene pegada
        if (addressData.colonia.toLowerCase().startsWith('col.')) {
            addressData.colonia = addressData.colonia.replace(/col\.?/i, '').trim();
        }

        return addressData;
    }

    document.getElementById('quickMapsInput')?.addEventListener('input', function(e) {
        let rawText = e.target.value;
        // Solo disparar si el texto tiene una longitud razonable para ser dirección
        if (rawText.length > 10) {
            let parsed = parseGoogleMapsAddress(rawText);

            // Autocomllenar los inputs del formulario solo si tienen datos detectados
            if (parsed.street) document.getElementById('addr_street').value = parsed.street;
            if (parsed.exterior_number) document.getElementById('addr_exterior_number').value = parsed.exterior_number;
            if (parsed.colonia) document.getElementById('addr_colonia').value = parsed.colonia;
            if (parsed.municipio) document.getElementById('addr_municipio').value = parsed.municipio;
            if (parsed.codigo_postal) document.getElementById('addr_codigo_postal').value = parsed.codigo_postal;
            if (parsed.estado) document.getElementById('addr_estado').value = parsed.estado;
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
</script>