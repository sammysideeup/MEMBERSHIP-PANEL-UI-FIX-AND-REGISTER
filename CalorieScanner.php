<?php
// CalorieScanner.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: Loginpage.php");
    exit();
}

// Process log request from scanner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_from_scanner'])) {
    include 'connection.php';
    
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $food_description = filter_input(INPUT_POST, 'food_description', FILTER_SANITIZE_STRING);
    $calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_FLOAT);
    $meal_type = filter_input(INPUT_POST, 'meal_type', FILTER_SANITIZE_STRING) ?? 'Snack';
    
    if ($user_id && $food_description && $calories && $calories > 0) {
        $log_date = date("Y-m-d");
        
        $insert_sql = "INSERT INTO dietary_logs (user_id, log_date, meal_type, description, calories) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        if ($insert_stmt) {
            $insert_stmt->bind_param("isssd", $user_id, $log_date, $meal_type, $food_description, $calories);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success_message'] = "Food '{$food_description}' ({$calories} kcal) logged successfully!";
            } else {
                $_SESSION['error_message'] = 'Database Error: Could not log food.';
            }
            $insert_stmt->close();
        } else {
            $_SESSION['error_message'] = 'Error preparing log query.';
        }
    } else {
        $_SESSION['error_message'] = 'Invalid data received from scanner.';
    }
    
    // Redirect back to scanner
    header("Location: CalorieScanner.php");
    exit();
}

// Get current user ID for JavaScript
$email = $_SESSION['email'];
include 'connection.php';
$user_sql = "SELECT id FROM users WHERE email = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$user_result = $user_stmt->get_result()->fetch_assoc();
$current_user_id = $user_result['id'] ?? null;
$user_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Calorie Scanner</title>
  
  <link rel="stylesheet" href="Memberstyle.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <script src="https://cdn.tailwindcss.com"></script> 
  
  <style>
    /* Additional animation for card entrance */
/* Additional animation for card entrance */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-container {
    animation: fadeInUp 0.6s ease forwards;
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Disabled form styling */
.disabled-form {
    opacity: 0.6;
    pointer-events: none;
}

/* Style for submenu transition */
.submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}
.toggle-icon {
    transition: transform 0.3s;
}

/* ===== CALORIE SCANNER SPECIFIC STYLES ===== */

.scanner-card {
    background-color: #ffffff;
    border: 2px solid #e5e5e5;
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    width: 100%;
}

.scanner-card:hover {
    border-color: #FFD700;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

/* ===== UPDATED CAMERA STYLES - LARGER & RESPONSIVE ===== */

.camera-container {
    position: relative;
    width: 100%;
    max-width: 800px; /* Optimal desktop width */
    margin: 0 auto;
    background-color: #000;
    border-radius: 1.25rem;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

video {
    width: 100%;
    height: auto;
    min-height: 500px; /* Balanced desktop height */
    border-radius: 1rem;
    border: 3px solid #FFD700;
    box-shadow: 0 0 25px rgba(255, 215, 0, 0.4);
    background-color: #000;
    object-fit: cover;
    display: block;
    transition: all 0.3s ease;
}

/* Smooth camera size transitions */
video.resizing {
    transition: min-height 0.3s ease, border-width 0.3s ease;
}

/* Camera overlay adjustments */
.camera-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.6);
    border-radius: 1rem;
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    display: none;
    z-index: 10;
}

.camera-overlay .text-center {
    text-align: center;
    padding: 1.5rem;
}

.camera-overlay i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

/* Scan button styling */
#scanBtn {
    background-color: #000000;
    color: #ffffff;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border: none;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin: 1.5rem auto;
    min-width: 200px;
}

#scanBtn:hover {
    background-color: #333333;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

#scanBtn:active {
    transform: translateY(0);
}

/* Result Display */
#resultBox {
    background-color: #ffffff;
    border: 2px solid #e5e5e5;
    border-radius: 1rem;
    padding: 1.5rem;
    width: 100%;
    max-width: 800px;
    margin: 1.5rem auto;
    text-align: left;
    display: none;
    animation: fadeInUp 0.5s ease forwards;
}

#resultBox b {
    color: #000000;
    font-size: 1.1rem;
}

/* Log button styling */
#logToTrackerBtn {
    background-color: #000000;
    color: #ffffff;
    padding: 0.875rem 1.75rem;
    font-size: 1rem;
    font-weight: 600;
    border: none;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: none;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin: 1rem auto;
}

#logToTrackerBtn:hover:not(:disabled) {
    background-color: #333333;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

#logToTrackerBtn:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Meal type selector */
.meal-type-selector {
    background-color: #ffffff;
    border: 2px solid #e5e5e5;
    border-radius: 1rem;
    padding: 1.25rem;
    width: 100%;
    max-width: 800px;
    margin: 1rem auto;
    display: none;
}

.meal-type-selector label {
    display: block;
    margin-bottom: 0.75rem;
    color: #000000;
    font-weight: 600;
    font-size: 1rem;
}

.meal-type-selector select {
    background-color: #ffffff;
    color: #000000;
    border: 2px solid #e5e5e5;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    width: 100%;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.meal-type-selector select:focus {
    outline: none;
    border-color: #FFD700;
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
}

/* Message styling */
.message {
    background-color: #ffffff;
    border: 2px solid #e5e5e5;
    border-radius: 1rem;
    padding: 1.25rem;
    width: 100%;
    max-width: 800px;
    margin: 1rem auto;
    text-align: center;
    font-weight: 600;
    display: none;
    animation: fadeInUp 0.5s ease forwards;
}

.success-message {
    border-color: #10B981;
    background-color: rgba(16, 185, 129, 0.1);
    color: #065f46;
}

.error-message {
    border-color: #EF4444;
    background-color: rgba(239, 68, 68, 0.1);
    color: #7f1d1d;
}

.info-message {
    border-color: #3B82F6;
    background-color: rgba(59, 130, 246, 0.1);
    color: #1e40af;
}

/* Scanner status indicator */
.scanner-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-top: 1rem;
    color: #666666;
    font-size: 0.9rem;
    background-color: #F9FAFB;
    padding: 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid #E5E7EB;
    max-width: 800px;
    margin: 1rem auto;
}

.scanner-status i {
    color: #FFD700;
}

/* Adjust main container */
.card-container {
    animation: fadeInUp 0.6s ease forwards;
    max-width: 900px;
    margin: 0 auto;
}

/* ===== RESPONSIVE BREAKPOINTS ===== */

/* Mobile responsiveness - BALANCED VERTICAL HEIGHT */
@media (max-width: 768px) {
    .main-content {
        margin-top: 80px;
        padding: 1rem;
    }
    
    .scanner-card {
        padding: 1.25rem;
        margin: 0.75rem 0;
        border-radius: 1rem;
    }
    
    .camera-container {
        max-width: 100%;
        border-radius: 1rem;
        margin: 0.75rem 0;
    }
    
    video {
        min-height: 280px; /* BALANCED HEIGHT - not too tall */
        max-height: 320px; /* Prevents excessive height */
        border-radius: 0.75rem;
        border-width: 2px;
        object-fit: cover;
    }
    
    #scanBtn {
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
        width: 100%;
        max-width: 280px;
        margin: 1.25rem auto;
    }
    
    #resultBox,
    .meal-type-selector,
    .message {
        padding: 1.25rem;
        margin: 1rem 0;
        max-width: 100%;
    }
    
    #logToTrackerBtn {
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
        width: 100%;
        max-width: 280px;
        margin: 1rem auto;
    }
    
    .scanner-status {
        padding: 0.625rem;
        font-size: 0.85rem;
        margin: 0.875rem 0;
    }
    
    /* Camera overlay adjustments for mobile */
    .camera-overlay {
        font-size: 1rem;
        border-radius: 0.75rem;
    }
    
    .camera-overlay i {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    
    /* Toast notification for mobile */
    .toast-wrapper {
        width: 90%;
        max-width: 350px;
        right: 5%;
        top: 100px;
    }
    
    /* Extra small screens */
    @media (max-width: 480px) {
        video {
            min-height: 250px; /* Even more balanced for small phones */
            max-height: 300px;
        }
        
        .camera-container {
            border-radius: 0.75rem;
        }
        
        .scanner-card {
            padding: 1rem;
        }
        
        #scanBtn, #logToTrackerBtn {
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            max-width: 260px;
        }
    }
}

/* Portrait orientation on mobile - balanced */
@media (max-width: 768px) and (orientation: portrait) {
    video {
        min-height: 300px;
        max-height: 350px;
    }
}

/* Landscape orientation on mobile */
@media (max-height: 600px) and (orientation: landscape) {
    video {
        min-height: 250px;
        max-height: 280px;
    }
    
    .camera-container {
        max-width: 90%;
    }
    
    #scanBtn, #logToTrackerBtn {
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
    }
}

/* Tablet responsiveness */
@media (min-width: 769px) and (max-width: 1024px) {
    .main-content {
        padding: 1.5rem;
    }
    
    .scanner-card {
        padding: 1.75rem;
    }
    
    .camera-container {
        max-width: 90%;
    }
    
    video {
        min-height: 400px;
    }
    
    #scanBtn {
        padding: 1rem 1.75rem;
        font-size: 1.05rem;
        max-width: 300px;
    }
    
    .scanner-status {
        font-size: 0.95rem;
        padding: 0.875rem;
    }
}

/* Desktop large screens */
@media (min-width: 1025px) and (max-width: 1440px) {
    .camera-container {
        max-width: 850px;
    }
    
    video {
        min-height: 550px;
    }
    
    .scanner-card {
        padding: 2.25rem;
    }
    
    #scanBtn {
        padding: 1.1rem 2.25rem;
        font-size: 1.15rem;
        max-width: 320px;
    }
}

/* Extra large screens */
@media (min-width: 1441px) {
    .camera-container {
        max-width: 900px;
    }
    
    video {
        min-height: 600px;
    }
    
    .scanner-card {
        padding: 2.5rem;
    }
    
    #scanBtn {
        padding: 1.25rem 2.5rem;
        font-size: 1.2rem;
        max-width: 350px;
    }
    
    .scanner-status {
        font-size: 1rem;
        padding: 0.875rem;
    }
}

/* Loading animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.scanning {
    animation: pulse 1.5s ease-in-out infinite;
}

/* Camera permission warning */
.permission-warning {
    background-color: #FEF3C7;
    border: 2px solid #F59E0B;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    text-align: center;
    color: #92400E;
}

.permission-warning i {
    color: #F59E0B;
    margin-right: 0.5rem;
}

/* Updated button colors to match palette */
button.bg-indigo-500, 
button.bg-green-500,
button.bg-blue-600 {
    background-color: #000000 !important;
    color: #ffffff !important;
}

button.bg-indigo-500:hover,
button.bg-green-500:hover,
button.bg-blue-600:hover {
    background-color: #333333 !important;
}

/* Yellow accent buttons */
button.bg-yellow-600 {
    background-color: #FFD700 !important;
    color: #000000 !important;
}

button.bg-yellow-600:hover {
    background-color: #e6c200 !important;
}

/* Toast notification */
.toast-wrapper{
    position: fixed;
    top: 100px;
    right: 20px;
    width: 380px;
    z-index: 1001;
    opacity: 0;
    transform: translateX(100%);
    transition: opacity 0.5s, transform 0.5s;
}

.toast-wrapper.show{
    opacity: 1;
    transform: translateX(0);
}

.toast{
    width: 100%;
    height: 80px;
    padding: 20px;
    background-color: #ffffff;
    border-radius: 7px;
    display: grid;
    grid-template-columns: 1.3fr 6fr 0.5fr;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08)
}

.success{
    border-left: 8px solid #47D764;
}

.success i{
    color:#47D764;
}

.error {
    border-left: 8px solid #FF5050;
}

.error i {
    color: #FF5050;
}

.container-1, .container-2{
    align-self: center;
}

.container-1 i{
    font-size: 35px;
}

.container-2 p:first-child {
    color: #101020;
    font-weight: 600;
    font-size: 16px;
}

.container-2 p:last-child {
    font-size: 12px;
    font-weight: 400;
    color: #656565;
}

.toast button {
    align-self: flex-start;
    background-color: transparent;
    font-size: 25px;
    color: #656565;
    line-height: 0;
    cursor: pointer;
}
  </style>
</head>
<body class="min-h-screen">

<!-- Mobile Top Navbar -->
<nav class="mobile-navbar" id="mobileNavbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <i class='bx bx-scan text-yellow-500 text-2xl'></i>
            <h2>Calorie Scanner</h2>
        </div>
        <button class="navbar-toggle" id="navbarToggle">
            <i class='bx bx-menu'></i>
        </button>
    </div>
    <div class="navbar-menu" id="navbarMenu">
        <ul>
            <li><a href="Membership.php"><i class='bx bx-user'></i> User Details</a></li>
            <li><a href="WorkoutJournal.php"><i class='bx bx-notepad'></i> Workout Journal</a></li>
            <li><a href="Progress.php"><i class='bx bx-line-chart'></i> Progress</a></li>
            <li><a href="TrainerBooking.php"><i class='bx bxs-user-pin'></i> Trainers</a></li>
            <li class="more-menu-mobile">
                <a href="#" class="more-toggle-mobile">
                    <i class='bx bx-dots-horizontal-rounded'></i> More 
                    <i class='bx bx-chevron-down toggle-icon'></i>
                </a>
                <ul class="submenu" id="mobileSubmenu">
                    <li><a href="#" class="active"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                    <li><a href="gym_scanner_module.php"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
                </ul>
            </li>
            <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Desktop Sidebar -->
<div class="sidebar">
    <h2>Member Panel</h2>
    <ul>
        <li><a href="Membership.php"><i class='bx bx-user'></i> User Details</a></li>
        <li><a href="WorkoutJournal.php"><i class='bx bx-notepad'></i> Workout Journal</a></li>
        <li><a href="Progress.php"><i class='bx bx-line-chart'></i> Progress</a></li>
        <li><a href="TrainerBooking.php"><i class='bx bxs-user-pin'></i> Trainers</a></li>
        <li class="more-menu">
            <a href="#" class="more-toggle">
                 More 
                <i class='bx bx-chevron-down toggle-icon'></i>
            </a>
            <ul class="submenu" id="desktopSubmenu">
                <li><a href="#" class="bg-gray-700"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                <li><a href="gym_scanner_module.php"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
            </ul>
        </li>
        <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content Container -->
<div class="main-content">
    <div class="cards-container">
        <!-- Toast notification -->
        <div id="toast-notification" class="toast-wrapper">
            <div class="toast success">
                <div class="container-1">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="container-2">
                    <p>Success</p>
                    <p>Food logged successfully!</p>
                </div>
                <button onclick="document.getElementById('toast-notification').classList.remove('show')">&times;</button>
            </div>
        </div>
        
        <!-- Header -->
        <div class="header-container">
            <div class="header-title">
                <h1 class="main-heading">Calorie Scanner</h1>
                <div class="message-container">
                    <p class="text-center text-gray-600 mb-8">Scan your food to estimate calories using AI</p>
                </div>
            </div>
        </div>
        
        <!-- Scanner Card Container -->
        <div class="card-container w-full max-w-4xl mx-auto">
            <div class="scanner-card">
                <!-- Camera Permission Warning -->
                <div id="permissionWarning" class="permission-warning hidden">
                    <i class='bx bx-error-alt'></i>
                    <span>Camera access is required for scanning. Please allow camera permissions.</span>
                </div>
                
                <!-- Camera Feed -->
                <div class="camera-container">
                    <video id="videoFeed" autoplay playsinline></video>
                    <div id="cameraOverlay" class="camera-overlay">
                        <div class="text-center">
                            <i class='bx bx-camera text-4xl mb-2'></i>
                            <p>Position food in frame</p>
                        </div>
                    </div>
                </div>
                
                <!-- Scanner Status -->
                <div class="scanner-status" id="scannerStatus">
                    <i class='bx bx-video'></i>
                    <span>Camera ready for scanning</span>
                </div>
                
                <!-- Scan Button -->
                <button id="scanBtn">
                    <i class='bx bx-camera'></i>
                    Scan Food
                </button>
                
                <!-- Result Display -->
                <div id="resultBox"></div>
                
                <!-- Meal Type Selector -->
                <div class="meal-type-selector" id="mealTypeSelector">
                    <label for="mealType">Select Meal Type:</label>
                    <select id="mealType">
                        <option value="Breakfast">Breakfast</option>
                        <option value="Lunch">Lunch</option>
                        <option value="Dinner">Dinner</option>
                        <option value="Snack" selected>Snack</option>
                    </select>
                </div>
                
                <!-- Log Button -->
                <button id="logToTrackerBtn">
                    <i class='bx bx-calendar-plus'></i>
                    Log to Calorie Tracker
                </button>
                
                <!-- Message Display -->
                <div id="logMessage" class="message"></div>
            </div>
            
            <!-- Instructions Card -->
            <div class="scanner-card mt-6">
                <h3 class="text-xl font-bold mb-4 text-black flex items-center">
                    <i class='bx bx-info-circle text-yellow-600 mr-2'></i>
                    How to Use the Scanner
                </h3>
                <div class="space-y-3 text-gray-700">
                    <p class="flex items-start">
                        <i class='bx bx-check-circle text-yellow-600 mr-2 mt-1'></i>
                        <span>Ensure good lighting for better results</span>
                    </p>
                    <p class="flex items-start">
                        <i class='bx bx-check-circle text-yellow-600 mr-2 mt-1'></i>
                        <span>Position food clearly in the camera frame</span>
                    </p>
                    <p class="flex items-start">
                        <i class='bx bx-check-circle text-yellow-600 mr-2 mt-1'></i>
                        <span>Click "Scan Food" to capture and analyze</span>
                    </p>
                    <p class="flex items-start">
                        <i class='bx bx-check-circle text-yellow-600 mr-2 mt-1'></i>
                        <span>Review results and log to your calorie tracker</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for logging -->
<form id="logForm" method="POST" action="CalorieScanner.php" style="display: none;">
  <input type="hidden" name="log_from_scanner" value="1">
  <input type="hidden" name="user_id" id="logUserId" value="<?php echo htmlspecialchars($current_user_id); ?>">
  <input type="hidden" name="food_description" id="logFoodDescription">
  <input type="hidden" name="calories" id="logCalories">
  <input type="hidden" name="meal_type" id="logMealType">
</form>

<script type="module">
    // ====== MOBILE NAVIGATION ======
    document.addEventListener('DOMContentLoaded', function() {
        // Hamburger menu toggle
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarMenu = document.getElementById('navbarMenu');
        
        if (navbarToggle && navbarMenu) {
            navbarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                navbarMenu.classList.toggle('active');
                this.classList.toggle('active');
                
                const icon = this.querySelector('i');
                if (navbarMenu.classList.contains('active')) {
                    icon.classList.remove('bx-menu');
                    icon.classList.add('bx-x');
                } else {
                    icon.classList.remove('bx-x');
                    icon.classList.add('bx-menu');
                    closeSubmenu();
                }
            });
            
            document.addEventListener('click', function(event) {
                const isMobile = window.innerWidth <= 1024;
                const isClickInsideNavbar = navbarMenu.contains(event.target) || 
                                           navbarToggle.contains(event.target);
                
                if (isMobile && !isClickInsideNavbar && navbarMenu.classList.contains('active')) {
                    closeMenu();
                }
            });
            
            function closeMenu() {
                navbarMenu.classList.remove('active');
                navbarToggle.classList.remove('active');
                const icon = navbarToggle.querySelector('i');
                icon.classList.remove('bx-x');
                icon.classList.add('bx-menu');
                closeSubmenu();
            }
            
            const navLinks = document.querySelectorAll('.navbar-menu a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (!this.classList.contains('more-toggle-mobile')) {
                        navLinks.forEach(l => l.classList.remove('active'));
                        this.classList.add('active');
                        if (window.innerWidth <= 1024) {
                            closeMenu();
                        }
                    }
                });
            });
        }
        
        // ====== DESKTOP SIDEBAR SUBMENU ======
        const moreToggle = document.querySelector('.more-toggle');
        const desktopSubmenu = document.getElementById('desktopSubmenu');
        const desktopToggleIcon = document.querySelector('.more-menu .toggle-icon');

        if (moreToggle && desktopSubmenu && desktopToggleIcon) {
            moreToggle.addEventListener('click', function(e) {
                e.preventDefault(); 
                e.stopPropagation();
                
                if (desktopSubmenu.style.maxHeight === '0px' || desktopSubmenu.style.maxHeight === '') {
                    desktopSubmenu.style.maxHeight = desktopSubmenu.scrollHeight + 'px'; 
                    desktopToggleIcon.style.transform = 'rotate(180deg)';
                } else {
                    desktopSubmenu.style.maxHeight = '0px';
                    desktopToggleIcon.style.transform = 'rotate(0deg)';
                }
            });
        }
        
        // ====== MOBILE SUBMENU TOGGLE ======
        const moreToggleMobile = document.querySelector('.more-toggle-mobile');
        const mobileSubmenu = document.getElementById('mobileSubmenu');
        const mobileToggleIcon = document.querySelector('.more-menu-mobile .toggle-icon');
        
        function closeSubmenu() {
            if (mobileSubmenu) {
                mobileSubmenu.style.maxHeight = '0px';
                mobileSubmenu.classList.remove('active');
            }
            if (mobileToggleIcon) {
                mobileToggleIcon.style.transform = 'rotate(0deg)';
            }
        }
        
        if (moreToggleMobile && mobileSubmenu && mobileToggleIcon) {
            moreToggleMobile.addEventListener('click', function(e) {
                e.preventDefault(); 
                e.stopPropagation();
                
                const isOpen = mobileSubmenu.style.maxHeight && mobileSubmenu.style.maxHeight !== '0px';
                
                if (isOpen) {
                    mobileSubmenu.style.maxHeight = '0px';
                    mobileToggleIcon.style.transform = 'rotate(0deg)';
                } else {
                    mobileSubmenu.style.maxHeight = mobileSubmenu.scrollHeight + 'px'; 
                    mobileToggleIcon.style.transform = 'rotate(180deg)';
                }
            });
        }
    });

    // ====== CALORIE SCANNER LOGIC ======
    const video = document.getElementById('videoFeed');
    const scanBtn = document.getElementById('scanBtn');
    const resultBox = document.getElementById('resultBox');
    const logBtn = document.getElementById('logToTrackerBtn');
    const mealTypeSelector = document.getElementById('mealTypeSelector');
    const mealTypeSelect = document.getElementById('mealType');
    const logMessage = document.getElementById('logMessage');
    const scannerStatus = document.getElementById('scannerStatus');
    const permissionWarning = document.getElementById('permissionWarning');
    const cameraOverlay = document.getElementById('cameraOverlay');
    
    // Hidden form elements
    const logForm = document.getElementById('logForm');
    const logUserId = document.getElementById('logUserId');
    const logFoodDescription = document.getElementById('logFoodDescription');
    const logCalories = document.getElementById('logCalories');
    const logMealType = document.getElementById('logMealType');

    // Variables to store parsed food data
    let currentFoodData = null;
    let cameraStream = null;
    
    // ===== CAMERA RESPONSIVENESS VARIABLES =====
    let resizeTimeout;
    let isResizing = false;

    // ====== CAMERA RESPONSIVENESS FUNCTIONS ======

    // Enhanced resize handler
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        
        if (!isResizing) {
            isResizing = true;
            showCameraOverlay(true, 'Adjusting camera...');
            updateScannerStatus('Adjusting camera for new size...');
            
            // Add resizing class to video for smooth transition
            video.classList.add('resizing');
        }
        
        resizeTimeout = setTimeout(() => {
            handleResizeComplete();
        }, 200);
    });

    function handleResizeComplete() {
        isResizing = false;
        video.classList.remove('resizing');
        showCameraOverlay(false);
        
        // Update status based on camera state
        if (video.srcObject && video.srcObject.active) {
            updateScannerStatus('Camera ready for scanning');
            
            // Optimize camera constraints based on new size
            optimizeCameraForCurrentViewport();
        } else {
            updateScannerStatus('Camera adjusted - ready to scan');
        }
    }

    // Function to optimize camera based on viewport size
    function optimizeCameraForCurrentViewport() {
        if (!cameraStream) return;
        
        const tracks = cameraStream.getVideoTracks();
        if (tracks.length === 0) return;
        
        const track = tracks[0];
        
        // Get current viewport dimensions
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Update constraints based on viewport size
        let newConstraints = {};
        
        if (viewportWidth <= 768) {
            // Mobile constraints - optimized for balanced height
            if (viewportHeight <= 600) { // Landscape mobile
                newConstraints = {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    frameRate: { ideal: 30 }
                };
            } else { // Portrait mobile
                newConstraints = {
                    width: { ideal: 720 },
                    height: { ideal: 960 },
                    frameRate: { ideal: 30 }
                };
            }
        } else if (viewportWidth <= 1024) {
            // Tablet constraints
            newConstraints = {
                width: { ideal: 1280 },
                height: { ideal: 720 },
                frameRate: { ideal: 30 }
            };
        } else {
            // Desktop constraints
            newConstraints = {
                width: { ideal: 1920 },
                height: { ideal: 1080 },
                frameRate: { ideal: 60 }
            };
        }
        
        // Apply new constraints
        track.applyConstraints(newConstraints).then(() => {
            console.log('Camera constraints optimized');
        }).catch(err => {
            console.log('Could not apply camera constraints:', err);
        });
    }

    // Function to adjust video aspect ratio
    function adjustVideoAspectRatio() {
        if (!video.videoWidth || !video.videoHeight) return;
        
        const container = video.parentElement;
        const containerWidth = container.clientWidth;
        const containerHeight = container.clientHeight;
        
        const videoAspect = video.videoWidth / video.videoHeight;
        const containerAspect = containerWidth / containerHeight;
        
        if (videoAspect > containerAspect) {
            // Video is wider than container
            video.style.objectFit = 'cover';
            video.style.width = '100%';
            video.style.height = 'auto';
        } else {
            // Video is taller than container
            video.style.objectFit = 'contain';
            video.style.width = 'auto';
            video.style.height = '100%';
            video.style.margin = '0 auto';
        }
    }

    // Enhanced camera initialization with size optimization
    async function startCamera() {
        try {
            // Get viewport dimensions for optimal camera setup
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            
            let constraints = {
                video: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    frameRate: { ideal: 30 }
                }
            };
            
            // Mobile optimization
            if (/Mobi|Android/i.test(navigator.userAgent)) {
                const screenWidth = window.screen.width;
                const screenHeight = window.screen.height;
                
                // Balanced constraints for mobile
                if (screenWidth >= 414 && screenHeight >= 736) {
                    // Large phones
                    constraints.video = {
                        facingMode: "environment",
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        frameRate: { ideal: 30 }
                    };
                } else {
                    // Regular and small phones
                    constraints.video = {
                        facingMode: "environment",
                        width: { ideal: 720 },
                        height: { ideal: 960 },
                        frameRate: { ideal: 30 }
                    };
                }
                
                // Adjust for orientation
                if (window.innerHeight < window.innerWidth) {
                    // Landscape mode
                    constraints.video = {
                        facingMode: "environment",
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        frameRate: { ideal: 30 }
                    };
                }
            }
            
            // For desktop, use higher quality
            if (viewportWidth >= 1024) {
                constraints.video = {
                    facingMode: "environment",
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                    frameRate: { ideal: 60 }
                };
            }
            
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            cameraStream = stream;
            video.srcObject = stream;
            
            // Hide permission warning
            if (permissionWarning) {
                permissionWarning.classList.add('hidden');
            }
            
            // Wait for video to load and adjust size
            video.onloadedmetadata = () => {
                // Update scanner status
                const track = stream.getVideoTracks()[0];
                const settings = track.getSettings();
                updateScannerStatus(`Camera ready (${settings.width || '?'}×${settings.height || '?'})`);
                
                // Adjust video element aspect ratio
                adjustVideoAspectRatio();
            };
            
            // Show camera overlay briefly
            showCameraOverlay(true, 'Camera starting...');
            setTimeout(() => {
                showCameraOverlay(false);
            }, 1000);
            
            return true;
        } catch (err) {
            console.error("Camera error:", err);
            handleCameraError(err);
            return false;
        }
    }

    // Enhanced camera error handling
    function handleCameraError(error) {
        if (permissionWarning) {
            permissionWarning.classList.remove('hidden');
            permissionWarning.innerHTML = `
                <i class='bx bx-error-alt'></i>
                <span>Camera access is required. Please allow camera permissions.</span>
            `;
        }
        
        updateScannerStatus(`Camera error: ${error.name}`, true);
        
        // Disable scan button
        scanBtn.disabled = true;
        scanBtn.innerHTML = '<i class="bx bx-error-alt"></i> Camera Required';
        scanBtn.style.backgroundColor = '#9ca3af';
        
        // Create informative video placeholder
        video.style.backgroundColor = '#1f2937';
        video.style.display = 'flex';
        video.style.alignItems = 'center';
        video.style.justifyContent = 'center';
        video.style.minHeight = '280px';
        video.innerHTML = `
            <div class="text-center text-white p-4">
                <i class="bx bx-camera-off text-4xl mb-4"></i>
                <p class="text-lg font-bold mb-2">Camera Not Available</p>
                <p class="text-sm text-gray-300">Please check camera permissions</p>
            </div>
        `;
    }

    // Enhanced showCameraOverlay function
    function showCameraOverlay(show, message = 'Position food in frame') {
        if (cameraOverlay) {
            if (show) {
                cameraOverlay.innerHTML = `
                    <div class="text-center">
                        <i class='bx bx-camera text-3xl mb-2'></i>
                        <p class="text-lg">${message}</p>
                        ${isResizing ? '<p class="text-sm mt-1 text-gray-300">Adjusting...</p>' : ''}
                    </div>
                `;
                cameraOverlay.style.display = 'flex';
            } else {
                cameraOverlay.style.display = 'none';
            }
        }
    }

    // Enhanced updateScannerStatus function
    function updateScannerStatus(status, isError = false) {
        if (!scannerStatus) return;
        
        const icon = scannerStatus.querySelector('i');
        const text = scannerStatus.querySelector('span');
        
        if (isError) {
            scannerStatus.style.color = '#EF4444';
            scannerStatus.style.backgroundColor = '#FEF2F2';
            scannerStatus.style.border = '1px solid #FECACA';
            icon.style.color = '#EF4444';
        } else {
            scannerStatus.style.color = '#666666';
            scannerStatus.style.backgroundColor = '#F9FAFB';
            scannerStatus.style.border = '1px solid #E5E7EB';
            icon.style.color = '#FFD700';
        }
        
        text.textContent = status;
    }

    // Toast notification function
    function showToast(type, message) {
        const toastWrapper = document.getElementById('toast-notification');
        const toastElement = toastWrapper.querySelector('.toast');
        const iconElement = toastWrapper.querySelector('.container-1 i');
        const titleElement = toastWrapper.querySelector('.container-2 p:first-child');
        const messageElement = toastWrapper.querySelector('.container-2 p:last-child');

        toastElement.classList.remove('success', 'error');
        if (type === 'success') {
            toastElement.classList.add('success');
            iconElement.className = 'fas fa-check-circle'; 
            titleElement.textContent = 'Success';
        } else if (type === 'error') {
            toastElement.classList.add('error');
            iconElement.className = 'fas fa-times-circle'; 
            titleElement.textContent = 'Error';
        }

        messageElement.textContent = message;
        toastWrapper.classList.add('show');
        
        setTimeout(() => {
            toastWrapper.classList.remove('show');
        }, 4000); 
    }

    // Parse the AI response to extract food description and calories
    function parseAIResponse(text) {
        const foodData = {
            description: '',
            calories: 0,
            fullText: text
        };
        
        try {
            // Extract food description
            const descMatch = text.match(/Food Identification and Description\s*([^\n]+)/i);
            if (descMatch && descMatch[1]) {
                foodData.description = descMatch[1].trim();
            } else {
                const lines = text.split('\n').filter(line => line.trim().length > 0);
                if (lines.length > 0) {
                    foodData.description = lines[0].replace(/^#\s*/, '').trim();
                }
            }
            
            // Extract calories
            const calorieMatch = text.match(/Total Estimated Calories[^\n]*?(\d+(?:\.\d+)?)\s*(?:kcal)?/i);
            if (calorieMatch && calorieMatch[1]) {
                foodData.calories = parseFloat(calorieMatch[1]);
            } else {
                const altCalorieMatch = text.match(/(\d+(?:\.\d+)?)\s*(?:kcal|calories)/i);
                if (altCalorieMatch && altCalorieMatch[1]) {
                    foodData.calories = parseFloat(altCalorieMatch[1]);
                }
            }
            
            // If still no calories found
            if (foodData.calories === 0) {
                const numberMatch = text.match(/\b(\d{1,3}(?:,\d{3})*(?:\.\d+)?)\b/);
                if (numberMatch && numberMatch[1]) {
                    const possibleCalories = parseFloat(numberMatch[1].replace(',', ''));
                    if (possibleCalories > 0 && possibleCalories < 2000) {
                        foodData.calories = possibleCalories;
                    }
                }
            }
            
        } catch (error) {
            console.error('Error parsing AI response:', error);
        }
        
        return foodData;
    }

    // Show log button when food is scanned
    function showLogButton(foodData) {
        currentFoodData = foodData;
        
        if (foodData.description && foodData.calories > 0) {
            mealTypeSelector.style.display = 'block';
            logBtn.style.display = 'flex';
            logBtn.disabled = false;
            
            // Truncate long descriptions for mobile
            const maxLength = window.innerWidth <= 768 ? 25 : 40;
            const displayDescription = foodData.description.length > maxLength 
                ? foodData.description.substring(0, maxLength) + '...' 
                : foodData.description;
            
            logBtn.innerHTML = `<i class='bx bx-calendar-plus'></i> Log "${displayDescription}" (${foodData.calories} kcal)`;
            
            // Scroll to log button on mobile
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    logBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
        } else {
            mealTypeSelector.style.display = 'none';
            logBtn.style.display = 'none';
            currentFoodData = null;
        }
    }

    // Show message function
    function showMessage(text, type) {
        if (!logMessage) return;
        
        logMessage.textContent = text;
        logMessage.className = 'message';
        
        if (type === 'success') {
            logMessage.classList.add('success-message');
        } else if (type === 'error') {
            logMessage.classList.add('error-message');
        } else {
            logMessage.classList.add('info-message');
        }
        
        logMessage.style.display = 'block';
        
        // Auto-hide after 5 seconds for non-error messages
        if (type !== 'error') {
            setTimeout(() => {
                logMessage.style.display = 'none';
            }, 5000);
        }
        
        // Scroll to message on mobile
        if (window.innerWidth <= 768) {
            setTimeout(() => {
                logMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    }

    // Handle log button click
    logBtn.addEventListener('click', async () => {
        if (!currentFoodData || !currentFoodData.description || currentFoodData.calories <= 0) {
            showMessage('No valid food data to log.', 'error');
            return;
        }
        
        // Disable button while processing
        logBtn.disabled = true;
        logBtn.innerHTML = '<i class="bx bx-loader-circle bx-spin"></i> Logging...';
        
        // Set form values
        logFoodDescription.value = currentFoodData.description;
        logCalories.value = currentFoodData.calories;
        logMealType.value = mealTypeSelect.value;
        
        // Show loading message
        showMessage('Logging food to your calorie tracker...', 'info');
        
        try {
            // Submit the form
            const response = await fetch('CalorieScanner.php', {
                method: 'POST',
                body: new FormData(logForm)
            });
            
            const result = await response.text();
            
            if (response.ok) {
                showToast('success', 'Food logged successfully!');
                showMessage('Food logged successfully! Check your calorie tracker.', 'success');
                
                // Reset button after success
                setTimeout(() => {
                    logBtn.innerHTML = '<i class="bx bx-calendar-plus"></i> Log to Calorie Tracker';
                    logBtn.style.display = 'none';
                    mealTypeSelector.style.display = 'none';
                    logMessage.style.display = 'none';
                    currentFoodData = null;
                }, 3000);
            } else {
                throw new Error('Failed to log food');
            }
            
        } catch (error) {
            console.error('Error logging food:', error);
            showToast('error', 'Failed to log food');
            showMessage('Failed to log food. Please try again.', 'error');
            logBtn.disabled = false;
            logBtn.innerHTML = '<i class="bx bx-calendar-plus"></i> Log to Calorie Tracker';
        }
    });

    // On scan button click — capture image and send to Gemini
    scanBtn.addEventListener('click', async () => {
        // Add scanning animation
        scanBtn.classList.add('scanning');
        scanBtn.innerHTML = '<i class="bx bx-loader-circle bx-spin"></i> Scanning...';
        scanBtn.disabled = true;
        
        resultBox.style.display = "block";
        resultBox.innerHTML = `
            <div class="text-center py-6">
                <i class='bx bx-loader-circle bx-spin text-yellow-600 text-3xl mb-3'></i>
                <p class="text-gray-600 font-medium">Scanning food... please wait.</p>
                <p class="text-sm text-gray-500 mt-2">Analyzing image with AI</p>
            </div>
        `;
        
        // Hide previous log button and message
        mealTypeSelector.style.display = 'none';
        logBtn.style.display = 'none';
        logMessage.style.display = 'none';
        currentFoodData = null;
        
        // Show camera overlay during scan
        showCameraOverlay(true, 'Capturing image...');
        
        // Add brief delay to ensure frame is stable
        await new Promise(resolve => setTimeout(resolve, 500));

        // Capture image
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageBase64 = canvas.toDataURL('image/jpeg', 0.8);

        // Hide overlay
        showCameraOverlay(false);

        try {
            updateScannerStatus('Sending to AI for analysis...');
            
            const response = await fetch('gemini_calorie_estimator.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: imageBase64 })
            });

            if (!response.ok) {
                const errorDetails = await response.json().catch(() => ({ error: `Server responded with status ${response.status}` }));
                throw new Error(errorDetails.error || `HTTP Error ${response.status}: ${JSON.stringify(errorDetails.details)}`);
            }

            const result = await response.json();
            console.log(result);

            if (result.candidates && result.candidates[0].content) {
                const text = result.candidates[0].content.parts[0].text;
                const formattedText = text.replace(/\n/g, '<br>');
                resultBox.innerHTML = `
                    <div class="mb-4">
                        <h4 class="text-lg font-bold text-black mb-2 flex items-center">
                            <i class='bx bx-food-menu text-yellow-600 mr-2'></i>
                            Scan Results
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            ${formattedText}
                        </div>
                    </div>
                `;
                
                // Parse the response and show log button
                const foodData = parseAIResponse(text);
                showLogButton(foodData);
                updateScannerStatus('Scan complete - ready to log');
                
            } else if (result.error) {
                resultBox.innerHTML = `
                    <div class="text-center py-6">
                        <i class='bx bx-error text-red-500 text-3xl mb-3'></i>
                        <p class="text-red-600 font-medium">Error scanning food</p>
                        <p class="text-sm text-gray-500 mt-2">${result.error}</p>
                    </div>
                `;
                updateScannerStatus('Scan failed', true);
            } else {
                resultBox.innerHTML = `
                    <div class="text-center py-6">
                        <i class='bx bx-error text-yellow-500 text-3xl mb-3'></i>
                        <p class="text-yellow-600 font-medium">Unexpected response</p>
                        <p class="text-sm text-gray-500 mt-2">Please try again</p>
                    </div>
                `;
                updateScannerStatus('Unexpected response', true);
            }
        } catch (error) {
            console.error(error);
            resultBox.innerHTML = `
                <div class="text-center py-6">
                    <i class='bx bx-wifi-off text-red-500 text-3xl mb-3'></i>
                    <p class="text-red-600 font-medium">Network Error</p>
                    <p class="text-sm text-gray-500 mt-2">Please check your connection and try again</p>
                </div>
            `;
            updateScannerStatus('Network error - try again', true);
        } finally {
            // Reset scan button
            scanBtn.classList.remove('scanning');
            scanBtn.innerHTML = '<i class="bx bx-camera"></i> Scan Food';
            scanBtn.disabled = false;
            
            // Scroll to results on mobile
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    resultBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        }
    });

    // ===== ENHANCED ORIENTATION AND VISIBILITY HANDLING =====

    // Handle orientation changes on mobile
    window.addEventListener('orientationchange', () => {
        setTimeout(async () => {
            showCameraOverlay(true, 'Adjusting orientation...');
            
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
            
            await new Promise(resolve => setTimeout(resolve, 500));
            await startCamera();
            adjustVideoAspectRatio();
            showCameraOverlay(false);
        }, 300);
    });

    // Handle visibility changes
    document.addEventListener('visibilitychange', async () => {
        if (!document.hidden && !video.srcObject) {
            showCameraOverlay(true, 'Restarting camera...');
            await startCamera();
            adjustVideoAspectRatio();
            showCameraOverlay(false);
        }
    });

    // Initialize camera
    setTimeout(async () => {
        await startCamera();
        
        // Initial aspect ratio adjustment
        adjustVideoAspectRatio();
        
        // Listen for video metadata
        video.addEventListener('loadedmetadata', adjustVideoAspectRatio);
    }, 100);
</script>

</body>
</html>