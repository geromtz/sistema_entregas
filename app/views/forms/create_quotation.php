<div class="container-fluid py-4 px-4">
    <!-- Header Minimalista y Pro -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-uppercase fs-7 text-muted tracking-wider">
                    <li class="breadcrumb-item"><a href="index.php?page=my_quotations" class="text-decoration-none text-muted">Cotizaciones</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Nueva Oportunidad</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0"><i class="bi bi-window-sidebar text-primary me-2"></i>Generador de Cotización y Costos</h1>
        </div>
        <div class="bg-white border rounded-pill px-3 py-2 shadow-sm d-flex align-items-center gap-2">
            <div class="bg-soft-primary text-primary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="text-start pe-2">
                <span class="d-block fs-8 text-muted lh-1">Vendedor</span>
                <span class="fw-semibold fs-7 text-dark"><?= htmlspecialchars($formData['user_fullname']) ?></span>
            </div>
        </div>
    </div>

    <form id="demo_quotation_form" class="row g-4">

        <!-- Columna Izquierda: Datos Generales & Destino -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-light-subtle">
                <h5 class="fw-bold text-dark mb-3 fs-6 text-uppercase tracking-wide text-secondary"><i class="bi bi-info-circle me-2"></i>Datos Generales</h5>

                <!-- Folio y Fecha en tarjeta compacta -->
                <div class="bg-white border rounded-3 p-3 mb-3 shadow-2xs">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-7 text-muted fw-medium">Folio Interno</span>
                        <span class="badge bg-danger-subtle text-danger fw-bold font-monospace px-2 py-1">16181010137-26</span>
                        <input type="hidden" name="folio" value="16181010137-26">
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-7 text-muted fw-medium">Emisión</span>
                        <span class="fw-semibold fs-7 text-dark"><?= date('d / M / Y') ?></span>
                        <input type="hidden" name="quotation_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <!-- Selector de Destino (Cliente vs Prospecto) -->
                <div class="mb-3">
                    <label class="form-label fw-semibold fs-7 text-dark mb-2">Destino Comercial</label>
                    <div class="btn-group w-100 mb-3" role="group">
                        <input type="radio" class="btn-check dest-radio" name="dest_type" id="type_customer" value="customer" checked>
                        <label class="btn btn-outline-primary" for="type_customer">Cliente</label>

                        <input type="radio" class="btn-check dest-radio" name="dest_type" id="type_prospect" value="prospect">
                        <label class="btn btn-outline-secondary" for="type_prospect">Prospecto</label>
                    </div>
                </div>

                <!-- Input dinámico  -->
                <div class="position-relative mb-3">
                    <label class="form-label" id="search_label">Buscar Cliente</label>
                    <input type="text" class="form-control" id="search_target" placeholder="Escribe para buscar..." autocomplete="off">

                    <!-- Campos ocultos para el backend -->
                    <input type="hidden" id="id_customer" name="id_customer" value="">
                    <input type="hidden" id="id_prospect" name="id_prospect" value="">

                    <!-- Contenedor de la lista flotante -->
                    <div id="search_list" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000;"></div>
                </div>

                <div class="mb-3 position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold fs-7 text-dark mb-0">Atención a (Contacto)</label>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fs-8" id="btn_quick_contact" data-bs-toggle="modal" data-bs-target="#quickContactModal" disabled>
                            <i class="bi bi-plus-circle me-1"></i>Nuevo Contacto
                        </button>
                    </div>

                    <!-- Input visual de texto libre y autocompletado -->
                    <input type="text" class="form-control form-control-sm shadow-2xs border-subtle" id="attention_to_input" name="attention_to" placeholder="Selecciona o escribe el contacto..." autocomplete="off" required value="">

                    <!-- Contenedor flotante para las sugerencias de contactos del cliente -->
                    <div id="contact_search_list" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000;"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold fs-7 text-dark">Vigencia de Precios (Días)</label>
                    <div class="input-group input-group-sm shadow-2xs border-subtle rounded-2 overflow-hidden">
                        <!-- Campo numérico limpio -->
                        <input type="number" class="form-control form-control-sm border-0 text-center" id="validity_number" min="1" max="999" value="4" style="max-width: 70px;" required>

                        <span class="input-group-text bg-light border-0 text-muted fs-8 flex-fill justify-content-start" id="validity_label">
                            días o hasta agotar existencias
                        </span>

                        <!-- Input oculto con la frase completa inicial -->
                        <input type="hidden" name="validity_text" id="validity_text_hidden" value="4 días o hasta agotar existencias">
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="row g-3 mb-3">
                        <!-- Condición de Pago -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-7 text-dark">Condiciones de Pago</label>
                            <select class="form-select form-select-sm shadow-2xs border-subtle" id="payment_terms_select" name="payment_terms" required>
                                <option value="Credito" selected>Crédito</option>
                                <option value="Contado">Contado</option>
                            </select>
                        </div>

                        <!-- Moneda de la Cotización -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-7 text-dark">Moneda</label>
                            <select class="form-select form-select-sm shadow-2xs border-subtle" id="currency_select" name="currency">
                                <option value="MXN" selected>Pesos Mexicanos (MXN)</option>
                                <option value="USD">Dólares Estadounidenses (USD)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 🔹 Días de Crédito Dinámicos (Oculto por defecto si inicia en contado, o visible si está en crédito) -->
                    <div class="mb-3" id="credit_days_container">
                        <label class="form-label fw-semibold fs-7 text-dark">Plazo de Crédito</label>
                        <div class="input-group input-group-sm shadow-2xs border-subtle rounded-2 overflow-hidden" style="max-width: 280px;">
                            <!-- Input numérico corto para los días -->
                            <input type="number" class="form-control form-control-sm border-0 text-center" id="credit_days_number" min="1" max="365" value="30" style="max-width: 70px;">

                            <!-- Leyenda dinámica (día / días) -->
                            <span class="input-group-text bg-light border-0 text-muted fs-8 flex-fill justify-content-start" id="credit_days_label">
                                días de crédito
                            </span>

                            <!-- Input oculto que concatena y manda la frase completa al backend -->
                            <input type="hidden" name="credit_terms_text" id="credit_terms_hidden" value="30 días de crédito">
                        </div>
                    </div>

                    <!-- Contenedor dinámico para el Tipo de Cambio (Oculto por defecto, aparece si eligen USD) -->
                    <div class="mb-3 d-none" id="exchange_rate_container">
                        <label class="form-label fw-semibold fs-7 text-dark">Tipo de Cambio (USD a MXN)</label>
                        <div class="input-group input-group-sm shadow-2xs border-subtle rounded-2 overflow-hidden" style="max-width: 250px;">
                            <span class="input-group-text bg-light border-0 text-muted">$</span>
                            <input type="number" step="0.0001" class="form-control form-control-sm border-0" id="exchange_rate_input" name="exchange_rate" value="" placeholder="Ej. 19.1750">
                            <span class="input-group-text bg-light border-0 text-muted">MXN</span>
                        </div>
                        <div class="form-text fs-8 text-muted">Reemplaza el viejo truco del Excel; este valor se usará para la conversión limpia de tus costos.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Partidas, Cocina de Costos y Logística -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0 fs-6 text-uppercase tracking-wide text-secondary"><i class="bi bi-cart-check me-2"></i>Partidas, Costos & Logística</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold shadow-2xs" id="add_item_btn">
                            <i class="bi bi-plus-lg me-1"></i> Añadir Partida
                        </button>
                    </div>

                    <!-- Contenedor Dinámico de Renglones -->
                    <div id="items_container" class="d-flex flex-column gap-3 mb-4">
                        <!-- Inyectado por JS -->
                    </div>
                </div>

                <!-- Footer Financiero & Acciones -->
                <div class="bg-light rounded-4 p-3 border mt-auto">
                    <div class="row align-items-center">
                        <div class="col-md-7 border-end text-muted small">
                            <div class="d-flex align-items-center gap-2 text-danger fw-medium">
                                <i class="bi bi-shield-lock-fill"></i> Costos y datos logísticos protegidos para uso interno.
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex justify-content-between mb-1 fs-7">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-semibold text-dark" id="lbl_subtotal">$0.00</span>
                                <input type="hidden" name="subtotal" id="input_subtotal" value="0.00">
                            </div>
                            <div class="d-flex justify-content-between mb-2 fs-7">
                                <span class="text-muted">IVA (16%):</span>
                                <span class="fw-semibold text-dark" id="lbl_iva">$0.00</span>
                                <input type="hidden" name="tax_iva" id="input_iva" value="0.00">
                            </div>
                            <div class="d-flex justify-content-between mb-3 fs-6 border-top pt-2">
                                <span class="fw-bold text-dark">Total Venta:</span>
                                <span class="fw-bold text-success fs-5" id="lbl_total">$0.00</span>
                                <input type="hidden" name="total" id="input_total" value="0.00">
                            </div>
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold shadow-sm mb-2" id="preview_quotation_btn" data-bs-toggle="modal" data-bs-target="#previewModal">
                                <i class="bi bi-eye me-1"></i> Vista Previa de Cotización
                            </button>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Guardar y Emitir Cotización
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template Pro para Partida con Cocina de Costos y Logística -->
<template id="item_row_template">
    <div class="item-row card border shadow-2xs p-3 rounded-3 position-relative bg-white">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3 remove-item-btn" aria-label="Eliminar"></button>

        <!-- Fila Superior: Datos Comerciales Visibles -->
        <div class="row g-2 align-items-center mb-3">
            <div class="col-auto">
                <span class="badge bg-secondary-subtle text-secondary font-monospace row-number px-2 py-1">#1</span>
                <input type="hidden" class="item-number-input" name="items[INDEX][item_number]" value="1">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-8 text-muted mb-0">No. Parte</label>
                <input type="text" class="form-control form-control-sm font-monospace" name="items[INDEX][part_number]" placeholder="Ej. SAMSUNG" required>
            </div>
            <div class="col-md-5">
                <label class="form-label fs-8 text-muted mb-0">Descripción del Producto</label>
                <input type="text" class="form-control form-control-sm" name="items[INDEX][description]" placeholder="Especificaciones técnicas..." required>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 text-muted mb-0">Cant.</label>
                <input type="number" class="form-control form-control-sm qty-input" name="items[INDEX][quantity]" value="1" min="1" required>
            </div>
        </div>

        <!-- Cocina Interna de Costos & Logística (Diseño Pro en 2 bloques) -->
        <div class="bg-light-subtle border border-warning-subtle rounded-3 p-3 mt-1">
            <div class="row g-2">
                <!-- Bloque Financiero -->
                <div class="col-md-6 border-end">
                    <span class="d-block fs-8 fw-bold text-danger text-uppercase mb-2"><i class="bi bi-calculator me-1"></i> Márgenes y Costos</span>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-0">Costo Unit. ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-sm border-danger-subtle cost-input" name="items[INDEX][purchase_unit_cost]" value="0.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-0">% Utilidad</label>
                            <input type="number" step="0.01" class="form-control form-control-sm border-danger-subtle profit-input" name="items[INDEX][profit_percentage]" value="40.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-dark fw-medium mb-0">Precio Venta Unit.</label>
                            <input type="number" step="0.01" class="form-control form-control-sm bg-white fw-bold text-success price-input" name="items[INDEX][unit_price]" value="0.00" required>
                            <input type="hidden" class="total-price-input" name="items[INDEX][total_price]" value="0.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-0">Utilidad Neta ($)</label>
                            <input type="text" class="form-control form-control-sm bg-white text-muted net-profit-display" readonly value="$0.00">
                            <input type="hidden" class="net-profit-input" name="items[INDEX][net_profit]" value="0.00">
                        </div>
                    </div>
                </div>

                <!-- Bloque Logístico (Proveedor, Sucursal, Flete, Entrega) -->
                <div class="col-md-6 ps-md-3">
                    <span class="d-block fs-8 fw-bold text-primary text-uppercase mb-2"><i class="bi bi-truck me-1"></i> Logística y Abastecimiento</span>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-0">Proveedor / Mayorista</label>
                            <select class="form-select form-select-sm border-primary-subtle" name="items[INDEX][id_supplier]">
                                <option value="">Seleccionar...</option>
                                <option value="1">Syscom</option>
                                <option value="2">CT Internacional</option>
                                <option value="3">Cisco</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-0">Sucursal Prov.</label>
                            <select class="form-select form-select-sm border-primary-subtle" name="items[INDEX][id_branch]">
                                <option value="">Sucursal...</option>
                                <option value="1">Local (Monterrey)</option>
                                <option value="2">Foránea (CDMX)</option>
                                <option value="3"> Guadalajara</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-0">Flete Unitario ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="items[INDEX][freight_cost]" value="0.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-muted mb-0">Tiempo de Entrega</label>
                            <input type="text" class="form-control form-control-sm" name="items[INDEX][delivery_time_days]" value="2 a 4 días">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- ================= MODAL RÁPIDO PARA NUEVO CONTACTO (OCULTO) ================= -->
<div class="modal fade" id="quickContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark fs-6"><i class="bi bi-person-plus text-primary me-2"></i>Registrar Nuevo Contacto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="quick_contact_form">
                    <!-- Input oculto para asociar el contacto al ID del cliente actual -->
                    <input type="hidden" id="qc_customer_id" name="customer_id" value="">

                    <div class="mb-3">
                        <label class="form-label fs-7 fw-semibold text-dark">Nombre del Contacto *</label>
                        <input type="text" class="form-control form-control-sm shadow-2xs" name="contact_name" placeholder="Ej. Lic. María Fernández" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-7 fw-semibold text-dark">Puesto / Área</label>
                        <input type="text" class="form-control form-control-sm shadow-2xs" name="position" placeholder="Ej. Compras / Tesorería">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fs-7 fw-semibold text-dark">Teléfono</label>
                            <input type="text" class="form-control form-control-sm shadow-2xs" name="phone" placeholder="81XXXXXXXX">
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-7 fw-semibold text-dark">Correo Electrónico</label>
                            <input type="email" class="form-control form-control-sm shadow-2xs" name="email" placeholder="correo@empresa.com">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-medium" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm" id="save_quick_contact_btn">Guardar Contacto</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal de Vista Previa Estilo Cotización Formal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 p-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i>Vista Previa del Documento Comercial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body bg-white p-4 border rounded-3 mt-2 shadow-2xs" id="quotation_preview_content">
                <!-- AQUI SE RENDERIZARA DINAMICAMENTE EL FORMATO IGUAL AL DE TU EXCEL -->
            </div>

            <div class="modal-footer border-0 pt-3">
                <button type="button" class="btn.btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir / PDF</button>
            </div>
        </div>
    </div>
</div>

<style>
    .fs-7 {
        font-size: 0.875rem;
    }

    .fs-8 {
        font-size: 0.75rem;
    }

    .tracking-wider {
        letter-spacing: 0.05em;
    }

    .shadow-2xs {
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
    }

    .bg-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
</style>

<!-- Tus scripts organizados y linkeados limpiamente -->
<script src="public/assets/js/create_quotation/quotation_dest_autocomplete.js"></script>
<script src="public/assets/js/create_quotation/quotation_conditions.js"></script>
<script src="public/assets/js/create_quotation/quotation_items.js"></script>