<?php
class LogisticController
{
    public function pickups()
    {
        AuthMiddleware::checkDepartment([4]);

        require_once __DIR__ . '/../models/Logistic.php';
        $model = new Logistic();

        $orders = $model->getPickupOrdersGrouped();

        $view = 'logistic/monitor_pickups';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function monitorPickups()
    {
        AuthMiddleware::checkDepartment([4]);

        require_once __DIR__ . '/../models/Logistic.php';
        $model = new Logistic();

        $orders = $model->getPickupOrdersGrouped();

        $view = 'logistic/monitor_pickups';
        require __DIR__ . '/../views/layouts/app.php';
    }
}
