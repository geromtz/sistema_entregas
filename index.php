<?php
session_start();


// Autoload de controllers, models y middleware
spl_autoload_register(function ($class_name) {
    $paths = [
        __DIR__ . '/app/controllers/' . $class_name . '.php',
        __DIR__ . '/app/models/' . $class_name . '.php',
        __DIR__ . '/app/middleware/' . $class_name . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Cargamos todas las rutas
$routes = require __DIR__ . '/app/routes.php';

// Página solicitada
$page = $_GET['page'] ?? 'login';
$routes = require __DIR__ . '/app/routes.php';

// Verificamos si la ruta existe
if (!isset($routes[$page])) {
    http_response_code(404);
    echo "<h1>Página no encontrada</h1>";
    exit;
}

// Obtenemos la configuración de la ruta
$route = $routes[$page];
$controllerName = $route['controller'];
$method = $route['method'];
$isPublic = $route['public'] ?? false;

// Middleware de autenticación: si no es pública y no hay sesión, redirigimos
if (!$isPublic && !isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

// Instanciamos el controller
if (!class_exists($controllerName)) {
    http_response_code(500);
    echo "<h1>Controller '{$controllerName}' no encontrado</h1>";
    exit;
}

$controller = new $controllerName();

// Ejecutamos el método del controller
if (!method_exists($controller, $method)) {
    http_response_code(500);
    echo "<h1>Método '{$method}' no encontrado en controller '{$controllerName}'</h1>";
    exit;
}

// Ejecutamos el método, que normalmente cargará la vista o layout
$controller->$method();
