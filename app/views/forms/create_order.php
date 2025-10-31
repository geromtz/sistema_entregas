<style>
  /* 🔹 Estilos para el autocompletado de clientes */
  #customer-list {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    max-height: 250px;
    overflow-y: auto;
  }

  /* Cada cliente en la lista */
  #customer-list .list-group-item {
    cursor: pointer;
    /* 👈 muestra el puntero tipo mano */
    transition: background 0.15s;
  }

  /* Resalta al pasar el mouse */
  #customer-list .list-group-item:hover {
    background-color: #0d6efd;
    color: white;
    /* gris clarito */
  }

  /* Elemento seleccionado con teclado (Arrow keys) */
  #customer-list .list-group-item.active {
    background-color: #0d6efd;
    color: white;
  }

  /* 🌀 Animación general de entrada y salida */
  @keyframes fadeSlideIn {
    0% {
      opacity: 0;
      transform: translateY(-15px);
    }

    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeSlideOut {
    0% {
      opacity: 1;
      transform: translateY(0);
    }

    100% {
      opacity: 0;
      transform: translateY(-15px);
    }
  }

  /* 🔹 Animación de salida suave con colapso */
  @keyframes fadeSlideCollapse {
    0% {
      opacity: 1;
      transform: translateY(0);
      height: var(--initial-height, auto);
      margin-bottom: var(--initial-margin, 1rem);
    }

    100% {
      opacity: 0;
      transform: translateY(-15px);
      height: 0;
      margin-bottom: 0;
    }
  }

  .fade-slide-collapse {
    animation: fadeSlideCollapse 0.45s ease forwards;
    overflow: hidden;
    /* evita que el contenido salte */
  }

  .fade-slide-in {
    animation: fadeSlideIn 0.4s ease forwards;
  }

  .fade-slide-out {
    animation: fadeSlideOut 0.4s ease forwards;
  }

  /* Mini formularios dentro del bloque */
  #supplier_template [id^="form-"] {
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.35s ease, transform 0.35s ease;
  }

  #supplier_template [id^="form-"][style*="display: block"] {
    opacity: 1;
    transform: translateY(0);
  }
</style>

<div class="container mt-5">
  <!-- Encabezado de la orden del sistema -->
  <div class="container mt-4">
    <input type="hidden" id="system_order_id" value="<?= htmlspecialchars($formData['system_order_id']) ?>">

    <div class="row mb-3">
      <div class="col-md-6 col-12 mb-3">
        <label for="system_order_date" class="form-label">Fecha y hora:</label>
        <input
          type="text"
          class="form-control"
          id="system_order_date"
          name="system_order_date"
          value="<?= htmlspecialchars($formData['system_order_date'] ?? '') ?>"
          readonly
          disabled>
      </div>
    </div>
  </div>
  <!-- 🔹 Datos del Pedido del Cliente -->
  <div class="container mb-3 py-2 border-bottom">
    <div class="row align-items-center">
      <div class="col-md-4 col-12">
        <small class="text-muted text-uppercase fw-semibold">Pedido del Cliente</small>
      </div>
      <div class="col-md-8 col-12 text-md-end text-start">
        <small>
          <span class="me-3">
            <strong>Serie:</strong>
            <span id="order_seller_series">
              <?= htmlspecialchars($formData['order_seller_series'] ?? '-') ?>
            </span>
          </span>
          <span>
            <strong>No.:</strong>
            <span id="order_seller_number">
              <?= htmlspecialchars($formData['order_seller_number'] ?? '-') ?>
            </span>
          </span>
        </small>
      </div>
    </div>

    <!-- Campos ocultos -->
    <input type="hidden" id="customer_order_id" name="customer_order_id"
      value="<?= htmlspecialchars($formData['customer_order_id'] ?? '') ?>">
    <input type="hidden" id="order_seller_series_input" name="order_seller_series"
      value="<?= htmlspecialchars($formData['order_seller_series'] ?? '') ?>">
    <input type="hidden" id="order_seller_number_input" name="order_seller_number"
      value="<?= htmlspecialchars($formData['order_seller_number'] ?? '') ?>">
  </div>


  <!-- Formulario de pedido de cliente -->
  <div class="container mt-4">


    <form id="customer_order_form" action="guardar_pedido.php" method="POST">



      <!-- Primera fila: Cliente y Título -->
      <div class="row mb-3">
        <div class="col-md-6 col-12 mb-3 position-relative">
          <label for="customer" class="form-label">Cliente</label>
          <input
            type="text"
            class="form-control"
            id="customer"
            name="customer_name"
            placeholder="Nombre del cliente"
            required
            autocomplete="off"
            value="<?= htmlspecialchars($formData['business_name'] ?? '') ?>">
          <input type="hidden" id="customer_id" name="customer_id" value="<?= htmlspecialchars($formData['id_customer'] ?? '') ?>">
          <div id="customer-list" class="list-group position-absolute w-100" style="z-index:1000;"></div>
        </div>

        <div class="col-md-6 col-12 mb-3">
          <label for="order_name" class="form-label">Título:</label>
          <input
            type="text"
            class="form-control"
            id="order_name"
            name="order_name"
            placeholder="Título del pedido"
            required
            value="<?= htmlspecialchars($formData['name_order'] ?? '') ?>">
        </div>
      </div>

      <!-- Segunda fila: Orden de compra y PDF -->
      <div class="row mb-3">
        <!-- Orden de compra -->
        <div class="col-md-6 col-12 mb-3">
          <label for="order_number" class="form-label">Orden de compra</label>
          <input
            type="text"
            class="form-control"
            id="order_number"
            name="order_number"
            placeholder="Número de orden de compra"
            required
            value="<?= htmlspecialchars($formData['purchase_order'] ?? '') ?>">
        </div>

        <!-- Subir PDF (La función de subir OC, se realizará en otra actualización)-->
        <div class="col-md-6 col-12 mb-3" id="pdf-upload-section">
          <label for="order_pdf" class="form-label">Subir / Actualizar PDF de la orden</label>

          <!-- Campo para subir archivo -->
          <input
            type="file"
            class="form-control"
            id="order_pdf"
            name="order_pdf"
            accept=".pdf">

          <!-- Campo oculto para la ruta -->
          <input type="hidden" id="order_pdf_path" name="order_pdf_path"
            value="<?= htmlspecialchars($formData['order_pdf_path'] ?? '') ?>">

          <!-- 🔹 ESTE DIV ES CLAVE -->
          <div id="pdf-viewer-container" class="mt-2">
            <?php if (!empty($formData['order_pdf_path'])): ?>
              <a
                href="index.php?page=download_purchase_order&system_order_id=<?= htmlspecialchars($formData['system_order_id']) ?>"
                class="btn btn-outline-primary btn-sm"
                target="_blank"
                id="view-pdf-btn">
                📎 Ver PDF actual
              </a>
              <small class="text-muted ms-2">
                (<?= htmlspecialchars($formData['order_pdf_original'] ?? 'PDF sin nombre') ?>)
              </small>
            <?php else: ?>
              <small class="text-muted">Aún no hay PDF cargado.</small>
            <?php endif; ?>
          </div>
        </div>




        <!-- Tercera fila: Persona que recibe, Comentarios y Uso CFDI -->
        <div class="row mb-3">
          <div class="col-md-4 col-12 mb-3">
            <label class="form-label">Comentarios:</label>
            <input
              type="text"
              class="form-control"
              name="comments"
              placeholder="Notas del pedido"
              value="<?= htmlspecialchars($formData['comments'] ?? '') ?>">
          </div>

          <div class="col-md-4 col-12 mb-3">
            <label class="form-label">Persona que recibe:</label>
            <input
              type="text"
              class="form-control"
              name="receiver"
              placeholder="Nombre de quien recibe"
              required
              value="<?= htmlspecialchars($formData['attn'] ?? '') ?>">
          </div>

          <div class="col-md-4 col-12 mb-3">
            <label class="form-label">Uso CFDI:</label>
            <select
              class="form-select"
              id="uso_cfdi"
              name="id_uso_cfdi"
              data-current-value="<?= htmlspecialchars($formData['id_uso_cfdi'] ?? '') ?>"
              required>
              <option value="">Seleccione un uso CFDI...</option>
              <!-- Opciones cargadas dinámicamente -->
            </select>

          </div>
        </div>


        <!-- Botón para añadir pedido mayorista -->
        <div class="row mb-3">
          <div class="col d-flex justify-content-end">
            <div id="supplier-alert" class="me-3 text-danger d-none">Ya tienes un formulario de proveedor activo</div>
            <button id="addSupplierOrder" type="button" class="btn btn-primary">Añadir pedido mayorista</button>
          </div>
        </div>

    </form>
  </div>
</div>




<div class="container mt-4" id="supplier_type_form">
  <?php require_once __DIR__ . "/supplier_order_form.php"; ?>

</div>

<div class="container mt-5" id="supplier_orders_container">
  <h5 class="mb-3 text-secondary">
    <i class="bi bi-box-seam"></i> Pedidos mayoristas guardados
  </h5>

  <!-- Grid de pedidos guardados -->
  <div class="row g-3" id="supplier_orders_grid">
    <!-- 🔹 Aquí se insertarán dinámicamente las tarjetas o filas de pedidos -->
    <div class="col-12 text-muted text-center py-4" id="no_supplier_orders">
      <i class="bi bi-inbox"></i><br>
      Aún no hay pedidos mayoristas guardados.
    </div>
  </div>
</div>




<!-- Modal de confirmación reutilizable -->
<div class="modal fade" id="confirmCreateModal" tabindex="-1" aria-labelledby="confirmCreateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="confirmCreateModalLabel">Crear nuevo registro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ⚠️ El registro <strong id="customerNameToCreate"></strong> no existe.<br>
        ¿Deseas crearlo?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="confirmCreateBtn" class="btn btn-primary">Crear</button>
      </div>
    </div>
  </div>
</div>


<!-- Modal de confirmación de cancelación -->
<div class="modal fade" id="cancelSupplierModal" tabindex="-1" aria-labelledby="cancelSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="cancelSupplierModalLabel">
          Cancelar pedido mayorista
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">
          ¿Deseas cancelar el pedido
          <strong id="cancelOrderNumber" class="text-danger">—</strong>
          del proveedor
          <strong id="cancelSupplierName" class="text-dark">—</strong>?
        </p>
        <p class="text-muted small mb-0">
          Esta acción no se puede deshacer.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cerrar
        </button>
        <button type="button" id="confirmCancelSupplierBtn" class="btn btn-danger">
          Cancelar pedido
        </button>
      </div>
    </div>
  </div>
</div>





<!-- Contenedor para alertas -->
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>




</main>
<script src="public/assets/js/create_order/auto_save_customer.js"></script>
<script src="public/assets/js/create_order/supplier_form_manager.js"></script>
<script src="public/assets/js/create_order/supplier_order_submit.js"></script>
<script src="public/assets/js/create_order/supplier_order_viewer.js"></script>
<script src="public/assets/js/create_order/customer_autocomplete.js"></script>
<script src="public/assets/js/create_order/order_status_manager.js"></script>
<script src="public/assets/js/create_order/supplier_autocomplete.js"></script>
<script src="public/assets/js/create_order/branch_autocomplete.js"></script>