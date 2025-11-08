<style>
  .accordion-button:not(.collapsed) {
    background-color: #0d6efd10;
    color: #0d6efd;
  }

  .accordion-item {
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .table-secondary th {
    background-color: #f0f3f7;
  }
</style>

<div class="container mt-4">
  <h3 class="mb-4 text-primary">
    <i class="bi bi-truck"></i> Panel de Monitoreo de Recolecciones
  </h3>

  <?php if (empty($orders)): ?>
    <div class="alert alert-info">No hay pedidos registrados para recolección.</div>
  <?php else: ?>

    <div class="accordion" id="accordionPickups">

      <?php foreach ($orders as $index => $order): ?>
        <div class="accordion-item shadow-sm mb-3">
          <h2 class="accordion-header" id="heading<?= $index ?>">
            <button class="accordion-button collapsed" type="button"
              data-bs-toggle="collapse"
              data-bs-target="#collapse<?= $index ?>"
              aria-expanded="false"
              aria-controls="collapse<?= $index ?>">

              <div class="d-flex flex-column w-100">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-bold text-dark">
                    <?php
                    switch ((int)$order['order_type']) {
                      case 1:
                        echo 'Pedido Local';
                        break;
                      case 2:
                        echo 'Pedido Foráneo a ERA';
                        break;
                      case 3:
                        echo 'Pedido Foráneo a Sucursal';
                        break;
                      case 4:
                        echo 'Pedido Varias Sucursales';
                        break;
                      case 5:
                        echo 'Pedido Proveedor a Usuario Final';
                        break;
                      default:
                        echo 'Tipo de pedido no definido';
                    }
                    ?>
                    —
                    <?= htmlspecialchars($order['supplier']) ?>
                    <?php if (!empty($order['supplier_order_number'])): ?>
                      — Pedido #<?= htmlspecialchars($order['supplier_order_number']) ?>
                    <?php endif; ?>
                  </span>

                  <?php
                  switch ($order['global_status']) {
                    case 1:
                      echo '<span class="badge bg-warning text-dark">En proceso</span>';
                      break;
                    case 2:
                      echo '<span class="badge bg-info text-dark">Parcial</span>';
                      break;
                    case 3:
                      echo '<span class="badge bg-success">Completo</span>';
                      break;
                  }
                  ?>
                </div>

                <small class="text-muted">
                  Cliente: <?= htmlspecialchars($order['customer']) ?> |
                  Fecha: <?= htmlspecialchars($order['system_number_date']) ?>
                </small>
              </div>
            </button>
          </h2>

          <div id="collapse<?= $index ?>" class="accordion-collapse collapse"
            aria-labelledby="heading<?= $index ?>" data-bs-parent="#accordionPickups">
            <div class="accordion-body bg-light">
              <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-secondary">
                  <tr>
                    <th>Proveedor</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Fecha pedido</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($order['orders'] as $item): ?>
                    <tr>
                      <td><?= htmlspecialchars($item['supplier']) ?></td>
                      <td>
                        <?php
                        switch ((int)$item['order_type']) {
                          case 1:
                            echo 'Local';
                            break;
                          case 2:
                            echo 'Foráneo ERA';
                            break;
                          case 3:
                            echo 'Foráneo Sucursal';
                            break;
                          case 4:
                            echo 'Multi Sucursal';
                            break;
                          case 5:
                            echo 'Proveedor a cliente';
                            break;
                          default:
                            echo 'N/D';
                        }
                        ?>
                      </td>
                      <td><?= htmlspecialchars($item['item_desc']) ?></td>
                      <td>
                        <?php
                        switch ((int)$item['supplier_status']) {
                          case 1:
                            echo '<span class="badge bg-warning text-dark">Pendiente</span>';
                            break;
                          case 2:
                            echo '<span class="badge bg-info text-dark">En proceso</span>';
                            break;
                          case 3:
                            echo '<span class="badge bg-success">Recolectado</span>';
                            break;
                          default:
                            echo '<span class="badge bg-secondary">Desconocido</span>';
                        }
                        ?>
                      </td>
                      <td><?= htmlspecialchars($item['order_date']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  <?php endif; ?>
</div>
