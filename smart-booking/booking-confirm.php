<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/appointments.php';

// Require login
redirectIfNotLoggedIn();

$appointments = new Appointments();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = $_SESSION['user_id'];
    $providerId = filter_input(INPUT_POST, 'provider_id', FILTER_VALIDATE_INT);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $time = filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING);

    if (!$providerId || !$date || !$time) {
        $error = "Invalid booking details";
    } else {
        // Create appointment with default duration of 30 minutes
        $result = $appointments->createBasicAppointment($clientId, $providerId, $date, $time);
        if ($result['success']) {
            header("Location: confirmation.php?id=" . $result['appointment_id']);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Get provider details
$conn = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - Smart Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-white text-gray-900">
    <div class="container mx-auto max-w-3xl py-8 px-4">
        <h1 class="text-3xl font-bold mb-8 text-center">Confirm Your Booking</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Booking Summary -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-semibold mb-6">Booking Summary</h2>
            <div id="booking-summary" class="space-y-4">
                <!-- Will be populated by JavaScript -->
                <p class="text-gray-500">Loading booking details...</p>
            </div>
        </div>

        <!-- Confirmation Form -->
        <form id="confirm-form" method="POST" class="space-y-6">
            <input type="hidden" name="provider_id" id="provider-id">
            <input type="hidden" name="date" id="booking-date">
            <input type="hidden" name="time" id="booking-time">
            
            <div class="flex items-start mb-6">
                <div class="flex items-center h-5">
                    <input type="checkbox" id="terms" name="terms" required
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                </div>
                <label for="terms" class="ml-2 text-sm text-gray-600">
                    I agree to the terms and conditions and confirm that all provided information is correct.
                </label>
            </div>

            <div class="flex justify-between">
                <a href="booking-datetime.php" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Back
                </a>
                
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Confirm Booking
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookingDetails = JSON.parse(sessionStorage.getItem('bookingDetails'));
            if (!bookingDetails) {
                window.location.href = 'booking-datetime.php';
                return;
            }

            // Populate hidden form fields
            document.getElementById('provider-id').value = bookingDetails.provider_id;
            document.getElementById('booking-date').value = bookingDetails.date;
            document.getElementById('booking-time').value = bookingDetails.time;

            // Format date for display
            const formattedDate = new Date(bookingDetails.date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Format time for display
            const formattedTime = new Date(`2000-01-01T${bookingDetails.time}`).toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit'
            });

            // Display booking summary
            document.getElementById('booking-summary').innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-medium">${formattedDate}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Time</p>
                        <p class="font-medium">${formattedTime}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Duration</p>
                        <p class="font-medium">30 minutes</p>
                    </div>
                </div>
            `;
        });
    </script>
</body>
</html>