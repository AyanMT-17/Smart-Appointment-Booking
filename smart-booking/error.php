<?php
$errorMessage = $_GET['message'] ?? 'An unexpected error occurred';
$returnUrl = $_GET['return'] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Smart Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <svg class="mx-auto h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Oops! Something went wrong</h1>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($errorMessage); ?></p>
            
            <div class="space-y-4">
                <a href="<?php echo htmlspecialchars($returnUrl); ?>" 
                   class="block w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Go Back
                </a>
                
                <a href="index.php" class="block w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                    Return to Home
                </a>

                <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
                    <button onclick="window.history.back()" 
                            class="w-full px-4 py-2 text-gray-600 hover:text-gray-900">
                        ← Previous Page
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
        <script>
            // Add browser back button support
            window.addEventListener('keydown', function(e) {
                if ((e.key === 'Backspace' || e.key === 'Delete') && !e.target.matches('input, textarea')) {
                    window.history.back();
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>