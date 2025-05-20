<?php
session_start();

$response = [
    'isLoggedIn' => isset($_SESSION['owner_name']) && isset($_SESSION['shop_name']),
    'userData' => [
        'owner_name' => $_SESSION['owner_name'] ?? null,
        'shop_name' => $_SESSION['shop_name'] ?? null
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>