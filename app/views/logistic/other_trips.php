<div class="container mt-4">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Actividades adicionales</h5>
      <i class="bi bi-clipboard2-check"></i>
    </div>

    <div class="card-body">
      <!-- 🔧 Se añadió onsubmit="return false;" para prevenir envío normal -->
      <form id="other-trip-form" class="needs-validation" novalidate onsubmit="return false;">
        <div class="row g-3">
          <div class="col-12">
            <label for="title" class="form-label fw-semibold">
              Título de la actividad <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="title" name="title" required
                   placeholder="Ej. Ir con contador, recoger equipo, visita técnica">
            <div class="invalid-feedback">Por favor ingresa un título.</div>
          </div>

          <div class="col-12">
            <label for="description" class="form-label fw-semibold">
              Descripción (opcional)
            </label>
            <textarea class="form-control" id="description" name="description"
                      rows="3" placeholder="Agrega una breve descripción o detalle adicional..."></textarea>
          </div>
        </div>

        <input type="hidden" id="id_driver" name="id_driver" value="<?= $_SESSION['user']['id'] ?? 1 ?>">

        <div class="mt-4 text-end">
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-plus-circle me-1"></i> Registrar actividad
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card mt-4 shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-semibold">Historial de actividades</h6>
      <i class="bi bi-clock-history text-secondary"></i>
    </div>

    <div class="card-body p-0">
      <?php if (!empty($trips) && !isset($trips[0]['error'])): ?>
        <div class="list-group list-group-flush">
          <?php foreach ($trips as $trip): ?>
            <div class="list-group-item d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold text-primary">
                  <?= htmlspecialchars($trip['title'] ?? 'Sin título') ?>
                </div>
                <small class="text-muted">
                  <?= htmlspecialchars($trip['description'] ?? 'Sin descripción') ?>
                </small><br>
                <small class="text-secondary">
                  Por: <?= htmlspecialchars($trip['driver_name'] ?? 'Desconocido') ?> —
                  <?= !empty($trip['created_at'])
                    ? date('d/m/Y H:i', strtotime($trip['created_at']))
                    : 'Sin fecha' ?>
                </small>
              </div>

              <div class="d-flex align-items-center">
                <span class="badge 
                  <?php
                    $status = strtolower($trip['status'] ?? 'pendiente');
                    echo match ($status) {
                      'pendiente' => 'bg-secondary',
                      'en curso' => 'bg-info',
                      'completado' => 'bg-success',
                      'cancelado' => 'bg-danger',
                      default => 'bg-secondary'
                    };
                  ?>">
                  <?= ucfirst(htmlspecialchars($trip['status'] ?? 'pendiente')) ?>
                </span>

                <!-- Botón para editar -->
                <button class="btn btn-sm btn-outline-secondary ms-2 btn-edit-trip"
                        data-id="<?= $trip['id'] ?>">
                  <i class="bi bi-pencil"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif (!empty($trips[0]['error'])): ?>
        <div class="p-4 text-danger text-center">
          ⚠️ <?= htmlspecialchars($trips[0]['error']) ?>
        </div>
      <?php else: ?>
        <div class="p-4 text-center text-muted">
          <i class="bi bi-inbox fs-2 d-block mb-2"></i>
          No hay actividades registradas aún.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ⚙️ Tu JS cargado después del formulario -->
<script src="public/assets/js/logistic/other_trips.js"></script>
