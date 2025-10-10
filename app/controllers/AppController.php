<?php
require_once __DIR__ . '/../models/Menu.php';

class AppController {

    public function index() {
        // 1️⃣ Sesión y autenticación
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        // 2️⃣ Generar menú dinámico
        $menuModel = new Menu();
        $menuTree = $menuModel->buildMenuTree(
            $menuModel->getMenusByDepartment($_SESSION['user']['department_id'])
        );

        // 3️⃣ Cargar header
        require_once __DIR__ . '/../views/layouts/header.php';

        // 4️⃣ Determinar vista solicitada
        $pageToLoad = $_GET['view'] ?? 'dashboard_sales';
        $basePath   = realpath(__DIR__ . '/../../');
        $viewFile   = $basePath . "/app/views/{$pageToLoad}.php";

        echo '<div class="container mt-4">';

        // 5️⃣ Deducción automática del controlador y método
        $controllerName = null;
        $methodName = null;

        if (strpos($pageToLoad, '/') !== false) {
            // Ejemplo: system/my_system_orders → folder: system, view: my_system_orders
            [$folder, $file] = explode('/', $pageToLoad, 2);

            // Ejemplo → OrdersController (por “order”), ProfileController (por “profile”), etc.
            // Detectamos el prefijo real del archivo, removemos sufijos tipo "my_", "view_", etc.
            $baseFileName = preg_replace('/^(my_|view_|edit_|create_)/', '', $file);
            $cleanName = str_replace('_', '', ucwords($baseFileName, '_'));

            // Nombre del controlador → basado en el segundo segmento o carpeta
            $controllerBase = ucfirst($folder);
            $controllerName = "{$controllerBase}Controller";

            // Nombre del método → basado en el archivo
            $methodName = lcfirst($cleanName);
        }

        // 6️⃣ Si hay controlador correspondiente, ejecútalo
        if ($controllerName) {
            $controllerFile = __DIR__ . "/{$controllerName}.php";

            if (file_exists($controllerFile)) {
                require_once $controllerFile;

                if (class_exists($controllerName)) {
                    $controller = new $controllerName();

                    if (method_exists($controller, $methodName)) {
                        // ✅ Ejecuta el método (este debe incluir la vista)
                        $controller->$methodName();
                    } else {
                        echo "<div class='alert alert-warning'>
                                <strong>{$controllerName}::{$methodName}()</strong> no existe.
                              </div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>
                            Clase <strong>{$controllerName}</strong> no encontrada.
                          </div>";
                }
            } else {
                // Si no hay controlador, intenta cargar la vista directamente
                if (file_exists($viewFile)) {
                    include $viewFile;
                } else {
                    echo "<div class='alert alert-warning'>
                            No se encontró controlador ni vista para <strong>{$pageToLoad}</strong>.
                          </div>";
                }
            }
        } 
        else {
            // Si no hay subcarpeta (vista directa como dashboard_sales)
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo "<div class='alert alert-warning'>
                        Vista <strong>{$pageToLoad}</strong> no encontrada.
                      </div>";
            }
        }

        echo '</div>';

        // 7️⃣ Footer
        require_once __DIR__ . '/../views/layouts/footer.php';
    }
}
