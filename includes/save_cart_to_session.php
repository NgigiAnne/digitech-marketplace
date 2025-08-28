<?php
// C:\xampp\htdocs\Digitech\includes\save_cart_to_session.php
session_start();
header('Content-Type: application/json'); // Ensure the response is JSON

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the JSON input from the request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Check if 'cart' data is present and is an array
    if (isset($data['cart']) && is_array($data['cart'])) {
        $_SESSION['cart'] = $data['cart']; // Store the cart array in the session
        $response = ['success' => true, 'message' => 'Cart saved to session.'];
    } else {
        $response['message'] = 'Cart data missing or invalid in request.';
    }
} else {
    $response['message'] = 'Only POST requests are allowed.';
}

echo json_encode($response); // Send the JSON response back to the client
exit();
?>