<?php
class DashboardController {
    public function index() {
        session_start();

        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $positionId = $_SESSION['user']['position_id'] ?? null;

        // Cargar vista según el puesto
        switch ($positionId) {
            case 1: // Admin general
                $view = 'admin_dashboard.php';
                break;
            case 2: // Ventas
                $view = 'sales_dashboard.php';
                break;
            case 3: // Compras
                $view = 'purchasing_dashboard.php';
                break;
            case 4: // Logística
                $view = 'logistics_dashboard.php';
                break;
            case 5: // Tesorería
                $view = 'treasury_dashboard.php';
                break;
            case 6: // Soporte Técnico
                $view = 'support_dashboard.php';
                break;
            default:
                $view = 'general_dashboard.php';
        }

        require 'app/views/dashboard/' . $view;
    }
}
