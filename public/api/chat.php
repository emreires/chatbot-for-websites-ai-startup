<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\ChatbotController;

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['website_id']) || !isset($input['visitor_id']) || !isset($input['message'])) {
        throw new Exception('Missing required parameters');
    }

    // Initialize chatbot controller
    $chatbot = new ChatbotController();

    // Handle message
    $response = $chatbot->handleMessage(
        $input['website_id'],
        $input['visitor_id'],
        $input['message']
    );

    // Return response
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 