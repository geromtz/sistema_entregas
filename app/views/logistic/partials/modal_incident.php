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
