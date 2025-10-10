<?php
if (!isset($orders)) {
    echo '<div class="alert alert-danger">La variable $orders no está definida. Llama primero al OrdersController.</div>';
    $orders = []; // Para que la tabla no rompa
}
?>



<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Mis Pedidos</h3>
      <div>
        <button class="btn btn-outline-secondary me-2" data-bs-toggle="collapse" data-bs-target="#filtros">
          <i class="bi bi-funnel"></i> Filtros
        </button>
        <a href="/sistema_entregas/view/system/create_order.php" class="btn btn-primary">
          <i class="bi bi-plus-lg"></i> Nuevo Pedido
        </a>
      </div>
    </div>

    <!-- Contenedor de filtros -->
    <div class="collapse mb-3" id="filtros">
      <div class="card card-body">
        <form class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Buscar</label>
            <input type="text" name="q" class="form-control" placeholder="Buscar en todos los campos..." value="">
          </div>
          <div class="col-md-3">
            <label class="form-label">De</label>
            <input type="date" name="from" class="form-control" value="">
          </div>
          <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" name="to" class="form-control" value="">
          </div>
          <div class="col-md-2">
            <label class="form-label">Estatus</label>
            <select name="status" class="form-select">
              <option value="">Todos</option>
              <option value="finalizado">Finalizado</option>
              <option value="pendiente">Pendiente</option>
              <option value="incompleto">Incompleto</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary mt-2">Aplicar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabla de pedidos -->
    <div class="table-responsive">
      <table class="table table-striped table-bordered table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>Orden del sistema</th>
            <th>Fecha de orden</th>
            <th>Status</th>
            <th>Cliente</th>
            <th>Orden cliente</th>
            <th>Título</th>
            <th>Proveedores</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
              <?php if (isset($order['error'])): ?>
                <tr>
                  <td colspan="7" class="text-center text-danger"><?= htmlspecialchars($order['error']) ?></td>
                </tr>
              <?php else: ?>
                <tr>
                  <td><?= htmlspecialchars($order['system_number'] ?? $order['system_id']) ?></td>
                  <td><?= htmlspecialchars($order['system_number_date'] ?? '-') ?></td>
                  <td>
                    <?php
                        $status = $order['system_status'] ?? '';
                        $badge = match($status) {
                            1 => 'bg-success',
                            2 => 'bg-warning text-dark',
                            3 => 'bg-danger',
                            default => 'bg-secondary'
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
              <td colspan="7" class="text-center text-muted">
                No se encontraron pedidos de prueba. Revisa la base de datos o la conexión.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
</div>
