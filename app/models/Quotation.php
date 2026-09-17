<?php
require_once __DIR__ . '/../../config/db.php';

class Quotation
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getConnection()
    {
        return $this->db;
    }


    /**
     * Obtener el listado de cotizaciones visibles para los IDs de usuario dados (equipo/líder)
     */
    public function getQuotationsByUsers(array $userIds): array
    {
        if (empty($userIds)) return [];

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        $sql = "
            SELECT 
                q.*,
                c.business_name AS customer_name,
                p.company_name AS prospect_name,
                u.firstname, 
                u.apellidos
            FROM quotations q
            LEFT JOIN customers c ON c.id = q.id_customer
            LEFT JOIN prospects p ON p.id = q.id_prospect
            LEFT JOIN users u ON u.id = q.created_by
            WHERE q.created_by IN ($placeholders)
            ORDER BY q.quotation_date DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($userIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quotations by users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener el siguiente número consecutivo para la serie de cotización
     */
    public function getNextQuotationNumber(string $series): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(MAX(quotation_number), 0) + 1 
                FROM quotations 
                WHERE quotation_series = :series
            ");
            $stmt->execute([':series' => $series]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching next quotation number: " . $e->getMessage());
            return 1;
        }
    }


    /**
     * Crear cabecera de la cotización
     */
    public function createQuotation(array $data, int $userId): ?int
    {
        try {
            $series = $data['quotation_series'] ?? 'COT';
            $number = $this->getNextQuotationNumber($series);
            $folio  = $series . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            $stmt = $this->db->prepare("
                INSERT INTO quotations (
                    quotation_series, quotation_number, folio, 
                    id_customer, id_prospect, attention_to, 
                    quotation_date, validity_days, payment_conditions, 
                    currency, subtotal, tax_iva, total, id_status, created_by
                ) VALUES (
                    :series, :num, :folio, 
                    :custId, :prosId, :attn, 
                    :qDate, :validity, :payCond, 
                    :curr, :sub, :iva, :total, 1, :userId
                )
            ");

            $success = $stmt->execute([
                ':series'   => $series,
                ':num'      => $number,
                ':folio'    => $folio,
                ':custId'   => !empty($data['id_customer']) ? (int)$data['id_customer'] : null,
                ':prosId'   => !empty($data['id_prospect']) ? (int)$data['id_prospect'] : null,
                ':attn'     => $data['attention_to'] ?? '',
                ':qDate'    => $data['quotation_date'] ?? date('Y-m-d'),
                ':validity' => $data['validity_days'] ?? 5,
                ':payCond'  => $data['payment_conditions'] ?? 'Crédito',
                ':curr'     => $data['currency'] ?? 'MXN',
                ':sub'      => $data['subtotal'] ?? 0.00,
                ':iva'      => $data['tax_iva'] ?? 0.00,
                ':total'    => $data['total'] ?? 0.00,
                ':userId'   => $userId
            ]);

            return $success ? (int)$this->db->lastInsertId() : null;
        } catch (PDOException $e) {
            error_log("Error creating quotation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registrar una partida (Item) con su cocina interna de costos
     */
    public function addItem(int $quotationId, array $item, int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO quotation_items (
                    id_quotation, item_number, product_id, quantity, 
                    unit_price, total_price, delivery_time_days, 
                    id_supplier, id_branch, purchase_unit_cost, 
                    freight_cost, profit_percentage, net_profit, created_by
                ) VALUES (
                    :quotId, :itemNum, :prodId, :qty, 
                    :uPrice, :tPrice, :delTime, 
                    :suppId, :branchId, :pCost, 
                    :freight, :profPct, :netProf, :userId
                )
            ");

            return $stmt->execute([
                ':quotId'   => $quotationId,
                ':itemNum'  => $item['item_number'] ?? 1,
                ':prodId'   => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                ':qty'      => $item['quantity'] ?? 1,
                ':uPrice'   => $item['unit_price'] ?? 0.00,
                ':tPrice'   => $item['total_price'] ?? 0.00,
                ':delTime'  => $item['delivery_time_days'] ?? '4 a 7 días hábiles',
                ':suppId'   => !empty($item['id_supplier']) ? (int)$item['id_supplier'] : null,
                ':branchId' => !empty($item['id_branch']) ? (int)$item['id_branch'] : null,
                ':pCost'    => $item['purchase_unit_cost'] ?? 0.00,
                ':freight'  => $item['freight_cost'] ?? 0.00,
                ':profPct'  => $item['profit_percentage'] ?? 0.00,
                ':netProf'  => $item['net_profit'] ?? 0.00,
                ':userId'   => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error adding quotation item: " . $e->getMessage());
            return false;
        }
    }

    public function searchProspects(string $term, int $userId): array 
{
    if (!$term) return [];

    try {
        $stmt = $this->db->prepare("
            SELECT id, company_name, contact_name, phone, email 
            FROM prospects 
            WHERE (company_name LIKE :term OR contact_name LIKE :term)
            -- AND (registered_by = :userId OR prospect_owner = :userId) <-- Comenta esto temporalmente para probar
            ORDER BY company_name ASC
            LIMIT 10
        ");
        $stmt->execute([
            ':term' => "%$term%"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error searching prospects: " . $e->getMessage());
        return [];
    }
}

    public function getContactsByCustomer($customerId)
    {
        $stmt = $this->db->prepare("SELECT * FROM customer_contacts WHERE customer_id = :cid ORDER BY is_primary DESC");
        $stmt->execute([':cid' => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveQuickContact(array $data): bool
    {
        $stmt = $this->db->prepare("
        INSERT INTO customer_contacts (customer_id, contact_name, position, phone, email, is_primary) 
        VALUES (:customer_id, :contact_name, :position, :phone, :email, 0)
    ");

        return $stmt->execute([
            ':customer_id'  => $data['customer_id'],
            ':contact_name' => $data['contact_name'],
            ':position'     => $data['position'],
            ':phone'        => $data['phone'],
            ':email'        => $data['email']
        ]);
    }

    /**
     * Obtener una cotización por su ID con datos del cliente o prospecto
     */
    public function getQuotationById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    q.*,
                    c.business_name AS customer_name,
                    c.address AS customer_address,
                    c.phone AS customer_phone,
                    p.company_name AS prospect_name,
                    p.contact_name AS prospect_contact,
                    u.firstname, 
                    u.apellidos
                FROM quotations q
                LEFT JOIN customers c ON c.id = q.id_customer
                LEFT JOIN prospects p ON p.id = q.id_prospect
                LEFT JOIN users u ON u.id = q.created_by
                WHERE q.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $quotation ? $quotation : null;
        } catch (PDOException $e) {
            error_log("Error fetching quotation by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener las partidas (items) de una cotización
     */
    public function getItemsByQuotationId(int $quotationId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    qi.*,
                    p.name AS product_name,
                    p.part_number AS product_part_number,
                    p.description AS product_description
                FROM quotation_items qi
                LEFT JOIN products p ON p.id = qi.product_id
                WHERE qi.id_quotation = :quotationId
                ORDER BY qi.item_number ASC
            ");
            $stmt->execute([':quotationId' => $quotationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quotation items: " . $e->getMessage());
            return [];
        }
    }
}
