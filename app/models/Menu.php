<?php
require_once __DIR__ . '/../../config/db.php';

class Menu
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtiene todos los menús permitidos para un departamento
     *
     * @param int $departmentId
     * @return array
     */
    public function getMenusByDepartment($departmentId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT m.*
            FROM menus m
            LEFT JOIN department_menus dm 
                ON dm.id_menu = m.id AND dm.id_department = :departmentId
            WHERE dm.id_department = :departmentId OR m.is_global = 1
            ORDER BY COALESCE(m.parent_id, m.id), m.order_num
        ");
            $stmt->bindParam(':departmentId', $departmentId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching menus: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Construye un árbol jerárquico de menús
     *
     * @param array $menus
     * @param int|null $parentId
     * @return array
     */
    public function buildMenuTree(array $menus, $parentId = null)
    {
        $tree = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                $children = $this->buildMenuTree($menus, $menu['id']);
                if (!empty($children)) {
                    $menu['children'] = $children;
                }
                $tree[] = $menu;
            }
        }
        return $tree;
    }

    /**
     * Renderiza el menú en HTML para el header
     *
     * @param array $menuTree
     */
    public function renderMenu(array $menuTree)
    {
        foreach ($menuTree as $item) {
            if (isset($item['children'])) {
                echo "<li class='nav-item dropdown'>";
                echo "<a class='nav-link dropdown-toggle' href='#' role='button' data-bs-toggle='dropdown'>{$item['name']}</a>";
                echo "<ul class='dropdown-menu'>";
                foreach ($item['children'] as $child) {
                    $href = !empty($child['view_name']) && $child['view_name'] !== '#'
                        ? "index.php?page={$child['view_name']}"
                        : "#";

                    echo "<li><a class='dropdown-item' href='{$href}'>{$child['name']}</a></li>";
                }
                echo "</ul>";
                echo "</li>";
            } else {
                $href = !empty($item['view_name']) && $item['view_name'] !== '#'
                    ? "index.php?page={$item['view_name']}"
                    : "#";
                echo "<li class='nav-item'><a class='nav-link' href='{$href}'>{$item['name']}</a></li>";
            }
        }
    }
}
