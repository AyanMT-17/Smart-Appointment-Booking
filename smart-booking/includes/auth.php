<?php
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function register($firstName, $lastName, $email, $password, $userType, $phone = null) {
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $this->conn->prepare(
            "INSERT INTO users (first_name, last_name, email, password, phone, user_type) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $phone, $userType);
        
        if ($stmt->execute()) {
            $userId = $this->conn->insert_id;
            
            // If registering as provider, set up default availability and services
            if ($userType === 'provider') {
                $this->setupDefaultAvailability($userId);
                $this->setupDefaultServices($userId);
            }
            
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare(
            "SELECT user_id, first_name, last_name, email, password, user_type 
             FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                return ['success' => true, 'user_type' => $user['user_type']];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    }

    private function setupDefaultAvailability($providerId) {
        // Set default availability for weekdays 9 AM to 5 PM
        $stmt = $this->conn->prepare(
            "INSERT INTO availability (provider_id, day_of_week, start_time, end_time) 
             VALUES (?, ?, ?, ?)"
        );

        $startTime = '09:00:00';
        $endTime = '17:00:00';

        // Add availability for Monday through Friday (1-5)
        for ($day = 1; $day <= 5; $day++) {
            $stmt->bind_param("iiss", $providerId, $day, $startTime, $endTime);
            $stmt->execute();
        }
    }

    private function setupDefaultServices($providerId) {
        // Get all active services
        $stmt = $this->conn->prepare("SELECT service_id FROM services WHERE status = 'active'");
        $stmt->execute();
        $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Assign all services to the provider
        $stmt = $this->conn->prepare("INSERT INTO provider_services (provider_id, service_id) VALUES (?, ?)");
        foreach ($services as $service) {
            $stmt->bind_param("ii", $providerId, $service['service_id']);
            $stmt->execute();
        }
    }
}