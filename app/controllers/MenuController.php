<?php
require_once __DIR__ . '/../model/Menu.php';

class MenuController {
    private $menuModel;

    public function __construct() {
        $this->menuModel = new Menu();
    }

    public function getMenuForUser($id_position) {
    // 🔍 Depuración rápida: ¿Qué ID de posición está llegando aquí y qué devuelve el modelo?
    $menus = $this->menuModel->getMenusByPosition($id_position);
    // var_dump($id_position, $menus); exit; 
    return $menus;
}
}