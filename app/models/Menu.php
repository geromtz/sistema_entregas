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
     * @param int $positionId
     * @return array
     */
   public function getMenusByPosition($positionId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT m.*
                FROM menus m
                LEFT JOIN position_menus pm ON pm.id_menu = m.id AND pm.id_position = :positionId
                WHERE pm.id_position IS NOT NULL 
                   OR m.is_global = 1 
                   OR m.id IN (
                       SELECT m2.parent_id FROM menus m2 
                       JOIN position_menus pm2 ON pm2.id_menu = m2.id 
                       WHERE pm2.id_position = :positionId AND m2.parent_id IS NOT NULL
                   )
                ORDER BY COALESCE(m.parent_id, m.id), m.order_num
            ");
            $stmt->bindParam(':positionId', $positionId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching menus by position: " . $e->getMessage());
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
            if (isset($item['children']) && !empty($item['children'])) {
                echo "<li class='nav-item dropdown'>";
                echo "<a class='nav-link dropdown-toggle' href='#' role='button' data-bs-toggle='dropdown' aria-expanded='false'>{$item['name']}</a>";
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
                
                // Evitar pintar elementos vacíos que sean solo contenedores sin hijos
                if ($item['view_name'] !== '#') {
                    echo "<li class='nav-item'><a class='nav-link' href='{$href}'>{$item['name']}</a></li>";
                }
            }
        }
    }

    public function getAllPositions()
{
    try {
        $stmt = $this->db->query("SELECT * FROM positions ORDER BY name_position");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

    public function getAllMenus()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM menus ORDER BY COALESCE(parent_id, id), order_num");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getMenusForPosition($positionId)
{
    try {
        $stmt = $this->db->prepare("SELECT id_menu FROM position_menus WHERE id_position = ?");
        $stmt->execute([$positionId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

    public function savePositionMenus($positionId, array $menuIds)
{
    try {
        $this->db->beginTransaction();

        $stmt = $this->db->prepare("DELETE FROM position_menus WHERE id_position = ?");
        $stmt->execute([$positionId]);

        if (!empty($menuIds)) {
            $stmt = $this->db->prepare("INSERT INTO position_menus (id_position, id_menu) VALUES (?, ?)");
            foreach ($menuIds as $menuId) {
                $stmt->execute([$positionId, $menuId]);
            }
        }

        $this->db->commit();
        return true;
    } catch (PDOException $e) {
        $this->db->rollBack();
        return false;
    }
}
}
