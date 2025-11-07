<?php

class LogisticController
{
    public function pickups()
    {
        // Solo Logística (ajusta si tu depto logística tiene otro ID)
        AuthMiddleware::checkDepartment([4]);

        require_once __DIR__ . '/../models/Logistic.php';
        $model = new Logistic();

        // Lista inicial (server-side) para render rápido
        $pickups = $model->getWholesalerPending();
        $drivers = $model->getDriverList();

        $view = 'logistic/pickups';
        require __DIR__ . '/../views/layouts/app.php';
    }
}
