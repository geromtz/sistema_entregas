<div class="container-fluid py-4 px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-uppercase fs-7 text-muted tracking-wider">
                    <li class="breadcrumb-item"><a href="index.php?page=my_quotations" class="text-decoration-none text-muted">Cotizaciones</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Convertir a Pedido Logístico</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">
                <i class="bi bi-layers text-success me-2"></i>Asistente de Conversión por Lotes: <span class="text-primary font-monospace"><?= htmlspecialchars($quotation['folio'] ?? '') ?></span>
            </h1>
        </div>
        <div class="bg-white border rounded-pill px-3 py-2 shadow-sm">
            <span class="badge bg-success-subtle text-success px-3 py-2 fw-bold">Estatus: <?= htmlspecialchars($quotation['status'] ?? '') ?></span>
        </div>
    </div>

    <!-- Stepper de Progreso -->
    <div class="row bs-stepper mb-4 text-center">
        <div class="col-6 step-indicator active" id="stepIndicator1">
            <div class="p-3 border-bottom border-primary border-3 fw-bold text-primary bg-white rounded-top shadow-2xs">
                <span class="badge bg-primary rounded-circle me-1">1</span> Selección Global de Partidas (<?= count($items ?? []) ?>)
            </div>
        </div>
        <div class="col-6 step-indicator text-muted" id="stepIndicator2">
            <div class="p-3 border-bottom border-3 fw-bold bg-light rounded-top">
                <span class="badge bg-secondary rounded-circle me-1">2</span> Configuración de Pedidos por Proveedor
            </div>
        </div>
    </div>

    <form id="conversionMasterForm">
        <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['id'] ?? '') ?>">
        <input type="hidden" name="id_customer" value="<?= htmlspecialchars($quotation['customer_id'] ?? '') ?>">

        <!-- ================= PASO 1: SELECCIÓN GLOBAL ================= -->
        <div class="wizard-step" id="wizardStep1">
            <div class="alert alert-info shadow-sm mb-4">
                <i class="bi bi-info-circle-fill me-2"></i> <strong>Paso 1:</strong> Selecciona las partidas aprobadas y ajusta las cantidades si el cliente hizo un pedido parcial. El sistema las agrupará por proveedor en el siguiente paso.
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">
                                    <input type="checkbox" id="selectAllRealItems" class="form-check-input" checked>
                                </th>
                                <th># Parte / Descripción</th>
                                <th class="text-center">Cant. Cotizada</th>
                                <th class="text-center" style="width: 140px;">Cant. a Convertir</th>
                                <th>Proveedor Teórico Sugerido</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $index =>$item): ?>
                                    <tr class="item-selection-row">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input item-check" checked name="items[<?= $index ?>][selected]" value="1">
                                            <input type="hidden" name="items[<?= $index ?>][quotation_item_id]" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?>">
                                            <input type="hidden" name="items[<?= $index ?>][description]" value="<?= htmlspecialchars($item['product_description'] ?? '') ?>">
                                            <input type="hidden" name="items[<?= $index ?>][part_number]" value="<?= htmlspecialchars($item['product_part_number'] ?? '') ?>">
                                        </td>
                                        <td>
                                            <strong class="font-monospace text-dark d-block"><?= htmlspecialchars($item['product_part_number'] ?? 'N/D') ?></strong>
                                            <span class="text-muted small"><?= htmlspecialchars($item['product_description'] ?? '') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary"><?= $item['quantity'] ?> pzas</span>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm text-center fw-bold qty-convert-input" 
                                                   name="items[<?= $index ?>][quantity]" 
                                                   value="<?= $item['quantity'] ?>" 
                                                   min="1" max="<?= $item['quantity'] ?>">
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border supplier-label">
                                                <i class="bi bi-shop me-1 text-success"></i> <span class="s-name"><?= htmlspecialchars($item['supplier_name'] ?? 'Sin asignar') ?></span>
                                            </span>
                                            <input type="hidden" class="supplier-id-val" name="items[<?= $index ?>][id_supplier]" value="<?= $item['id_supplier'] ?? 0 ?>">
                                        </td>
                                        <td class="text-end fw-bold text-success">$<?= number_format($item['total_price'] ?? 0, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= PASO 2: CONFIGURACIÓN POR LOTES DE PROVEEDOR ================= -->
        <div class="wizard-step d-none" id="wizardStep2">
            <div class="alert alert-warning shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Paso 2:</strong> Las partidas seleccionadas se han agrupado por proveedor. Rellena el número de pedido del mayorista y el tipo de logística correspondiente para cada bloque.
            </div>

            <!-- Contenedor dinámico donde se inyectarán los formularios por proveedor -->
            <div id="supplierBatchesContainer">
                <!-- Se genera mediante JavaScript con base en los proveedores seleccionados -->
            </div>

            <!-- Datos generales del pedido del cliente -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mt-4 bg-light">
                <h5 class="text-dark fw-bold mb-3"><i class="bi bi-file-earmark-text me-2"></i> Datos de la Orden del Cliente (Globales)</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">No. de Orden de Compra (OC) del Cliente *</label>
                        <input type="text" class="form-control form-control-sm" name="purchase_order" required placeholder="Ej. OC-2026-99">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Uso de CFDI *</label>
                        <select class="form-select form-select-sm" name="id_uso_cfdi">
                            <option value="3" selected>G03 - Gastos en general</option>
                            <option value="1">G01 - Adquisición de mercancías</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Adjuntar PDF de Orden de Compra</label>
                        <input type="file" class="form-control form-control-sm" name="order_pdf" accept=".pdf">
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Navegación -->
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border mt-4">
            <button type="button" class="btn btn-outline-secondary px-4 rounded-pill d-none" id="prevWizardBtn">
                <i class="bi bi-arrow-left me-1"></i> Anterior
            </button>
            <div class="ms-auto d-flex gap-2">
                <a href="index.php?page=my_quotations" class="btn btn-light px-4 rounded-pill">Cancelar</a>
                <button type="button" class="btn c-btn btn-primary px-4 rounded-pill fw-semibold" id="nextWizardBtn">
                    Siguiente (Agrupar Proveedores) <i class="bi bi-arrow-right ms-1"></i>
                </button>
                <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold d-none" id="submitConversionBtn">
                    <i class="bi bi-check-circle-fill me-1"></i> Confirmar y Generar Pedido Completo
                </button>
            </div>
        </div>
    </form>
</div>

<!-- SCRIPT DE PRUEBA Y AGRUPACIÓN POR LOTES -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nextBtn = document.getElementById('nextWizardBtn');
    const prevBtn = document.getElementById('prevWizardBtn');
    const submitBtn = document.getElementById('submitConversionBtn');
    
    const step1 = document.getElementById('wizardStep1');
    const step2 = document.getElementById('wizardStep2');
    const ind1 = document.getElementById('stepIndicator1');
    const ind2 = document.getElementById('stepIndicator2');
    const batchesContainer = document.getElementById('supplierBatchesContainer');

    let currentStep = 1;

    // Control de checkboxes en la tabla inicial
    document.querySelectorAll('.item-check').forEach(chk => {
        chk.addEventListener('change', function() {
            const row = this.closest('tr');
            const qtyInput = row.querySelector('.qty-convert-input');
            qtyInput.disabled = !this.checked;
        });
    });

    // Botón Siguiente -> Transición y Agrupación automática
    nextBtn.addEventListener('click', () => {
        if(currentStep === 1) {
            // Recolectar filas seleccionadas
            const selectedRows = document.querySelectorAll('.item-selection-row');
            const groups = {};

            selectedRows.forEach(row => {
                const chk = row.querySelector('.item-check');
                if(!chk.checked) return;

                const supplierId = row.querySelector('.supplier-id-val').value || '0';
                const supplierName = row.querySelector('.s-name').innerText || 'Proveedor General';
                
                if(!groups[supplierId]) {
                    groups[supplierId] = {
                        name: supplierName,
                        items: []
                    };
                }

                groups[supplierId].items.push({
                    partNumber: row.querySelector('input[name*="[part_number]"]').value,
                    description: row.querySelector('input[name*="[description]"]').value,
                    quantity: row.querySelector('.qty-convert-input').value,
                    quotationItemId: row.querySelector('input[name*="[quotation_item_id]"]').value,
                    productId: row.querySelector('input[name*="[product_id]"]').value
                });
            });

            // Verificar si hay elementos seleccionados
            if(Object.keys(groups).length === 0) {
                alert('Por favor selecciona al menos una partida para continuar.');
                return;
            }

            // Inyectar bloques modulares por cada proveedor agrupado
            batchesContainer.innerHTML = '';
            let batchIndex = 0;

            for(const [supId, group] of Object.entries(groups)) {
                let itemsHtml = '';
                group.items.forEach((item, idx) => {
                    itemsHtml += `
                        <tr>
                            <td>${idx + 1}</td>
                            <td><span class="font-monospace">${item.partNumber}</span> - ${item.description}</td>
                            <td class="text-center"><strong>${item.quantity}</strong> pzas</td>
                            <input type="hidden" name="batches[${batchIndex}][items][${idx}][quotation_item_id]" value="${item.quotationItemId}">
                            <input type="hidden" name="batches[${batchIndex}][items][${idx}][product_id]" value="${item.productId}">
                            <input type="hidden" name="batches[${batchIndex}][items][${idx}][quantity]" value="${item.quantity}">
                        </tr>
                    `;
                });

                batchesContainer.innerHTML += `
                    <div class="card border shadow-sm rounded-4 p-3 mb-4 bg-white">
                        <h5 class="text-success fw-bold border-bottom pb-2">
                            <i class="bi bi-shop me-2"></i> Lote para Mayorista: ${group.name}
                        </h5>
                        <input type="hidden" name="batches[${batchIndex}][id_supplier]" value="${supId}">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">No. de Pedido Mayorista (Folio Proveedor) *</label>
                                <input type="text" class="form-control form-control-sm font-monospace" name="batches[${batchIndex}][supplier_order]" required placeholder="Ej. PED-PROV-1234">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tipo de Pedido Logístico *</label>
                                <select class="form-select form-select-sm" name="batches[${batchIndex}][id_order_type]" required>
                                    <option value="1">Local</option>
                                    <option value="2">Foráneo a ERA (Liega a oficina)</option>
                                    <option value="3">Foráneo a Sucursal</option>
                                    <option value="4">Varias Sucursales</option>
                                    <option value="5">Proveedor a Usuario Final</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Producto</th>
                                        <th class="text-center" style="width:120px;">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                batchIndex++;
            }

            // Cambiar de paso visualmente
            step1.classList.add('d-none');
            step2.classList.remove('d-none');
            ind1.firstElementChild.className = "p-3 border-bottom border-3 fw-bold text-muted bg-light rounded-top";
            ind2.firstElementChild.className = "p-3 border-bottom border-primary border-3 fw-bold text-primary bg-white rounded-top shadow-2xs";

            nextBtn.classList.add('d-none');
            prevBtn.classList.remove('d-none');
            submitBtn.classList.remove('d-none');
            currentStep = 2;
        }
    });

    // Botón Anterior
    prevBtn.addEventListener('click', () => {
        if(currentStep === 2) {
            step2.classList.add('d-none');
            step1.classList.remove('d-none');
            ind2.firstElementChild.className = "p-3 border-bottom border-3 fw-bold text-muted bg-light rounded-top";
            ind1.firstElementChild.className = "p-3 border-bottom border-primary border-3 fw-bold text-primary bg-white rounded-top shadow-2xs";

            nextBtn.classList.remove('d-none');
            prevBtn.classList.add('d-none');
            submitBtn.classList.add('d-none');
            currentStep = 1;
        }
    });

    // Simulación de envío final
    submitBtn.addEventListener('click', () => {
        alert('¡Estructura de lotes por proveedor generada exitosamente! Lista para ser procesada por tu controlador backend.');
    });
});
</script>