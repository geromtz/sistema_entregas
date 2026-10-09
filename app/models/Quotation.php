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

        try {
            // 🔹 1. EVALUACIÓN Y ACTUALIZACIÓN AUTOMÁTICA DE VENCIDAS
            // Busca todas las cotizaciones con estatus 'Enviada' cuya fecha de emisión 
            // más sus días de vigencia ya hayan pasado respecto a la fecha actual del servidor.
            $sqlExpireCheck = "
                UPDATE quotations 
                SET status = 'Vencida' 
                WHERE status = 'Enviada' 
                AND DATE_ADD(quotation_date, INTERVAL validity_days DAY) < CURDATE()
            ";
            $this->db->exec($sqlExpireCheck);

            // 🔹 2. OBTENER EL LISTADO ACTUALIZADO
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));

            $sql = "
                SELECT 
                    q.*,
                    c.business_name AS customer_name,
                    p.company_name AS prospect_name,
                    u.firstname, 
                    u.apellidos,
                    child.id AS replacement_id,
                    child.folio AS replacement_folio
                FROM quotations q
                LEFT JOIN customers c ON c.id = q.customer_id
                LEFT JOIN prospects p ON p.id = q.prospect_id
                LEFT JOIN users u ON u.id = q.created_by
                LEFT JOIN quotations child ON child.parent_quotation_id = q.id
                WHERE q.created_by IN ($placeholders)
                ORDER BY q.quotation_date DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($userIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching quotations by users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener el siguiente número consecutivo para la serie de cotización de ESTE vendedor
     */
    public function getNextQuotationNumber(string $series, int $userId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(MAX(quotation_number), 0) + 1 
                FROM quotations 
                WHERE quotation_series = :series AND created_by = :userId
            ");
            $stmt->execute([':series' => $series, ':userId' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching next quotation number: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Crear cabecera de la cotización con el nuevo formato de folio semántico
     */
    public function createQuotation(array $data, int $userId): ?int
    {
        try {
            $series = $data['quotation_series'] ?? 'COT';

            // 1. Obtenemos el consecutivo específico de ESTE vendedor
            $number = $this->getNextQuotationNumber($series, $userId);

            // 2. Armamos la fecha en formato AAAAMMDD
            $dateFormatted = date('Ymd');

            // 3. Construimos el folio semántico unificado
            $folio = "{$series}-{$userId}-{$dateFormatted}-{$number}";

            // 🔹 LÓGICA SEGURA: Exclusividad mutua entre Cliente y Prospecto
            $destType = $data['dest_type'] ?? 'customer';
            $custId = null;
            $prosId = null;

            if ($destType === 'prospect') {
                $prosId = !empty($data['id_prospect']) ? (int)$data['id_prospect'] : null;
            } else {
                $custId = !empty($data['id_customer']) ? (int)$data['id_customer'] : null;
            }

            // Definir estatus (por defecto Borrador)
            $status = $data['status'] ?? 'Borrador';

            $stmt = $this->db->prepare("
            INSERT INTO quotations (
            quotation_series, quotation_number, folio, 
            customer_id, prospect_id, attention_to, 
            quotation_date, validity_days, payment_conditions, credit_days, 
            currency, subtotal, tax_iva, total, status, created_by
            ) VALUES (
            :series, :num, :folio, 
            :custId, :prosId, :attn, 
            :qDate, :validity, :payCond, :credDays, 
            :curr, :sub, :iva, :total, :status, :userId
            )
            ");

            $success = $stmt->execute([
                ':series'    => $series,
                ':num'       => $number,
                ':folio'     => $folio,
                ':custId'    => $custId,
                ':prosId'    => $prosId,
                ':attn'      => $data['attention_to'] ?? '',
                ':qDate'     => $data['quotation_date'] ?? date('Y-m-d'),
                ':validity'  => !empty($data['validity_days']) ? (int)$data['validity_days'] : 4,
                ':payCond'   => $data['payment_terms'] ?? 'Credito',
                ':credDays'  => !empty($data['credit_days']) ? (int)$data['credit_days'] : 30,
                ':curr'      => $data['currency'] ?? 'MXN',
                ':sub'       => $data['subtotal'] ?? 0.00,
                ':iva'       => $data['tax_iva'] ?? 0.00,
                ':total'     => $data['total'] ?? 0.00,
                ':status'    => $status, // 👈 Aquí inyectamos la variable
                ':userId'    => $userId
            ]);

            return $success ? (int)$this->db->lastInsertId() : null;
        } catch (PDOException $e) {
            error_log("Error creating quotation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clona una cotización existente para crear una Nueva Versión (Re-cotización)
     */
    public function cloneAsNewVersion(int $quotationId, int $userId): ?int
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener la cotización original
            $original = $this->getQuotationById($quotationId);
            if (!$original) {
                $this->db->rollBack();
                return null;
            }

            // 2. Marcar la cotización original como 'Reemplazada'
            $stmtUpdate = $this->db->prepare("UPDATE quotations SET status = 'Reemplazada' WHERE id = :id");
            $stmtUpdate->execute([':id' => $quotationId]);

            // 3. Preparar datos para la nueva versión manteniendo el número base original y agregando el sufijo -V{version}
            $series = $original['quotation_series'] ?? 'COT';
            $quotationNumber = $original['quotation_number']; // Mismo número base de la cotización madre
            $dateFormatted = date('Ymd');

            $newVersionNumber = (int)($original['version'] ?? 1) + 1;

            // Folio estructurado con sufijo de versión limpio (Ej: COT-2-20260922-1-V2)
            $newFolio = "{$series}-{$userId}-{$dateFormatted}-{$quotationNumber}-V{$newVersionNumber}";

            // 4. Insertar la nueva cabecera de versión
            $stmtInsert = $this->db->prepare("
                INSERT INTO quotations (
                    quotation_series, quotation_number, folio, 
                    customer_id, prospect_id, attention_to, 
                    quotation_date, validity_days, payment_conditions, credit_days, 
                    currency, subtotal, tax_iva, total, status, 
                    version, parent_quotation_id, created_by
                ) VALUES (
                    :series, :num, :folio, 
                    :custId, :prosId, :attn, 
                    :qDate, :validity, :payCond, :credDays, 
                    :curr, :sub, :iva, :total, 'Borrador', 
                    :version, :parentId, :userId
                )
            ");

            $stmtInsert->execute([
                ':series'    => $series,
                ':num'       => $quotationNumber, // Hereda el número consecutivo original
                ':folio'     => $newFolio,        // Folio con sufijo -V2, -V3, etc.
                ':custId'    => $original['customer_id'],
                ':prosId'    => $original['prospect_id'],
                ':attn'      => $original['attention_to'],
                ':qDate'     => date('Y-m-d'),    // Fecha de hoy para la nueva versión
                ':validity'  => $original['validity_days'],
                ':payCond'   => $original['payment_conditions'],
                ':credDays'  => $original['credit_days'],
                ':curr'      => $original['currency'],
                ':sub'       => $original['subtotal'],
                ':iva'       => $original['tax_iva'],
                ':total'     => $original['total'],
                ':version'   => $newVersionNumber,
                ':parentId'  => $quotationId,     // Relación con la cotización madre
                ':userId'    => $userId
            ]);

            $newQuotationId = (int)$this->db->lastInsertId();

            // 5. Duplicar las partidas (items) de la original a la nueva versión
            $originalItems = $this->getItemsByQuotationId($quotationId);
            foreach ($originalItems as $item) {
                $this->addItem($newQuotationId, [
                    'item_number'          => $item['item_number'],
                    'product_id'           => $item['product_id'],
                    'quantity'             => $item['quantity'],
                    'unit_price'           => $item['unit_price'],
                    'total_price'          => $item['total_price'],
                    'delivery_time_days'   => $item['delivery_time_days'],
                    'id_supplier'          => $item['id_supplier'],
                    'id_branch'            => $item['id_branch'],
                    'purchase_unit_cost'   => $item['purchase_unit_cost'],
                    'freight_cost'         => $item['freight_cost'],
                    'profit_percentage'    => $item['profit_percentage'],
                    'net_profit'           => $item['net_profit']
                ], $userId);
            }

            $this->db->commit();
            return $newQuotationId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error cloning quotation as new version: " . $e->getMessage());
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

            // 🔢 Limpieza final a número entero positivo para la BD
            $cleanDeliveryDays = max(1, (int)($item['delivery_time_days'] ?? 1));

            return $stmt->execute([
                ':quotId'   => $quotationId,
                ':itemNum'  => $item['item_number'] ?? 1,
                ':prodId'   => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                ':qty'      => $item['quantity'] ?? 1,
                ':uPrice'   => $item['unit_price'] ?? 0.00,
                ':tPrice'   => $item['total_price'] ?? 0.00,
                ':delTime'  => $cleanDeliveryDays, // 👈 ¡Solo números enteros limpios en la BD!
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
                    p.company_name AS prospect_name,
                    p.contact_name AS prospect_contact,
                    u.firstname, 
                    u.apellidos,
                    parent.folio AS parent_folio,
                    child.id AS replacement_id,
                    child.folio AS replacement_folio
                FROM quotations q
                LEFT JOIN customers c ON c.id = q.customer_id
                LEFT JOIN prospects p ON p.id = q.prospect_id
                LEFT JOIN users u ON u.id = q.created_by
                LEFT JOIN quotations parent ON parent.id = q.parent_quotation_id
                LEFT JOIN quotations child ON child.parent_quotation_id = q.id
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
        $stmt = $this->db->prepare("
        SELECT 
            qi.*,
            p.part_number AS product_part_number,
            p.description AS product_description,
            s.business_name AS supplier_name
        FROM quotation_items qi
        LEFT JOIN products p ON p.id = qi.product_id
        LEFT JOIN suppliers s ON s.id = qi.id_supplier
        WHERE qi.id_quotation = ?
    ");
        $stmt->execute([$quotationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza la cabecera de una cotización existente
     */
    public function updateQuotation(int $id, array $data, int $userId): bool
    {
        try {
            // 🔹 LÓGICA SEGURA: Exclusividad mutua en actualización
            $destType = $data['dest_type'] ?? 'customer';
            $custId = null;
            $prosId = null;

            if ($destType === 'prospect') {
                $prosId = !empty($data['id_prospect']) ? (int)$data['id_prospect'] : null;
            } else {
                $custId = !empty($data['id_customer']) ? (int)$data['id_customer'] : null;
            }

            $status = $data['status'] ?? 'Borrador';

            $stmt = $this->db->prepare("
            UPDATE quotations SET 
            customer_id = :custId, 
            prospect_id = :prosId, 
            attention_to = :attn, 
            quotation_date = :qDate, 
            validity_days = :validity, 
            payment_conditions = :payCond, 
            credit_days = :credDays,
            currency = :curr, 
            subtotal = :sub, 
            tax_iva = :iva, 
            total = :total,
            status = :status
            WHERE id = :id
            ");

            return $stmt->execute([
                ':custId'    => $custId,
                ':prosId'    => $prosId,
                ':attn'      => $data['attention_to'] ?? '',
                ':qDate'     => $data['quotation_date'] ?? date('Y-m-d'),
                ':validity'  => !empty($data['validity_days']) ? (int)$data['validity_days'] : 4,
                ':payCond'   => $data['payment_terms'] ?? 'Credito',
                ':credDays'  => !empty($data['credit_days']) ? (int)$data['credit_days'] : 30,
                ':curr'      => $data['currency'] ?? 'MXN',
                ':sub'       => $data['subtotal'] ?? 0.00,
                ':iva'       => $data['tax_iva'] ?? 0.00,
                ':total'     => $data['total'] ?? 0.00,
                ':status'    => $status, // 👈 Actualizamos el estatus aquí también
                ':id'        => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating quotation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina todas las partidas asociadas a una cotización (útil antes de reinsertar en edición)
     */
    public function deleteItemsByQuotationId(int $quotationId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM quotation_items WHERE id_quotation = :quotId");
            return $stmt->execute([':quotId' => $quotationId]);
        } catch (PDOException $e) {
            error_log("Error deleting quotation items: " . $e->getMessage());
            return false;
        }
    }
}
