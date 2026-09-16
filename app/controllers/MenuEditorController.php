<?php
require_once __DIR__ . '/../models/Menu.php';

class MenuEditorController
{
    private $menuModel;

    public function __construct()
    {
        $this->menuModel = new Menu();
    }

    public function index()
    {
        $departments = $this->menuModel->getAllDepartments();
        $menus = $this->menuModel->getAllMenus();
        
        $selectedDepartment = $_GET['department_id'] ?? null;
        $assignedMenus = $selectedDepartment ? $this->menuModel->getMenusForDepartment($selectedDepartment) : [];

        $view = 'system/menu_editor';
        require __DIR__ . '/../views/layouts/app.php';
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $departmentId = $_POST['id_department'] ?? null;
            $menus = $_POST['menus'] ?? [];

            if ($departmentId) {
                $this->menuModel->saveDepartmentMenus($departmentId, $menus);
            }
            
            header("Location: index.php?page=menu_editor&department_id=" . $departmentId);
            exit;
        }
    }
}