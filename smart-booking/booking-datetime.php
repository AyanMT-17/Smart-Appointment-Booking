<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/appointments.php';

// Require login
redirectIfNotLoggedIn();

$appointments = new Appointments();

// Get all providers
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT DISTINCT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as name
    FROM users u
    WHERE u.user_type = 'provider'
    ORDER BY name
");

$stmt->execute();
$providers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if this is a reschedule
$isReschedule = isset($_GET['reschedule']);
if ($isReschedule) {
    $appointmentDetails = $appointments->getAppointmentDetails($_GET['reschedule']);
    if (!$appointmentDetails || 
        ($appointmentDetails['client_id'] != $_SESSION['user_id'] && 
         $appointmentDetails['provider_id'] != $_SESSION['user_id'])) {
        header('Location: error.php?message=' . urlencode('Invalid appointment'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Smart Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .time-slot.selected { @apply bg-blue-500 text-white; }
        .time-slot:not(.selected):hover { @apply bg-blue-50; }
    </style>
</head>
<body class="bg-white text-gray-900">
    <div class="container mx-auto max-w-6xl py-8 px-4">
        <h1 class="text-3xl font-bold mb-8 text-center">Book Your Appointment</h1>

        <!-- Provider Selection -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Select Provider</h2>
            <?php if (empty($providers)): ?>
                <p class="text-red-500 mb-2">No providers available</p>
            <?php endif; ?>
            <select id="provider-select" class="w-full p-2 border rounded-md" <?php echo empty($providers) ? 'disabled' : ''; ?>>
                <option value="">Choose a provider...</option>
                <?php foreach ($providers as $provider): ?>
                    <option value="<?php echo htmlspecialchars($provider['user_id']); ?>">
                        <?php echo htmlspecialchars($provider['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Calendar and Time Slots -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Calendar -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Select Date</h2>
                <input type="date" id="date-picker" class="w-full p-2 border rounded-md" 
                       min="<?php echo date('Y-m-d'); ?>" 
                       max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
            </div>

            <!-- Time Slots -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Select Time</h2>
                <div id="time-slots" class="grid grid-cols-3 gap-2">
                    <p class="col-span-3 text-gray-500">Please select a date first.</p>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex justify-between">
            <a href="dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Back
            </a>
            
            <button id="continue-btn" disabled
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 opacity-50 cursor-not-allowed">
                Continue
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const providerSelect = document.getElementById('provider-select');
            const datePicker = document.getElementById('date-picker');
            const timeSlots = document.getElementById('time-slots');
            const continueBtn = document.getElementById('continue-btn');
            let selectedTime = null;
            
            async function loadAvailableSlots() {
                if (!providerSelect.value && !datePicker.value) {
                    timeSlots.innerHTML = '<p class="col-span-3 text-gray-500">Please select both a provider and a date.</p>';
                    return;
                }
                if (!providerSelect.value) {
                    timeSlots.innerHTML = '<p class="col-span-3 text-gray-500">Please select a provider first.</p>';
                    return;
                }
                if (!datePicker.value) {
                    timeSlots.innerHTML = '<p class="col-span-3 text-gray-500">Please select a date.</p>';
                    return;
                }

                const formData = new FormData();
                formData.append('provider_id', providerSelect.value);
                formData.append('date', datePicker.value);

                try {
                    const response = await fetch('api/availability.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        renderTimeSlots(data.slots);
                    } else {
                        timeSlots.innerHTML = `<p class="col-span-3 text-red-500">${data.message}</p>`;
                    }
                } catch (error) {
                    timeSlots.innerHTML = '<p class="col-span-3 text-red-500">Failed to load available times.</p>';
                }
            }

            function renderTimeSlots(slots) {
                if (!slots || slots.length === 0) {
                    timeSlots.innerHTML = '<p class="col-span-3 text-gray-500">No available slots for this date.</p>';
                    return;
                }

                timeSlots.innerHTML = slots.map(time => `
                    <button class="time-slot p-2 border rounded-md hover:bg-blue-50" data-time="${time}">
                        ${formatTime(time)}
                    </button>
                `).join('');

                // Add click handlers to time slots
                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.addEventListener('click', () => {
                        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                        slot.classList.add('selected');
                        selectedTime = slot.dataset.time;
                        continueBtn.disabled = false;
                        continueBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    });
                });
            }

            function formatTime(time) {
                return new Date(`2000-01-01T${time}`).toLocaleTimeString([], 
                    { hour: 'numeric', minute: '2-digit' });
            }

            // Event Listeners
            providerSelect.addEventListener('change', loadAvailableSlots);
            datePicker.addEventListener('change', loadAvailableSlots);

            continueBtn.addEventListener('click', () => {
                if (selectedTime && providerSelect.value && datePicker.value) {
                    const bookingDetails = {
                        provider_id: providerSelect.value,
                        date: datePicker.value,
                        time: selectedTime
                    };
                    sessionStorage.setItem('bookingDetails', JSON.stringify(bookingDetails));
                    window.location.href = 'booking-confirm.php';
                }
            });
        });
    </script>
</body>
</html>