<div class="card mb-3 p-3">
    <h5>Pedido mayorista #1</h5>

    <div class="row g-3"> <!-- g-3 agrega espacio entre columnas -->

      <!-- Mayorista -->
      <div class="col-md-4 col-12">
        <label class="form-label">Mayorista</label>
        <input type="text" class="form-control" name="pedidos[1][mayorista]" placeholder="Nombre del mayorista" required>
      </div>

      <!-- Número de Pedido Mayorista -->
      <div class="col-md-4 col-12">
        <label class="form-label">No. Pedido Mayorista</label>
        <input type="text" class="form-control" name="pedidos[1][id_order]" required>
      </div>

      <!-- Tipo de pedido -->
      <div class="col-md-4 col-12">
        <label class="form-label">Tipo de pedido</label>
        <select class="form-select form-select-sm" id="tipo_pedido" name="pedidos[1][items][0][id_type_order]" required>
          <option value="0">Seleccionar...</option>
          <option value="1">Local</option>
          <option value="2">Foráneo a ERA</option>
          <option value="3">Proveedor a usuario final</option>
          <option value="4">Foráneo a sucursal</option>
          <option value="5">Varias sucursales</option>
        </select>
      </div>




     <div class="container mt-4">

  <!-- ================= LOCAL ================= -->
  <div class="card mb-3 p-3" id="form-local">
    <h5>Local</h5>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Cantidad</th>
            <th>Producto</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td><input type="number" class="form-control form-control-sm" name="local[items][0][quantity]" min="1" required></td>
            <td><input type="text" class="form-control form-control-sm" name="local[items][0][product]" required></td>
          </tr>
          <tr>
            <td>2</td>
            <td><input type="number" class="form-control form-control-sm" name="local[items][1][quantity]" min="1"></td>
            <td><input type="text" class="form-control form-control-sm" name="local[items][1][product]"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ================= FORANEO A ERA ================= -->
  <div class="card mb-3 p-3" id="form-foraneo-oficina">
    <h5>Foráneo a ERA</h5>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
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
            <td>1</td>
            <td><input type="number" class="form-control form-control-sm" name="foraneo_oficina[items][0][quantity]" min="1" required></td>
            <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][0][product]" required></td>
            <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][0][guide_number]"></td>
            <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][0][courier]"></td>
            <td><input type="date" class="form-control form-control-sm" name="foraneo_oficina[items][0][eta]"></td>
          </tr>
          <tr>
            <td>2</td>
            <td><input type="number" class="form-control form-control-sm" name="foraneo_oficina[items][1][quantity]" min="1"></td>
            <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][1][product]"></td>
            <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][1][guide_number]"></td>
            <td><input type="text" class="form-control form-control-sm" name="foraneo_oficina[items][1][courier]"></td>
            <td><input type="date" class="form-control form-control-sm" name="foraneo_oficina[items][1][eta]"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ================= FORANEO USUARIO FINAL ================= -->
  <div class="card mb-3 p-3" id="form-foraneo-usuario">
    <h5>Proveedor a Usuario Final</h5>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
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
  </div>

  <!-- ================= FORANEO SUCURSAL ================= -->
  <div class="card mb-3 p-3"  id="form-foraneo-sucursal">
    <h5>Foráneo a Sucursal</h5>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Cantidad</th>
            <th>Producto</th>
            <th>Fecha estimada de arribo</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td><input type="number" class="form-control form-control-sm" name="foraneo_sucursal[items][0][quantity]" min="1" required></td>
            <td><input type="text" class="form-control form-control-sm" name="foraneo_sucursal[items][0][product]" required></td>
            <td><input type="date" class="form-control form-control-sm" name="foraneo_sucursal[items][0][eta]" required></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ================= VARIAS SUCURSALES ================= -->
  <div class="card mb-3 p-3" id="form-varias-sucursales">
    <h5>Varias Sucursales</h5>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Cantidad</th>
            <th>Producto</th>
            <th>Sucursal de origen</th>
            <th>Fecha estimada de arribo</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td><input type="number" class="form-control form-control-sm" name="varias_sucursales[items][0][quantity]" min="1" required></td>
            <td><input type="text" class="form-control form-control-sm" name="varias_sucursales[items][0][product]" required></td>
            <td><input type="text" class="form-control form-control-sm" name="varias_sucursales[items][0][origin_branch]" required></td>
            <td><input type="date" class="form-control form-control-sm" name="varias_sucursales[items][0][eta]" required></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>





    </div>
  </div>