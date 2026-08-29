<?php
require __DIR__ . '/../models/User.php'; // ajusta la ruta según donde esté tu User.php

$user = new User();
$ids = $user->getTeamUserIds(2); // reemplaza 2 por un ID de usuario que exista en tu DB
echo "<pre>";
print_r($ids);
echo "</pre>";
