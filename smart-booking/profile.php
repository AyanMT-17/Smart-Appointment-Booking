<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Require login
redirectIfNotLoggedIn();

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if ($firstName && $lastName) {
        // Update basic info
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $firstName, $lastName, $phone, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = "$firstName $lastName";
            $success = "Profile updated successfully";
        } else {
            $error = "Failed to update profile";
        }
    }
    
    // Handle password change if requested
    if ($currentPassword && $newPassword) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($currentPassword, $result['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if ($stmt->execute()) {
                $success = "Password updated successfully";
            } else {
                $error = "Failed to update password";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone, user_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Smart Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white border-r">
            <div class="p-6">
                <h1 class="text-xl font-bold text-gray-900">Smart Booking</h1>
                <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            </div>
            
            <nav class="mt-6">
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <?php if ($user['user_type'] === 'provider'): ?>
                    <a href="availability.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                        <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Manage Availability
                    </a>
                <?php endif; ?>
                <a href="profile.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profile
                </a>
                <a href="logout.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto p-8">
            <div class="max-w-3xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>
                    <p class="text-gray-600 mt-1">Manage your account details and preferences</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                        <p class="text-green-700"><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <!-- Profile Information -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-8">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold mb-4">Basic Information</h2>
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        First Name
                                    </label>
                                    <input type="text" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                           required 
                                           class="w-full p-2 border rounded-md">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Last Name
                                    </label>
                                    <input type="text" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                           required 
                                           class="w-full p-2 border rounded-md">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Email
                                </label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       disabled 
                                       class="w-full p-2 border rounded-md bg-gray-50">
                                <p class="mt-1 text-sm text-gray-500">
                                    Contact support to change your email address
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone Number
                                </label>
                                <input type="tel" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                       class="w-full p-2 border rounded-md">
                            </div>

                            <div>
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold mb-4">Change Password</h2>
                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Current Password
                                </label>
                                <input type="password" name="current_password" required 
                                       class="w-full p-2 border rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    New Password
                                </label>
                                <input type="password" name="new_password" required 
                                       minlength="8"
                                       class="w-full p-2 border rounded-md">
                                <p class="mt-1 text-sm text-gray-500">
                                    Password must be at least 8 characters long
                                </p>
                            </div>

                            <div>
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const passwordForm = this.querySelector('input[name="current_password"]') !== null;
                
                if (passwordForm) {
                    const newPassword = this.querySelector('input[name="new_password"]').value;
                    if (newPassword.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long');
                    }
                }
            });
        });
    </script>
</body>
</html>