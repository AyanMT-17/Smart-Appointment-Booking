<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Require provider login
redirectIfNotProvider();

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $dayOfWeek = filter_input(INPUT_POST, 'day', FILTER_VALIDATE_INT);
                $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
                $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
                $specificDate = filter_input(INPUT_POST, 'specific_date', FILTER_SANITIZE_STRING);
                
                if ($specificDate) {
                    // Handle date-specific availability
                    $stmt = $conn->prepare(
                        "INSERT INTO availability (provider_id, specific_date, start_time, end_time) 
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param("isss", $userId, $specificDate, $startTime, $endTime);
                    
                    if ($stmt->execute()) {
                        $success = "Date-specific availability added successfully";
                    } else {
                        $error = "Failed to add date-specific availability";
                    }
                } else if ($dayOfWeek && $startTime && $endTime) {
                    // Handle regular weekly availability
                    $stmt = $conn->prepare(
                        "INSERT INTO availability (provider_id, day_of_week, start_time, end_time) 
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param("iiss", $userId, $dayOfWeek, $startTime, $endTime);
                    
                    if ($stmt->execute()) {
                        $success = "Availability added successfully";
                    } else {
                        $error = "Failed to add availability";
                    }
                }
                break;

            case 'delete':
                $availabilityId = filter_input(INPUT_POST, 'availability_id', FILTER_VALIDATE_INT);
                $dateAvailabilityId = filter_input(INPUT_POST, 'date_availability_id', FILTER_VALIDATE_INT);
                
                if ($availabilityId) {
                    $stmt = $conn->prepare(
                        "DELETE FROM availability 
                         WHERE availability_id = ? AND provider_id = ?"
                    );
                    $stmt->bind_param("ii", $availabilityId, $userId);
                    
                    if ($stmt->execute()) {
                        $success = "Availability removed successfully";
                    } else {
                        $error = "Failed to remove availability";
                    }
                } else if ($dateAvailabilityId) {
                    $stmt = $conn->prepare(
                        "DELETE FROM availability 
                         WHERE date_availability_id = ? AND provider_id = ?"
                    );
                    $stmt->bind_param("ii", $dateAvailabilityId, $userId);
                    
                    if ($stmt->execute()) {
                        $success = "Date-specific availability removed successfully";
                    } else {
                        $error = "Failed to remove date-specific availability";
                    }
                }
                break;
        }
    }
}

// Get current availability
$stmt = $conn->prepare(
    "SELECT * FROM availability 
     WHERE provider_id = ? 
     ORDER BY day_of_week, start_time"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$availability = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get date-specific availability
$stmt = $conn->prepare(
    "SELECT * FROM availability 
     WHERE provider_id = ? AND specific_date >= CURDATE()
     ORDER BY specific_date, start_time"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$dateSpecificAvailability = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group availability by day
$availabilityByDay = [];
foreach ($availability as $slot) {
    $availabilityByDay[$slot['day_of_week']][] = $slot;
}

$days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - Smart Booking</title>
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
                <a href="availability.php" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Manage Availability
                </a>
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
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">Manage Your Availability</h1>
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

                <!-- Add New Availability -->
                <div class="bg-white p-6 rounded-lg shadow-sm border mb-8">
                    <h2 class="text-lg font-semibold mb-4">Add New Availability</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                                <select name="day" class="w-full p-2 border rounded-md">
                                    <option value="">Select Day</option>
                                    <?php foreach ($days as $num => $day): ?>
                                        <option value="<?php echo $num; ?>"><?php echo $day; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Specific Date</label>
                                <input type="date" name="specific_date" class="w-full p-2 border rounded-md"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                                <input type="time" name="start_time" required class="w-full p-2 border rounded-md">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                <input type="time" name="end_time" required class="w-full p-2 border rounded-md">
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Add Availability
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Current Availability -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-8">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold mb-4">Current Weekly Availability</h2>
                        
                        <?php if (empty($availability)): ?>
                            <p class="text-gray-500">No weekly availability slots set up yet.</p>
                        <?php else: ?>
                            <?php foreach ($days as $dayNum => $dayName): ?>
                                <?php if (isset($availabilityByDay[$dayNum])): ?>
                                    <div class="mb-6">
                                        <h3 class="text-md font-medium mb-2"><?php echo $dayName; ?></h3>
                                        <div class="space-y-2">
                                            <?php foreach ($availabilityByDay[$dayNum] as $slot): ?>
                                                <div class="flex items-center justify-between bg-gray-50 p-3 rounded-md">
                                                    <span>
                                                        <?php 
                                                            echo date('g:i A', strtotime($slot['start_time'])) . ' - ' . 
                                                                 date('g:i A', strtotime($slot['end_time']));
                                                        ?>
                                                    </span>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="availability_id" 
                                                               value="<?php echo $slot['availability_id']; ?>">
                                                        <button type="submit" 
                                                                class="text-red-600 hover:text-red-900"
                                                                onclick="return confirm('Are you sure you want to remove this availability slot?')">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Date-Specific Availability -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold mb-4">Date-Specific Availability</h2>
                        
                        <?php if (empty($dateSpecificAvailability)): ?>
                            <p class="text-gray-500">No date-specific availability slots set up yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($dateSpecificAvailability as $slot): ?>
                                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded-md">
                                        <span>
                                            <?php 
                                                echo date('F j, Y', strtotime($slot['specific_date'])) . ': ' . 
                                                     date('g:i A', strtotime($slot['start_time'])) . ' - ' . 
                                                     date('g:i A', strtotime($slot['end_time']));
                                            ?>
                                        </span>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <!-- <input type="hidden" name="date_availability_id" 
                                                   value="<?php echo $slot['date_availability_id']; ?>"> -->
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Are you sure you want to remove this date-specific availability slot?')">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = document.querySelector('input[name="start_time"]').value;
            const endTime = document.querySelector('input[name="end_time"]').value;
            const daySelect = document.querySelector('select[name="day"]');
            const specificDate = document.querySelector('input[name="specific_date"]');
            
            // Validate time range
            if (startTime >= endTime) {
                e.preventDefault();
                alert('End time must be later than start time');
                return;
            }

            // Validate day/date selection
            if ((daySelect.value && specificDate.value) || (!daySelect.value && !specificDate.value)) {
                e.preventDefault();
                alert('Please select either a day of the week OR a specific date, but not both');
                return;
            }

            // Validate specific date is not in the past
            if (specificDate.value) {
                const selectedDate = new Date(specificDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    e.preventDefault();
                    alert('Cannot set availability for past dates');
                    return;
                }
            }
        });

        // Add mutual exclusivity to day and date selection
        document.querySelector('select[name="day"]').addEventListener('change', function() {
            if (this.value) {
                document.querySelector('input[name="specific_date"]').value = '';
            }
        });

        document.querySelector('input[name="specific_date"]').addEventListener('change', function() {
            if (this.value) {
                document.querySelector('select[name="day"]').value = '';
            }
        });
    </script>
</body>
</html>