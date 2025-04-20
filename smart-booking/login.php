<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$auth = new Auth();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Check if we have a return URL
$returnUrl = isset($_GET['return']) ? $_GET['return'] : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Booking</title>
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
                <p class="text-gray-600">Sign in to your account</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
                    
                    <div>
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Sign In
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Don't have an account? 
                        <a href="register.php" class="text-blue-600 hover:underline">Register</a>
                    </p>
                    <a href="index.php" class="text-gray-600 hover:text-gray-900 text-sm mt-4 inline-block">
                        ← Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>