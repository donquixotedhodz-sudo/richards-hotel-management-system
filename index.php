<?php
// Richards Hotel Management System - Landing Page
session_start();
require_once 'config/database.php';

// Fetch room types with their rates from database
try {
    $stmt = $conn->prepare("
        SELECT rt.id, rt.type_name, rt.description, 
               MIN(br.price) as min_price, MAX(br.price) as max_price
        FROM room_types rt 
        LEFT JOIN booking_rates br ON rt.id = br.room_type_id 
        GROUP BY rt.id, rt.type_name, rt.description
        ORDER BY rt.id
    ");
    $stmt->execute();
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $room_types = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richards Hotel - Luxury Accommodation</title>
     <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/logo/logo.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <img src="images/logo/logo.png" alt="Richards Hotel" height="40" class="me-2">
                Richards Hotel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home"><i class="fas fa-home me-2"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#rooms"><i class="fas fa-bed me-2"></i>Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services"><i class="fas fa-concierge-bell me-2"></i>Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about"><i class="fas fa-info-circle me-2"></i>About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact"><i class="fas fa-envelope me-2"></i>Contact</a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])): ?>
                            <div class="dropdown">
                                <a class="btn btn-outline-light ms-2 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? $_SESSION['email'] ?? 'User'); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo $_SESSION['user_type'] === 'admin' ? 'admin/dashboard.php' : 'customer/dashboard.php'; ?>">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="centralized-login/controller/LoginController.php?action=logout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a class="btn btn-primary ms-2" href="centralized-login/login.php"><i class="fas fa-sign-in-alt me-2"></i>Log In</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Notification Container -->
        <div id="notification-container" class="position-fixed" style="top: 80px; right: 20px; z-index: 9999; display: none;">
            <div class="alert alert-success alert-dismissible fade show shadow" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <span id="notification-message">Booking successful!</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-overlay">
            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-8 mx-auto text-center text-white">
                        <div class="mb-4 animate-fade-in">
                            <img src="images/logo/logo.png" alt="Richards Hotel Logo" height="80" class="mb-3">
                        </div>
                        <h1 class="display-3 fw-bold mb-4 animate-fade-in">Welcome to <span class="brand-highlight">Richards Hotel</span></h1>
                        <p class="lead mb-5 animate-fade-in-delay">Experience luxury and comfort in the heart of the city. Your perfect getaway awaits.</p>
                        <div class="animate-fade-in-delay-2">
                            <!-- <a href="#booking" class="btn btn-primary btn-lg me-3">Book Your Stay</a>
                            <a href="#rooms" class="btn btn-outline-light btn-lg">Explore Rooms</a> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Rooms Section -->
    <section id="rooms" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold">Our Rooms</h2>
                    <p class="lead">Choose from our selection of beautifully appointed rooms and suites</p>
                </div>
            </div>
            <div class="row g-4">
                <?php if (!empty($room_types)): ?>
                    <?php foreach ($room_types as $index => $room): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="room-image-placeholder bg-secondary" style="height: 250px; background: linear-gradient(45deg, #6c757d, #495057);"></div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($room['type_name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($room['description'] ?? 'Comfortable accommodation with quality amenities.'); ?></p>
                                    <div class="text-center mb-3">
                                        <div class="price-section">
                                            <?php if ($room['min_price']): ?>
                                                <div class="price-label">Starting from</div>
                                                <?php if ($room['min_price'] == $room['max_price']): ?>
                                                    <span class="room-price">₱<?php echo number_format($room['min_price'], 2); ?></span>
                                                    <div class="price-label mt-1">per night</div>
                                                <?php else: ?>
                                                    <span class="room-price">₱<?php echo number_format($room['min_price'], 2); ?></span>
                                                    <div class="price-label mt-1">per night</div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="price-label">Pricing</div>
                                                <span class="room-price">Contact for rates</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-primary" onclick="openBookingModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['type_name']); ?>')">
                                            Book Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="lead">No rooms available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold">Our Services</h2>
                    <p class="lead">Experience world-class amenities and services</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="service-icon mb-3">
                            <i class="fas fa-wifi fa-3x"></i>
                        </div>
                        <h5>Free WiFi</h5>
                        <p>High-speed internet access throughout the hotel</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="service-icon mb-3">
                            <i class="fas fa-swimming-pool fa-3x"></i>
                        </div>
                        <h5>Swimming Pool</h5>
                        <p>Outdoor pool with stunning city views</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="service-icon mb-3">
                            <i class="fas fa-dumbbell fa-3x"></i>
                        </div>
                        <h5>Fitness Center</h5>
                        <p>State-of-the-art gym equipment available 24/7</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="text-center">
                        <div class="service-icon mb-3">
                            <i class="fas fa-utensils fa-3x"></i>
                        </div>
                        <h5>Restaurant</h5>
                        <p>Fine dining with international cuisine</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">About Richards Hotel</h2>
                    <p class="lead mb-4">For over two decades, Richards Hotel has been synonymous with luxury, comfort, and exceptional service.</p>
                    <p>Located in the heart of the city, we offer our guests unparalleled access to business districts, shopping centers, and cultural attractions. Our commitment to excellence ensures that every stay is memorable.</p>
                    <div class="row mt-4">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-primary">150+</h3>
                                <p>Luxury Rooms</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-primary">20+</h3>
                                <p>Years Experience</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image-placeholder bg-secondary rounded" style="height: 400px; background: linear-gradient(45deg, #6c757d, #495057);"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="display-5 fw-bold">Contact Us</h2>
                    <p class="lead">Get in touch with us for reservations and inquiries</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="text-center">
                        <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                        <h5>Address</h5>
                        <p>123 Hotel Street<br>City Center, State 12345</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="text-center">
                        <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                        <h5>Phone</h5>
                        <p>+1 (555) 123-4567<br>+1 (555) 987-6543</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="text-center">
                        <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                        <h5>Email</h5>
                        <p>info@richardshotel.com<br>reservations@richardshotel.com</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">
                        <i class="fas fa-hotel me-2"></i>Book Your Room
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bookingForm" action="bookings/controller/BookingController.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body py-3">
                        <div class="row g-2">
                            <!-- Customer Information -->
                            <div class="col-12">
                                <h6 class="text-danger mb-2"><i class="fas fa-user me-2"></i>Customer Information</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="customer_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                       value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="customer_email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                       value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="customer_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                       value="<?php echo isset($_SESSION['user_phone']) ? htmlspecialchars($_SESSION['user_phone']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="room_type_id" class="form-label">Room Type *</label>
                                <select class="form-select" id="room_type_id" name="room_type_id" required>
                                    <option value="">Select Room Type</option>
                                    <?php foreach ($room_types as $room_type): ?>
                                        <option value="<?php echo $room_type['id']; ?>">
                                            <?php echo htmlspecialchars($room_type['type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="customer_address" class="form-label">Address *</label>
                                <textarea class="form-control" id="customer_address" name="customer_address" rows="2" required></textarea>
                            </div>
                            
                            <!-- Booking Details -->
                            <div class="col-12 mt-3">
                                <h6 class="text-danger mb-2"><i class="fas fa-calendar-alt me-2"></i>Booking Details</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="check_in_datetime" class="form-label">Check-in Date & Time *</label>
                                <div class="input-group">
                                    <input type="datetime-local" class="form-control" id="check_in_datetime" name="check_in_datetime" required>
                                   
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="duration_hours" class="form-label">Duration (Hours) *</label>
                                <select class="form-select" id="duration_hours" name="duration_hours" required>
                                    <option value="">Select Duration</option>
                                    <option value="3">3 Hours</option>
                                    <option value="12">12 Hours</option>
                                    <option value="24">24 Hours (1 Day)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="check_out_display" class="form-label">Check-out Time</label>
                                <input type="text" class="form-control" id="check_out_display" readonly placeholder="Auto-calculated">
                            </div>
                            <div class="col-md-6">
                                <label for="guests" class="form-label">Number of Guests</label>
                                <select class="form-select" id="guests" name="guests">
                                    <option value="1">1 Guest</option>
                                    <option value="2">2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests</option>
                                    <option value="5">5 Guests</option>
                                    <option value="6">6 Guests</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="special_requests" class="form-label">Special Requests</label>
                                <textarea class="form-control" id="special_requests" name="special_requests" rows="3" placeholder="Any special requests or requirements..."></textarea>
                            </div>
                            
                            <!-- Payment Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-danger mb-3"><i class="fas fa-credit-card me-2"></i>Payment Information</h6>
                                <div class="alert alert-warning" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Booking Fee Notice:</strong> A booking fee of <span id="booking-fee-amount">₱150</span> will be sent to this <span class="text-primary fw-bold">GCash</span> number <span class="text-danger fw-bold">09958714112</span>.
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="proof_of_payment" class="form-label">Proof of Payment</label>
                                <input type="file" class="form-control" id="proof_of_payment" name="proof_of_payment" accept="image/*,.pdf">
                                <div class="form-text">Upload your payment receipt (JPG, PNG, PDF)</div>
                            </div>
                            
                            <!-- Price Display -->
                            <div class="col-12 mt-3">
                                <div class="alert alert-light border" role="alert">
                                    <h6 class="mb-2"><i class="fas fa-calculator me-2"></i>Booking Summary</h6>
                                    <div id="price_display" class="text-muted">
                                        Select room type and duration to see price
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check me-2"></i>Submit Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <p>&copy; <?php echo date('Y'); ?> Richards Hotel. All rights reserved.</p>
                </div>
                <div class="col-lg-6 text-end">
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/booking.js"></script>
</body>
</html>