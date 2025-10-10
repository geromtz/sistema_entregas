<?php
// app/middleware/AuthMiddleware.php

class AuthMiddleware {

    /**
     * Verifica si el usuario está logueado.
     * Redirige al login si no lo está.
     */
    public static function checkLogin() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    /**
     * Verifica si el usuario tiene un rol/permiso específico
     * Ejemplo: departamentos permitidos
     */
    public static function checkDepartment(array $allowedDepartments) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userDept = $_SESSION['user']['department_id'] ?? null;
        if (!in_array($userDept, $allowedDepartments)) {
            echo "<h2>No tienes permisos para acceder a esta sección.</h2>";
            exit;
        }
    }
}
