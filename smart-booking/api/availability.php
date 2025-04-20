<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/appointments.php';

try {
    if (!isLoggedIn()) {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $providerId = filter_input(INPUT_POST, 'provider_id', FILTER_VALIDATE_INT);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);

    if (!$providerId || !$date) {
        throw new Exception('Missing required parameters');
    }

    // Validate date format
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        throw new Exception('Invalid date format');
    }

    // Don't allow booking in the past
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        throw new Exception('Cannot book appointments in the past');
    }

    $appointments = new Appointments();
    $availableSlots = $appointments->getProviderAvailability($providerId, $date);

    echo json_encode([
        'success' => true,
        'slots' => $availableSlots
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}