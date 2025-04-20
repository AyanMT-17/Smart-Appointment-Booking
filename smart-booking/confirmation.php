<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/appointments.php';

// Require login
redirectIfNotLoggedIn();

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header('Location: booking.php');
    exit;
}

$appointments = new Appointments();
$appointmentDetails = $appointments->getAppointmentDetails($_GET['id']);

// Verify this appointment belongs to the logged-in user
if (!$appointmentDetails || 
    ($appointmentDetails['client_id'] != $_SESSION['user_id'] && 
     $appointmentDetails['provider_id'] != $_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Generate a confirmation code
$confirmationCode = strtoupper(substr(md5($appointmentDetails['appointment_id']), 0, 8));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Smart Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto py-12 px-4 max-w-3xl">
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <!-- Success Icon -->
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold mb-2">Booking Confirmed!</h1>
            <p class="text-gray-600 mb-8">Your appointment has been successfully scheduled</p>
            
            <!-- Appointment Details -->
            <div class="bg-gray-50 p-6 rounded-lg text-left mb-8">
                <h2 class="text-lg font-semibold mb-4">Appointment Details</h2>
                
                <div class="space-y-4">
                    <?php if (isset($appointmentDetails['service_name'])): ?>
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($appointmentDetails['service_name']); ?></p>
                                <p class="text-gray-600"><?php echo $appointmentDetails['duration']; ?> min • 
                                    $<?php echo number_format($appointmentDetails['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="font-medium">Basic Appointment</p>
                                <p class="text-gray-600">30 minutes</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div>
                            <p class="font-medium">
                                <?php echo date('l, F j, Y', strtotime($appointmentDetails['date'])); ?>
                            </p>
                            <p class="text-gray-600">
                                <?php 
                                    echo date('g:i A', strtotime($appointmentDetails['start_time'])) . ' - ' . 
                                         date('g:i A', strtotime($appointmentDetails['end_time'])); 
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <div>
                            <p class="font-medium">Provider</p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($appointmentDetails['provider_name']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Confirmation ID -->
            <div class="mb-8 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600">Confirmation ID</p>
                <p class="font-mono text-lg font-bold"><?php echo $confirmationCode; ?></p>
            </div>
            
            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="dashboard.php" 
                   class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-center">
                    View in Dashboard
                </a>
                <a href="index.php" 
                   class="px-6 py-3 bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-center">
                    Back to Home
                </a>
            </div>

            <!-- Appointment Management -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <div class="flex justify-center gap-4">
                    <button onclick="cancelAppointment(<?php echo $appointmentDetails['appointment_id']; ?>)"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Cancel Appointment
                    </button>
                    <button onclick="window.location.href='booking-datetime.php?reschedule=<?php echo $appointmentDetails['appointment_id']; ?>'"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Reschedule
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                fetch('api/appointments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'cancel',
                        appointment_id: appointmentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Appointment cancelled successfully');
                        window.location.href = 'dashboard.php';
                    } else {
                        alert('Failed to cancel appointment: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred while cancelling the appointment');
                });
            }
        }
    </script>
</body>
</html>