<div class="container-fluid py-3 px-2 px-md-5">
    <!-- 🔹 Título -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h3 class="fw-bold text-primary mb-3">🚛 Recolecciones pendientes</h3>
    </div>

    <!-- 🔎 FILTROS -->
    <div class="card shadow-sm mb-4 border-0 rounded-4 p-3 bg-light">
        <div class="row g-3 align-items-end">

            <!-- Búsqueda -->
            <div class="col-12 col-md-4">
                <label for="filterSearch" class="form-label fw-semibold">Buscar</label>
                <input type="text" id="filterSearch" class="form-control form-control-lg"
                    placeholder="Proveedor o número de pedido">
            </div>

            <!-- Estatus -->
            <div class="col-6 col-md-2">
                <label for="filterStatus" class="form-label fw-semibold">Estatus</label>
                <select id="filterStatus" class="form-select form-select-lg">
                    <option value="">Todos</option>
                    <option value="2">En proceso</option>
                    <option value="3">Parcial</option>
                    <option value="4">Completo</option>
                </select>
            </div>

            <!-- Proveedor (dinámico por JS) -->
            <div class="col-6 col-md-3">
                <label for="filterSupplier" class="form-label fw-semibold">Proveedor</label>
                <select id="filterSupplier" class="form-select form-select-lg">
                    <option value="">Todos</option>
                </select>
            </div>

            <!-- Fecha (rango) -->
            <div class="col-6 col-md-1">
                <label for="filterDateStart" class="form-label fw-semibold">Desde</label>
                <input type="date" id="filterDateStart" class="form-control form-control-lg">
            </div>
            <div class="col-6 col-md-1">
                <label for="filterDateEnd" class="form-label fw-semibold">Hasta</label>
                <input type="date" id="filterDateEnd" class="form-control form-control-lg">
            </div>

            <!-- Botones -->
            <div class="col-12 col-md-1 d-grid">
                <button class="btn btn-primary btn-lg" id="applyFilters">Filtrar</button>
            </div>
            <div class="col-12 col-md-1 d-grid">
                <button class="btn btn-outline-secondary btn-lg" id="clearFilters">Limpiar</button>
            </div>
        </div>
    </div>

    <!-- 🔹 LISTADO DE RECOLECCIONES -->
    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center fs-5 py-4 shadow-sm">
            No hay recolecciones pendientes.
        </div>
    <?php else: ?>
        <div id="accordionPickups" class="accordion">
            <?php foreach ($orders as $order): ?>
                <div class="accordion-item mb-3 border-0 shadow-sm rounded-4 pickup-card"
                    data-order-id="<?= $order['id'] ?>"
                    data-supplier="<?= htmlspecialchars(strtolower($order['supplier_name'])) ?>"
                    data-status="<?= htmlspecialchars($order['id_status']) ?>"
                    data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($order['supplier_order_date']))) ?>">

                    <h2 class="accordion-header" id="heading<?= $order['id'] ?>">
                        <button class="accordion-button collapsed fs-5 py-3" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse<?= $order['id'] ?>">

                            <div class="w-100 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                                <!-- Columna izquierda: Proveedor + pedido -->
                                <div class="mb-2 mb-md-0">
                                    <strong class="d-block text-dark"><?= htmlspecialchars($order['supplier_name']) ?></strong>
                                    <small class="text-muted">
                                        Pedido #<?= htmlspecialchars($order['supplier_order']) ?>
                                    </small>
                                </div>

                                <!-- Columna derecha: Fecha + usuario -->
                                <div class="text-md-end text-muted small">
                                    <div>📅 <?= date('d/m/Y H:i', strtotime($order['supplier_order_date'])) ?></div>
                                    <div>👤 <?= htmlspecialchars($order['created_by_name'] ?? 'Desconocido') ?></div>
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="collapse<?= $order['id'] ?>" class="accordion-collapse collapse"
                        data-bs-parent="#accordionPickups">
                        <div class="accordion-body bg-light">

                            <!-- 🔘 Botones globales de acción (solo a nivel pedido) -->
                            <div class="d-flex justify-content-end mb-3 gap-2 flex-wrap">
                                <button class="btn btn-danger btn-lg btn-order-incident"
                                    data-order-id="<?= $order['id'] ?>">
                                    🚨 Reportar incidente
                                </button>
                                <button class="btn btn-warning btn-lg btn-order-partial"
                                    data-order-id="<?= $order['id'] ?>">
                                    ⚠️ Marcar pedido parcial
                                </button>
                                <button class="btn btn-success btn-lg btn-order-complete"
                                    data-order-id="<?= $order['id'] ?>">
                                    ✅ Marcar pedido completo
                                </button>
                            </div>

                            <!-- 🔹 Partidas (sin botones por ítem) -->
                            <div id="items-container-<?= $order['id'] ?>"
                                class="pickup-items"
                                data-order-id="<?= $order['id'] ?>">
                                <div class="text-center text-muted p-4">
                                    <div class="spinner-border text-primary mb-2" role="status"></div><br>
                                    <span>Cargando partidas...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 🚨 MODAL INCIDENTE -->
<div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">🚨 Reportar incidente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="incidentForm" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" id="incidentOrderId">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de incidente</label>
                        <select id="incidentType" name="incident_type" class="form-select">
                            <option value="anomalia">Anomalía</option>
                            <option value="cancelacion">Cancelación</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="description" id="incidentDesc" rows="3" class="form-control"
                            placeholder="Describe brevemente lo ocurrido..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fotografía (opcional)</label>
                        <input type="file" name="photo" id="incidentPhoto" accept="image/*" class="form-control">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" id="sendIncident">Enviar reporte</button>
            </div>
        </div>
    </div>
</div>


<!-- ⚠️ MODAL PARCIAL -->
<div class="modal fade" id="partialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">⚠️ Reportar entrega parcial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="partialForm">
                    <input type="hidden" name="order_id" id="partialOrderId">

                    <div class="alert alert-info small mb-3">
                        Selecciona los productos que fueron entregados parcialmente y escribe observaciones si aplica.
                    </div>

                    <div id="partialItemsContainer">
                        <div class="text-center text-muted p-3">
                            <div class="spinner-border spinner-border-sm"></div> Cargando productos...
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea name="description" id="partialDesc" rows="3" class="form-control"
                            placeholder="Describe la razón de la parcialidad..."></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-warning" id="sendPartial">Guardar parcialidad</button>
            </div>
        </div>
    </div>
</div>


<script src="public/assets/js/logistic/pickups.js"></script>

<style>
    /* 🔧 Ajustes generales */
    .pickup-card {
        border-radius: 1.25rem !important;
    }

    .accordion-button {
        font-size: 1.1rem;
    }

    /* 🔹 Layout adaptativo */
    @media (max-width: 768px) {
        h3 {
            font-size: 1.4rem;
            text-align: center;
        }

        .accordion-button {
            font-size: 1rem;
            padding: 1rem;
        }

        .form-control-lg,
        .form-select-lg,
        .btn-lg {
            font-size: 1rem !important;
            padding: 0.6rem 0.9rem;
        }

        .card.p-3 {
            padding: 1rem !important;
        }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
        .accordion-button {
            font-size: 1.15rem;
        }

        .form-control-lg,
        .form-select-lg,
        .btn-lg {
            font-size: 1.05rem;
        }
    }

    @media (min-width: 1025px) {
        .accordion-button {
            font-size: 1.2rem;
        }

        .form-control-lg,
        .form-select-lg,
        .btn-lg {
            font-size: 1.1rem;
        }
    }

    /* 🔸 Espaciado y estilo */
    .form-label {
        font-size: 0.9rem;
    }

    .btn {
        border-radius: 0.75rem;
    }

    .accordion-item {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .accordion-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    /* 🔹 Color lateral según estatus */
    .pickup-card[data-status="2"] {
        border-left: 5px solid #0d6efd;
    }

    /* En proceso */
    .pickup-card[data-status="3"] {
        border-left: 5px solid #0dcaf0;
    }

    /* Parcial */
    .pickup-card[data-status="4"] {
        border-left: 5px solid #198754;
    }

    /* Completo */
</style>