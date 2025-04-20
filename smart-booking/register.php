<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $userType = filter_input(INPUT_POST, 'user_type', FILTER_SANITIZE_STRING);

    // Basic validation
    if (!$firstName || !$lastName || !$email || !$password || !$userType) {
        $error = "All fields are required";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        $auth = new Auth();
        $result = $auth->register($firstName, $lastName, $email, $password, $userType, $phone);
        
        if ($result['success']) {
            // Auto-login after registration
            $auth->login($email, $password);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Smart Booking</h1>
                <p class="text-gray-600">Create your account</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                First Name
                            </label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Last Name
                            </label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email
                        </label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <input type="password" id="password" name="password" required minlength="8"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500">Must be at least 8 characters long</p>
                    </div>

                    <div>
                        <label for="user_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Account Type
                        </label>
                        <select id="user_type" name="user_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select account type</option>
                            <option value="client" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'client') ? 'selected' : ''; ?>>
                                Client
                            </option>
                            <option value="provider" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'provider') ? 'selected' : ''; ?>>
                                Service Provider
                            </option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Create Account
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="text-gray-600">Already have an account? 
                            <a href="login.php" class="text-blue-600 hover:underline">Login</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
            }
        });
    </script>
</body>
</html>