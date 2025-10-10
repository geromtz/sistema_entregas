<?php
require __DIR__ . '/../models/Order.php'; // ruta a Order.php

$orderModel = new Order();

// Aquí probamos con los IDs de usuarios que obtuvimos antes
$userIds = [2, 10];

try {
    $orders = $orderModel->getOrdersByUsers($userIds);
    echo "<pre>";
    if (!empty($orders)) {
        print_r($orders);
    } else {
        echo "No se encontraron pedidos para los IDs: ";
        print_r($userIds);
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
