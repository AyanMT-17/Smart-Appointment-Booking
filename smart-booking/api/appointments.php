<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/appointments.php';

try {
    if (!isLoggedIn()) {
        throw new Exception('Unauthorized access');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request format');
    }

    $appointments = new Appointments();
    
    switch ($input['action']) {
        case 'cancel':
            if (!isset($input['appointment_id'])) {
                throw new Exception('Appointment ID required');
            }
            
            if ($appointments->cancelAppointment($input['appointment_id'], $_SESSION['user_id'])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Appointment cancelled successfully'
                ]);
            } else {
                throw new Exception('Failed to cancel appointment');
            }
            break;

        case 'reschedule':
            if (!isset($input['appointment_id']) || !isset($input['new_date']) || !isset($input['new_time'])) {
                throw new Exception('Missing required parameters for rescheduling');
            }
            
            $result = $appointments->rescheduleAppointment(
                $input['appointment_id'],
                $_SESSION['user_id'],
                $input['new_date'],
                $input['new_time']
            );
            
            echo json_encode($result);
            break;

        case 'get_details':
            if (!isset($input['appointment_id'])) {
                throw new Exception('Appointment ID required');
            }
            
            $details = $appointments->getAppointmentDetails($input['appointment_id']);
            if ($details) {
                echo json_encode([
                    'success' => true,
                    'appointment' => $details
                ]);
            } else {
                throw new Exception('Appointment not found');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}