<style>
  /* Ocultar todos los subformularios de proveedor por defecto */
  #supplier_template #form-local,
  #supplier_template #form-foraneo-oficina,
  #supplier_template #form-foraneo-usuario,
  #supplier_template #form-foraneo-sucursal,
  #supplier_template #form-varias-sucursales {
    display: none;
  }
</style>


<form id="supplier_template" class="card mb-3 p-3 card-supplier" onsubmit="return false;">


  <div class="container mt-4">
    <h5>Formulario pedido de mayorista</h5>

    <div class="row g-3">
      <!-- Mayorista -->
      <div class="col-md-3 col-12 mb-3 position-relative">
        <label for="supplier" class="form-label">Mayorista</label>
        <input type="text" class="form-control" id="supplier" name="supplier_name"
          placeholder="Nombre del mayorista" required autocomplete="off">
        <input type="hidden" id="supplier_id" name="mayorista">
        <div id="supplier-list" class="list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
      </div>

      <!-- 🏪 Sucursal -->
      <div class="col-md-3 col-12 mb-3 position-relative">
        <label for="branch" class="form-label">Sucursal</label>
        <input type="text" class="form-control" id="branch" name="branch_name"
          placeholder="Nombre de la sucursal" autocomplete="off" disabled>
        <input type="hidden" id="branch_id" name="id_branch">
        <div id="branch-list" class="list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
      </div>

      <!-- Número de Pedido Mayorista -->
      <div class="col-md-3 col-12 mb-3">
        <label class="form-label">No. Pedido Mayorista</label>
        <input type="text" class="form-control" name="id_order" required>
      </div>

      <!-- Tipo de pedido -->
      <div class="col-md-3 col-12 mb-3">
        <label class="form-label">Tipo de pedido</label>
        <select class="form-select form-select-sm" id="tipo_pedido" name="id_type_order" required>
          <!-- opciones dinámicas -->
        </select>
      </div>
    </div>

    <div>
      <button type="button" class="btn btn-outline-info">Agregar Nuevo Producto</button>
    </div>

    <div class="container mt-4">

      <!-- ================= LOCAL ================= -->
      <div class="card mb-3 p-3" id="form-local">
        <h5>Local</h5>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:40px;"><input type="checkbox" class="select-all"></th>
                <th>#</th>
                <th>Cantidad</th>
                <th>Producto</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="checkbox" class="row-check"></td>
                <td>1</td>
                <td>
                  <input
                    type="number"
                    class="form-control form-control-sm"
                    name="local[items][0][quantity]"
                    min="1"
                    required>
                </td>
                <td class="position-relative">
                  <input
                    type="text"
                    class="form-control form-control-sm product-search"
                    name="local[items][0][product]"
                    placeholder="Buscar por parte o descripción..."
                    autocomplete="off"
                    required>
                  <input type="hidden" name="local[items][0][product_id]" class="product-id">
                  <div class="product-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-primary add-row-btn"
            data-target="form-local">
            ➕ Agregar partida
          </button>
          <button
            type="button"
            class="btn btn-sm btn-outline-danger delete-row-btn"
            data-target="form-local">
            🗑 Eliminar seleccionadas
          </button>
        </div>
      </div>

      <!-- ================= FORÁNEO A ERA ================= -->
      <div class="card mb-3 p-3" id="form-foraneo-oficina">
        <h5>Foráneo a ERA</h5>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:40px;"><input type="checkbox" class="select-all"></th>
                <th>#</th>
                <th>Cantidad</th>
                <th>Producto</th>
                <th>No. Guía</th>
                <th>Paquetería</th>
                <th>Fecha estimada de llegada</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="checkbox" class="row-check"></td>
                <td>1</td>
                <td><input type="number" class="form-control form-control-sm" name="foraneo_oficina[items][0][quantity]" min="1" required></td>
                <td class="position-relative">
                  <input type="text" class="form-control form-control-sm product-search" name="foraneo_oficina[items][0][product]" placeholder="Buscar producto..." autocomplete="off" required>
                  <input type="hidden" name="foraneo_oficina[items][0][product_id]" class="product-id">
                  <div class="product-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
                </td>
                <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][0][guide_number]"></td>
                <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][0][courier]"></td>
                <td><input type="date" class="form-control form-control-sm" name="foraneo_oficina[items][0][eta]"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-2">
          <button type="button" class="btn btn-sm btn-outline-primary add-row-btn" data-target="form-foraneo-oficina">
            ➕ Agregar partida
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger delete-row-btn" data-target="form-foraneo-oficina">
            🗑 Eliminar seleccionadas
          </button>
        </div>
      </div>

      

      <!-- ================= FORÁNEO A SUCURSAL ================= -->
      <div class="card mb-3 p-3" id="form-foraneo-sucursal">
        <h5>Foráneo a Sucursal</h5>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:40px;"><input type="checkbox" class="select-all"></th>
                <th>#</th>
                <th>Cantidad</th>
                <th>Producto</th>
                <th>Fecha estimada de arribo</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="checkbox" class="row-check"></td>
                <td>1</td>
                <td><input type="number" class="form-control form-control-sm" name="foraneo_sucursal[items][0][quantity]" min="1" required></td>
                <td class="position-relative">
                  <input type="text" class="form-control form-control-sm product-search" name="foraneo_sucursal[items][0][product]" placeholder="Buscar producto..." autocomplete="off" required>
                  <input type="hidden" name="foraneo_sucursal[items][0][product_id]" class="product-id">
                  <div class="product-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
                </td>
                <td><input type="date" class="form-control form-control-sm" name="foraneo_sucursal[items][0][eta]" required></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-2">
          <button type="button" class="btn btn-sm btn-outline-primary add-row-btn" data-target="form-foraneo-sucursal">
            ➕ Agregar partida
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger delete-row-btn" data-target="form-foraneo-sucursal">
            🗑 Eliminar seleccionadas
          </button>
        </div>
      </div>

      <!-- ================= VARIAS SUCURSALES ================= -->
      <div class="card mb-3 p-3" id="form-varias-sucursales">
        <h5>Varias Sucursales</h5>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:40px;"><input type="checkbox" class="select-all"></th>
                <th style="width:50px;">#</th>
                <th style="width:90px;">Cantidad</th>
                <th>Producto</th>
                <th>Sucursal de origen</th>
                <th style="width:140px;" class="text-center" title="Marcar si se recoge en sucursal local de Mty">
                  Recoger en Suc. <br>
                  <input type="checkbox" class="form-check-input master-pickup-branch mt-1">
                </th>
                <th>Fecha estimada de arribo</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="checkbox" class="row-check"></td>
                <td>1</td>
                <td><input type="number" class="form-control form-control-sm" name="varias_sucursales[items][0][quantity]" min="1" required></td>
                <td class="position-relative">
                  <input type="text" class="form-control form-control-sm product-search" name="varias_sucursales[items][0][product]" placeholder="Buscar producto..." autocomplete="off" required>
                  <input type="hidden" name="varias_sucursales[items][0][product_id]" class="product-id">
                  <div class="product-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
                </td>
                <td class="position-relative">
                  <input type="text" class="form-control form-control-sm branch-search" name="varias_sucursales[items][0][origin_branch]" placeholder="Buscar sucursal..." autocomplete="off" required>
                  <input type="hidden" class="branch-id" name="varias_sucursales[items][0][origin_branch_id]">
                  <div class="branch-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
                </td>
                <td class="text-center">
                  <input type="checkbox" class="form-check-input item-pickup-branch" name="varias_sucursales[items][0][pickup_at_branch]" value="1">
                </td>
                <td><input type="date" class="form-control form-control-sm" name="varias_sucursales[items][0][eta]" required></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-2">
          <button type="button" class="btn btn-sm btn-outline-primary add-row-btn" data-target="form-varias-sucursales">
            ➕ Agregar partida
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger delete-row-btn" data-target="form-varias-sucursales">
            🗑 Eliminar seleccionadas
          </button>
        </div>
      </div>


      <!-- ================= PROVEEDOR A USUARIO FINAL ================= -->
      <div class="card mb-3 p-3" id="form-foraneo-usuario">
        <h5>Proveedor a Usuario Final</h5>

        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:40px;"><input type="checkbox" class="select-all"></th>
                <th>#</th>
                <th>Cantidad</th>
                <th>Producto</th>
                <th>No. Guía</th>
                <th>Paquetería</th>
                <th>Fecha estimada de llegada</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="checkbox" class="row-check"></td>
                <td>1</td>
                <td><input type="number" class="form-control form-control-sm" name="foraneo_usuario_final[items][0][quantity]" min="1" required></td>
                <td class="position-relative">
                  <input type="text" class="form-control form-control-sm product-search" name="foraneo_usuario_final[items][0][product]" placeholder="Buscar producto..." autocomplete="off" required>
                  <input type="hidden" name="foraneo_usuario_final[items][0][product_id]" class="product-id">
                  <div class="product-list list-group position-absolute w-100 shadow-sm" style="z-index:1000;"></div>
                </td>
                <td><input type="text" class="form-control form-control-sm" name="foraneo_usuario_final[items][0][guide_number]"></td>
                <td><input type="text" class="form-control form-control-sm" name="foraneo_usuario_final[items][0][courier]"></td>
                <td><input type="date" class="form-control form-control-sm" name="foraneo_usuario_final[items][0][eta]"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-2">
          <button type="button" class="btn btn-sm btn-outline-primary add-row-btn" data-target="form-foraneo-usuario">
            ➕ Agregar partida
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger delete-row-btn" data-target="form-foraneo-usuario">
            🗑 Eliminar seleccionadas
          </button>
        </div>
      </div>

      <div class="d-flex justify-content-end mt-3">
        <button type="button" class="btn btn-success" id="save-supplier-btn">
          Guardar pedido proveedor
        </button>
      </div>

    </div>



  </div>
</form>
