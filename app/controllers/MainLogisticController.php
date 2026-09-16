<?php
// app/controllers/MainLogisticController.php

require_once __DIR__ . '/../models/MainLogisticModel.php';

class MainLogisticController
{
    private $model;

    public function __construct()
    {
        // Aseguramos control de sesión/acceso estándar
        if (class_exists('AuthMiddleware')) {
            AuthMiddleware::checkLogin();
        }
        $this->model = new MainLogisticModel();
    }

    /**
     * Muestra la vista principal del Centro de Control Logístico (Tablero)
     */
    public function index()
    {
        // Opcional: Obtener contadores iniciales para pintar las tarjetas de golpe
        $counts = $this->model->getStagesCounts();
        
        $view = 'logistic/main_logistic';
        require __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * API Endpoint para obtener los registros y contadores de la etapa seleccionada
     */
    public function apiGetStageData()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $stage = $_GET['stage'] ?? 'recolecciones';

        try {
            $data = $this->model->getItemsByStage($stage);
            $counts = $this->model->getStagesCounts();

            echo json_encode([
                'success' => true,
                'stage'   => $stage,
                'counts'  => $counts,
                'data'    => $data
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("apiGetStageData error: " . $e->getMessage());
            
            // Forzamos la salida del error exacto de PHP para depuración en pantalla
            echo json_encode([
                'success' => false,
                'message' => 'Error en servidor: ' . $e->getMessage() . ' (Línea: ' . $e->getLine() . ')'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}