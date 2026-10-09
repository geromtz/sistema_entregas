<?php

/**
 * Vista de Requisición Interna (Promesa de Compra con Costos, Utilidad y Deshacer Fracción)
 * Ruta: app/views/forms/convert_quotation_to_requisition.php
 */
?>
<!-- Este campo se activa o se añade dinámicamente cuando el usuario acepta la advertencia -->
<input type="hidden" name="loss_accepted" id="loss_accepted" value="0">
<div class="container-fluid py-4 px-4">
    <!-- Header Minimalista y Pro -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-uppercase fs-7 text-muted tracking-wider">
                    <li class="breadcrumb-item"><a href="index.php?page=my_quotations" class="text-decoration-none text-muted">Cotizaciones</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Requisición Interna de Compra</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">
                <i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Generar Requisición: <span class="text-success font-monospace"><?= htmlspecialchars($quotation['folio'] ?? '') ?></span>
            </h1>
        </div>
        <div class="bg-white border rounded-pill px-3 py-2 shadow-sm d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-bold font-monospace">Estatus: Requisición Borrador</span>
        </div>
    </div>

    <!-- Alerta Informativa -->
    <div class="alert alert-primary border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center p-3">
        <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
        <div>
            <h6 class="fw-bold mb-1">Solicitud de Abastecimiento Interno</h6>
            <p class="mb-0 small text-muted">Puedes fraccionar partidas para surtir con distintos proveedores. Si te equivocas, usa el botón de "X" en la fracción para eliminarla.</p>
        </div>
    </div>

    <!-- FORMULARIO DE REQUISICIÓN -->
    <form id="requisitionMasterForm" action="index.php?page=store_requisition" method="POST" novalidate>
        <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['id'] ?? '') ?>">
        <input type="hidden" id="customer_id" name="customer_id" value="<?= htmlspecialchars($quotation['customer_id'] ?? '') ?>">

        <!-- Datos Generales Internos -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h5 class="text-secondary fw-bold mb-3"><i class="bi bi-info-square me-2"></i> Datos Generales de la Solicitud</h5>
            <div class="row mb-3">
                <div class="col-md-6 col-12 mb-3">
                    <label class="form-label small fw-bold">Cliente Destino</label>
                    <input type="text" class="form-control bg-light" name="customer_name"
                        value="<?= htmlspecialchars($quotation['customer_name'] ?? '') ?>" readonly>
                </div>
                <div class="col-md-6 col-12 mb-3">
                    <label class="form-label small fw-bold">Referencia / Título de la Solicitud *</label>
                    <input type="text" class="form-control" name="requisition_title"
                        value="Requisición para OC de Cliente - Cot. <?= htmlspecialchars($quotation['folio'] ?? '') ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label class="form-label small fw-bold">Observaciones / Notas para el Departamento de Compras</label>
                    <textarea class="form-control" name="internal_notes" rows="2" placeholder="Ej. El cliente solicita entrega express..."></textarea>
                </div>
            </div>
        </div>

        <!-- Tabla de Partidas -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2"></i> Partidas Solicitadas, Costos, Utilidad y Asignación</h6>
                    <span class="text-muted fs-8">Ajusta libremente las cantidades a requisitar. El sistema calculará el total en tiempo real frente a lo cotizado.</span>
                </div>
                <!-- Indicador de total global dinámico -->
                <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-7 fw-bold font-monospace">
                    Total a Surtir: <span id="header-total-requisition-qty">0</span> pzas
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="requisitionItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">
                                <input type="checkbox" id="selectAllItems" class="form-check-input" checked>
                            </th>
                            <th># Parte / Descripción</th>
                            <th class="text-center" style="width: 90px;">Cant. Cotizada</th>
                            <th class="text-center" style="width: 100px;">Cant. a Req. <span class="text-danger">*</span></th>
                            <th style="width: 180px;">Proveedor Sugerido</th>
                            <th style="width: 140px;">Sucursal / Bodega</th>
                            <th class="text-center" style="width: 90px;">T. Entrega (Días)</th>
                            <th class="text-center" style="width: 90px;">Flete ($)</th>
                            <th class="text-center" style="width: 110px;">Costo Unit. Compra</th>
                            <th class="text-center" style="width: 80px;">Acciones</th>
                            <th class="text-end" style="width: 100px;">Subtotal Costo</th>
                            <th class="text-end" style="width: 100px;">Subtotal Venta</th>
                            <th class="text-end" style="width: 100px;">Utilidad Real</th>
                        </tr>
                    </thead>
                    <tbody id="itemsContainer" data-initial-count="<?= count($items ?? []) ?>">
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $index => $item): ?>
                                <tr class="item-row-selector" data-row-id="<?= $index ?>" data-parent-id="<?= $index ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input item-check" checked name="items[<?= $index ?>][selected]" value="1">
                                        <input type="hidden" name="items[<?= $index ?>][quotation_item_id]" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?>">
                                        <input type="hidden" name="items[<?= $index ?>][part_number]" value="<?= htmlspecialchars($item['product_part_number'] ?? '') ?>">
                                        <input type="hidden" name="items[<?= $index ?>][description]" value="<?= htmlspecialchars($item['product_description'] ?? '') ?>">
                                        <input type="hidden" name="items[<?= $index ?>][unit_price]" value="<?= $item['unit_price'] ?? 0 ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div>
                                                <strong class="font-monospace text-dark d-block"><?= htmlspecialchars($item['product_part_number'] ?? 'N/D') ?></strong>
                                                <span class="text-muted small"><?= htmlspecialchars($item['product_description'] ?? '') ?></span>
                                            </div>
                                            <span class="badge bg-warning text-dark fs-9 split-tag d-none shadow-2xs">
                                                <i class="bi bi-pie-chart-fill me-1"></i>Fracción
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center total-oc-cell">
                                        <span class="badge bg-dark text-white fw-bold font-monospace original-total-badge"><?= $item['quantity'] ?> pzas</span>
                                        <input type="hidden" class="max-qty-val" value="<?= $item['quantity'] ?>">
                                    </td>
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm text-center fw-bold text-primary qty-convert-input"
                                            name="items[<?= $index ?>][quantity]"
                                            value="<?= $item['quantity'] ?>"
                                            min="0">
                                        <!-- 👁️ CONTENEDOR PARA LA SUMA TOTAL DE FRACCIONES -->
                                        <div class="group-total-hint text-muted fs-9 fst-italic mt-1 d-none"></div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm position-relative">
                                            <input type="text" class="form-control supplier-search-input"
                                                value="<?= htmlspecialchars($item['supplier_name'] ?? 'Sin asignar') ?>"
                                                placeholder="Buscar proveedor..." autocomplete="off">
                                            <input type="hidden" class="sup-id-val" name="items[<?= $index ?>][id_supplier]" value="<?= $item['id_supplier'] ?? 0 ?>">
                                            <div class="supplier-autocomplete-list list-group position-absolute w-100 shadow-sm rounded-bottom" style="top: 100%; z-index: 1050; font-size: 0.85rem;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm branch-select" name="items[<?= $index ?>][id_branch]">
                                            <option value="<?= $item['id_branch'] ?? 1 ?>">Seleccione sucursal...</option>
                                        </select>
                                    </td>
                                    <!-- Columna de Tiempo de Entrega (Días) -->
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm text-center font-monospace delivery-time-input"
                                            name="items[<?= $index ?>][delivery_time_days]"
                                            value="<?= $item['delivery_time_days'] ?? 0 ?>"
                                            min="0" placeholder="Días" style="width: 90px; min-width: 90px;" <?= $isEditable ?? true ? '' : 'readonly' ?>>
                                    </td>

                                    <!-- Columna de Costo de Flete -->
                                    <td class="text-center">
                                        <input type="number" step="0.01" class="form-control form-control-sm text-center font-monospace freight-cost-input"
                                            name="items[<?= $index ?>][freight_cost]"
                                            value="<?= $item['freight_cost'] ?? 0 ?>"
                                            placeholder="0.00" style="width: 90px; min-width: 90px;" <?= $isEditable ?? true ? '' : 'readonly' ?>>
                                    </td>
                                    <td class="text-center">
                                        <input type="number" step="0.01" class="form-control form-control-sm text-center font-monospace purchase-cost-input"
                                            name="items[<?= $index ?>][purchase_unit_cost]"
                                            value="<?= $item['purchase_unit_cost'] ?? 0 ?>"
                                            placeholder="0.00">
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary split-item-btn shadow-2xs" title="Dividir partida">
                                                <i class="bi bi-layout-split"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger remove-split-btn d-none shadow-2xs" title="Eliminar fracción">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-secondary item-cost-total-cell">$0.00</td>
                                    <td class="text-end fw-bold text-success item-total-cell" data-unit-price="<?= $item['unit_price'] ?? 0 ?>">$0.00</td>
                                    <td class="text-end fw-bold font-monospace item-profit-cell text-primary">$0.00</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Tarjeta de Totales -->
        <div class="row justify-content-end mb-4">
            <div class="col-md-5 col-12">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Subtotal Venta:</span>
                        <span id="grand-total-sale" class="fw-bold font-monospace">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">IVA (16%):</span>
                        <span id="grand-total-tax" class="fw-bold font-monospace">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <span class="text-muted small">Total Costo de Adquisición:</span>
                        <span id="grand-total-cost" class="fw-bold font-monospace text-secondary">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1">
                        <span class="fw-bold text-dark">Utilidad Global Proyectada:</span>
                        <span id="grand-total-profit" class="fs-5 fw-bold text-success font-monospace">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
            <a href="index.php?page=my_quotations" class="btn btn-light px-4 rounded-pill">Cancelar</a>

            <!-- Protegemos la variable con $isEditable ?? true para que nunca marque Warning si no viene definida -->
            <?php if ($isEditable ?? true): ?>
                <div class="d-flex gap-2">
                    <!-- Botón Borrador (Cambiado a type="button" con ID) -->
                    <button type="button" name="action" value="draft" id="btnDraft" class="btn btn-outline-secondary px-4 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-save me-1"></i> Guardar Borrador
                    </button>
                    <!-- Botón Enviar a Compras (Cambiado a type="button" con ID) -->
                    <button type="button" name="action" value="emit" id="btnEmit" class="btn btn-success px-4 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-send-check me-2"></i> Enviar a Compras
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- MODAL DE CONFIRMACIÓN DE ACCIONES (Pérdida Crítica / Utilidad Negativa) -->
        <div class="modal fade" id="quotationActionConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark fs-6" id="confirmModalTitle">
                            <i class="bi bi-shield-exclamation text-warning me-2"></i> Confirmar Acción
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body py-4">
                        <p class="text-secondary mb-0" id="confirmModalText">¿Estás seguro de realizar esta acción?</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-medium" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-sm rounded-pill px-4 fw-semibold shadow-sm btn-danger" id="confirmActionBtn">Sí, continuar</button>
                    </div>
                </div>
            </div>
        </div>


        <script src="public/assets/js/requisitions/requisition_conversion.js"></script>