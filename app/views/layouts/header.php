<!-- app/views/layouts/header.php -->

<?php
require_once __DIR__ . '/../../models/Menu.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$menuModel = new Menu();
$menuTree = $menuModel->buildMenuTree(
    $menuModel->getMenusByPosition($_SESSION['user']['id_position'])
);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERA</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">ERA</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
     <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <?php $menuModel->renderMenu($menuTree); ?>
    </ul>
    </div>
  </div>
</nav>
