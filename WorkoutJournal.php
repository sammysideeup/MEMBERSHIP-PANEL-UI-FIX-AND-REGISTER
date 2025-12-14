<?php 
// 🔑 PHP Session and Authentication
session_start();

include 'connection.php'; // Ensure this path is correct

// Check if user is logged in using the 'email' session variable
if (!isset($_SESSION['email'])) {
    header("Location: Loginpage.php");
    exit();
}

$user_email = $_SESSION['email'];
$current_user_id = null;
$user_gender = null;
$user_goal = null;
$user_focus = null;
$user_training_days = null;

// Fetch user details including gender, goal, focus, and training days
$sql = "SELECT id, gender, goal, focus, training_days FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $current_user_id = $row['id'];
    $user_gender = $row['gender'];
    $user_goal = $row['goal'];
    $user_focus = $row['focus'];
    $user_training_days = $row['training_days'];
} else {
    session_destroy();
    header("Location: Loginpage.php");
    exit();
}
$stmt->close();
$conn->close();

// Parse training days
$selected_training_days = [];
if ($user_training_days && $user_training_days !== 'NULL' && $user_training_days !== '0') {
    $selected_training_days = preg_split('/\s*,\s*/', $user_training_days);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Journal</title>
    <link rel="stylesheet" href="Memberstyle.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <!-- UPDATED: Enhanced Styles -->
    <style>
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
        
        .card-container:nth-child(1) { animation-delay: 0.1s; }
        .card-container:nth-child(2) { animation-delay: 0.2s; }
        .card-container:nth-child(3) { animation-delay: 0.3s; }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Modal transitions */
        .transition-opacity { transition: opacity 0.3s ease-in-out; }
        .transition-transform { transition: transform 0.3s ease-in-out; }
        
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
        
        /* Workout Journal Specific Styles */
        .day-tab.active-day {
            background-color: #000000; /* Black background */
            color: #ffffffff; /* Gold text */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Highlight recommended workouts */
        .recommended-workout {
            border-left: 4px solid #FFD700 !important;
            background-color: #fffdf0 !important;
        }
        
        .highlighted-goal {
            background-color: #FFD700 !important;
            color: #000000 !important;
            font-weight: 700 !important;
            border: 2px solid #000000 !important;
        }
        
        /* Workout plan cards */
        .workout-plan-card {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }
        
        .workout-plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #FFD700;
        }
        
        .difficulty-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .beginner-badge { background-color: #10b981; color: white; }
        .intermediate-badge { background-color: #f59e0b; color: white; }
        .advanced-badge { background-color: #ef4444; color: white; }

        /* UPDATED: Toast notification beside header - top right */
        .toast-wrapper{
            position: fixed;
            top: 100px; /* Below the mobile navbar */
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

        /* UPDATED: EXACT Exercise List Design from Abs-Beginner.php */
        .exercises-section {
            margin-top: 2rem;
        }
        
        .exercises-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #FFD700;
        }
        
        /* Description field for each day */
        .day-description {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .day-description textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e5e5e5;
            border-radius: 0.5rem;
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }
        
        .day-description textarea:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }
        
        /* EXACT Exercise List Items from Abs-Beginner.php */
        .exercises-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .exercise-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background-color: white;
            border-radius: 1rem;
            border: 1px solid #e5e5e5;
            cursor: grab;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .exercise-item:hover {
            border-color: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .exercise-item.dragging {
            opacity: 0.5;
            background-color: #f8f8f8;
        }
        
        /* Drag handle from Abs-Beginner.php */
        .draggable-handle {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-right: 1rem;
            cursor: grab;
            padding: 5px;
        }
        
        .draggable-handle span {
            width: 20px;
            height: 3px;
            background-color: #666;
            border-radius: 2px;
        }
        
        .exercise-item:hover .draggable-handle span {
            background-color: #FFD700;
        }
        
        /* Exercise thumbnail from Abs-Beginner.php */
        .exercise-thumb {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            margin-right: 1rem;
            background-color: #f8f8f8;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e5e5e5;
        }
        
        .exercise-item:hover .exercise-thumb {
            border-color: #FFD700;
        }
        
        /* Exercise content from Abs-Beginner.php */
        .exercise-content {
            flex-grow: 1;
        }
        
        .exercise-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 0.25rem;
        }
        
        .exercise-info {
            display: flex;
            gap: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .exercise-info span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        /* Arrow indicator from Abs-Beginner.php */
        .exercise-arrow {
            color: #999;
            font-size: 1.2rem;
            font-weight: bold;
            margin-left: 0.5rem;
            transform: rotate(90deg);
        }
        
        .exercise-item:hover .exercise-arrow {
            color: #FFD700;
        }
        
        /* Action buttons */
        .exercise-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }
        
        /* Updated button colors to match palette */
        button.bg-indigo-500 {
            background-color: #000000 !important;
            color: #ffffffff !important;
        }
        
        button.bg-indigo-500:hover {
            background-color: #333333 !important;
        }
        
        button.bg-green-500 {
            background-color: #000000 !important;
            color: #ffffffff !important;
        }
        
        button.bg-green-500:hover {
            background-color: #333333 !important;
        }
        
        button.bg-blue-600, #saveRoutineButton {
            background-color: #000000 !important;
            color: #ffffffff !important;
        }
        
        button.bg-blue-600:hover, #saveRoutineButton:hover {
            background-color: #333333 !important;
        }
        
        #start-button {
            background-color: #000000 !important;
            color: #ffffffff !important;
        }
        
        #start-button:hover {
            background-color: #333333 !important;
        }
        
        /* Yellow save button for modifications */
        button.bg-yellow-600 {
            background-color: #FFD700 !important;
            color: #000000 !important;
        }
        
        button.bg-yellow-600:hover {
            background-color: #e6c200 !important;
        }
        
        /* Browse workouts modal improvements */
        #browseWorkoutModal {
            max-width: 95%;
            width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .browse-tabs {
            display: flex;
            border-bottom: 2px solid #e5e5e5;
            margin-bottom: 1rem;
            overflow-x: auto;
        }
        
        .browse-tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            font-weight: 500;
        }
        
        .browse-tab.active {
            border-bottom-color: #FFD700;
            color: #000000;
            font-weight: 600;
        }
        
        .browse-tab-content {
            display: none;
        }
        
        .browse-tab-content.active {
            display: block;
        }
        
        /* Workout plan grid */
        .workout-plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .workout-plan-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            position: relative;
        }
        
        /* Filter buttons */
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .filter-button {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            background: #f3f4f6;
            border: 1px solid #e5e5e5;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-button.active {
            background: #FFD700;
            color: #000000;
            border-color: #FFD700;
            font-weight: 600;
        }
        
        /* Day tabs - only show available days */
        .days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        
        /* Day tabs horizontal on mobile */
        @media (max-width: 768px) {
            .days-grid {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 0.5rem;
                padding-bottom: 0.5rem;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            
            .day-tab {
                flex: 0 0 auto;
                min-width: 100px;
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
            
            .action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .action-buttons button {
                flex: 1 1 auto;
                min-width: 120px;
            }
            
            .toast-wrapper {
                width: 90%;
                max-width: 380px;
                right: 5%;
                top: 90px;
            }
            
            /* Mobile exercise item adjustments */
            .exercise-item {
                padding: 0.875rem;
            }
            
            .exercise-name {
                font-size: 1rem;
            }
            
            .exercise-info {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .exercise-thumb {
                width: 40px;
                height: 40px;
                margin-right: 0.75rem;
            }
            
            .draggable-handle {
                margin-right: 0.75rem;
            }
            
            .exercise-actions {
                margin-left: 0.5rem;
            }
            
            /* Browse modal mobile adjustments */
            #browseWorkoutModal {
                max-width: 95%;
                width: 95%;
                margin: 10px;
            }
            
            .workout-plan-grid {
                grid-template-columns: 1fr;
            }
            
            .browse-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
            }
        }
        
        /* Animation for exercise items from Abs-Beginner.php */
        @keyframes fadeInUpItem {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .exercise-item {
            animation: fadeInUpItem 0.4s ease forwards;
        }
        
        .exercise-item:nth-child(1) { animation-delay: 0.1s; }
        .exercise-item:nth-child(2) { animation-delay: 0.2s; }
        .exercise-item:nth-child(3) { animation-delay: 0.3s; }
        .exercise-item:nth-child(4) { animation-delay: 0.4s; }
        .exercise-item:nth-child(5) { animation-delay: 0.5s; }
        .exercise-item:nth-child(6) { animation-delay: 0.6s; }
        .exercise-item:nth-child(7) { animation-delay: 0.7s; }
        
        /* Header container for positioning toast */
        .header-container {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .header-title {
            flex-grow: 1;
        }
        
        /* For larger screens, toast is absolute positioned */
        @media (min-width: 769px) {
            .header-container {
                position: relative;
            }
        }
    </style>
</head>
<body class="min-h-screen">

<!-- Mobile Top Navbar -->
<nav class="mobile-navbar" id="mobileNavbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <i class='bx bx-notepad text-yellow-500 text-2xl'></i>
            <h2>Workout Journal</h2>
        </div>
        <button class="navbar-toggle" id="navbarToggle">
            <i class='bx bx-menu'></i>
        </button>
    </div>
    <div class="navbar-menu" id="navbarMenu">
        <ul>
            <li><a href="Membership.php"><i class='bx bx-user'></i> User Details</a></li>
            <li><a href="#" class="active"><i class='bx bx-notepad'></i> Workout Journal</a></li>
            <li><a href="Progress.php"><i class='bx bx-line-chart'></i> Progress</a></li>
            <li><a href="TrainerBooking.php"><i class='bx bxs-user-pin'></i> Trainers</a></li>
            <li class="more-menu-mobile">
                <a href="#" class="more-toggle-mobile">
                    <i class='bx bx-dots-horizontal-rounded'></i> More 
                    <i class='bx bx-chevron-down toggle-icon'></i>
                </a>
                <ul class="submenu" id="mobileSubmenu">
                    <li><a href="CalorieScanner.php"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                    <li><a href="gym_scanner_module.php"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
                </ul>
            </li>
            <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </div>
</nav>

<div class="sidebar">
    <h2>Member Panel</h2>
    <ul>
        <li><a href="Membership.php"><i class='bx bx-user'></i> User Details</a></li>
        <li><a href="#" class="bg-gray-700"><i class='bx bx-notepad'></i> Workout Journal</a></li>
        <li><a href="Progress.php"><i class='bx bx-line-chart'></i>Progress</a></li>
        <li><a href="TrainerBooking.php"><i class='bx bxs-user-pin'></i> Trainers</a></li>
        <li class="more-menu">
            <a href="#" class="more-toggle">
                 More 
                <i class='bx bx-chevron-down toggle-icon'></i>
            </a>
            <ul class="submenu" id="desktopSubmenu">
                <li><a href="CalorieScanner.php"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                <li><a href="gym_scanner_module.php"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
            </ul>
        </li>
        <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content Container -->
<div class="main-content">
    <div class="cards-container">
        <!-- Toast notification at top right -->
        <div id="toast-notification" class="toast-wrapper">
            <div class="toast success">
                <div class="container-1">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="container-2">
                    <p>Success</p>
                    <p>Workout Journal updated!</p>
                </div>
                <button onclick="document.getElementById('toast-notification').classList.remove('show')">&times;</button>
            </div>
        </div>
        
        <!-- Header container for proper alignment -->
        <div class="header-container">
            <div class="header-title">
                <h1 class="main-heading">Workout Journal</h1>
                <div class="message-container">
                    <p class="text-center text-gray-600 mb-8">Customize your own personalized workout</p>
                    <!-- Display user info for reference -->
                    <div class="flex flex-wrap gap-4 justify-center mb-4">
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                            <i class='bx bx-male-female mr-1'></i> <?php echo htmlspecialchars($user_gender); ?>
                        </span>
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                            <i class='bx bx-target-lock mr-1'></i> Focus: <?php echo htmlspecialchars($user_focus); ?>
                        </span>
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                            <i class='bx bx-run mr-1'></i> Goal: <?php echo htmlspecialchars($user_goal); ?>
                        </span>
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                            <i class='bx bx-calendar mr-1'></i> Training Days: <?php echo count($selected_training_days); ?> days/week
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card container for workout journal -->
        <div class="card-container">
            <section class="p-6">
                <!-- Day tabs - Only show selected training days -->
                <div class="days-grid grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mt-6 mb-8">
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $firstDay = true;
                    
                    foreach ($days as $day):
                        $isAvailable = in_array($day, $selected_training_days);
                        if (!$isAvailable) continue; // Skip unavailable days
                        
                        $classes = "day-tab bg-gray-100 rounded-xl p-4 text-center shadow-inner font-semibold cursor-pointer hover:bg-gray-200 transition-colors";
                        if ($firstDay) {
                            $classes .= " active-day";
                            $firstDay = false;
                        }
                    ?>
                        <div onclick="showDay('<?php echo $day; ?>')" class="<?php echo $classes; ?>">
                            <?php echo $day; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($selected_training_days)): ?>
                        <div class="col-span-full text-center p-4 text-gray-500">
                            <i class='bx bx-calendar-x text-3xl mb-2'></i>
                            <p>No training days selected. Please update your training days in User Details.</p>
                            <a href="Membership.php" class="text-blue-600 hover:underline mt-2 inline-block">Go to User Details</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action buttons wrap on mobile -->
                <div class="action-buttons flex gap-4 mb-6 flex-wrap">
                    <button class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-3 px-6 rounded-lg transition-all hover:scale-105 flex items-center justify-center" onclick="browseWorkout()">
                        <i class='bx bx-search-alt mr-2'></i>Browse Workout
                    </button>
                    <button class="bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-lg transition-all hover:scale-105 flex items-center justify-center" onclick="document.getElementById('addExerciseModal').showModal()">
                        <i class='bx bx-plus mr-2'></i>Add Exercise
                    </button>
                    <button id="saveRoutineButton" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-all hover:scale-105 flex items-center justify-center" onclick="saveWorkoutRoutine()">
                        <i class='bx bx-save mr-2'></i>Save Routine
                    </button>
                </div>

                <button id="start-button" class="mt-6 p-4 bg-blue-500 text-white rounded-lg font-medium text-lg w-full hover:scale-105 transition-transform flex items-center justify-center">
                    <i class='bx bx-play-circle mr-2'></i>Start Workout
                </button>

                <!-- EXACT Exercise List Design from Abs-Beginner.php -->
                <div class="exercises-section" id="day-views">
                    <?php 
                    $firstAvailable = true;
                    foreach ($days as $day): 
                        $isAvailable = in_array($day, $selected_training_days);
                        if (!$isAvailable) continue; // Skip unavailable days
                        
                        $displayClass = $firstAvailable ? '' : 'hidden';
                        $firstAvailable = false;
                    ?>
                        <div id="<?php echo $day; ?>" class="day-view <?php echo $displayClass; ?>">
                            <h2 class="text-xl font-bold mb-4 text-gray-800">Exercises for <?php echo $day; ?></h2>
                            
                            <!-- Description field for each day -->
                            <div class="day-description">
                                <textarea id="description-<?php echo $day; ?>" placeholder="Add notes or description for today's workout..."></textarea>
                            </div>
                            
                            <ul class="exercises-list" role="list"></ul>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($selected_training_days)): ?>
                        <div class="text-center p-8">
                            <i class='bx bx-dumbbell text-4xl text-gray-400 mb-4'></i>
                            <p class="text-gray-500">Please select your training days in User Details to start using the Workout Journal.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Modals (unchanged) -->
<dialog id="addExerciseModal" class="rounded-lg p-6 w-full max-w-md border border-gray-300 shadow-xl">
    <form method="dialog" class="space-y-4">
        <h3 class="text-xl font-semibold text-gray-800">Add New Exercise</h3>
        <div>
            <label class="block text-sm font-medium mb-1 text-gray-700">Routine Name</label>
            <input type="text" id="routineName" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" required />
        </div>
        <div class="flex gap-2">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1 text-gray-700">Sets</label>
                <input type="number" id="exerciseSets" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" />
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1 text-gray-700">Reps / Time (e.g., '12x' or '30secs')</label>
                <input type="text" id="exerciseReps" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" />
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-4">
            <button type="submit" class="bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium">
                <i class='bx bx-plus mr-1'></i>Add
            </button>
            <button type="button" class="bg-gray-200 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors font-medium" onclick="document.getElementById('addExerciseModal').close()">Cancel</button>
        </div>
    </form>
</dialog>

<!-- UPDATED: Browse Workout Modal with enhanced navigation -->
<dialog id="browseWorkoutModal" class="rounded-lg p-6 w-full max-w-6xl border border-gray-300 shadow-xl">
    <div class="space-y-4">
        <h3 class="text-xl font-semibold text-gray-800">Browse Workouts & Plans</h3>
        
        <!-- Tabs for navigation -->
        <div class="browse-tabs">
            <div class="browse-tab active" onclick="switchBrowseTab('exercises')">
                <i class='bx bx-dumbbell mr-2'></i>Exercises
            </div>
            <div class="browse-tab" onclick="switchBrowseTab('target-muscle')">
                <i class='bx bx-target-lock mr-2'></i>Target Muscle Plans
            </div>
            <div class="browse-tab" onclick="switchBrowseTab('goal-plans')">
                <i class='bx bx-run mr-2'></i>Goal-Based Plans
            </div>
        </div>
        
        <!-- Exercises Tab -->
        <div id="exercises-tab" class="browse-tab-content active">
            <div class="mb-4">
                <input type="text" id="searchInput" placeholder="Search by name or muscle group..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent" 
                       oninput="filterExercises()">
                <div class="filter-buttons mt-2">
                    <button class="filter-button active" onclick="filterByGroup('all')">All</button>
                    <button class="filter-button" onclick="filterByGroup('Arms')">Arms</button>
                    <button class="filter-button" onclick="filterByGroup('Chest')">Chest</button>
                    <button class="filter-button" onclick="filterByGroup('Legs')">Legs</button>
                    <button class="filter-button" onclick="filterByGroup('Back')">Back</button>
                    <button class="filter-button" onclick="filterByGroup('Shoulders')">Shoulders</button>
                    <button class="filter-button" onclick="filterByGroup('Abs')">Abs</button>
                    <button class="filter-button" onclick="filterByGroup('Cardio')">Cardio</button>
                </div>
            </div>
            <div id="browseExerciseList" class="space-y-2 max-h-60 overflow-y-auto"></div>
        </div>
        
        <!-- Target Muscle Plans Tab -->
        <div id="target-muscle-tab" class="browse-tab-content">
            <div class="mb-4">
                <p class="text-gray-600 mb-2">Select target muscle group:</p>
                <div class="filter-buttons">
                    <button class="filter-button active" onclick="showMusclePlans('Arms')">Arms</button>
                    <button class="filter-button" onclick="showMusclePlans('Chest')">Chest</button>
                    <button class="filter-button" onclick="showMusclePlans('Legs')">Legs</button>
                    <button class="filter-button" onclick="showMusclePlans('Full Body')">Full Body</button>
                </div>
            </div>
            <div id="muscle-plans-container"></div>
        </div>
        
        <!-- Goal-Based Plans Tab -->
        <div id="goal-plans-tab" class="browse-tab-content">
            <div class="mb-4">
                <p class="text-gray-600 mb-2">Select your fitness goal:</p>
                <div class="filter-buttons">
                    <?php 
                    $goals = ['Lose Weight', 'Gain Muscle', 'Stay Fit', 'Maintain'];
                    foreach ($goals as $goal): 
                        $isUserGoal = ($goal === $user_goal);
                        $classes = $isUserGoal ? 'filter-button highlighted-goal' : 'filter-button';
                    ?>
                        <button class="<?php echo $classes; ?>" onclick="showGoalPlans('<?php echo $goal; ?>')">
                            <?php echo $goal; ?>
                            <?php if ($isUserGoal): ?><i class='bx bx-check ml-1'></i><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="goal-plans-container"></div>
        </div>
        
        <div class="flex justify-end gap-2 pt-4 border-t mt-4">
            <button type="button" onclick="addSelectedExercises()" class="bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium">
                <i class='bx bx-check mr-1'></i>Add Selected Exercises
            </button>
            <button type="button" class="bg-gray-200 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors font-medium" onclick="document.getElementById('browseWorkoutModal').close()">Cancel</button>
        </div>
    </div>
</dialog>

<dialog id="editExerciseModal" class="rounded-lg p-6 w-full max-w-md border border-gray-300 shadow-xl">
    <form method="dialog" class="space-y-4" id="editExerciseForm">
        <h3 class="text-xl font-semibold text-gray-800">Edit Exercise</h3>
        <input type="hidden" id="editExerciseId">
        <div>
            <label class="block text-sm font-medium mb-1 text-gray-700">Name</label>
            <input type="text" id="editExerciseName" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent" required>
        </div>
        <div class="flex gap-2">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1 text-gray-700">Sets</label>
                <input type="number" id="editExerciseSets" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1 text-gray-700">Reps / Time</label>
                <input type="text" id="editExerciseReps" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-4">
            <button type="submit" class="bg-black text-yellow-500 px-6 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium">
                <i class='bx bx-save mr-1'></i>Save
            </button>
            <button type="button" class="bg-gray-200 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors font-medium" onclick="document.getElementById('editExerciseModal').close()">Cancel</button>
        </div>
    </form>
</dialog>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ====== 1. HAMBURGER MENU TOGGLE ======
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarMenu = document.getElementById('navbarMenu');
    
    if (navbarToggle && navbarMenu) {
        // Toggle hamburger menu
        navbarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle menu and icon
            navbarMenu.classList.toggle('active');
            this.classList.toggle('active');
            
            // Toggle menu icon
            const icon = this.querySelector('i');
            if (navbarMenu.classList.contains('active')) {
                icon.classList.remove('bx-menu');
                icon.classList.add('bx-x');
            } else {
                icon.classList.remove('bx-x');
                icon.classList.add('bx-menu');
                // Close submenu when hamburger closes
                closeSubmenu();
            }
        });
        
        // Close menu when clicking outside (mobile only)
        document.addEventListener('click', function(event) {
            const isMobile = window.innerWidth <= 1024;
            const isClickInsideNavbar = navbarMenu.contains(event.target) || 
                                       navbarToggle.contains(event.target);
            
            if (isMobile && !isClickInsideNavbar && navbarMenu.classList.contains('active')) {
                closeMenu();
            }
        });
        
        // Close menu function
        function closeMenu() {
            navbarMenu.classList.remove('active');
            navbarToggle.classList.remove('active');
            const icon = navbarToggle.querySelector('i');
            icon.classList.remove('bx-x');
            icon.classList.add('bx-menu');
            closeSubmenu();
        }
        
        // Mark active link
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
    
    // ====== 2. DESKTOP SIDEBAR SUBMENU ======
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
    
    // ====== 3. MOBILE SUBMENU TOGGLE ======
    const moreToggleMobile = document.querySelector('.more-toggle-mobile');
    const mobileSubmenu = document.getElementById('mobileSubmenu');
    const mobileToggleIcon = document.querySelector('.more-menu-mobile .toggle-icon');
    
    // Close submenu function
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
            
            // FIXED: Open/close submenu in ONE CLICK
            const isOpen = mobileSubmenu.style.maxHeight && mobileSubmenu.style.maxHeight !== '0px';
            
            if (isOpen) {
                // Close submenu
                mobileSubmenu.style.maxHeight = '0px';
                mobileToggleIcon.style.transform = 'rotate(0deg)';
            } else {
                // Open submenu - NO hamburger toggle, works immediately
                mobileSubmenu.style.maxHeight = mobileSubmenu.scrollHeight + 'px'; 
                mobileToggleIcon.style.transform = 'rotate(180deg)';
            }
        });
    }
});

// ====== WORKOUT JOURNAL SPECIFIC JAVASCRIPT ======
let currentDay = '<?php echo (count($selected_training_days) > 0) ? $selected_training_days[0] : "Monday"; ?>';
let draggedItem = null;

// Holds the currently visible exercises for each day
const dayExercises = {
    Monday: [], Tuesday: [], Wednesday: [], Thursday: [], Friday: [], Saturday: [], Sunday: []
};

// User information from PHP
const userGender = '<?php echo $user_gender; ?>';
const userFocus = '<?php echo $user_focus; ?>';
const userGoal = '<?php echo $user_goal; ?>';
const selectedTrainingDays = <?php echo json_encode($selected_training_days); ?>;
const currentUserId = <?php echo $current_user_id; ?>;

console.log('=== WORKOUT JOURNAL INITIALIZATION ===');
console.log('User ID:', currentUserId);
console.log('User Email:', '<?php echo $user_email; ?>');
console.log('Selected Training Days:', selectedTrainingDays);

// Clear any cached data from previous sessions
function clearUserCache() {
    // Clear the dayExercises object
    Object.keys(dayExercises).forEach(day => {
        dayExercises[day] = [];
    });
    
    // Clear localStorage cache
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('cachedExercises');
        localStorage.removeItem('lastLoadedUser');
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    console.log('Selected training days:', selectedTrainingDays);
    
    // Clear any cached data for new users
    clearUserCache();
    
    // Set initial day to first available training day
    const firstAvailableDay = selectedTrainingDays.length > 0 ? selectedTrainingDays[0] : 'Monday';
    console.log('Setting initial day to:', firstAvailableDay);
    
    // Wait a bit to ensure DOM is fully ready, then load exercises
    setTimeout(() => {
        showDay(firstAvailableDay);
    }, 100);
});

// Force refresh user data (for debugging/testing)
function forceRefreshUserData() {
    console.log('Forcing refresh of user data for user ID:', currentUserId);
    
    clearUserCache();
    
    // Clear visible lists
    Object.keys(dayExercises).forEach(day => {
        const list = document.querySelector(`#${day} .exercises-list`);
        if (list) {
            list.innerHTML = '<li class="p-3 text-gray-400 text-center">Refreshing data...</li>';
        }
    });
    
    // Reload current day
    setTimeout(() => {
        showDay(currentDay);
        showToast('success', 'Data refreshed successfully');
    }, 300);
}

// All available exercises - EXPANDED TO 50 PER MUSCLE GROUP
const allExercises = [
    // ARMS - 50 exercises
    { id: "dumbbell-bicep-curl", name: "Dumbbell Bicep Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "tricep-pushdown", name: "Tricep Pushdown", group: "Arms", sets: 3, reps: "12x" },
    { id: "ez-bar-curl", name: "EZ Bar Curl", group: "Arms", sets: 3, reps: "10x" },
    { id: "overhead-dumbbell-tricep-extension", name: "Overhead Dumbbell Tricep Extension", group: "Arms", sets: 3, reps: "10x" },
    { id: "hammer-curl", name: "Hammer Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "concentration-curl", name: "Concentration Curl", group: "Arms", sets: 3, reps: "10x" },
    { id: "barbell-curl", name: "Barbell Curl", group: "Arms", sets: 4, reps: "8-10x" },
    { id: "preacher-curl", name: "Preacher Curl", group: "Arms", sets: 3, reps: "10-12x" },
    { id: "skull-crushers", name: "Skull Crushers", group: "Arms", sets: 3, reps: "10-12x" },
    { id: "close-grip-bench-press", name: "Close Grip Bench Press", group: "Arms", sets: 4, reps: "8-10x" },
    { id: "rope-pushdown", name: "Rope Tricep Pushdown", group: "Arms", sets: 3, reps: "12-15x" },
    { id: "diamond-pushups", name: "Diamond Push-ups", group: "Arms", sets: 3, reps: "15x" },
    { id: "reverse-curl", name: "Reverse Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "zottman-curl", name: "Zottman Curl", group: "Arms", sets: 3, reps: "10x" },
    { id: "spider-curl", name: "Spider Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "cable-bicep-curl", name: "Cable Bicep Curl", group: "Arms", sets: 3, reps: "12-15x" },
    { id: "overhead-cable-curl", name: "Overhead Cable Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "dips", name: "Tricep Dips", group: "Arms", sets: 3, reps: "10-12x" },
    { id: "lying-tricep-extension", name: "Lying Tricep Extension", group: "Arms", sets: 3, reps: "12x" },
    { id: "cable-hammer-curl", name: "Cable Hammer Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "incline-dumbbell-curl", name: "Incline Dumbbell Curl", group: "Arms", sets: 3, reps: "10x" },
    { id: "standing-barbell-curl", name: "Standing Barbell Curl", group: "Arms", sets: 4, reps: "8-10x" },
    { id: "seated-dumbbell-curl", name: "Seated Dumbbell Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "single-arm-tricep-pushdown", name: "Single Arm Tricep Pushdown", group: "Arms", sets: 3, reps: "12x each" },
    { id: "bench-dips", name: "Bench Dips", group: "Arms", sets: 3, reps: "15x" },
    { id: "cable-overhead-tricep-extension", name: "Cable Overhead Tricep Extension", group: "Arms", sets: 3, reps: "12x" },
    { id: "21s-bicep-curl", name: "21s Bicep Curl", group: "Arms", sets: 3, reps: "21x" },
    { id: "reverse-grip-pushdown", name: "Reverse Grip Pushdown", group: "Arms", sets: 3, reps: "12x" },
    { id: "standing-cable-curl", name: "Standing Cable Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "close-grip-pushup", name: "Close Grip Push-up", group: "Arms", sets: 3, reps: "15x" },
    { id: "ez-bar-preacher-curl", name: "EZ Bar Preacher Curl", group: "Arms", sets: 3, reps: "10-12x" },
    { id: "single-arm-cable-curl", name: "Single Arm Cable Curl", group: "Arms", sets: 3, reps: "12x each" },
    { id: "lying-cable-curl", name: "Lying Cable Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "cross-body-hammer-curl", name: "Cross Body Hammer Curl", group: "Arms", sets: 3, reps: "12x each" },
    { id: "jumping-jack-press", name: "Jumping Jack Press", group: "Arms", sets: 3, reps: "20x" },
    { id: "barbell-wrist-curl", name: "Barbell Wrist Curl", group: "Arms", sets: 3, reps: "15x" },
    { id: "reverse-barbell-curl", name: "Reverse Barbell Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "cable-concentration-curl", name: "Cable Concentration Curl", group: "Arms", sets: 3, reps: "12x each" },
    { id: "standing-dumbbell-tricep-extension", name: "Standing Dumbbell Tricep Extension", group: "Arms", sets: 3, reps: "12x" },
    { id: "ez-bar-reverse-curl", name: "EZ Bar Reverse Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "rope-overhead-tricep-extension", name: "Rope Overhead Tricep Extension", group: "Arms", sets: 3, reps: "12x" },
    { id: "incline-hammer-curl", name: "Incline Hammer Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "cable-curl-with-rope", name: "Cable Curl with Rope", group: "Arms", sets: 3, reps: "12x" },
    { id: "single-arm-overhead-extension", name: "Single Arm Overhead Extension", group: "Arms", sets: 3, reps: "12x each" },
    { id: "drag-curl", name: "Drag Curl", group: "Arms", sets: 3, reps: "10x" },
    { id: "cable-kickback", name: "Cable Kickback", group: "Arms", sets: 3, reps: "12x each" },
    { id: "ez-bar-lying-tricep-extension", name: "EZ Bar Lying Tricep Extension", group: "Arms", sets: 3, reps: "12x" },
    { id: "standing-cable-hammer-curl", name: "Standing Cable Hammer Curl", group: "Arms", sets: 3, reps: "12x" },
    { id: "bench-alternating-curl", name: "Bench Alternating Curl", group: "Arms", sets: 3, reps: "10x each" },
    { id: "rope-hammer-curl", name: "Rope Hammer Curl", group: "Arms", sets: 3, reps: "12x" },
    
    // CHEST - 50 exercises
    { id: "bench-press", name: "Bench Press", group: "Chest", sets: 4, reps: "8x" },
    { id: "machine-chest-press", name: "Machine Chest Press", group: "Chest", sets: 3, reps: "12x" },
    { id: "dumbbell-bench-press", name: "Dumbbell Bench Press", group: "Chest", sets: 3, reps: "10x" },
    { id: "push-ups", name: "Push-Ups", group: "Chest", sets: 3, reps: "15x" },
    { id: "incline-bench-press", name: "Incline Bench Press", group: "Chest", sets: 4, reps: "8-10x" },
    { id: "decline-bench-press", name: "Decline Bench Press", group: "Chest", sets: 4, reps: "8-10x" },
    { id: "chest-fly", name: "Chest Fly", group: "Chest", sets: 3, reps: "12-15x" },
    { id: "cable-crossover", name: "Cable Crossover", group: "Chest", sets: 3, reps: "12-15x" },
    { id: "pec-deck", name: "Pec Deck Fly", group: "Chest", sets: 3, reps: "12-15x" },
    { id: "dumbbell-fly", name: "Dumbbell Fly", group: "Chest", sets: 3, reps: "12x" },
    { id: "incline-dumbbell-press", name: "Incline Dumbbell Press", group: "Chest", sets: 4, reps: "10-12x" },
    { id: "decline-dumbbell-press", name: "Decline Dumbbell Press", group: "Chest", sets: 4, reps: "10-12x" },
    { id: "machine-fly", name: "Machine Fly", group: "Chest", sets: 3, reps: "12-15x" },
    { id: "cable-chest-press", name: "Cable Chest Press", group: "Chest", sets: 3, reps: "12x" },
    { id: "smith-machine-bench-press", name: "Smith Machine Bench Press", group: "Chest", sets: 4, reps: "8-10x" },
    { id: "landmine-press", name: "Landmine Press", group: "Chest", sets: 3, reps: "10x" },
    { id: "push-up-variations", name: "Push-up Variations", group: "Chest", sets: 3, reps: "15x" },
    { id: "wide-grip-bench-press", name: "Wide Grip Bench Press", group: "Chest", sets: 4, reps: "8-10x" },
    { id: "close-grip-bench-press-chest", name: "Close Grip Bench Press (Chest)", group: "Chest", sets: 4, reps: "8-10x" },
    { id: "incline-cable-fly", name: "Incline Cable Fly", group: "Chest", sets: 3, reps: "12x" },
    { id: "decline-cable-fly", name: "Decline Cable Fly", group: "Chest", sets: 3, reps: "12x" },
    { id: "dumbbell-pullover", name: "Dumbbell Pullover", group: "Chest", sets: 3, reps: "12x" },
    { id: "barbell-pullover", name: "Barbell Pullover", group: "Chest", sets: 3, reps: "12x" },
    { id: "cable-pullover", name: "Cable Pullover", group: "Chest", sets: 3, reps: "12x" },
    { id: "machine-incline-press", name: "Machine Incline Press", group: "Chest", sets: 3, reps: "12x" },
    { id: "machine-decline-press", name: "Machine Decline Press", group: "Chest", sets: 3, reps: "12x" },
    { id: "single-arm-cable-press", name: "Single Arm Cable Press", group: "Chest", sets: 3, reps: "12x each" },
    { id: "push-up-with-rotation", name: "Push-up with Rotation", group: "Chest", sets: 3, reps: "10x each" },
    { id: "medicine-ball-push-up", name: "Medicine Ball Push-up", group: "Chest", sets: 3, reps: "10x" },
    { id: "plyometric-push-up", name: "Plyometric Push-up", group: "Chest", sets: 3, reps: "8x" },
    { id: "archer-push-up", name: "Archer Push-up", group: "Chest", sets: 3, reps: "8x each" },
    { id: "spider-man-push-up", name: "Spider-Man Push-up", group: "Chest", sets: 3, reps: "10x each" },
    { id: "incline-push-up", name: "Incline Push-up", group: "Chest", sets: 3, reps: "15x" },
    { id: "decline-push-up", name: "Decline Push-up", group: "Chest", sets: 3, reps: "12x" },
    { id: "diamond-push-up-chest", name: "Diamond Push-up (Chest)", group: "Chest", sets: 3, reps: "12x" },
    { id: "wide-push-up", name: "Wide Push-up", group: "Chest", sets: 3, reps: "15x" },
    { id: "banded-push-up", name: "Banded Push-up", group: "Chest", sets: 3, reps: "12x" },
    { id: "resistance-band-chest-press", name: "Resistance Band Chest Press", group: "Chest", sets: 3, reps: "15x" },
    { id: "single-arm-dumbbell-press", name: "Single Arm Dumbbell Press", group: "Chest", sets: 3, reps: "10x each" },
    { id: "guillotine-press", name: "Guillotine Press", group: "Chest", sets: 3, reps: "10x" },
    { id: "floor-press", name: "Floor Press", group: "Chest", sets: 3, reps: "10x" },
    { id: "board-press", name: "Board Press", group: "Chest", sets: 3, reps: "5x" },
    { id: "pin-press", name: "Pin Press", group: "Chest", sets: 3, reps: "5x" },
    { id: "chain-bench-press", name: "Chain Bench Press", group: "Chest", sets: 3, reps: "5x" },
    { id: "banded-bench-press", name: "Banded Bench Press", group: "Chest", sets: 3, reps: "5x" },
    { id: "reverse-grip-bench-press", name: "Reverse Grip Bench Press", group: "Chest", sets: 3, reps: "8x" },
    { id: "seated-chest-press", name: "Seated Chest Press", group: "Chest", sets: 3, reps: "12x" },
    { id: "standing-cable-press", name: "Standing Cable Press", group: "Chest", sets: 3, reps: "12x" },
    { id: "low-cable-crossover", name: "Low Cable Crossover", group: "Chest", sets: 3, reps: "12x" },
    { id: "high-cable-crossover", name: "High Cable Crossover", group: "Chest", sets: 3, reps: "12x" },
    { id: "neutral-grip-dumbbell-press", name: "Neutral Grip Dumbbell Press", group: "Chest", sets: 3, reps: "10x" },
    
    // LEGS - 50 exercises
    { id: "leg-press", name: "Leg Press", group: "Legs", sets: 4, reps: "10x" },
    { id: "bodyweight-squats", name: "Bodyweight Squats", group: "Legs", sets: 3, reps: "15x" },
    { id: "barbell-back-squat", name: "Barbell Back Squat", group: "Legs", sets: 4, reps: "10x" },
    { id: "lunges", name: "Lunges", group: "Legs", sets: 3, reps: "12x each leg" },
    { id: "leg-extension", name: "Leg Extension", group: "Legs", sets: 3, reps: "12-15x" },
    { id: "leg-curl", name: "Leg Curl", group: "Legs", sets: 3, reps: "12-15x" },
    { id: "calf-raise", name: "Calf Raise", group: "Legs", sets: 4, reps: "15-20x" },
    { id: "romanian-deadlift", name: "Romanian Deadlift", group: "Legs", sets: 3, reps: "10-12x" },
    { id: "bulgarian-split-squat", name: "Bulgarian Split Squat", group: "Legs", sets: 3, reps: "10x each" },
    { id: "hack-squat", name: "Hack Squat", group: "Legs", sets: 4, reps: "8-10x" },
    { id: "goblet-squat", name: "Goblet Squat", group: "Legs", sets: 3, reps: "12x" },
    { id: "front-squat", name: "Front Squat", group: "Legs", sets: 4, reps: "6-8x" },
    { id: "sumo-squat", name: "Sumo Squat", group: "Legs", sets: 3, reps: "12x" },
    { id: "step-ups", name: "Step-ups", group: "Legs", sets: 3, reps: "12x each" },
    { id: "box-jumps", name: "Box Jumps", group: "Legs", sets: 4, reps: "10x" },
    { id: "wall-sit", name: "Wall Sit", group: "Legs", sets: 3, reps: "60 secs" },
    { id: "single-leg-press", name: "Single Leg Press", group: "Legs", sets: 3, reps: "12x each" },
    { id: "seated-calf-raise", name: "Seated Calf Raise", group: "Legs", sets: 4, reps: "15-20x" },
    { id: "standing-calf-raise", name: "Standing Calf Raise", group: "Legs", sets: 4, reps: "15-20x" },
    { id: "donkey-calf-raise", name: "Donkey Calf Raise", group: "Legs", sets: 3, reps: "15x" },
    { id: "glute-bridge", name: "Glute Bridge", group: "Legs", sets: 3, reps: "15x" },
    { id: "hip-thrust", name: "Hip Thrust", group: "Legs", sets: 4, reps: "10-12x" },
    { id: "good-mornings", name: "Good Mornings", group: "Legs", sets: 3, reps: "12x" },
    { id: "machine-squat", name: "Machine Squat", group: "Legs", sets: 3, reps: "12x" },
    { id: "pistol-squat", name: "Pistol Squat", group: "Legs", sets: 3, reps: "8x each" },
    { id: "jump-squat", name: "Jump Squat", group: "Legs", sets: 4, reps: "15x" },
    { id: "cable-pull-through", name: "Cable Pull Through", group: "Legs", sets: 3, reps: "12x" },
    { id: "smith-machine-squat", name: "Smith Machine Squat", group: "Legs", sets: 4, reps: "8-10x" },
    { id: "smith-machine-lunge", name: "Smith Machine Lunge", group: "Legs", sets: 3, reps: "10x each" },
    { id: "machine-hip-abduction", name: "Machine Hip Abduction", group: "Legs", sets: 3, reps: "15x" },
    { id: "machine-hip-adduction", name: "Machine Hip Adduction", group: "Legs", sets: 3, reps: "15x" },
    { id: "single-leg-extension", name: "Single Leg Extension", group: "Legs", sets: 3, reps: "12x each" },
    { id: "single-leg-curl", name: "Single Leg Curl", group: "Legs", sets: 3, reps: "12x each" },
    { id: "stiff-leg-deadlift", name: "Stiff Leg Deadlift", group: "Legs", sets: 3, reps: "10x" },
    { id: "zercher-squat", name: "Zercher Squat", group: "Legs", sets: 3, reps: "8x" },
    { id: "belt-squat", name: "Belt Squat", group: "Legs", sets: 3, reps: "10x" },
    { id: "sissy-squat", name: "Sissy Squat", group: "Legs", sets: 3, reps: "12x" },
    { id: "curtsy-lunge", name: "Curtsy Lunge", group: "Legs", sets: 3, reps: "12x each" },
    { id: "lateral-lunge", name: "Lateral Lunge", group: "Legs", sets: 3, reps: "12x each" },
    { id: "reverse-lunge", name: "Reverse Lunge", group: "Legs", sets: 3, reps: "12x each" },
    { id: "walking-lunge", name: "Walking Lunge", group: "Legs", sets: 3, reps: "20 steps" },
    { id: "jumping-lunge", name: "Jumping Lunge", group: "Legs", sets: 3, reps: "10x each" },
    { id: "cossack-squat", name: "Cossack Squat", group: "Legs", sets: 3, reps: "10x each" },
    { id: "banded-squat", name: "Banded Squat", group: "Legs", sets: 3, reps: "12x" },
    { id: "resistance-band-leg-press", name: "Resistance Band Leg Press", group: "Legs", sets: 3, reps: "15x" },
    { id: "banded-walk", name: "Banded Walk", group: "Legs", sets: 3, reps: "20 steps" },
    { id: "machine-leg-press-single", name: "Machine Leg Press Single", group: "Legs", sets: 3, reps: "12x each" },
    { id: "seated-leg-curl", name: "Seated Leg Curl", group: "Legs", sets: 3, reps: "12x" },
    { id: "lying-leg-curl", name: "Lying Leg Curl", group: "Legs", sets: 3, reps: "12x" },
    { id: "toe-press", name: "Toe Press", group: "Legs", sets: 3, reps: "20x" },
    { id: "farmer-walk", name: "Farmer's Walk", group: "Legs", sets: 3, reps: "50 ft" },
    
    // BACK - 50 exercises
    { id: "lat-pulldown", name: "Lat Pulldown", group: "Back", sets: 3, reps: "12x" },
    { id: "deadlift", name: "Deadlift", group: "Back", sets: 4, reps: "5x" },
    { id: "pull-ups", name: "Pull-Ups", group: "Back", sets: 3, reps: "10x" },
    { id: "bent-over-row", name: "Bent Over Row", group: "Back", sets: 4, reps: "8-10x" },
    { id: "seated-row", name: "Seated Row", group: "Back", sets: 3, reps: "12x" },
    { id: "t-bar-row", name: "T-Bar Row", group: "Back", sets: 4, reps: "8-10x" },
    { id: "single-arm-dumbbell-row", name: "Single Arm Dumbbell Row", group: "Back", sets: 3, reps: "10x each" },
    { id: "face-pull", name: "Face Pull", group: "Back", sets: 3, reps: "15x" },
    { id: "straight-arm-pulldown", name: "Straight Arm Pulldown", group: "Back", sets: 3, reps: "12x" },
    { id: "hyperextension", name: "Hyperextension", group: "Back", sets: 3, reps: "15x" },
    { id: "chin-ups", name: "Chin-ups", group: "Back", sets: 3, reps: "10x" },
    { id: "inverted-row", name: "Inverted Row", group: "Back", sets: 3, reps: "12x" },
    { id: "rack-pull", name: "Rack Pull", group: "Back", sets: 4, reps: "5x" },
    { id: "goodmorning-back", name: "Good Morning (Back)", group: "Back", sets: 3, reps: "12x" },
    { id: "cable-row", name: "Cable Row", group: "Back", sets: 3, reps: "12x" },
    { id: "machine-pulldown", name: "Machine Pulldown", group: "Back", sets: 3, reps: "12x" },
    { id: "machine-row", name: "Machine Row", group: "Back", sets: 3, reps: "12x" },
    { id: "wide-grip-pulldown", name: "Wide Grip Pulldown", group: "Back", sets: 3, reps: "12x" },
    { id: "close-grip-pulldown", name: "Close Grip Pulldown", group: "Back", sets: 3, reps: "12x" },
    { id: "reverse-grip-pulldown", name: "Reverse Grip Pulldown", group: "Back", sets: 3, reps: "12x" },
    { id: "neutral-grip-pulldown", name: "Neutral Grip Pulldown", group: "Back", sets: 3, reps: "12x" },
    { id: "barbell-shrug", name: "Barbell Shrug", group: "Back", sets: 4, reps: "12x" },
    { id: "dumbbell-shrug", name: "Dumbbell Shrug", group: "Back", sets: 3, reps: "15x" },
    { id: "smith-machine-row", name: "Smith Machine Row", group: "Back", sets: 3, reps: "10x" },
    { id: "landmine-row", name: "Landmine Row", group: "Back", sets: 3, reps: "10x each" },
    { id: "cable-shrug", name: "Cable Shrug", group: "Back", sets: 3, reps: "15x" },
    { id: "pullover-machine", name: "Pullover Machine", group: "Back", sets: 3, reps: "12x" },
    { id: "assisted-pull-up", name: "Assisted Pull-up", group: "Back", sets: 3, reps: "10x" },
    { id: "negative-pull-up", name: "Negative Pull-up", group: "Back", sets: 3, reps: "5x" },
    { id: "banded-pull-up", name: "Banded Pull-up", group: "Back", sets: 3, reps: "8x" },
    { id: "lat-prayer", name: "Lat Prayer", group: "Back", sets: 3, reps: "15x" },
    { id: "cable-face-pull-high", name: "Cable Face Pull High", group: "Back", sets: 3, reps: "15x" },
    { id: "cable-face-pull-low", name: "Cable Face Pull Low", group: "Back", sets: 3, reps: "15x" },
    { id: "reverse-hyperextension", name: "Reverse Hyperextension", group: "Back", sets: 3, reps: "15x" },
    { id: "back-extension", name: "Back Extension", group: "Back", sets: 3, reps: "15x" },
    { id: "superman", name: "Superman", group: "Back", sets: 3, reps: "15x" },
    { id: "bird-dog", name: "Bird Dog", group: "Back", sets: 3, reps: "10x each" },
    { id: "cable-pull-down-straight", name: "Cable Pull Down Straight", group: "Back", sets: 3, reps: "12x" },
    { id: "seated-cable-row-wide", name: "Seated Cable Row Wide", group: "Back", sets: 3, reps: "12x" },
    { id: "seated-cable-row-close", name: "Seated Cable Row Close", group: "Back", sets: 3, reps: "12x" },
    { id: "single-arm-cable-row", name: "Single Arm Cable Row", group: "Back", sets: 3, reps: "12x each" },
    { id: "standing-cable-row", name: "Standing Cable Row", group: "Back", sets: 3, reps: "12x" },
    { id: "kneeling-cable-row", name: "Kneeling Cable Row", group: "Back", sets: 3, reps: "12x" },
    { id: "bent-over-cable-row", name: "Bent Over Cable Row", group: "Back", sets: 3, reps: "12x" },
    { id: "resistance-band-row", name: "Resistance Band Row", group: "Back", sets: 3, reps: "15x" },
    { id: "banded-pulldown", name: "Banded Pulldown", group: "Back", sets: 3, reps: "15x" },
    { id: "banded-face-pull", name: "Banded Face Pull", group: "Back", sets: 3, reps: "15x" },
    { id: "incline-dumbbell-row", name: "Incline Dumbbell Row", group: "Back", sets: 3, reps: "12x" },
    { id: "prone-dumbbell-row", name: "Prone Dumbbell Row", group: "Back", sets: 3, reps: "12x" },
    { id: "yates-row", name: "Yates Row", group: "Back", sets: 3, reps: "10x" },
    { id: "pendlay-row", name: "Pendlay Row", group: "Back", sets: 3, reps: "8x" },
    
    // SHOULDERS - 50 exercises
    { id: "dumbbell-shoulder-press", name: "Dumbbell Shoulder Press", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "barbell-overhead-press", name: "Barbell Overhead Press", group: "Shoulders", sets: 4, reps: "8-10x" },
    { id: "arnold-press", name: "Arnold Press", group: "Shoulders", sets: 3, reps: "10x" },
    { id: "lateral-raise", name: "Lateral Raise", group: "Shoulders", sets: 3, reps: "12-15x" },
    { id: "front-raise", name: "Front Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "rear-delt-fly", name: "Rear Delt Fly", group: "Shoulders", sets: 3, reps: "12-15x" },
    { id: "upright-row", name: "Upright Row", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "face-pull-shoulders", name: "Face Pull (Shoulders)", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "cable-lateral-raise", name: "Cable Lateral Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "cable-front-raise", name: "Cable Front Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "machine-shoulder-press", name: "Machine Shoulder Press", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "smith-machine-overhead-press", name: "Smith Machine Overhead Press", group: "Shoulders", sets: 4, reps: "8-10x" },
    { id: "seated-dumbbell-press", name: "Seated Dumbbell Press", group: "Shoulders", sets: 3, reps: "10x" },
    { id: "standing-dumbbell-press", name: "Standing Dumbbell Press", group: "Shoulders", sets: 3, reps: "10x" },
    { id: "push-press", name: "Push Press", group: "Shoulders", sets: 4, reps: "5-8x" },
    { id: "behind-neck-press", name: "Behind Neck Press", group: "Shoulders", sets: 3, reps: "10x" },
    { id: "landmine-press-shoulder", name: "Landmine Press (Shoulder)", group: "Shoulders", sets: 3, reps: "10x each" },
    { id: "single-arm-dumbbell-press-shoulder", name: "Single Arm Dumbbell Press", group: "Shoulders", sets: 3, reps: "10x each" },
    { id: "cable-overhead-press", name: "Cable Overhead Press", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "machine-lateral-raise", name: "Machine Lateral Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "bent-over-lateral-raise", name: "Bent Over Lateral Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "seated-lateral-raise", name: "Seated Lateral Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "standing-lateral-raise", name: "Standing Lateral Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "plate-front-raise", name: "Plate Front Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "cable-rear-delt-fly", name: "Cable Rear Delt Fly", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "machine-rear-delt", name: "Machine Rear Delt", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "dumbbell-scaption", name: "Dumbbell Scaption", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "cable-scaption", name: "Cable Scaption", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "banded-lateral-raise", name: "Banded Lateral Raise", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "banded-front-raise", name: "Banded Front Raise", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "banded-rear-delt-fly", name: "Banded Rear Delt Fly", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "resistance-band-press", name: "Resistance Band Press", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "cuban-press", name: "Cuban Press", group: "Shoulders", sets: 3, reps: "10x" },
    { id: "shoulder-complex", name: "Shoulder Complex", group: "Shoulders", sets: 3, reps: "10x" },
    { id: "single-arm-cable-lateral", name: "Single Arm Cable Lateral", group: "Shoulders", sets: 3, reps: "12x each" },
    { id: "single-arm-cable-front", name: "Single Arm Cable Front", group: "Shoulders", sets: 3, reps: "12x each" },
    { id: "lying-lateral-raise", name: "Lying Lateral Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "incline-dumbbell-lateral", name: "Incline Dumbbell Lateral", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "prone-dumbbell-lateral", name: "Prone Dumbbell Lateral", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "kettlebell-overhead-press", name: "Kettlebell Overhead Press", group: "Shoulders", sets: 3, reps: "10x each" },
    { id: "kettlebell-lateral-raise", name: "Kettlebell Lateral Raise", group: "Shoulders", sets: 3, reps: "12x" },
    { id: "bottoms-up-kettlebell-press", name: "Bottoms Up Kettlebell Press", group: "Shoulders", sets: 3, reps: "8x each" },
    { id: "overhead-carry", name: "Overhead Carry", group: "Shoulders", sets: 3, reps: "50 ft" },
    { id: "farmers-walk-shoulders", name: "Farmer's Walk (Shoulders)", group: "Shoulders", sets: 3, reps: "50 ft" },
    { id: "shoulder-shrugs", name: "Shoulder Shrugs", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "cable-shrug-shoulder", name: "Cable Shrug (Shoulder)", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "plate-shrug", name: "Plate Shrug", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "machine-shrug-shoulder", name: "Machine Shrug (Shoulder)", group: "Shoulders", sets: 3, reps: "15x" },
    { id: "z-press", name: "Z Press", group: "Shoulders", sets: 3, reps: "8x" },
    { id: "seated-barbell-press", name: "Seated Barbell Press", group: "Shoulders", sets: 3, reps: "8x" },
    { id: "standing-barbell-press-shoulder", name: "Standing Barbell Press", group: "Shoulders", sets: 3, reps: "8x" },
    
    // ABS - 50 exercises
    { id: "crunches", name: "Crunches", group: "Abs", sets: 3, reps: "20x" },
    { id: "plank-hold", name: "Plank Hold", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "leg-raises", name: "Leg Raises", group: "Abs", sets: 3, reps: "15x" },
    { id: "russian-twists", name: "Russian Twists", group: "Abs", sets: 3, reps: "20x" },
    { id: "mountain-climbers", name: "Mountain Climbers", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "bicycle-crunches", name: "Bicycle Crunches", group: "Abs", sets: 3, reps: "20x" },
    { id: "reverse-crunches", name: "Reverse Crunches", group: "Abs", sets: 3, reps: "15x" },
    { id: "side-plank", name: "Side Plank", group: "Abs", sets: 3, reps: "30 secs each" },
    { id: "hollow-body-hold", name: "Hollow Body Hold", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "v-ups", name: "V-Ups", group: "Abs", sets: 3, reps: "15x" },
    { id: "flutter-kicks", name: "Flutter Kicks", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "scissor-kicks", name: "Scissor Kicks", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "toe-touches", name: "Toe Touches", group: "Abs", sets: 3, reps: "20x" },
    { id: "dead-bug", name: "Dead Bug", group: "Abs", sets: 3, reps: "15x each" },
    { id: "bird-dog-abs", name: "Bird Dog (Abs)", group: "Abs", sets: 3, reps: "10x each" },
    { id: "plank-jacks", name: "Plank Jacks", group: "Abs", sets: 3, reps: "20x" },
    { id: "spider-man-plank", name: "Spider-Man Plank", group: "Abs", sets: 3, reps: "10x each" },
    { id: "bear-crawl", name: "Bear Crawl", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "cable-crunch", name: "Cable Crunch", group: "Abs", sets: 3, reps: "15x" },
    { id: "hanging-leg-raise", name: "Hanging Leg Raise", group: "Abs", sets: 3, reps: "10x" },
    { id: "decline-crunch", name: "Decline Crunch", group: "Abs", sets: 3, reps: "15x" },
    { id: "ab-wheel-rollout", name: "Ab Wheel Rollout", group: "Abs", sets: 3, reps: "10x" },
    { id: "medicine-ball-slam", name: "Medicine Ball Slam", group: "Abs", sets: 3, reps: "15x" },
    { id: "woodchoppers", name: "Woodchoppers", group: "Abs", sets: 3, reps: "12x each" },
    { id: "pallof-press", name: "Pallof Press", group: "Abs", sets: 3, reps: "10x each" },
    { id: "dragon-flag", name: "Dragon Flag", group: "Abs", sets: 3, reps: "8x" },
    { id: "l-sit", name: "L-Sit", group: "Abs", sets: 3, reps: "20 secs" },
    { id: "windshield-wipers", name: "Windshield Wipers", group: "Abs", sets: 3, reps: "10x each" },
    { id: "sit-ups", name: "Sit-ups", group: "Abs", sets: 3, reps: "20x" },
    { id: "lying-leg-raise-abs", name: "Lying Leg Raise", group: "Abs", sets: 3, reps: "15x" },
    { id: "seated-leg-tucks", name: "Seated Leg Tucks", group: "Abs", sets: 3, reps: "15x" },
    { id: "heel-taps", name: "Heel Taps", group: "Abs", sets: 3, reps: "20x" },
    { id: "reverse-plank", name: "Reverse Plank", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "plank-up-down", name: "Plank Up-Down", group: "Abs", sets: 3, reps: "10x each" },
    { id: "side-plank-dips", name: "Side Plank Dips", group: "Abs", sets: 3, reps: "10x each" },
    { id: "side-plank-raise", name: "Side Plank Raise", group: "Abs", sets: 3, reps: "10x each" },
    { id: "bicycle-kicks", name: "Bicycle Kicks", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "standing-cable-crunch", name: "Standing Cable Crunch", group: "Abs", sets: 3, reps: "15x" },
    { id: "kneeling-cable-crunch", name: "Kneeling Cable Crunch", group: "Abs", sets: 3, reps: "15x" },
    { id: "machine-crunch", name: "Machine Crunch", group: "Abs", sets: 3, reps: "15x" },
    { id: "captains-chair", name: "Captain's Chair", group: "Abs", sets: 3, reps: "15x" },
    { id: "roman-chair", name: "Roman Chair", group: "Abs", sets: 3, reps: "15x" },
    { id: "decline-sit-up", name: "Decline Sit-up", group: "Abs", sets: 3, reps: "15x" },
    { id: "medicine-ball-twist", name: "Medicine Ball Twist", group: "Abs", sets: 3, reps: "20x" },
    { id: "medicine-ball-v-up", name: "Medicine Ball V-Up", group: "Abs", sets: 3, reps: "15x" },
    { id: "plate-twist", name: "Plate Twist", group: "Abs", sets: 3, reps: "20x" },
    { id: "plate-side-bend", name: "Plate Side Bend", group: "Abs", sets: 3, reps: "15x each" },
    { id: "resistance-band-crunch", name: "Resistance Band Crunch", group: "Abs", sets: 3, reps: "20x" },
    { id: "banded-woodchopper", name: "Banded Woodchopper", group: "Abs", sets: 3, reps: "12x each" },
    { id: "stability-ball-crunch", name: "Stability Ball Crunch", group: "Abs", sets: 3, reps: "20x" },
    { id: "stability-ball-plank", name: "Stability Ball Plank", group: "Abs", sets: 3, reps: "30 secs" },
    { id: "stability-ball-pike", name: "Stability Ball Pike", group: "Abs", sets: 3, reps: "15x" },
    
    // CARDIO - 50 exercises
    { id: "treadmill", name: "Treadmill", group: "Cardio", sets: 1, reps: "5 mins" },
    { id: "running", name: "Running", group: "Cardio", sets: 1, reps: "10 mins" },
    { id: "jogging", name: "Jogging", group: "Cardio", sets: 1, reps: "15 mins" },
    { id: "sprinting", name: "Sprinting", group: "Cardio", sets: 10, reps: "30 secs" },
    { id: "cycling", name: "Cycling", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "stationary-bike", name: "Stationary Bike", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "elliptical", name: "Elliptical", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "rowing-machine", name: "Rowing Machine", group: "Cardio", sets: 1, reps: "10 mins" },
    { id: "stair-climber", name: "Stair Climber", group: "Cardio", sets: 1, reps: "15 mins" },
    { id: "jump-rope", name: "Jump Rope", group: "Cardio", sets: 5, reps: "1 min" },
    { id: "burpees", name: "Burpees", group: "Cardio", sets: 4, reps: "10x" },
    { id: "high-knees", name: "High Knees", group: "Cardio", sets: 4, reps: "30 secs" },
    { id: "butt-kicks", name: "Butt Kicks", group: "Cardio", sets: 4, reps: "30 secs" },
    { id: "jumping-jacks", name: "Jumping Jacks", group: "Cardio", sets: 5, reps: "1 min" },
    { id: "mountain-climbers-cardio", name: "Mountain Climbers (Cardio)", group: "Cardio", sets: 4, reps: "30 secs" },
    { id: "box-jumps-cardio", name: "Box Jumps (Cardio)", group: "Cardio", sets: 4, reps: "10x" },
    { id: "skipping", name: "Skipping", group: "Cardio", sets: 5, reps: "1 min" },
    { id: "swimming", name: "Swimming", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "jump-squats-cardio", name: "Jump Squats (Cardio)", group: "Cardio", sets: 4, reps: "15x" },
    { id: "lunge-jumps", name: "Lunge Jumps", group: "Cardio", sets: 4, reps: "10x each" },
    { id: "kettlebell-swings", name: "Kettlebell Swings", group: "Cardio", sets: 4, reps: "20x" },
    { id: "battle-ropes", name: "Battle Ropes", group: "Cardio", sets: 4, reps: "30 secs" },
    { id: "speed-skater", name: "Speed Skater", group: "Cardio", sets: 4, reps: "20x" },
    { id: "incline-walking", name: "Incline Walking", group: "Cardio", sets: 1, reps: "15 mins" },
    { id: "hill-sprints", name: "Hill Sprints", group: "Cardio", sets: 8, reps: "30 secs" },
    { id: "stair-running", name: "Stair Running", group: "Cardio", sets: 1, reps: "10 mins" },
    { id: "agility-ladder", name: "Agility Ladder", group: "Cardio", sets: 3, reps: "1 min" },
    { id: "cone-drills", name: "Cone Drills", group: "Cardio", sets: 3, reps: "1 min" },
    { id: "shuttle-run", name: "Shuttle Run", group: "Cardio", sets: 6, reps: "30 secs" },
    { id: "fartlek-training", name: "Fartlek Training", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "interval-running", name: "Interval Running", group: "Cardio", sets: 8, reps: "1 min" },
    { id: "tabata", name: "Tabata", group: "Cardio", sets: 8, reps: "20 secs" },
    { id: "hiit", name: "HIIT", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "circuit-training", name: "Circuit Training", group: "Cardio", sets: 3, reps: "10 mins" },
    { id: "cross-country-skiing", name: "Cross Country Skiing", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "hiking", name: "Hiking", group: "Cardio", sets: 1, reps: "30 mins" },
    { id: "power-walking", name: "Power Walking", group: "Cardio", sets: 1, reps: "30 mins" },
    { id: "dancing", name: "Dancing", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "kickboxing", name: "Kickboxing", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "zumba", name: "Zumba", group: "Cardio", sets: 1, reps: "30 mins" },
    { id: "aerobics", name: "Aerobics", group: "Cardio", sets: 1, reps: "30 mins" },
    { id: "step-aerobics", name: "Step Aerobics", group: "Cardio", sets: 1, reps: "20 mins" },
    { id: "spinning", name: "Spinning", group: "Cardio", sets: 1, reps: "30 mins" },
    { id: "indoor-cycling", name: "Indoor Cycling", group: "Cardio", sets: 1, reps: "30 mins" },
    { id: "outdoor-cycling", name: "Outdoor Cycling", group: "Cardio", sets: 1, reps: "30 mins" },
    { id: "stationary-row", name: "Stationary Row", group: "Cardio", sets: 1, reps: "15 mins" },
    { id: "ski-erg", name: "Ski Erg", group: "Cardio", sets: 1, reps: "10 mins" },
    { id: "assault-bike", name: "Assault Bike", group: "Cardio", sets: 1, reps: "10 mins" },
    { id: "airdyne", name: "Airdyne", group: "Cardio", sets: 1, reps: "10 mins" },
    { id: "versaclimber", name: "Versaclimber", group: "Cardio", sets: 1, reps: "10 mins" },
    { id: "jacobs-ladder", name: "Jacob's Ladder", group: "Cardio", sets: 1, reps: "10 mins" }
];

// Get exercises based on user's gender
function getGenderSpecificExercises() {
    if (userGender === 'Female') {
        // Return female-specific exercises
        return allExercises.filter(ex => 
            !ex.id.includes('heavy') && 
            !ex.name.toLowerCase().includes('heavy') &&
            !ex.name.toLowerCase().includes('power')
        );
    }
    // Return all exercises for Male or Other
    return allExercises;
}

// Get recommended exercises based on user's focus area
function getRecommendedExercises() {
    const exercises = getGenderSpecificExercises();
    return exercises.filter(ex => ex.group === userFocus);
}

// Workout plans data
const workoutPlans = {
    // Target Muscle Plans
    'Arms': {
        'Beginner': {
            title: 'Arms Beginner Plan',
            description: 'A gentle introduction to arm training focusing on form and control.',
            exercises: [
                { name: 'Dumbbell Bicep Curl', sets: 3, reps: '12-15x' },
                { name: 'Tricep Pushdown', sets: 3, reps: '12-15x' },
                { name: 'Hammer Curls', sets: 3, reps: '12x' },
                { name: 'Overhead Tricep Extension', sets: 3, reps: '12x' }
            ],
            duration: '20-30 minutes'
        },
        'Intermediate': {
            title: 'Arms Intermediate Plan',
            description: 'Build arm strength and definition with varied exercises.',
            exercises: [
                { name: 'Barbell Curl', sets: 4, reps: '8-10x' },
                { name: 'Close Grip Bench Press', sets: 4, reps: '8-10x' },
                { name: 'Preacher Curl', sets: 3, reps: '10-12x' },
                { name: 'Skull Crushers', sets: 3, reps: '10-12x' },
                { name: 'Concentration Curl', sets: 3, reps: '12x each arm' }
            ],
            duration: '40-50 minutes'
        },
        'Advanced': {
            title: 'Arms Advanced Plan',
            description: 'Intense arm workout for experienced lifters.',
            exercises: [
                { name: 'Weighted Chin-ups', sets: 4, reps: '6-8x' },
                { name: 'Heavy Barbell Curl', sets: 4, reps: '6-8x' },
                { name: 'Weighted Dips', sets: 4, reps: '8-10x' },
                { name: 'Cable Curl Drop Set', sets: 3, reps: '10-12-15x' },
                { name: 'Tricep Rope Pushdown', sets: 4, reps: '10-12x' }
            ],
            duration: '60+ minutes'
        }
    },
    
    'Chest': {
        'Beginner': {
            title: 'Chest Beginner Plan',
            description: 'Learn proper chest exercise form with machine-based movements.',
            exercises: [
                { name: 'Machine Chest Press', sets: 3, reps: '12-15x' },
                { name: 'Pec Deck Fly', sets: 3, reps: '12-15x' },
                { name: 'Push-ups (Knees)', sets: 3, reps: '10-12x' },
                { name: 'Incline Machine Press', sets: 3, reps: '12x' }
            ],
            duration: '25-35 minutes'
        },
        'Intermediate': {
            title: 'Chest Intermediate Plan',
            description: 'Build chest strength with free weights and compound movements.',
            exercises: [
                { name: 'Barbell Bench Press', sets: 4, reps: '8-10x' },
                { name: 'Incline Dumbbell Press', sets: 4, reps: '10-12x' },
                { name: 'Cable Chest Fly', sets: 3, reps: '12-15x' },
                { name: 'Dips', sets: 3, reps: '10-12x' },
                { name: 'Push-ups', sets: 3, reps: '15-20x' }
            ],
            duration: '45-55 minutes'
        },
        'Advanced': {
            title: 'Chest Advanced Plan',
            description: 'Advanced chest training for maximum strength.',
            exercises: [
                { name: 'Heavy Bench Press', sets: 5, reps: '3-5x' },
                { name: 'Incline Barbell Press', sets: 4, reps: '6-8x' },
                { name: 'Weighted Dips', sets: 4, reps: '8-10x' },
                { name: 'Decline Bench Press', sets: 3, reps: '8-10x' },
                { name: 'Cable Crossover', sets: 3, reps: '12-15x' }
            ],
            duration: '60-75 minutes'
        }
    },
    
    'Legs': {
        'Beginner': {
            title: 'Legs Beginner Plan',
            description: 'Foundation leg workout focusing on proper form and mobility.',
            exercises: [
                { name: 'Bodyweight Squats', sets: 3, reps: '15-20x' },
                { name: 'Leg Press', sets: 3, reps: '12-15x' },
                { name: 'Leg Extension', sets: 3, reps: '12-15x' },
                { name: 'Leg Curl', sets: 3, reps: '12-15x' },
                { name: 'Standing Calf Raise', sets: 3, reps: '15-20x' }
            ],
            duration: '30-40 minutes'
        },
        'Intermediate': {
            title: 'Legs Intermediate Plan',
            description: 'Comprehensive leg workout for strength and muscle development.',
            exercises: [
                { name: 'Barbell Back Squat', sets: 4, reps: '8-10x' },
                { name: 'Romanian Deadlift', sets: 3, reps: '10-12x' },
                { name: 'Walking Lunges', sets: 3, reps: '12x each leg' },
                { name: 'Leg Press', sets: 4, reps: '10-12x' },
                { name: 'Seated Calf Raise', sets: 4, reps: '15-20x' }
            ],
            duration: '50-60 minutes'
        },
        'Advanced': {
            title: 'Legs Advanced Plan',
            description: 'Intense leg workout for advanced athletes.',
            exercises: [
                { name: 'Heavy Squats', sets: 5, reps: '3-5x' },
                { name: 'Front Squats', sets: 4, reps: '6-8x' },
                { name: 'Bulgarian Split Squats', sets: 3, reps: '8-10x each leg' },
                { name: 'Leg Press', sets: 4, reps: '10-12x' },
                { name: 'Stiff-Leg Deadlift', sets: 3, reps: '8-10x' }
            ],
            duration: '70-85 minutes'
        }
    },
    
    'Full Body': {
        'Beginner': {
            title: 'Full Body Beginner Plan',
            description: 'Complete full-body workout perfect for starting your fitness journey.',
            exercises: [
                { name: 'Bodyweight Squats', sets: 3, reps: '15x' },
                { name: 'Push-ups (Modified)', sets: 3, reps: '10x' },
                { name: 'Bent-over Rows', sets: 3, reps: '12x' },
                { name: 'Plank', sets: 3, reps: '30 secs' },
                { name: 'Glute Bridges', sets: 3, reps: '15x' }
            ],
            duration: '30-40 minutes'
        },
        'Intermediate': {
            title: 'Full Body Intermediate Plan',
            description: 'Balanced full-body workout for overall strength and fitness.',
            exercises: [
                { name: 'Barbell Squat', sets: 4, reps: '8-10x' },
                { name: 'Bench Press', sets: 4, reps: '8-10x' },
                { name: 'Bent-over Barbell Row', sets: 4, reps: '8-10x' },
                { name: 'Overhead Press', sets: 3, reps: '10-12x' },
                { name: 'Deadlift', sets: 3, reps: '8-10x' }
            ],
            duration: '60-70 minutes'
        },
        'Advanced': {
            title: 'Full Body Advanced Plan',
            description: 'High-intensity full-body workout for experienced athletes.',
            exercises: [
                { name: 'Heavy Deadlift', sets: 5, reps: '3-5x' },
                { name: 'Squats', sets: 5, reps: '5-8x' },
                { name: 'Bench Press', sets: 5, reps: '5-8x' },
                { name: 'Pull-ups', sets: 4, reps: '8-10x' },
                { name: 'Military Press', sets: 4, reps: '6-8x' }
            ],
            duration: '75-90 minutes'
        }
    }
};

// Goal-based workout plans
const goalPlans = {
    'Lose Weight': {
        'title': 'Weight Loss Program',
        'description': 'High-intensity workouts focusing on calorie burn.',
        'weeklyPlan': [
            {
                'focus': 'Cardio & Core',
                'exercises': [
                    { name: 'Treadmill Running', sets: 1, reps: '20 mins' },
                    { name: 'Burpees', sets: 4, reps: '15x' },
                    { name: 'Mountain Climbers', sets: 3, reps: '30 secs' },
                    { name: 'Jumping Jacks', sets: 4, reps: '1 min' },
                    { name: 'Plank', sets: 3, reps: '45 secs' }
                ]
            },
            {
                'focus': 'Full Body HIIT',
                'exercises': [
                    { name: 'Kettlebell Swings', sets: 4, reps: '20x' },
                    { name: 'Box Jumps', sets: 4, reps: '15x' },
                    { name: 'Battle Ropes', sets: 3, reps: '30 secs' },
                    { name: 'Rowing Machine', sets: 1, reps: '10 mins' }
                ]
            }
        ]
    },
    
    'Gain Muscle': {
        'title': 'Muscle Building Program',
        'description': 'Progressive overload focused workouts for muscle growth.',
        'weeklyPlan': [
            {
                'focus': 'Chest & Triceps',
                'exercises': [
                    { name: 'Bench Press', sets: 5, reps: '6-8x' },
                    { name: 'Incline Dumbbell Press', sets: 4, reps: '8-10x' },
                    { name: 'Cable Fly', sets: 3, reps: '12-15x' },
                    { name: 'Tricep Pushdown', sets: 4, reps: '10-12x' },
                    { name: 'Overhead Tricep Extension', sets: 3, reps: '12x' }
                ]
            },
            {
                'focus': 'Back & Biceps',
                'exercises': [
                    { name: 'Deadlift', sets: 4, reps: '5-6x' },
                    { name: 'Pull-ups', sets: 4, reps: '8-10x' },
                    { name: 'Barbell Row', sets: 4, reps: '8-10x' },
                    { name: 'Barbell Curl', sets: 4, reps: '8-10x' },
                    { name: 'Hammer Curl', sets: 3, reps: '10-12x' }
                ]
            }
        ]
    },
    
    'Stay Fit': {
        'title': 'Fitness Maintenance Program',
        'description': 'Balanced workouts to maintain fitness and health.',
        'weeklyPlan': [
            {
                'focus': 'Cardio & Strength',
                'exercises': [
                    { name: 'Treadmill', sets: 1, reps: '25 mins' },
                    { name: 'Bodyweight Squats', sets: 3, reps: '15x' },
                    { name: 'Push-ups', sets: 3, reps: '15x' },
                    { name: 'Plank', sets: 3, reps: '45 secs' },
                    { name: 'Lunges', sets: 3, reps: '12x each leg' }
                ]
            }
        ]
    },
    
    'Maintain': {
        'title': 'Maintenance Program',
        'description': 'Workouts focused on maintaining current fitness level.',
        'weeklyPlan': [
            {
                'focus': 'Maintenance Workout',
                'exercises': [
                    { name: 'Moderate Cardio', sets: 1, reps: '30 mins' },
                    { name: 'Full Body Strength', sets: 3, reps: '12x each' },
                    { name: 'Core Exercises', sets: 3, reps: '15x' }
                ]
            }
        ]
    }
};

function markRoutineAsModified() {
    const saveButton = document.getElementById('saveRoutineButton');
    // Remove default styles
    saveButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
    // Add warning/modification styles
    saveButton.classList.add('bg-yellow-600', 'hover:bg-yellow-700', 'animate-pulse');
}

function resetSaveButton() {
    const saveButton = document.getElementById('saveRoutineButton');
    // Reset to default styles
    saveButton.classList.remove('bg-yellow-600', 'hover:bg-yellow-700', 'animate-pulse');
    saveButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
}

function showDay(day) {
    // Check if day is available in selected training days
    if (!selectedTrainingDays.includes(day)) {
        console.log('Day not available:', day);
        return;
    }
    
    console.log('Showing day:', day);
    
    // 1. Visually switch the day tab
    document.querySelectorAll(".day-tab").forEach(tab => tab.classList.remove('active-day'));
    const dayTabs = document.querySelectorAll(`.day-tab`);
    dayTabs.forEach(tab => {
        if (tab.textContent.trim() === day) {
            tab.classList.add('active-day');
        }
    });
    
    // 2. Visually switch the day view
    document.querySelectorAll(".day-view").forEach(view => view.classList.add("hidden"));
    const dayView = document.getElementById(day);
    if (dayView) {
        dayView.classList.remove("hidden");
        currentDay = day;
        
        // 3. Load exercises for the newly selected day
        loadExercisesForDay(day);
    }
}

// UPDATED: Function to load saved workouts from database
function loadExercisesForDay(day) {
    const exerciseList = document.querySelector(`#${day} .exercises-list`);
    
    if (!exerciseList) {
        console.error('Exercise list not found for day:', day);
        return;
    }
    
    // Reset and show loading message
    resetSaveButton();
    
    // Clear the list immediately before loading
    exerciseList.innerHTML = '<li class="p-3 text-gray-400 text-center">Loading your workout...</li>';
    
    // Clear the JS array for this day
    dayExercises[day] = [];

    // Add a timestamp to prevent caching
    const timestamp = new Date().getTime();
    
    fetch(`load_workout.php?day=${day}&t=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Loaded data for', day, ':', data);
            
            // Clear the list completely before adding new items
            exerciseList.innerHTML = '';

            if (data.success) {
                // Load description if exists
                const descriptionField = document.getElementById(`description-${day}`);
                if (descriptionField && data.description) {
                    descriptionField.value = data.description;
                }
                
                if (data.exercises && data.exercises.length > 0) {
                    dayExercises[day] = data.exercises; 
                    
                    data.exercises.forEach(ex => {
                        exerciseList.appendChild(createExerciseItem(ex));
                    });
                    
                    // Setup drag and drop for this day's list
                    setupDragAndDrop(exerciseList, day);
                } else {
                    exerciseList.innerHTML = '<li class="empty-list-placeholder p-3 text-gray-500 text-center">No exercises saved for this day. Click "Browse Workout" or "Add Exercise" to begin.</li>';
                }
            } else {
                exerciseList.innerHTML = `<li class="p-3 text-red-500 text-center">Error loading data: ${data.message}</li>`;
                console.error('Server reported error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching workout:', error);
            exerciseList.innerHTML = `<li class="p-3 text-red-500 text-center">Connection Error. Please refresh the page.</li>`;
        });
}

// TOAST Notification
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

// Function to save workout routine to database
function saveWorkoutRoutine() {
    // Get description for current day
    const description = document.getElementById(`description-${currentDay}`)?.value || '';
    
    // Reorder exercises based on current DOM order
    const exerciseList = document.querySelector(`#${currentDay} .exercises-list`);
    if (!exerciseList) {
        showToast('error', 'No exercises to save.');
        return;
    }
    
    const items = exerciseList.querySelectorAll('.exercise-item');
    const reorderedExercises = [];
    
    items.forEach(li => {
        const id = li.id;
        const found = dayExercises[currentDay].find(e => e.id === id);
        if (found) {
            reorderedExercises.push(found);
        }
    });
    
    // Update the dayExercises array with new order
    dayExercises[currentDay] = reorderedExercises;
    
    const dataToSave = {
        day: currentDay, 
        description: description,
        exercises: reorderedExercises.map(ex => ({
            name: ex.name,
            sets: ex.sets,
            reps: ex.reps
        }))
    };

    console.log('Saving data for user ID:', currentUserId);

    fetch('save_workout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dataToSave)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Save response:', data);
        if (data.success) {
            showToast('success', 'Workout Journal updated!');
            resetSaveButton(); 
        } else {
            showToast('error', 'Error has occured while saving changes.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'A network error occurred while communicating with the server.');
    });
}

// DRAG AND DROP FUNCTIONALITY
function setupDragAndDrop(listContainer, day) {
    if (!listContainer) return;
    
    // Clear any existing event listeners by cloning and replacing
    const newContainer = listContainer.cloneNode(false);
    while (listContainer.firstChild) {
        newContainer.appendChild(listContainer.firstChild);
    }
    listContainer.parentNode.replaceChild(newContainer, listContainer);
    
    // Get the new reference
    const container = newContainer;
    
    container.addEventListener("dragstart", (e) => {
        if (e.target.classList.contains("exercise-item")) {
            draggedItem = e.target;
            setTimeout(() => {
                draggedItem.classList.add("dragging");
            }, 0);
            markRoutineAsModified();
        }
    });

    container.addEventListener("dragend", () => {
        if (draggedItem) {
            draggedItem.classList.remove("dragging");
            draggedItem = null;
        }
    });

    container.addEventListener("dragover", (e) => {
        e.preventDefault();
        if (!draggedItem) return;
        
        const afterElement = getDragAfterElement(container, e.clientY);
        
        if (afterElement == null) {
            container.appendChild(draggedItem);
        } else {
            container.insertBefore(draggedItem, afterElement);
        }
    });
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll(".exercise-item:not(.dragging)")];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Exercise Item Creation with Drag Support and BOXICONS
function createExerciseItem(ex) {
    const li = document.createElement("li");
    li.className = "exercise-item";
    li.id = ex.id;
    li.setAttribute("draggable", "true");
    li.setAttribute("role", "listitem");

    // Check if exercise is recommended for user's focus
    if (ex.group === userFocus) {
        li.classList.add('recommended-workout');
    }

    // Drag handle
    const handle = document.createElement("div");
    handle.className = "draggable-handle";
    handle.setAttribute("aria-label", "Drag to reorder");
    handle.tabIndex = 0;
    
    for (let i = 0; i < 3; i++) {
        const span = document.createElement("span");
        handle.appendChild(span);
    }

    // Thumbnail with BOXICONS
    const thumb = document.createElement("div");
    thumb.className = "exercise-thumb";
    thumb.innerHTML = `<i class='bx bx-dumbbell text-gray-600 text-xl'></i>`;

    // Content
    const content = document.createElement("div");
    content.className = "exercise-content";

    const name = document.createElement("div");
    name.className = "exercise-name";
    name.textContent = ex.name;
    
    // Add recommended badge if applicable
    if (ex.group === userFocus) {
        const badge = document.createElement("span");
        badge.className = "text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded ml-2";
        badge.textContent = "Recommended";
        name.appendChild(badge);
    }

    const info = document.createElement("div");
    info.className = "exercise-info";

    const sets = document.createElement("span");
    sets.innerHTML = `<i class='bx bx-repeat'></i> Sets: ${ex.sets || '-'}`;

    const reps = document.createElement("span");
    reps.innerHTML = `<i class='bx bx-timer'></i> ${ex.reps || '-'}`;

    info.appendChild(sets);
    info.appendChild(reps);

    content.appendChild(name);
    content.appendChild(info);

    // Arrow indicator
    const arrow = document.createElement("div");
    arrow.className = "exercise-arrow";
    arrow.textContent = "↕";

    // Action buttons (edit/delete) with BOXICONS
    const actions = document.createElement("div");
    actions.className = "exercise-actions";
    actions.innerHTML = `
        <button class="text-blue-500 hover:text-blue-700 transition-colors" onclick="openEditModal('${ex.id}')" title="Edit">
            <i class='bx bx-edit text-xl'></i>
        </button>
        <button class="text-red-500 hover:text-red-700 transition-colors" onclick="deleteExercise('${ex.id}')" title="Delete">
            <i class='bx bx-trash text-xl'></i>
        </button>
    `;

    // Assemble the exercise item
    li.appendChild(handle);
    li.appendChild(thumb);
    li.appendChild(content);
    li.appendChild(actions);
    li.appendChild(arrow);

    return li;
}

function deleteExercise(id) {
    dayExercises[currentDay] = dayExercises[currentDay].filter(e => e.id !== id);
    document.getElementById(id)?.remove();
    markRoutineAsModified();
}

// --- MODAL & FORM HANDLERS ---

function browseWorkout() {
    const modal = document.getElementById("browseWorkoutModal");
    modal.showModal();
    
    // Load recommended exercises first
    switchBrowseTab('exercises');
    renderExerciseList(getGenderSpecificExercises());
    
    // Highlight recommended exercises
    setTimeout(() => {
        const recommended = getRecommendedExercises();
        if (recommended.length > 0) {
            recommended.forEach(ex => {
                const checkbox = document.querySelector(`#browse-${ex.id}`);
                if (checkbox) {
                    const item = checkbox.closest('div');
                    item.classList.add('recommended-workout');
                }
            });
        }
    }, 100);
}

function switchBrowseTab(tabName) {
    // Update active tab
    document.querySelectorAll('.browse-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.browse-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Activate selected tab
    const activeTab = Array.from(document.querySelectorAll('.browse-tab')).find(tab => 
        tab.textContent.includes(tabName.charAt(0).toUpperCase() + tabName.slice(1).replace('-', ' '))
    );
    if (activeTab) activeTab.classList.add('active');
    
    // Show corresponding content
    const contentId = `${tabName.replace('-', '-')}-tab`;
    const content = document.getElementById(contentId);
    if (content) content.classList.add('active');
    
    // Load content based on tab
    if (tabName === 'target-muscle') {
        showMusclePlans('Arms');
    } else if (tabName === 'goal-plans') {
        showGoalPlans(userGoal);
    }
}

function showMusclePlans(muscleGroup) {
    const container = document.getElementById('muscle-plans-container');
    const plans = workoutPlans[muscleGroup];
    
    if (!plans) {
        container.innerHTML = '<p class="text-gray-500">No plans available for this muscle group.</p>';
        return;
    }
    
    let html = `<h4 class="font-semibold text-lg mb-3">${muscleGroup} Workout Plans</h4>`;
    
    // Filter buttons for difficulty
    html += `
        <div class="filter-buttons mb-4">
            <button class="filter-button active" onclick="filterMusclePlans('all')">All Levels</button>
            <button class="filter-button" onclick="filterMusclePlans('Beginner')">Beginner</button>
            <button class="filter-button" onclick="filterMusclePlans('Intermediate')">Intermediate</button>
            <button class="filter-button" onclick="filterMusclePlans('Advanced')">Advanced</button>
        </div>
        <div class="workout-plan-grid" id="muscle-plan-grid">
    `;
    
    Object.keys(plans).forEach(level => {
        const plan = plans[level];
        const isRecommended = (muscleGroup === userFocus);
        
        html += `
            <div class="workout-plan-card ${isRecommended ? 'recommended-workout' : ''}" data-level="${level}">
                <div class="difficulty-badge ${level.toLowerCase()}-badge">${level}</div>
                <h5 class="font-bold text-lg mb-2">${plan.title}</h5>
                <p class="text-gray-600 text-sm mb-3">${plan.description}</p>
                <p class="text-gray-500 text-xs mb-3"><i class='bx bx-time'></i> Duration: ${plan.duration}</p>
                <div class="mb-3">
                    <h6 class="font-semibold text-sm mb-1">Exercises:</h6>
                    <ul class="text-sm text-gray-700 space-y-1">
                        ${plan.exercises.map(ex => `<li>${ex.name} - ${ex.sets} sets × ${ex.reps}</li>`).join('')}
                    </ul>
                </div>
                <button onclick="addWorkoutPlan('${muscleGroup}', '${level}')" 
                        class="w-full bg-black text-white py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm">
                    Add to Current Day
                </button>
            </div>
        `;
    });
    
    html += `</div>`;
    container.innerHTML = html;
    
    // Update filter buttons
    document.querySelectorAll('#muscle-plans-container .filter-button').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#muscle-plans-container .filter-button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

function filterMusclePlans(level) {
    const planCards = document.querySelectorAll('#muscle-plan-grid .workout-plan-card');
    
    planCards.forEach(card => {
        if (level === 'all' || card.dataset.level === level) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Function to show goal plans
function showGoalPlans(goal) {
    const container = document.getElementById('goal-plans-container');
    const plan = goalPlans[goal];
    
    if (!plan) {
        container.innerHTML = '<p class="text-gray-500">No plans available for this goal.</p>';
        return;
    }
    
    const isUserGoal = (goal === userGoal);
    
    let html = `
        <div class="${isUserGoal ? 'border-l-4 border-yellow-500 pl-4' : ''}">
            <h4 class="font-semibold text-lg mb-2">${plan.title}</h4>
            <p class="text-gray-600 mb-4">${plan.description}</p>
            ${isUserGoal ? '<p class="text-yellow-600 text-sm mb-4"><i class="bx bx-star"></i> This matches your selected goal</p>' : ''}
            
            <div class="space-y-4">
    `;
    
    plan.weeklyPlan.forEach((dayPlan, index) => {
        html += `
            <div class="bg-gray-50 p-4 rounded-lg">
                <h5 class="font-bold text-md mb-2">${dayPlan.focus}</h5>
                <div class="space-y-2">
                    ${dayPlan.exercises.map(ex => `
                        <div class="flex justify-between items-center bg-white p-2 rounded">
                            <span>${ex.name}</span>
                            <span class="text-sm text-gray-600">${ex.sets} sets × ${ex.reps}</span>
                        </div>
                    `).join('')}
                </div>
                <button onclick="addGoalPlanDay('${goal}', ${index})" 
                        class="mt-3 w-full bg-black text-white py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm">
                    Add ${dayPlan.focus} to Current Day
                </button>
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function addWorkoutPlan(muscleGroup, level) {
    const plan = workoutPlans[muscleGroup][level];
    if (!plan) return;
    
    const exerciseList = document.querySelector(`#${currentDay} .exercises-list`);
    if (!exerciseList) {
        showToast('error', 'Cannot add exercises. Please select a valid day.');
        return;
    }
    
    // Remove placeholder if exists
    const placeholder = exerciseList.querySelector('.empty-list-placeholder');
    if (placeholder) {
        placeholder.remove();
    }
    
    plan.exercises.forEach(ex => {
        const newExercise = {
            id: `plan-${muscleGroup.toLowerCase()}-${level.toLowerCase()}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            name: ex.name,
            group: muscleGroup,
            sets: ex.sets,
            reps: ex.reps
        };
        
        dayExercises[currentDay].push(newExercise);
        exerciseList.appendChild(createExerciseItem(newExercise));
    });
    
    setupDragAndDrop(exerciseList, currentDay);
    markRoutineAsModified();
    showToast('success', `Added ${plan.title} to ${currentDay}`);
}

function addGoalPlanDay(goal, dayIndex) {
    const plan = goalPlans[goal];
    if (!plan) return;
    
    const dayPlan = plan.weeklyPlan[dayIndex];
    if (!dayPlan) return;
    
    const exerciseList = document.querySelector(`#${currentDay} .exercises-list`);
    if (!exerciseList) {
        showToast('error', 'Cannot add exercises. Please select a valid day.');
        return;
    }
    
    // Remove placeholder if exists
    const placeholder = exerciseList.querySelector('.empty-list-placeholder');
    if (placeholder) {
        placeholder.remove();
    }
    
    dayPlan.exercises.forEach(ex => {
        const newExercise = {
            id: `goal-${goal.toLowerCase()}-${dayIndex}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
            name: ex.name,
            group: 'Full Body',
            sets: ex.sets,
            reps: ex.reps
        };
        
        dayExercises[currentDay].push(newExercise);
        exerciseList.appendChild(createExerciseItem(newExercise));
    });
    
    setupDragAndDrop(exerciseList, currentDay);
    markRoutineAsModified();
    showToast('success', `Added ${dayPlan.focus} workout to ${currentDay}`);
}

function filterByGroup(group) {
    const exercises = getGenderSpecificExercises();
    const filtered = group === 'all' ? exercises : exercises.filter(ex => ex.group === group);
    
    renderExerciseList(filtered);
    
    // Update active filter button
    document.querySelectorAll('.filter-button').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === group || (group === 'all' && btn.textContent === 'All')) {
            btn.classList.add('active');
        }
    });
}

function renderExerciseList(exercises) {
    const container = document.getElementById("browseExerciseList");
    container.innerHTML = "";
    
    const groups = [...new Set(exercises.map(ex => ex.group))];
    groups.forEach(group => {
        const groupLabel = document.createElement("h4");
        groupLabel.className = "font-semibold text-gray-700 mt-3";
        groupLabel.textContent = group;
        container.appendChild(groupLabel);
        
        exercises.filter(ex => ex.group === group).forEach(ex => {
            const isRecommended = (ex.group === userFocus);
            const div = document.createElement("div");
            div.className = `flex items-center gap-2 p-2 hover:bg-gray-50 rounded ${isRecommended ? 'recommended-workout' : ''}`;
            div.innerHTML = `
                <input type="checkbox" id="browse-${ex.id}" value="${ex.id}" class="rounded text-yellow-500 focus:ring-yellow-500">
                <label for="browse-${ex.id}" class="flex-grow cursor-pointer">
                    <strong class="text-gray-800">${ex.name}</strong>
                    ${isRecommended ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded ml-2">Recommended</span>' : ''}
                    <br>
                    <small class="text-gray-600">Sets: ${ex.sets}, Reps: ${ex.reps}</small>
                </label>`;
            container.appendChild(div);
        });
    });
}

function filterExercises() {
    const query = document.getElementById("searchInput").value.toLowerCase();
    const exercises = getGenderSpecificExercises();
    const filtered = exercises.filter(ex =>
        ex.name.toLowerCase().includes(query) || ex.group.toLowerCase().includes(query)
    );
    renderExerciseList(filtered);
}

function addSelectedExercises() {
    const selected = [...document.querySelectorAll("#browseExerciseList input:checked")];
    const exerciseList = document.querySelector(`#${currentDay} .exercises-list`);

    if (!exerciseList) {
        showToast('error', 'Please select a valid day first.');
        return;
    }

    if(selected.length > 0) {
        // Remove placeholder instantly when an exercise is added from browse
        const placeholder = exerciseList.querySelector('.empty-list-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        selected.forEach(cb => {
            const ex = getGenderSpecificExercises().find(s => s.id === cb.value);
            if (ex) {
                // Ensure a unique ID for exercises added from browse
                const newItem = { 
                    ...ex, 
                    id: "browse-" + Date.now() + "-" + Math.random().toString(36).substr(2, 9) 
                }; 
                dayExercises[currentDay].push(newItem);
                exerciseList.appendChild(createExerciseItem(newItem));
            }
        });
        
        // Setup drag and drop for the newly added items
        setupDragAndDrop(exerciseList, currentDay);
        
        markRoutineAsModified(); // Mark as modified after adding from browse
        
        document.getElementById("browseWorkoutModal").close();
        showToast('success', `Added ${selected.length} exercise(s) to ${currentDay}`);
    } else {
        alert('Please select at least one exercise to add.');
    }
}

function openEditModal(id) {
    const ex = dayExercises[currentDay].find(e => e.id === id);
    if (!ex) return;
    document.getElementById("editExerciseId").value = id;
    document.getElementById("editExerciseName").value = ex.name;
    document.getElementById("editExerciseSets").value = ex.sets;
    document.getElementById("editExerciseReps").value = ex.reps;
    document.getElementById("editExerciseModal").showModal();
}

// Event Listeners for CRUD operations
document.getElementById("editExerciseForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const id = document.getElementById("editExerciseId").value;
    const name = document.getElementById("editExerciseName").value;
    const sets = document.getElementById("editExerciseSets").value;
    const reps = document.getElementById("editExerciseReps").value;
    const index = dayExercises[currentDay].findIndex(e => e.id === id);
    
    if (index !== -1) {
        const updated = { ...dayExercises[currentDay][index], name, sets, reps }; 
        dayExercises[currentDay][index] = updated;
        document.getElementById(id)?.replaceWith(createExerciseItem(updated)); 
        markRoutineAsModified(); // Mark as modified after edit
    }
    document.getElementById("editExerciseModal").close();
});

document.querySelector("#addExerciseModal form").addEventListener("submit", function (e) {
    e.preventDefault();
    const name = document.getElementById("routineName").value.trim();
    const sets = document.getElementById("exerciseSets").value.trim();
    const reps = document.getElementById("exerciseReps").value.trim();
    if (!name) return alert("Routine name is required.");

    const newExercise = {
        id: "custom-" + Date.now(),
        name,
        sets: sets || 3,
        reps: reps || "12x",
        group: "Custom"
    };

    const exerciseList = document.querySelector(`#${currentDay} .exercises-list`);
    if (!exerciseList) {
        alert('Please select a valid day first.');
        return;
    }
    
    // Remove placeholder instantly when an exercise is added
    const placeholder = exerciseList.querySelector('.empty-list-placeholder');
    if (placeholder) {
        placeholder.remove();
    }

    dayExercises[currentDay].push(newExercise);
    exerciseList.appendChild(createExerciseItem(newExercise));
    
    // Setup drag and drop for the new items
    setupDragAndDrop(exerciseList, currentDay);
    
    document.getElementById('addExerciseModal').close();
    
    // Clear form fields
    this.reset();
    markRoutineAsModified(); // Mark as modified after add
});

document.getElementById('start-button').addEventListener('click', () => {
    const exercisesToSend = dayExercises[currentDay] || [];
    localStorage.setItem('selectedExercises', JSON.stringify(exercisesToSend));
    window.location.href = "WorkoutViewerMember.php";
});
</script>
</body>
</html>