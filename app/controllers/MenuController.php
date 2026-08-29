<?php
require_once __DIR__ . '/../model/Menu.php';

class MenuController {
    private $menuModel;

    public function __construct() {
        $this->menuModel = new Menu();
    }

    public function getMenuForUser($id_department) {
        return $this->menuModel->getMenusByDepartment($id_department);
    }
}
