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
