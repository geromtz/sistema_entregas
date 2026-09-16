<?php
require_once __DIR__ . '/../../config/db.php';

class Customer
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Buscar clientes por business_name o company_name y por vendedor (created_by)
     */
    public function searchCustomers(string $term, int $userId): array
    {
        if (!$term || !$userId) return [];

        try {
            $stmt = $this->db->prepare("
            SELECT id, business_name, company_name
            FROM customers
            WHERE (business_name LIKE :term OR company_name LIKE :term)
              AND created_by = :userId
            ORDER BY business_name ASC
            LIMIT 10
        ");
            $stmt->execute([
                ':term' => "%$term%",
                ':userId' => $userId
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching customers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un cliente nuevo.
     * 
     * @param string $businessName
     * @param string $companyName
     * @param int    $userId
     * @return int|null  ID del cliente creado o existente
     * @throws Exception Si ocurre un error al crear
     */
    public function createCustomer(string $businessName, string $companyName, int $userId): ?int
    {
        try {
            // 1️⃣ Verificar si ya existe un cliente con el mismo nombre
            $stmt = $this->db->prepare("
            SELECT id FROM customers 
            WHERE TRIM(LOWER(business_name)) = TRIM(LOWER(:business_name))
            LIMIT 1
        ");
            $stmt->execute([':business_name' => $businessName]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                // ⚠️ Ya existe → devolver su ID
                return (int)$existingId;
            }

            // 2️⃣ Si no existe, crear nuevo
            $stmt = $this->db->prepare("
            INSERT INTO customers (business_name, company_name, created_by, created_at)
            VALUES (:business_name, :company_name, :created_by, NOW())
        ");
            $stmt->execute([
                ':business_name' => $businessName,
                ':company_name' => $companyName,
                ':created_by' => $userId
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error al crear cliente: " . $e->getMessage());
        }
    }

    /**
     * Obtener clientes aplicando la regla de seguridad por posición / rol
     */
    public function getCustomersByRole(int $userId, int $positionId): array
    {
        try {
            // Puestos globales con acceso total (Dirección, Administración, Logística, etc.)
            // Ajusta estos IDs de posición según los que maneja tu base de datos
            $globalPositions = [1, 4, 7, 11, 12];

            if (in_array($positionId, $globalPositions)) {
                // En la vista global (para administradores/directivos):
                $stmt = $this->db->query("
                    SELECT c.*, cp.* 
                    FROM customers c
                    LEFT JOIN customer_preferences cp ON cp.customer_id = c.id
                    ORDER BY c.business_name ASC
                    ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Vista de Vendedor: Solo los clientes creados por él o su equipo
                require_once __DIR__ . '/User.php';
                $userModel = new User();
                $allowedIds = $userModel->getTeamUserIds($userId);

                if (empty($allowedIds)) {
                    $allowedIds = [$userId]; // Asegurar al menos el propio ID
                }

                $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
                $stmt = $this->db->prepare("
                SELECT c.*, cp.* 
                FROM customers c
                LEFT JOIN customer_preferences cp ON cp.customer_id = c.id
                WHERE c.created_by IN ($placeholders)
                ORDER BY c.business_name ASC
                ");
                $stmt->execute($allowedIds);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log("Error fetching customers by role: " . $e->getMessage());
            return [];
        }
    }

    public function updateFullCustomer(array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE customer_preferences 
            SET invoice_cutoff_day = :invoice_cutoff_day,
                accepts_partial_invoices = :accepts_partial_invoices,
                december_close_date = :december_close_date,
                requires_remission_first = :requires_remission_first,
                requires_po_number = :requires_po_number,
                allow_monday = :allow_monday,
                allow_tuesday = :allow_tuesday,
                allow_wednesday = :allow_wednesday,
                allow_thursday = :allow_thursday,
                allow_friday = :allow_friday,
                allow_saturday = :allow_saturday,
                allow_sunday = :allow_sunday,
                delivery_time_start = :delivery_time_start,
                delivery_time_end = :delivery_time_end,
                billing_emails = :billing_emails,
                special_instructions = :special_instructions
            WHERE customer_id = :customer_id
        ");

            // Nota: Como en tu arreglo no vinieron allow_saturday ni allow_sunday 
            // (porque estaban desmarcados), los protegemos con un operador ternario o isset:
            return $stmt->execute([
                ':customer_id' => $data['customer_id'],
                ':invoice_cutoff_day' => $data['invoice_cutoff_day'] ?? null,
                ':accepts_partial_invoices' => $data['accepts_partial_invoices'] ?? 0,
                ':december_close_date' => $data['december_close_date'] ?? null,
                ':requires_remission_first' => $data['requires_remission_first'] ?? 0,
                ':requires_po_number' => $data['requires_po_number'] ?? 0,
                ':allow_monday' => isset($data['allow_monday']) ? 1 : 0,
                ':allow_tuesday' => isset($data['allow_tuesday']) ? 1 : 0,
                ':allow_wednesday' => isset($data['allow_wednesday']) ? 1 : 0,
                ':allow_thursday' => isset($data['allow_thursday']) ? 1 : 0,
                ':allow_friday' => isset($data['allow_friday']) ? 1 : 0,
                ':allow_saturday' => isset($data['allow_saturday']) ? 1 : 0,
                ':allow_sunday' => isset($data['allow_sunday']) ? 1 : 0,
                ':delivery_time_start' => $data['delivery_time_start'] ?? '08:00:00',
                ':delivery_time_end' => $data['delivery_time_end'] ?? '17:00:00',
                ':billing_emails' => $data['billing_emails'] ?? null,
                ':special_instructions' => $data['special_instructions'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error updating customer preferences: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todas las direcciones / sucursales de un cliente específico
     */
    public function getAddressesByCustomerId(int $customerId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * 
                FROM customers_address 
                WHERE id_customer = ? 
                ORDER BY id ASC
            ");
            $stmt->execute([$customerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching customer addresses: " . $e->getMessage());
            return [];
        }
    }

    public function saveAddress(array $data): bool
    {
        try {
            if (!empty($data['address_id'])) {
                // Actualizar dirección existente
                $stmt = $this->db->prepare("
                UPDATE customers_address 
                SET branch = :branch, google_maps = :google_maps, street = :street, 
                    exterior_number = :exterior_number, interior_number = :interior_number, 
                    colonia = :colonia, municipio = :municipio, codigo_postal = :codigo_postal, 
                    estado = :estado, pais = :pais, referencias = :referencias
                WHERE id = :id AND id_customer = :id_customer
            ");
                return $stmt->execute([
                    ':branch' => $data['branch'] ?? null,
                    ':google_maps' => $data['google_maps'] ?? null,
                    ':street' => $data['street'],
                    ':exterior_number' => $data['exterior_number'],
                    ':interior_number' => $data['interior_number'] ?? null,
                    ':colonia' => $data['colonia'] ?? null,
                    ':municipio' => $data['municipio'] ?? null,
                    ':codigo_postal' => $data['codigo_postal'] ?? null,
                    ':estado' => $data['estado'] ?? null,
                    ':pais' => $data['pais'] ?? 'México',
                    ':referencias' => $data['referencias'] ?? null,
                    ':id' => $data['address_id'],
                    ':id_customer' => $data['id_customer']
                ]);
            } else {
                // Crear nueva dirección
                $stmt = $this->db->prepare("
                INSERT INTO customers_address (id_customer, branch, google_maps, street, exterior_number, interior_number, colonia, municipio, codigo_postal, estado, pais, referencias)
                VALUES (:id_customer, :branch, :google_maps, :street, :exterior_number, :interior_number, :colonia, :municipio, :codigo_postal, :estado, :pais, :referencias)
            ");
                return $stmt->execute([
                    ':id_customer' => $data['id_customer'],
                    ':branch' => $data['branch'] ?? null,
                    ':google_maps' => $data['google_maps'] ?? null,
                    ':street' => $data['street'],
                    ':exterior_number' => $data['exterior_number'],
                    ':interior_number' => $data['interior_number'] ?? null,
                    ':colonia' => $data['colonia'] ?? null,
                    ':municipio' => $data['municipio'] ?? null,
                    ':codigo_postal' => $data['codigo_postal'] ?? null,
                    ':estado' => $data['estado'] ?? null,
                    ':pais' => $data['pais'] ?? 'México',
                    ':referencias' => $data['referencias'] ?? null
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error saving address: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAddress(int $addressId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM customers_address WHERE id = ?");
            return $stmt->execute([$addressId]);
        } catch (PDOException $e) {
            error_log("Error deleting address: " . $e->getMessage());
            return false;
        }
    }
}
