<div class="container mt-4">
  <h3 class="mb-3">Entregas / Recolecciones — Mayorista</h3>

  <div class="card mb-3">
    <div class="card-body d-flex gap-3 flex-wrap align-items-end">
      <div>
        <label class="form-label">Chofer</label>
        <select id="driver" class="form-select">
          <option value="">Selecciona chofer</option>
          <?php foreach($drivers as $d): ?>
            <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['fullname']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Tipo</label>
        <select id="assignType" class="form-select">
          <option value="delivery">Entrega</option>
          <option value="pickup">Recolección</option>
        </select>
      </div>
      <div>
        <label class="form-label">Fecha programada</label>
        <input id="schedule" type="datetime-local" class="form-control" />
      </div>
      <div class="flex-grow-1">
        <label class="form-label">Notas</label>
        <input id="notes" type="text" class="form-control" placeholder="Notas (opcional)" />
      </div>
      <button id="btnAssignSelected" class="btn btn-primary">Asignar seleccionados</button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle" id="tbl-pickups">
      <thead class="table-light">
        <tr>
          <th><input type="checkbox" id="checkAll" /></th>
          <th>Tracking</th>
          <th>Pedido</th>
          <th>Cliente</th>
          <th>Mayorista</th>
          <th>Ítem</th>
          <th>Status</th>
          <th>Prometida</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($pickups as $row): ?>
          <tr>
            <td><input type="checkbox" class="row-check" data-id="<?= (int)$row['tracking_id'] ?>" /></td>
            <td><?= (int)$row['tracking_id'] ?></td>
            <td><?= htmlspecialchars($row['system_number']) ?></td>
            <td><?= htmlspecialchars($row['customer']) ?></td>
            <td><?= htmlspecialchars($row['supplier']) ?></td>
            <td><?= htmlspecialchars($row['item_desc']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['tracking_status']) ?></span></td>
            <td><?= htmlspecialchars($row['promised_date']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="public/assets/js/logistic/pickups.js"></script>
