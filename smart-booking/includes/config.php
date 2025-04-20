<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smart_booking');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        header("Location: error.php?message=" . urlencode("Database connection failed"));
        exit();
    }
    
    return $conn;
}

// Utility functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirectIfNotProvider() {
    if (!isLoggedIn() || getUserType() !== 'provider') {
        header("Location: login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isLoggedIn() || getUserType() !== 'admin') {
        header("Location: login.php");
        exit();
    }
}

// Clean and validate input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}