<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteger la página si el usuario no está logueado
if (!isset($_SESSION['user'])) {
    header('Location: /sistema_entregas/index.php?page=login');
    exit;
}

