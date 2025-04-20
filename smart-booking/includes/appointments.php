<?php
require_once 'config.php';

class Appointments {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getServices() {
        $stmt = $this->conn->prepare("SELECT * FROM services WHERE status = 'active'");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProviderAvailability($providerId, $date) {
        $dayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)
        
        // First check for date-specific availability
        $stmt = $this->conn->prepare(
            "SELECT start_time, end_time 
             FROM availability 
             WHERE provider_id = ? AND specific_date = ?"
        );
        $stmt->bind_param("is", $providerId, $date);
        $stmt->execute();
        $dateSpecific = $stmt->get_result()->fetch_assoc();
        
        // If no date-specific availability, check regular weekly availability
        if (!$dateSpecific) {
            $stmt = $this->conn->prepare(
                "SELECT start_time, end_time 
                 FROM availability 
                 WHERE provider_id = ? AND day_of_week = ?"
            );
            $stmt->bind_param("ii", $providerId, $dayOfWeek);
            $stmt->execute();
            $availability = $stmt->get_result()->fetch_assoc();
            
            if (!$availability) {
                return []; // No availability set for this day
            }
        } else {
            $availability = $dateSpecific;
        }

        // Get booked appointments for the date
        $stmt = $this->conn->prepare(
            "SELECT start_time, end_time 
             FROM appointments 
             WHERE provider_id = ? AND date = ? AND status != 'cancelled'"
        );
        $stmt->bind_param("is", $providerId, $date);
        $stmt->execute();
        $bookedSlots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Generate available time slots
        return $this->generateTimeSlots($availability, $bookedSlots);
    }

    public function createAppointment($clientId, $providerId, $serviceId, $date, $startTime) {
        // Validate the date is not in the past
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            return [
                'success' => false,
                'message' => 'Cannot book appointments in the past'
            ];
        }

        // Get service duration and validate service exists
        $stmt = $this->conn->prepare("SELECT duration FROM services WHERE service_id = ? AND status = 'active'");
        $stmt->bind_param("i", $serviceId);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();
        
        if (!$service) {
            return [
                'success' => false,
                'message' => 'Invalid service selected'
            ];
        }
        
        // Calculate end time
        $endTime = date('H:i:s', strtotime($startTime . ' + ' . $service['duration'] . ' minutes'));
        
        // Verify the provider offers this service
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM provider_services 
             WHERE provider_id = ? AND service_id = ?"
        );
        $stmt->bind_param("ii", $providerId, $serviceId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Selected provider does not offer this service'
            ];
        }
        
        // Check if the slot is available
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM appointments 
             WHERE provider_id = ? AND date = ? 
             AND ((start_time <= ? AND end_time > ?) 
                  OR (start_time < ? AND end_time >= ?))
             AND status != 'cancelled'"
        );
        $stmt->bind_param("isssss", 
            $providerId, 
            $date, 
            $startTime, 
            $startTime, 
            $endTime, 
            $endTime
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Selected time slot is not available'
            ];
        }
        
        // Create appointment
        $stmt = $this->conn->prepare(
            "INSERT INTO appointments (client_id, provider_id, service_id, date, start_time, end_time) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iissss", $clientId, $providerId, $serviceId, $date, $startTime, $endTime);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'appointment_id' => $this->conn->insert_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to create appointment'
        ];
    }

    public function createBasicAppointment($clientId, $providerId, $date, $startTime) {
        // Validate the date is not in the past
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            return [
                'success' => false,
                'message' => 'Cannot book appointments in the past'
            ];
        }

        // Calculate end time (30 minute default duration)
        $endTime = date('H:i:s', strtotime($startTime . ' + 30 minutes'));
        
        // Check if the slot is available
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM appointments 
             WHERE provider_id = ? AND date = ? 
             AND ((start_time <= ? AND end_time > ?) 
                  OR (start_time < ? AND end_time >= ?))
             AND status != 'cancelled'"
        );
        $stmt->bind_param("isssss", 
            $providerId, 
            $date, 
            $startTime, 
            $startTime, 
            $endTime, 
            $endTime
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Selected time slot is not available'
            ];
        }
        
        // Create appointment without service_id
        $stmt = $this->conn->prepare(
            "INSERT INTO appointments (client_id, provider_id, date, start_time, end_time) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $clientId, $providerId, $date, $startTime, $endTime);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'appointment_id' => $this->conn->insert_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to create appointment'
        ];
    }

    public function getAppointmentDetails($appointmentId) {
        $stmt = $this->conn->prepare(
            "SELECT a.*, 
                    s.name as service_name, s.duration, s.price,
                    CONCAT(p.first_name, ' ', p.last_name) as provider_name,
                    CONCAT(c.first_name, ' ', c.last_name) as client_name
             FROM appointments a
             LEFT JOIN services s ON a.service_id = s.service_id
             JOIN users p ON a.provider_id = p.user_id
             JOIN users c ON a.client_id = c.user_id
             WHERE a.appointment_id = ?"
        );
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserAppointments($userId, $userType) {
        $field = ($userType === 'provider') ? 'provider_id' : 'client_id';
        
        $stmt = $this->conn->prepare(
            "SELECT a.*, 
                    s.name as service_name, s.duration, s.price,
                    CONCAT(p.first_name, ' ', p.last_name) as provider_name,
                    CONCAT(c.first_name, ' ', c.last_name) as client_name
             FROM appointments a
             LEFT JOIN services s ON a.service_id = s.service_id
             JOIN users p ON a.provider_id = p.user_id
             JOIN users c ON a.client_id = c.user_id
             WHERE a.$field = ?
             ORDER BY a.date DESC, a.start_time DESC"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function cancelAppointment($appointmentId, $userId) {
        // Check if appointment exists and is not already cancelled
        $stmt = $this->conn->prepare(
            "SELECT status FROM appointments 
             WHERE appointment_id = ? AND (client_id = ? OR provider_id = ?)"
        );
        $stmt->bind_param("iii", $appointmentId, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            return false;
        }

        if ($result['status'] === 'cancelled') {
            return true; // Already cancelled
        }

        $stmt = $this->conn->prepare(
            "UPDATE appointments 
             SET status = 'cancelled' 
             WHERE appointment_id = ? AND (client_id = ? OR provider_id = ?)"
        );
        $stmt->bind_param("iii", $appointmentId, $userId, $userId);
        return $stmt->execute();
    }

    public function rescheduleAppointment($appointmentId, $userId, $newDate, $newTime) {
        // First verify the appointment belongs to this user and is not cancelled
        $stmt = $this->conn->prepare(
            "SELECT service_id, client_id, provider_id, status 
             FROM appointments 
             WHERE appointment_id = ? AND (client_id = ? OR provider_id = ?)"
        );
        $stmt->bind_param("iii", $appointmentId, $userId, $userId);
        $stmt->execute();
        $appointment = $stmt->get_result()->fetch_assoc();

        if (!$appointment) {
            return [
                'success' => false,
                'message' => 'Appointment not found or access denied'
            ];
        }

        if ($appointment['status'] === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Cannot reschedule a cancelled appointment'
            ];
        }

        // Validate the new date is not in the past
        if (strtotime($newDate) < strtotime(date('Y-m-d'))) {
            return [
                'success' => false,
                'message' => 'Cannot reschedule to a past date'
            ];
        }

        // Calculate new end time based on service_id or default duration
        if ($appointment['service_id']) {
            $stmt = $this->conn->prepare("SELECT duration FROM services WHERE service_id = ?");
            $stmt->bind_param("i", $appointment['service_id']);
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
            $duration = $service['duration'];
        } else {
            $duration = 30; // Default duration for basic appointments
        }
        
        $endTime = date('H:i:s', strtotime($newTime . ' + ' . $duration . ' minutes'));
        
        // Check if the new slot is available
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as count FROM appointments 
             WHERE provider_id = ? AND date = ? 
             AND ((start_time <= ? AND end_time > ?) 
                  OR (start_time < ? AND end_time >= ?))
             AND status != 'cancelled'
             AND appointment_id != ?"
        );
        $stmt->bind_param("isssssi", 
            $appointment['provider_id'], 
            $newDate, 
            $newTime, 
            $newTime, 
            $endTime, 
            $endTime,
            $appointmentId
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Selected time slot is not available'
            ];
        }
        
        // Update the appointment
        $stmt = $this->conn->prepare(
            "UPDATE appointments 
             SET date = ?, start_time = ?, end_time = ? 
             WHERE appointment_id = ?"
        );
        $stmt->bind_param("sssi", $newDate, $newTime, $endTime, $appointmentId);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Appointment rescheduled successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to reschedule appointment'
        ];
    }
    
    private function generateTimeSlots($availability, $bookedSlots) {
        $slots = [];
        $interval = 30; // 30-minute intervals
        
        $start = strtotime($availability['start_time']);
        $end = strtotime($availability['end_time']);
        
        for ($time = $start; $time < $end; $time += ($interval * 60)) {
            $slotStart = date('H:i:s', $time);
            $slotEnd = date('H:i:s', $time + ($interval * 60));
            
            // Check if slot conflicts with any booked appointments
            $isAvailable = true;
            foreach ($bookedSlots as $booked) {
                if ($slotStart < $booked['end_time'] && $slotEnd > $booked['start_time']) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                $slots[] = $slotStart;
            }
        }
        
        return $slots;
    }
}