<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/appointments.php';

// Require login
redirectIfNotLoggedIn();

$appointments = new Appointments();
$userType = getUserType();
$userId = $_SESSION['user_id'];

// Get user's appointments
$userAppointments = $appointments->getUserAppointments($userId, $userType);

// For providers, get their services
$providerServices = [];
if ($userType === 'provider') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT s.* 
        FROM services s
        JOIN provider_services ps ON s.service_id = ps.service_id
        WHERE ps.provider_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $providerServices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Booking</title>
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
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <?php if ($userType === 'client'): ?>
                    <a href="booking-datetime.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                        <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Book Appointment
                    </a>
                <?php endif; ?>
                <?php if ($userType === 'provider'): ?>
                    <a href="availability.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                        <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Manage Availability
                    </a>
                <?php endif; ?>
                <a href="profile.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
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
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <?php echo $userType === 'provider' ? 'Appointments Overview' : 'My Appointments'; ?>
                    </h1>
                    
                    <?php if ($userType === 'client'): ?>
                        <a href="booking.php" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Book New Appointment
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($userType === 'provider' && !empty($providerServices)): ?>
                    <!-- Provider Services Section -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold mb-4">Your Services</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php foreach ($providerServices as $service): ?>
                                <div class="bg-white p-4 rounded-lg shadow-sm border">
                                    <h3 class="font-medium"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo $service['duration']; ?> min</p>
                                    <p class="text-sm font-medium mt-2">$<?php echo number_format($service['price'], 2); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Appointments Table -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                <?php if ($userType === 'provider'): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <?php else: ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($userAppointments)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No appointments found.
                                        <?php if ($userType === 'client'): ?>
                                            <a href="booking-datetime.php" class="text-blue-600 hover:underline">Book one now</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($userAppointments as $apt): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <?php echo date('M j, Y', strtotime($apt['date'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php echo date('g:i A', strtotime($apt['start_time'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                if (isset($apt['service_name'])) {
                                                    echo htmlspecialchars($apt['service_name']);
                                                } else {
                                                    echo 'Basic Appointment (30 min)';
                                                }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                echo htmlspecialchars($userType === 'provider' ? 
                                                    $apt['client_name'] : $apt['provider_name']); 
                                            ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                <?php
                                                    switch ($apt['status']) {
                                                        case 'scheduled':
                                                            echo 'bg-blue-100 text-blue-800';
                                                            break;
                                                        case 'completed':
                                                            echo 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'bg-red-100 text-red-800';
                                                            break;
                                                    }
                                                ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($apt['status'] === 'scheduled'): ?>
                                                <button onclick="cancelAppointment(<?php echo $apt['appointment_id']; ?>)"
                                                        class="text-red-600 hover:text-red-900 mr-3">
                                                    Cancel
                                                </button>
                                                <button onclick="window.location.href='booking-datetime.php?reschedule=<?php echo $apt['appointment_id']; ?>'"
                                                        class="text-blue-600 hover:text-blue-900">
                                                    Reschedule
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                        location.reload();
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