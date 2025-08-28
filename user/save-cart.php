<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['cart'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid cart data']);
    exit;
}

try {
    $_SESSION['cart'] = $data['cart'];
    echo json_encode(['success' => true, 'message' => 'Cart saved successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}