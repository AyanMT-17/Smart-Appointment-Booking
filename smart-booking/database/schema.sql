-- Create database
CREATE DATABASE IF NOT EXISTS smart_booking;
USE smart_booking;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('client', 'provider', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- Duration in minutes
    price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Provider services mapping
CREATE TABLE IF NOT EXISTS provider_services (
    provider_service_id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT,
    service_id INT,
    FOREIGN KEY (provider_id) REFERENCES users(user_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);

-- Provider availability
CREATE TABLE IF NOT EXISTS availability (
    availability_id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT,
    day_of_week INT NOT NULL, -- 1 = Monday, 7 = Sunday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (provider_id) REFERENCES users(user_id)
);

-- Date-specific availability
CREATE TABLE IF NOT EXISTS date_specific_availability (
    date_availability_id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT,
    specific_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT true,
    FOREIGN KEY (provider_id) REFERENCES users(user_id)
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    provider_id INT,
    service_id INT,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(user_id),
    FOREIGN KEY (provider_id) REFERENCES users(user_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);

-- Insert sample services
INSERT INTO services (name, description, duration, price) VALUES
('Consultation', 'Initial consultation session', 30, 50.00),
('Full Service', 'Comprehensive service package', 60, 100.00),
('Express Service', 'Quick service for minor issues', 15, 25.00),
('Premium Service', 'Premium service with extended consultation', 90, 150.00);