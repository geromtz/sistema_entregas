<?php
if (!isset($orders)) {
  echo '<div class="alert alert-danger">La variable $orders no está definida. Llama primero al OrdersController.</div>';
  $orders = [];
}

// Asegurarnos que $tempPendingOrders exista
if (!isset($tempPendingOrders)) {
  $tempPendingOrders = [];
}
$tempCount = count($tempPendingOrders);
?>

<style>

  #rowContextMenu {
  min-width: 150px;
}
#rowContextMenu a {
  cursor: pointer;
}

.order-row {
  cursor: pointer;
}
.order-row:hover {
  background-color: #f8f9fa; /* ligero resaltado */
}
</style>

<div id="rowContextMenu" class="dropdown-menu" style="position:absolute; display:none; z-index:1050;">
  <a class="dropdown-item" href="#" id="viewOrder">Ver</a>
  <a class="dropdown-item" href="#" id="editOrder">Editar</a>
  <a class="dropdown-item text-danger" href="#" id="deleteOrder">Eliminar</a>
</div>



<?php if ($tempCount > 0): ?>
  <div class="modal fade" id="tempOrdersModal" tabindex="-1" aria-labelledby="tempOrdersLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="tempOrdersLabel">Pedidos Temporales/Pendientes</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p>Tienes <?= $tempCount ?> pedido(s) temporal(es) o pendiente(s). ¿Deseas revisarlos ahora?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, luego</button>
          <button type="button" id="reviewTempOrders" class="btn btn-primary">Revisar ahora</button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Mis Pedidos</h3>
    <div>
      <button class="btn btn-outline-secondary me-2" data-bs-toggle="collapse" data-bs-target="#filtros">
        <i class="bi bi-funnel"></i> Filtros
      </button>
      <a href="index.php?page=create_order" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Pedido
      </a>
    </div>
  </div>

  <!-- Contenedor de filtros -->
  <div class="collapse mb-3" id="filtros">
    <div class="card card-body">
      <form class="row g-3" id="filterForm">
        <div class="col-md-4">
          <label class="form-label">Buscar</label>
          <input type="text" id="searchAll" class="form-control" placeholder="Buscar en todos los campos...">
        </div>
        <div class="col-md-3">
          <label class="form-label">De</label>
          <input type="date" id="fromDate" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Hasta</label>
          <input type="date" id="toDate" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Estatus</label>
          <select id="statusFilter" class="form-select">
            <option value="">Todos</option>
            <option value="abierto">Abierto</option>
            <option value="finalizado">Finalizado</option>
            <option value="incompleto">Incompleto</option>
            <option value="temporal">Temporal</option>
            <option value="pendiente">Pendiente</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Mostrar</label>
          <select id="rowsPerPage" class="form-select">
            <option value="10" <?= ($rowsPerPage ?? 10) == 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= ($rowsPerPage ?? 10) == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= ($rowsPerPage ?? 10) == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= ($rowsPerPage ?? 10) == 100 ? 'selected' : '' ?>>100</option>
          </select>
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button type="button" id="applyFilters" class="btn btn-primary mt-2">Aplicar</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Tabla de pedidos -->
  <div class="table-responsive">
  <table class="table table-striped table-bordered table-hover align-middle">
    <thead class="table-dark">
      <tr>
        <!-- ✅ Nueva columna combinada -->
        <th scope="col" class="sortable" data-column="order_code" data-order="asc">
          Orden <i class="bi bi-arrow-down"></i>
        </th>
        <th scope="col" class="sortable" data-column="customer_name" data-order="asc">
          Cliente <i class="bi bi-arrow-down"></i>
        </th>
        <th scope="col" class="sortable" data-column="system_number_date" data-order="asc">
          Fecha de orden <i class="bi bi-arrow-down"></i>
        </th>
        <th scope="col" class="sortable" data-column="system_status" data-order="asc">
          Status <i class="bi bi-arrow-down"></i>
        </th>
        <th scope="col" class="sortable" data-column="author" data-order="asc">
          Autor <i class="bi bi-arrow-down"></i>
        </th>
        <th scope="col" class="sortable" data-column="purchase_order" data-order="asc">
          Orden cliente <i class="bi bi-arrow-down"></i>
        </th>
        <th scope="col" class="sortable" data-column="name_order" data-order="asc">
          Título <i class="bi bi-arrow-down"></i>
        </th>
        <th scope="col" class="sortable" data-column="suppliers" data-order="asc">
          Proveedores <i class="bi bi-arrow-down"></i>
        </th>
      </tr>
    </thead>

    <tbody>
      <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order): ?>
          <?php if (isset($order['error'])): ?>
            <tr>
              <td colspan="8" class="text-center text-danger">
                <?= htmlspecialchars($order['error']) ?>
              </td>
            </tr>
          <?php else: ?>
            <tr data-id="<?= htmlspecialchars($order['system_id']) ?>" class="order-row">

              <!-- ✅ Muestra serie + número -->
              <td><?= htmlspecialchars($order['order_code'] ?? '—') ?></td>

              <!-- ✅ Muestra nombre del cliente -->
              <td><?= htmlspecialchars($order['customer_name'] ?? '—') ?></td>

              <td><?= htmlspecialchars($order['customer_order_date'] ?? '-') ?></td>
              <td>
                <?php
                $status = strtolower($order['system_status'] ?? '');
                $badge = match ($status) {
                  'abierto' => 'bg-success',
                  'finalizado' => 'bg-warning text-dark',
                  'incompleto' => 'bg-danger',
                  'temporal' => 'bg-secondary',
                  'pendiente' => 'bg-info text-dark',
                  default => 'bg-light text-dark'
                };
                ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span>
              </td>

              <td><?= htmlspecialchars($order['firstname'] ?? '-') ?> <?= htmlspecialchars($order['apellidos'] ?? '') ?></td>
              <td><?= htmlspecialchars($order['purchase_order'] ?? '-') ?></td>
              <td><?= htmlspecialchars($order['name_order'] ?? '-') ?></td>
              <td><?= htmlspecialchars($order['suppliers'] ?? '-') ?></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="8" class="text-center text-muted">
            No se encontraron pedidos de prueba. Revisa la base de datos o la conexión.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

  <!-- Paginación -->
  <div id="pagination" data-current-page="1">
    <ul class="pagination justify-content-center mt-3"></ul>
  </div>

</div>

<script src="public/assets/js/system/systemOrderActions.js"></script>
<script>
  // --- Variables globales ---
  let sortColumn = 'system_number'; // columna por defecto
  let sortOrder = 'asc'; // asc o desc

  document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPageSelect = document.getElementById('rowsPerPage');
    const paginationContainer = document.getElementById('pagination');
    if (!paginationContainer || !rowsPerPageSelect) return;

    // Inicializar valores por defecto si no hay selección previa
    if (!rowsPerPageSelect.value) rowsPerPageSelect.value = 10;
    paginationContainer.dataset.currentPage = 1;

    // --- Modal de pedidos temporales ---
    <?php if ($tempCount > 0): ?>
      var tempModal = new bootstrap.Modal(document.getElementById('tempOrdersModal'));
      tempModal.show();

      document.getElementById('reviewTempOrders').addEventListener('click', function() {
        const tableRows = document.querySelectorAll('table tbody tr');
        tableRows.forEach(row => {
          const statusBadge = row.querySelector('td:nth-child(3) .badge');
          if (statusBadge) {
            const text = statusBadge.textContent.trim().toLowerCase();
            if (text === 'temporal' || text === 'pendiente') {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          }
        });
        tempModal.hide();
        renderTable(1, parseInt(rowsPerPageSelect.value));
      });
    <?php endif; ?>

    // --- Función para obtener índice de columna ---
    function getColumnIndex(column) {
      const ths = document.querySelectorAll('th.sortable');
      for (let i = 0; i < ths.length; i++) {
        if (ths[i].dataset.column === column) return i + 1;
      }
      return 1;
    }

    // --- Filtro de tabla ---
    function filterTableRows() {
      const searchInput = document.getElementById('searchAll').value.toLowerCase();
      const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
      const fromDate = document.getElementById('fromDate').value;
      const toDate = document.getElementById('toDate').value;

      const table = document.querySelector('table');
      const rows = Array.from(table.querySelectorAll('tbody tr'));

      return rows.filter(row => {
        const cells = Array.from(row.children).map(c => {
          return c.textContent.trim().toLowerCase(); // quitar espacios y pasar a minúscula
        });
        const text = cells.join(' ');

        // Búsqueda global
        if (!cells.join(' ').includes(searchInput)) return false;

        // Filtro estatus
        const rowStatus = cells[2]; // columna Status
        if (statusFilter && rowStatus !== statusFilter.toLowerCase()) return false;

        // Filtro fechas
        const rowDate = cells[1]; // columna Fecha
        if (fromDate && rowDate !== '-' && rowDate < fromDate) return false;
        if (toDate && rowDate !== '-' && rowDate > toDate) return false;

        return true;
      });
    }

    // --- Renderizar tabla con ordenamiento y paginación ---
    function renderTable(page = 1, perPage = 10) {
      const table = document.querySelector('table');
      const tbody = table.querySelector('tbody');
      let filteredRows = filterTableRows();

      // Ordenamiento global
      const idx = getColumnIndex(sortColumn);
      filteredRows.sort((a, b) => {
        const aText = a.querySelector(`td:nth-child(${idx})`).textContent.trim().toLowerCase();
        const bText = b.querySelector(`td:nth-child(${idx})`).textContent.trim().toLowerCase();

        const aNum = parseFloat(aText.replace(',', '.'));
        const bNum = parseFloat(bText.replace(',', '.'));

        if (!isNaN(aNum) && !isNaN(bNum)) {
          return sortOrder === 'asc' ? aNum - bNum : bNum - aNum;
        }

        return sortOrder === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
      });

      // --- Reinsertar todas las filas filtradas en tbody ---
      filteredRows.forEach(row => tbody.appendChild(row));

      // --- Ocultar todas las filas ---
      tbody.querySelectorAll('tr').forEach(row => row.style.display = 'none');

      // --- Mostrar solo filas de la página actual ---
      const start = (page - 1) * perPage;
      const end = start + perPage;
      filteredRows.slice(start, end).forEach(row => row.style.display = '');

      renderPagination(Math.ceil(filteredRows.length / perPage), page);
    }

    // --- Paginación truncada ---
    function renderPagination(totalPages, currentPage = 1) {
      const pagination = document.querySelector('#pagination ul');
      pagination.innerHTML = '';
      if (totalPages <= 1) return;

      const createPageItem = (page, isActive = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${isActive ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = page;
        a.addEventListener('click', e => {
          e.preventDefault();
          document.querySelector('#pagination').dataset.currentPage = page;
          renderTable(page, parseInt(rowsPerPageSelect.value));
        });
        li.appendChild(a);
        return li;
      };

      const createEllipsis = () => {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        const span = document.createElement('span');
        span.className = 'page-link';
        span.textContent = '…';
        li.appendChild(span);
        return li;
      };

      // Flecha anterior
      const prev = document.createElement('li');
      prev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
      const prevLink = document.createElement('a');
      prevLink.className = 'page-link';
      prevLink.href = '#';
      prevLink.textContent = '<';
      prevLink.addEventListener('click', e => {
        e.preventDefault();
        if (currentPage > 1) renderTable(currentPage - 1, parseInt(rowsPerPageSelect.value));
      });
      prev.appendChild(prevLink);
      pagination.appendChild(prev);

      // Primera página
      pagination.appendChild(createPageItem(1, currentPage === 1));

      // Páginas centrales
      let start = Math.max(2, currentPage - 1);
      let end = Math.min(totalPages - 1, currentPage + 1);
      if (currentPage === 1) end = Math.min(totalPages - 1, 3);
      if (currentPage === totalPages) start = Math.max(2, totalPages - 2);

      if (start > 2) pagination.appendChild(createEllipsis());
      for (let i = start; i <= end; i++) {
        pagination.appendChild(createPageItem(i, currentPage === i));
      }
      if (end < totalPages - 1) pagination.appendChild(createEllipsis());

      // Última página
      if (totalPages > 1) pagination.appendChild(createPageItem(totalPages, currentPage === totalPages));

      // Flecha siguiente
      const next = document.createElement('li');
      next.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
      const nextLink = document.createElement('a');
      nextLink.className = 'page-link';
      nextLink.href = '#';
      nextLink.textContent = '>';
      nextLink.addEventListener('click', e => {
        e.preventDefault();
        if (currentPage < totalPages) renderTable(currentPage + 1, parseInt(rowsPerPageSelect.value));
      });
      next.appendChild(nextLink);
      pagination.appendChild(next);
    }

    // --- Eventos de filtros y rowsPerPage ---
    rowsPerPageSelect.addEventListener('change', () => {
      paginationContainer.dataset.currentPage = 1;
      renderTable(1, parseInt(rowsPerPageSelect.value));
    });

    document.getElementById('applyFilters').addEventListener('click', () => {
      paginationContainer.dataset.currentPage = 1;
      renderTable(1, parseInt(rowsPerPageSelect.value));
    });

    document.getElementById('searchAll').addEventListener('input', () => {
      paginationContainer.dataset.currentPage = 1;
      renderTable(1, parseInt(rowsPerPageSelect.value));
    });

    // --- Ordenamiento global ---
    document.querySelectorAll('th.sortable').forEach(th => {
      th.addEventListener('click', () => {
        const column = th.dataset.column;
        if (sortColumn === column) {
          sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
          sortColumn = column;
          sortOrder = 'asc';
        }

        // Actualizar flechas
        document.querySelectorAll('th.sortable i').forEach(i => i.className = 'bi bi-arrow-down');
        th.querySelector('i').className = sortOrder === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';

        // Renderizar desde página 1
        renderTable(1, parseInt(rowsPerPageSelect.value));
      });
    });

    // --- Inicializar tabla ---
    renderTable(1, parseInt(rowsPerPageSelect.value));
  });
</script>