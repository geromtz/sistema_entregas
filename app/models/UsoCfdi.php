<?php
// app/models/UsoCfdi.php

require_once __DIR__ . '/../../config/db.php';

class UsoCfdi
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Obtiene todos los registros de la tabla usos_cfdi.
     */
    public function getAll()
    {
        $sql = "SELECT id, uso_cfdi, descripcion FROM usos_cfdi ORDER BY uso_cfdi ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
