<?php
// app/views/layouts/app.php

// Creamos instancia de menú y generamos el árbol para el usuario logueado
$menuModel = new Menu();
$menuTree = $menuModel->buildMenuTree(
    $menuModel->getMenusByDepartment($_SESSION['user']['department_id'])
);

// Cargamos el header (navbar)
require_once __DIR__ . '/header.php';
?>

<div class="container mt-4">
    <h1>Bienvenido a Era Computacional, <?= $_SESSION['user']['fullname'] ?>!</h1>

    <?php
    // $view debe venir definido por el controller
    if (!isset($view)) {
        echo "<div class='alert alert-warning'>No se definió la vista interna.</div>";
        return;
    }

    $basePath = realpath(__DIR__ . '/../../'); // Raíz del proyecto
    $viewFile = $basePath . "/app/views/{$view}.php";

    // Depuración opcional
    // echo "<pre>Intentando cargar: {$viewFile}</pre>";

    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo "<div class='alert alert-warning'>Vista '{$view}' no encontrada.</div>";
    }
    ?>
</div>

<?php
// Cargamos el footer
require_once __DIR__ . '/footer.php';
