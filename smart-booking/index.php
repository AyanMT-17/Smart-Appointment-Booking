<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/appointments.php';

$appointments = new Appointments();
$services = $appointments->getServices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Booking - Book Your Services Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Smart Booking</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="text-gray-700 hover:text-gray-900 px-3 py-2">
                            Dashboard
                        </a>
                        <a href="logout.php" class="ml-4 text-gray-700 hover:text-gray-900 px-3 py-2">
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-gray-900 px-3 py-2">
                            Login
                        </a>
                        <a href="register.php" class="ml-4 text-gray-700 hover:text-gray-900 px-3 py-2">
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-blue-600 text-white">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold sm:text-5xl md:text-6xl">
                    Book Your Services Online
                </h1>
                <p class="mt-6 text-xl max-w-2xl mx-auto">
                    Easy and convenient way to schedule appointments with our service providers
                </p>
                <?php if (!isLoggedIn()): ?>
                    <div class="mt-8 flex justify-center">
                        <a href="register.php" class="px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10">
                            Get Started
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <div class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">Our Services</h2>
                <p class="mt-4 text-lg text-gray-600">
                    Choose from our wide range of professional services
                </p>
            </div>

            <div class="mt-12 grid gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($services as $service): ?>
                    <div class="bg-gray-50 rounded-lg p-6 hover:shadow-md transition-shadow">
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
                            <a href="<?php echo isLoggedIn() ? 'booking-datetime.php?service=' . $service['service_id'] : 'login.php'; ?>" 
                               class="text-blue-600 hover:text-blue-700 font-medium">
                                Book Now →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">Why Choose Us</h2>
            </div>

            <div class="mt-12 grid gap-8 grid-cols-1 md:grid-cols-3">
                <div class="text-center">
                    <div class="mx-auto h-12 w-12 text-blue-600">
                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">24/7 Online Booking</h3>
                    <p class="mt-2 text-gray-500">
                        Book your appointments anytime, anywhere with our easy-to-use online system
                    </p>
                </div>

                <div class="text-center">
                    <div class="mx-auto h-12 w-12 text-blue-600">
                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Secure Booking</h3>
                    <p class="mt-2 text-gray-500">
                        Your data is protected with industry-standard security measures
                    </p>
                </div>

                <div class="text-center">
                    <div class="mx-auto h-12 w-12 text-blue-600">
                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Instant Confirmation</h3>
                    <p class="mt-2 text-gray-500">
                        Get immediate confirmation for your bookings with email notifications
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-gray-500">&copy; <?php echo date('Y'); ?> Smart Booking. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>