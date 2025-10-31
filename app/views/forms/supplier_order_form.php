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

    <div class="row g-3"> <!-- g-3 agrega espacio entre columnas -->
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




    <div class="container mt-4">

      <!-- ================= LOCAL ================= -->
      <form id="form_local" class="supplier-form" onsubmit="return false;">
        <div class="card mb-3 p-3" id="form-local" style="display:none;" onsubmit="return false;">
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
                  <td>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      name="local[items][0][product]"
                      required>
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
      </form>


      <!-- ================= FORÁNEO A ERA ================= -->
      <form id="form_foraneo_era" class="supplier-form" onsubmit="return false;">
        <div class="card mb-3 p-3" id="form-foraneo-oficina" style="display:none;">
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
                  <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][0][product]" required></td>
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
      </form>


      <!-- ================= PROVEEDOR A USUARIO FINAL ================= -->
      <form id="form_proveedor_usuario" class="supplier-form" onsubmit="return false;">
        <div class="card mb-3 p-3" id="form-foraneo-usuario" style="display:none;">
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
                  <td><input type="text" class="form-control form-control-sm" name="foraneo_usuario_final[items][0][product]" required></td>
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
      </form>


      <!-- ================= FORÁNEO A SUCURSAL ================= -->
      <form id="form_foraneo_sucursal" class="supplier-form" onsubmit="return false;">
        <div class="card mb-3 p-3" id="form-foraneo-sucursal" style="display:none;">
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
                  <td><input type="text" class="form-control form-control-sm" name="foraneo_sucursal[items][0][product]" required></td>
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
      </form>


      <!-- ================= VARIAS SUCURSALES ================= -->
      <form id="form_varias_sucursales" class="supplier-form" onsubmit="return false;">
        <div class="card mb-3 p-3" id="form-varias-sucursales" style="display:none;">
          <h5>Varias Sucursales</h5>

          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:40px;"><input type="checkbox" class="select-all"></th>
                  <th>#</th>
                  <th>Cantidad</th>
                  <th>Producto</th>
                  <th>Sucursal de origen</th>
                  <th>Fecha estimada de arribo</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><input type="checkbox" class="row-check"></td>
                  <td>1</td>
                  <td><input type="number" class="form-control form-control-sm" name="varias_sucursales[items][0][quantity]" min="1" required></td>
                  <td><input type="text" class="form-control form-control-sm" name="varias_sucursales[items][0][product]" required></td>
                  <td><input type="text" class="form-control form-control-sm" name="varias_sucursales[items][0][origin_branch]" required></td>
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
      </form>



      <div class="d-flex justify-content-end mt-3">
        <button type="button" class="btn btn-success" id="save-supplier-btn">
          Guardar pedido proveedor
        </button>
      </div>

    </div>
  </div>
  </div>
  </main>