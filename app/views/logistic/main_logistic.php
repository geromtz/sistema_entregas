<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.bootstrap5.min.css">

<style>
    /* Estilo para ocupar el 100% real del monitor */
    .main-wrapper-logistica {
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        background: #fff;
        padding: 0 40px;
    }

    .tabla-minimalista {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.95rem;
        /* El tamaño de letra ideal solicitado */
    }

    .tabla-minimalista th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.8px;
        color: #555;
        padding: 15px 12px;
        border-bottom: 2px solid #ddd;
        background: #fff;
    }

    .tabla-minimalista td {
        padding: 14px 12px;
        border-bottom: 1px solid #f0f0f0;
        color: #222;
        transition: background-color 0.2s ease;
    }

    /* Hover gris solicitado */
    .tabla-minimalista tr:hover td {
        background-color: #eeeeee !important;
        cursor: default;
    }

    /* Estilo para las filas de agrupación */
    .dtrg-group td {
        background-color: #f8f9fa !important;
        font-weight: 700 !important;
        color: #333 !important;
        padding: 10px 15px !important;
        border-bottom: 2px solid #eee !important;
    }

    .filter-box {
        background: #fafafa;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 15px;
    }

    .status-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 8px;
    }

    /* Hacer que el popover sea ancho para el historial detallado */
    .popover {
        max-width: 550px !important;
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
        /* SOLUCIÓN AL LOOP: El popover no debe interferir con el mouse */
        pointer-events: none;
    }

    .popover-body {
        /* Si el mensaje es MUY largo, permitimos que salte de línea 
       pero mantenemos un ancho cómodo para evitar el loop */
        white-space: normal;
        padding: 12px 15px;
        font-size: 0.88rem;
    }

    /* Ajuste del header del popover */
    .popover-header {
        background-color: #ffc107;
        color: #000;
        font-weight: bold;
        border-bottom: 1px solid #e9ecef;
    }

    /* Animación para que el icono de incidente llame la atención del líder */
    .animate-pulse {
        animation: pulse-orange 2s infinite;
    }

    @keyframes pulse-orange {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.2);
            opacity: 0.7;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Estilo para que el Popover se vea limpio */
    .popover {
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2);
    }

    .popover-header {
        background-color: #ffc107;
        color: #000;
        font-weight: bold;
    }
</style>

<div class="main-wrapper-logistica">
    <div class="py-3 d-flex justify-content-between align-items-center">
        <h2 class="fw-light m-0 text-dark">Logística</h2>
        <div class="d-flex gap-2">
            <select id="changeGroup" class="form-select form-select-sm" style="width: 200px;">
                <option value="">Sin Agrupar</option>
                <option value="2">Agrupar por Pedido</option>
                <option value="3">Agrupar por Tipo de Pedido</option>
                <option value="8">Agrupar por Cliente</option>
            </select>
        </div>
    </div>

    <div class="filter-box d-flex gap-3 align-items-end mb-3">
        <div>
            <label class="small text-muted d-block">Estatus</label>
            <select id="filterStatus" class="form-select form-select-sm" style="width: 150px;">
                <option value="">Todos</option>
                <option value="Entregado">Entregado</option>
                <option value="Tránsito">En tránsito</option>
                <option value="Pendiente">Pendiente</option>
            </select>
        </div>
        <div class="flex-grow-1">
            <label class="small text-muted d-block">Búsqueda rápida</label>
            <input type="text" id="customSearch" class="form-control form-control-sm" placeholder="Buscar proveedor, artículo, pedido...">
        </div>
    </div>

    <div style="overflow-x: auto; width: 100%;">
        <table id="tablaLogistica" class="tabla-minimalista">
            <thead>
                <tr class="text-center">
                    <th>Fecha Pedido</th>
                    <th>Proveedor</th>
                    <th>Pedido Mayorista</th>
                    <th>Tipo</th>
                    <th>Recolectado</th>
                    <th>Fecha Recol.</th>
                    <th>Artículos</th>
                    <th>Vendedor</th>
                    <th>Cliente</th>
                    <th>Entregado</th>
                    <th>Fecha Entrega</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <?php
                    if ($row['delivery_status'] == 1) {
                        $dot = "#28a745";
                        $txt = "Entregado";
                    } elseif ($row['pickup_status'] == 1) {
                        $dot = "#fd7e14";
                        $txt = "Tránsito";
                    } else {
                        $dot = "#adb5bd";
                        $txt = "Pendiente";
                    }
                    ?>
                    <tr>
                        <td class="text-center text-nowrap"><?= date('d/m/y H:i', strtotime($row['fecha_pedido'])) ?></td>
                        <td><?= $row['proveedor_name'] ?></td>
                        <td class="fw-bold text-primary text-center"><?= $row['pedido_mayorista'] ?></td>
                        <td class="text-center small text-muted text-uppercase"><?= $row['tipo_pedido_nombre'] ?></td>
                        <td><?= $row['pickup_user'] ?? '-' ?></td>
                        <td class="text-center text-nowrap"><?= $row['pickup_date'] ? date('d/m/y H:i', strtotime($row['pickup_date'])) : '-' ?></td>
                        <td style="white-space:normal; min-width:250px;">
                            <div class="d-flex align-items-center justify-content-between">
                                <span><?= $row['articulos'] ?></span>

                                <?php if (!empty($row['incident_history'])): ?>
                                    <button type="button"
                                        class="btn btn-link p-0 ms-2 text-warning border-0"
                                        data-bs-toggle="popover"
                                        data-bs-html="true"
                                        data-bs-trigger="focus hover"
                                        data-bs-placement="left"
                                        title="Historial de Incidencias"
                                        data-bs-content="<?= htmlspecialchars($row['incident_history']) ?>">
                                        <i class="bi bi-exclamation-triangle-fill animate-pulse" style="font-size: 1.1rem;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= $row['vendedor_name'] ?></td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($row['cliente_name']) ?></span>

                                <a href="#" class="text-decoration-none small text-primary fw-bold">
                                    (PO<?= htmlspecialchars($row['purchase_order']) ?>)
                                </a>
                            </div>
                        </td>
                        <td><?= $row['delivery_user'] ?? '-' ?></td>
                        <td class="text-center text-nowrap"><?= $row['delivery_date'] ? date('d/m/y H:i', strtotime($row['delivery_date'])) : '-' ?></td>
                        <td class="text-center">
                            <span style="white-space:nowrap; font-size:0.85rem;"><i class="status-dot" style="background:<?= $dot ?>;"></i><?= $txt ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>

<script>
    $(document).ready(function() {
        // UNIFICADO: Solo una inicialización con el drawCallback incluido
        var table = $('#tablaLogistica').DataTable({
            pageLength: 25,
            dom: 'rtip',
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [
                [0, 'desc']
            ],
            rowGroup: {
                dataSrc: null,
                enable: false
            },
            // Se ejecuta cada vez que la tabla se redibuja (filtros, paginación, grupos)
            drawCallback: function() {
                var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
                popoverTriggerList.map(function(popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl, {
                        trigger: 'focus hover', // Importante para móvil y PC
                        container: 'body'
                    });
                });
            }
        });

        // Filtro de Estatus (Columna 11)
        $('#filterStatus').on('change', function() {
            table.column(11).search(this.value).draw();
        });

        // Búsqueda personalizada
        $('#customSearch').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Agrupación dinámica
        $('#changeGroup').on('change', function() {
            var val = $(this).val();

            if (val === "") {
                table.rowGroup().disable().draw();
                table.order([
                    [0, 'desc']
                ]).draw();
            } else {
                var colIndex = parseInt(val);
                table.order([
                    [colIndex, 'asc']
                ]).rowGroup().dataSrc(colIndex).enable().draw();
            }
        });
    });
</script>