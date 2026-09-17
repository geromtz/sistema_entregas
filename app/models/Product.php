<?php
require_once __DIR__ . '/../../config/db.php';

class Product
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Buscar productos por número de parte o descripción (para autocompletado)
     */
    public function searchProducts(string $term): array
    {
        if (!$term) return [];

        try {
            $stmt = $this->db->prepare("
                SELECT id, part_number, description, stock, unit_measure
                FROM products
                WHERE part_number LIKE :term OR description LIKE :term
                ORDER BY part_number ASC
                LIMIT 15
            ");
            $stmt->execute([':term' => "%$term%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un producto rápido desde el modal de cotización
     */
    public function createProduct(array $data): ?int
    {
        try {
            $partNumber = trim($data['part_number'] ?? '');
            $description = trim($data['description'] ?? '');

            if ($partNumber === '') {
                throw new Exception("El número de parte es obligatorio.");
            }

            // Verificar si ya existe un producto con el mismo número de parte
            $stmt = $this->db->prepare("SELECT id FROM products WHERE TRIM(LOWER(part_number)) = TRIM(LOWER(:part_number)) LIMIT 1");
            $stmt->execute([':part_number' => $partNumber]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                return (int)$existingId; // Si ya existe, devolvemos su ID existente
            }

            // Insertar el nuevo producto rápido
            $stmt = $this->db->prepare("
                INSERT INTO products (part_number, description, brand, line, status, created_at)
                VALUES (:part_number, :description, :brand, :line, 1, NOW())
            ");
            $stmt->execute([
                ':part_number' => $partNumber,
                ':description' => $description,
                ':brand'       => trim($data['brand'] ?? ''),
                ':line'        => trim($data['line'] ?? '')
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error al crear el producto: " . $e->getMessage());
        }
    }
}