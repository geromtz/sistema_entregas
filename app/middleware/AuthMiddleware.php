<?php
// app/middleware/AuthMiddleware.php

class AuthMiddleware {

  
    public static function checkLogin() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }



    public static function checkDepartment(array $allowedDepartments) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userDept = $_SESSION['user']['department_id'] ?? null;
        if (!in_array($userDept, $allowedDepartments)) {
            echo "<h2>No tienes permisos para acceder a esta sección.</h2>";
            exit;
        }
    }
}
