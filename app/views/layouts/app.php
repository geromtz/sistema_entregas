<?php
// app/views/layouts/app.php

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/Menu.php';

// Generar menú dinámico
$menuModel = new Menu();
$menuTree = $menuModel->buildMenuTree(
    $menuModel->getMenusByDepartment($_SESSION['user']['department_id'])
);
?>

<div class="container mt-4" style="margin-bottom: 100px;">
    <h1>Usuario: <?= htmlspecialchars($_SESSION['user']['fullname']) ?></h1>

    <?php
    // Validar que la vista interna esté definida
    if (!isset($view)) {
        echo "<div class='alert alert-warning'>No se definió la vista interna.</div>";
    } else {
        // Construir la ruta completa de la vista
        $viewFile = __DIR__ . "/../{$view}.php";

        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "<div class='alert alert-warning'>Vista '{$view}' no encontrada.</div>";
        }
    }
    ?>
</div>

<?php
// Cargar footer
require_once __DIR__ . '/footer.php';
