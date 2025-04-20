<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/appointments.php';

// Require login
redirectIfNotLoggedIn();

$appointments = new Appointments();
$services = $appointments->getServices();

// Get pre-selected service from URL if present
$selectedServiceId = isset($_GET['service']) ? (int)$_GET['service'] : null;

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
    $selectedServiceId = $appointmentDetails['service_id'];
}

// Store selected service in session when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'])) {
    $_SESSION['service_id'] = (int)$_POST['service_id'];
    if ($isReschedule) {
        header('Location: booking-datetime.php?reschedule=' . $_GET['reschedule']);
    } else {
        header('Location: booking-datetime.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - Smart Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .service-card {
            transition: all 0.3s ease;
        }
        .service-card.selected {
            @apply ring-2 ring-blue-500;
            transform: scale(1.02);
            background-color: #f0f7ff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .service-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Book a Service</h1>
                <p class="text-gray-600 mt-2">Select the service you'd like to book</p>
            </div>

            <!-- Progress Steps -->
            <div class="mb-12">
                <div class="flex items-center justify-center mb-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-medium bg-blue-600 text-white">
                            1
                        </div>
                        <div class="h-1 w-16 mx-2 bg-gray-200"></div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-medium bg-gray-200 text-gray-500">
                            2
                        </div>
                        <div class="h-1 w-16 mx-2 bg-gray-200"></div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-medium bg-gray-200 text-gray-500">
                            3
                        </div>
                    </div>
                </div>
                <div class="flex justify-center text-sm text-gray-500">
                    <div class="w-24 text-center">Select Service</div>
                    <div class="w-24 text-center">Choose Date & Time</div>
                    <div class="w-24 text-center">Confirm Details</div>
                </div>
            </div>

            <!-- Service Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($services as $service): ?>
                    <div class="service-card bg-white p-6 rounded-lg shadow-sm border cursor-pointer hover:shadow-md transition-shadow"
                         data-service='<?php echo json_encode($service); ?>'>
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($service['name']); ?>
                        </h3>
                        <?php if (isset($service['description'])): ?>
                            <p class="mt-2 text-gray-600">
                                <?php echo htmlspecialchars($service['description']); ?>
                            </p>
                        <?php endif; ?>
                        <div class="mt-4 flex items-center justify-between">
                            <div>
                                <span class="text-gray-500"><?php echo $service['duration']; ?> min</span>
                                <span class="mx-2">•</span>
                                <span class="font-medium">$<?php echo number_format($service['price'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Navigation -->
            <form method="POST" class="mt-8 flex justify-between">
                <input type="hidden" name="service_id" id="selected-service-id">
                <a href="dashboard.php" 
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                
                <button type="submit" id="continue-btn" disabled
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 opacity-50 cursor-not-allowed">
                    Continue
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceCards = document.querySelectorAll('.service-card');
            const continueBtn = document.getElementById('continue-btn');
            const serviceIdInput = document.getElementById('selected-service-id');
            let selectedService = null;

            // Check if there's a pre-selected service (from reschedule or URL)
            const preSelectedServiceId = <?php echo json_encode($selectedServiceId); ?>;
            if (preSelectedServiceId) {
                const preSelectedCard = document.querySelector(`.service-card[data-service*='"service_id":${preSelectedServiceId}']`);
                if (preSelectedCard) {
                    selectService(preSelectedCard);
                }
            }

            function selectService(card) {
                // Remove selection from all cards
                serviceCards.forEach(c => c.classList.remove('selected'));
                
                // Add selection to clicked card
                card.classList.add('selected');
                
                // Enable continue button and update form input
                continueBtn.disabled = false;
                continueBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                
                try {
                    // Store selected service
                    selectedService = JSON.parse(card.dataset.service);
                    serviceIdInput.value = selectedService.service_id;
                    
                    // Also store in sessionStorage for client-side use
                    sessionStorage.setItem('selectedService', JSON.stringify(selectedService));
                } catch (e) {
                    console.error('Error parsing service data:', e);
                }
            }

            // Add click handlers to service cards
            serviceCards.forEach(card => {
                card.addEventListener('click', () => {
                    selectService(card);
                });
            });
        });
    </script>
</body>
</html>