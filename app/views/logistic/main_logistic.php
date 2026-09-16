<?php
// app/views/logistic/main_logistic.php
// Tablero de Control Logístico con paginación automática (20 registros por sección)
?>

<div class="container-fluid px-4 py-4">
    <!-- Encabezado Principal -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark m-0"><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Centro de Control Logístico</h2>
            <p class="text-muted small mb-0">Gestión modular y flujo en tiempo real de operaciones de almacén y reparto.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="#" id="goToModuleBtn" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> <span id="goToText">Ir a Recolecciones</span>
            </a>
            <button class="btn btn-outline-secondary btn-sm shadow-sm" onclick="reloadPipelineData()">
                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar Tablero
            </button>
        </div>
    </div>

    <!-- 1. Embudo de Operaciones (Widgets / Tarjetas de Resumen por Etapa) -->
    <div class="row g-3 mb-4">
        <!-- Etapa 1 -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 stage-card active" data-stage="recolecciones" data-name="Recolecciones" data-url="index.php?page=logistic_pickups" onclick="switchStage('recolecciones', 'Recolecciones', 'index.php?page=logistic_pickups')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">1. Recolecciones</span>
                        <h3 class="fw-bold mb-0 text-primary" id="count-recolecciones">0</h3>
                    </div>
                    <div class="icon-box bg-primary-subtle text-primary rounded-3 p-3">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted"><i class="bi bi-clock-history me-1"></i>Por asignar ruta</div>
            </div>
        </div>

        <!-- Etapa 2 -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 stage-card" data-stage="transito_bodega" data-name="Recepción en Andén" data-url="index.php?page=warehouse_reception" onclick="switchStage('transito_bodega', 'En Ruta a Bodega / Por recepcionar', 'index.php?page=warehouse_reception')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">2. En Ruta a Bodega</span>
                        <h3 class="fw-bold mb-0 text-warning" id="count-transito_bodega">0</h3>
                    </div>
                    <div class="icon-box bg-warning-subtle text-warning rounded-3 p-3">
                        <i class="bi bi-box-arrow-in-down fs-4"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted"><i class="bi bi-upc-scan me-1"></i>Pendiente de escanear series</div>
            </div>
        </div>

        <!-- Etapa 3 -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 stage-card" data-stage="stock" data-name="Pedidos en Stock" data-url="index.php?page=stock_audit" onclick="switchStage('stock', 'Pedidos en Stock', 'index.php?page=stock_audit')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">3. Pedidos en Stock</span>
                        <h3 class="fw-bold mb-0 text-info" id="count-stock">0</h3>
                    </div>
                    <div class="icon-box bg-info-subtle text-info rounded-3 p-3">
                        <i class="bi bi-boxes fs-4"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted"><i class="bi bi-check2-circle me-1"></i>Inventario validado</div>
            </div>
        </div>

        <!-- Etapa 4 -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 stage-card" data-stage="facturacion" data-name="Facturación" data-url="#" onclick="switchStage('facturacion', 'En proceso de Facturación', '#')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">4. Para Facturación</span>
                        <h3 class="fw-bold mb-0 text-secondary" id="count-facturacion">0</h3>
                    </div>
                    <div class="icon-box bg-secondary-subtle text-secondary rounded-3 p-3">
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted"><i class="bi bi-file-earmark-text me-1"></i>En proceso administrativo</div>
            </div>
        </div>

        <!-- Etapa 5 -->
        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm p-3 stage-card" data-stage="listos_entregar" data-name="Listos para Entregar" data-url="#" onclick="switchStage('listos_entregar', 'Listos para Entregar', '#')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">5. Listos para Entregar</span>
                        <h3 class="fw-bold mb-0 text-dark" id="count-listos_entregar">0</h3>
                    </div>
                    <div class="icon-box bg-dark-subtle text-dark rounded-3 p-3">
                        <i class="bi bi-bag-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Etapa 6 -->
        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm p-3 stage-card" data-stage="asignados_entrega" data-name="Rutas de Reparto" data-url="index.php?page=delivery" onclick="switchStage('asignados_entrega', 'En Ruta de Reparto', 'index.php?page=delivery')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">6. En Ruta de Reparto</span>
                        <h3 class="fw-bold mb-0 text-primary" id="count-asignados_entrega">0</h3>
                    </div>
                    <div class="icon-box bg-primary-subtle text-primary rounded-3 p-3">
                        <i class="bi bi-geo-alt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Etapa 7 -->
        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm p-3 stage-card" data-stage="entregados" data-name="Historial de Entregas" data-url="#" onclick="switchStage('entregados', 'Entregados con Éxito', '#')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted">7. Entregados con Éxito</span>
                        <h3 class="fw-bold mb-0 text-success" id="count-entregados">0</h3>
                    </div>
                    <div class="icon-box bg-success-subtle text-success rounded-3 p-3">
                        <i class="bi bi-check-all fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Contenedor Dinámico con Filtros Avanzados y Paginación -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h5 id="activeStageTitle" class="fw-bold text-primary m-0">Mostrando: Recolecciones</h5>
                
                <!-- Barra de Herramientas de Visualización -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Filtro por Fechas -->
                    <select id="dateFilterSelect" class="form-select form-select-sm w-auto" onchange="resetPaginationAndFilter()">
                        <option value="all">Fechas: Todo el historial</option>
                        <option value="today">Hoy</option>
                        <option value="week">Esta semana</option>
                        <option value="month">Este mes</option>
                        <option value="year">Este año</option>
                    </select>

                    <!-- Selector de Ordenamiento -->
                    <select id="tableSortSelect" class="form-select form-select-sm w-auto" onchange="resetPaginationAndFilter()">
                        <option value="recent">Ordenar por: Más recientes</option>
                        <option value="folio_asc">Pedido / Folio (Ascendente)</option>
                        <option value="folio_desc">Pedido / Folio (Descendente)</option>
                        <option value="supplier_asc">Proveedor / Cliente (A - Z)</option>
                        <option value="qty_desc">Cantidad (Mayor a menor)</option>
                    </select>

                    <!-- Buscador Rápido de Texto -->
                    <div class="input-group input-group-sm w-auto" style="min-width: 220px;">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="tableSearchFilter" class="form-control" placeholder="Buscar..." onkeyup="resetPaginationAndFilter()">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="pipelineTable">
                    <thead class="table-light text-uppercase fs-7 text-muted">
                        <tr>
                            <th class="ps-4"># Folio / Pedido</th>
                            <th>Proveedor / Cliente</th>
                            <th>Descripción / Producto</th>
                            <th>Cantidad</th>
                            <th>Fecha Registro</th>
                            <th class="pe-4">Responsable / Chofer</th>
                        </tr>
                    </thead>
                    <tbody id="pipelineTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Seleccione una etapa superior para ver los registros.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Paginador Dinámico -->
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center border-0">
            <span class="small text-muted" id="paginationInfo">Mostrando 0 de 0 registros</span>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                    <!-- Los botones de paginación se inyectarán aquí -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<style>
    .stage-card {
        cursor: pointer;
        transition: all 0.25s ease-in-out;
        border: 1px solid rgba(0,0,0,0.08) !important;
        background-color: #ffffff;
    }
    
    .stage-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important;
    }

    .stage-card.active {
        background-color: #0051a1 !important;
        color: #ffffff !important;
        border-color: transparent !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
    }

    .stage-card.active .text-muted,
    .stage-card.active span {
        color: #ecf4fc !important;
    }

    .stage-card.active h3 {
        color: #ffffff !important;
    }

    .stage-card.active .icon-box {
        background-color: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
    }

    .fs-7 {
        font-size: 0.75rem;
    }
</style>

<script>
    let currentGlobalData = [];
    let currentPage = 1;
    const itemsPerPage = 20;

    function switchStage(stageKey, stageTitle, targetUrl) {
        document.querySelectorAll('.stage-card').forEach(card => {
            card.classList.remove('active');
            if(card.getAttribute('data-stage') === stageKey) {
                card.classList.add('active');
            }
        });

        document.getElementById('activeStageTitle').innerText = `Mostrando: ${stageTitle}`;

        const goToBtn = document.getElementById('goToModuleBtn');
        const goToText = document.getElementById('goToText');
        goToText.innerText = `Ir a ${document.querySelector(`[data-stage="${stageKey}"]`).getAttribute('data-name')}`;
        goToBtn.setAttribute('href', targetUrl);

        const tbody = document.getElementById('pipelineTableBody');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando registros...</td></tr>`;

        fetch(`index.php?page=api_get_stage_data&stage=${stageKey}`)
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Respuesta no válida:", text);
                    throw new Error("El servidor devolvió una respuesta no válida.");
                }
            })
            .then(response => {
                if (response.success) {
                    updateStageCounts(response.counts);
                    currentGlobalData = response.data || [];
                    currentPage = 1; // Reiniciar a página 1 al cambiar de etapa
                    applyTableFilters();
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${response.message || 'Desconocido'}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${error.message}</td></tr>`;
            });
    }

    function resetPaginationAndFilter() {
        currentPage = 1; // Reiniciar a la primera página al buscar o cambiar filtros
        applyTableFilters();
    }

    function applyTableFilters() {
        const searchTerm = document.getElementById('tableSearchFilter').value.toLowerCase();
        const dateFilter = document.getElementById('dateFilterSelect').value;
        const sortBy = document.getElementById('tableSortSelect').value;

        let filtered = currentGlobalData.filter(item => {
            // 1. Filtro de búsqueda por texto
            const textMatch = Object.values(item).some(val => 
                String(val || '').toLowerCase().includes(searchTerm)
            );
            if (!textMatch) return false;

            // 2. Filtro de fechas (Si el filtro es 'all', pasan todos. Si es específico, validamos con tolerancia)
            if (dateFilter !== 'all') {
                const itemDateStr = item.created_at || item.date || item.pickup_date;
                if (!itemDateStr) {
                    return false; // Omitir si no tiene fecha y se pide un rango estricto
                }

                const itemDate = new Date(itemDateStr);
                if (isNaN(itemDate.getTime())) return false;

                const now = new Date();

                if (dateFilter === 'today') {
                    if (itemDate.toDateString() !== now.toDateString()) return false;
                } else if (dateFilter === 'week') {
                    const firstDayOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
                    firstDayOfWeek.setHours(0, 0, 0, 0);
                    if (itemDate < firstDayOfWeek) return false;
                } else if (dateFilter === 'month') {
                    if (itemDate.getMonth() !== new Date().getMonth() || itemDate.getFullYear() !== new Date().getFullYear()) return false;
                } else if (dateFilter === 'year') {
                    if (itemDate.getFullYear() !== new Date().getFullYear()) return false;
                }
            }
            return true;
        });

        // 3. Ordenamiento visual
        filtered.sort((a, b) => {
            if (sortBy === 'folio_asc') {
                return (a.folio || '').localeCompare(b.folio || '');
            } else if (sortBy === 'folio_desc') {
                return (b.folio || '').localeCompare(a.folio || '');
            } else if (sortBy === 'supplier_asc') {
                return (a.provider_or_client || '').localeCompare(b.provider_or_client || '');
            } else if (sortBy === 'qty_desc') {
                return (parseInt(b.quantity) || 0) - (parseInt(a.quantity) || 0);
            } else {
                return (b.item_id || 0) - (a.item_id || 0);
            }
        });

        // 4. Paginación de 20 registros
        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const startIndex = (currentPage - 1) * itemsPerPage;
        const paginatedData = filtered.slice(startIndex, startIndex + itemsPerPage);

        renderTableData(paginatedData, startIndex, totalItems);
        renderPaginationControls(totalPages, totalItems);
    }

    function renderTableData(data, startIndex, totalItems) {
        const tbody = document.getElementById('pipelineTableBody');
        
        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No hay registros que coincidan con los filtros seleccionados.</td></tr>`;
            return;
        }

        let html = '';
        data.forEach((item, index) => {
            html += `
                <tr>
                    <td class="ps-4 fw-bold text-primary">${item.folio || 'S/F'}</td>
                    <td>${item.provider_or_client || 'N/A'}</td>
                    <td><code>${item.part_number || 'N/A'}</code> - ${item.description || 'Sin descripción'}</td>
                    <td><span class="badge bg-secondary">${item.quantity || 0} pz</span></td>
                    <td><small class="text-muted"><i class="bi bi-calendar3 me-1"></i>${item.created_at || item.pickup_date || 'N/A'}</small></td>
                    <td class="pe-4"><i class="bi bi-person-badge me-1"></i>${item.responsible || 'Sin asignar'}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        // Actualizar etiqueta informativa del paginador
        const endRange = Math.min(startIndex + data.length, totalItems);
        document.getElementById('paginationInfo').innerText = `Mostrando del ${totalItems > 0 ? startIndex + 1 : 0} al ${endRange} de ${totalItems} registros`;
    }

    function renderPaginationControls(totalPages, totalItems) {
        const container = document.getElementById('paginationContainer');
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        // Botón Anterior
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Anterior</a>
                 </li>`;

        // Números de página
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                         </li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Botón Siguiente
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Siguiente</a>
                 </li>`;

        container.innerHTML = html;
    }

    function changePage(page) {
        if (page < 1) return;
        currentPage = page;
        applyTableFilters();
    }

    function updateStageCounts(counts) {
        if (!counts) return;
        for (const [key, value] of Object.entries(counts)) {
            const badge = document.getElementById(`count-${key}`);
            if (badge) {
                badge.innerText = value;
            }
        }
    }

    function reloadPipelineData() {
        const activeCard = document.querySelector('.stage-card.active');
        if (activeCard) {
            const stage = activeCard.getAttribute('data-stage');
            const title = document.getElementById('activeStageTitle').innerText.replace('Mostrando: ', '');
            const url = document.getElementById('goToModuleBtn').getAttribute('href');
            switchStage(stage, title, url);
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const defaultCard = document.querySelector('.stage-card.active');
        if (defaultCard) {
            switchStage(
                defaultCard.getAttribute('data-stage'), 
                defaultCard.getAttribute('data-name'), 
                defaultCard.getAttribute('data-url')
            );
        }
    });
</script>