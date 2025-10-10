<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Creater Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../../assets/css/create_order.css">
  </head>
<body>
<?php 
  require_once __DIR__ . '/../../init.php';
  include_once("../layouts/header_seller.php");

  var_dump($_SESSION);


  
?>
<main>


<div class="container mt-5">
  <div class="container mt-4">
      <h6 id="system_order_label">ORDEN DEL SISTEMA #</h6>

  </div>

  <div class="container mt-4">
  <form action="guardar_pedido.php" method="POST">
    
    <!-- Cliente y Orden de compra -->
    <div class="col-md-6 col-12 mb-3">
        <label for="order_name" class="form-label">Titulo: </label>
        <input type="text" class="form-control" id="order_name" name="order_name" placeholder="Número de orden de compra" required>
      </div>

    <div class="row mb-3">
      <div class="col-md-6 col-12 mb-3">
        <label for="customer" class="form-label">Cliente</label>
        <input type="text" class="form-control" id="customer" name="customer" placeholder="Nombre del cliente" required>
      </div>

      <div class="col-md-6 col-12 mb-3">
        <label for="order_number" class="form-label">Orden de compra</label>
        <input type="text" class="form-control" id="order_number" name="order_number" placeholder="Número de orden de compra" required>
      </div>

    <!-- Persona que recibe y Comentarios -->
    <div class="row mb-3">
      <div class="col-md-6 col-12 mb-3">
        <label class="form-label">Persona que recibe:</label>
        <input type="text" class="form-control" name="receiver" placeholder="Nombre de quien recibe" required>
      </div>

      <div class="col-md-6 col-12 mb-3">
        <label class="form-label">Comentarios:</label>
        <input type="text" class="form-control" name="comments" placeholder="Notas del pedido">
      </div>
    </div>

    <!-- Botón -->
    <div class="d-flex justify-content-end mt-4">
      <button type="submit" class="btn btn-primary">Añadir pedido mayorista</button>
    </div>

  </form>
</div>

    


    <div class="container mt-4" id="supplier_type_form">
      <?php require_once("../forms/supplier_order_form.php") ?>
    </div>


     <button type="submit" class="btn btn-success">Guardar Pedido</button>
  </form>
</div>




</main>


    
<script src="../../assets/js/create_order/create_order_forms.js"></script>
<script src="../../assets/js/create_order/sys_number_fetch.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</body>
</html>