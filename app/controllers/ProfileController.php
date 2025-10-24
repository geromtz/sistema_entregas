<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';


class ProfileController {

    public function info() {
        AuthMiddleware::checkLogin();

        // Vista interna
        $view = 'profile/my_info';

        // Cargamos layout principal
        require_once __DIR__ . '/../views/layouts/app.php';
    }
}
