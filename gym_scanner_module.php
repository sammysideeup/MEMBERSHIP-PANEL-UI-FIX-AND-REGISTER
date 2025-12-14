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
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $current_user_id = $row['id'];
} else {
    session_destroy();
    header("Location: Loginpage.php");
    exit();
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Equipment AR Scanner</title>
    
    <!-- Design System Stylesheets -->
    <link rel="stylesheet" href="Memberstyle.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* ===== DESIGN SYSTEM COLORS ===== */
        :root {
            --color-black: #000000;
            --color-yellow: #FFD700;
            --color-white: #FFFFFF;
            --color-dark-yellow: #e6c200;
            --color-dark-gray: #333333;
        }

        /* ===== APPLY COLOR PALETTE ===== */
        
        /* Sidebar - Use Memberstyle.css styling */
        .sidebar {
            background-color: var(--color-black);
            color: var(--color-white);
        }
        
        .sidebar h2 {
            color: var(--color-yellow);
            border-bottom: 2px solid #333333;
        }
        
        .sidebar ul li a {
            color: var(--color-white);
        }
        
        .sidebar ul li a i {
            color: var(--color-yellow);
        }
        
        .sidebar ul li:hover {
            background: var(--color-dark-gray);
        }
        
        .sidebar ul li a.bg-gray-700 {
            background-color: #1a1a1a !important;
            color: var(--color-yellow) !important;
        }
        
        .sidebar .submenu {
            background-color: rgba(51, 51, 51, 0.9);
        }

        /* Mobile Navbar */
        .mobile-navbar {
            background-color: var(--color-black);
            color: var(--color-white);
        }
        
        .navbar-brand h2 {
            color: var(--color-yellow);
        }
        
        .navbar-toggle {
            color: var(--color-yellow);
        }
        
        .navbar-menu {
            background-color: var(--color-black);
        }
        
        .navbar-menu ul li a {
            color: var(--color-white);
        }
        
        .navbar-menu ul li a i {
            color: var(--color-yellow);
        }
        
        .navbar-menu ul li a:hover,
        .navbar-menu ul li a.active {
            background-color: var(--color-dark-gray);
            color: var(--color-yellow);
        }

        /* Button colors */
        #start-button {
            background-color: var(--color-black);
            color: var(--color-white);
            border: none;
        }
        
        #start-button:hover:not(:disabled) {
            background-color: var(--color-dark-gray);
        }
        
        #start-button:disabled {
            background-color: #9ca3af;
            color: var(--color-white);
        }

        /* Status message */
        #status-message {
            border: 2px solid var(--color-yellow);
            background-color: rgba(255, 215, 0, 0.1);
            color: #856404;
        }

        /* AR Info Box - HIDDEN INITIALLY */
        #ar-info {
            background-color: var(--color-white);
            color: var(--color-black);
            border: 2px solid #e5e5e5;
            display: none; /* HIDDEN BY DEFAULT */
        }
        
        #ar-info.visible {
            display: block;
            animation: fadeInUp 0.5s ease forwards;
        }
        
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
        
        #ar-info h2 i {
            color: var(--color-yellow);
        }
        
        #equipment-display-name {
            color: var(--color-black);
        }

        /* Camera container */
        #webcam-container {
            background-color: var(--color-black);
            border: 3px solid var(--color-yellow);
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.4);
        }

        /* ===== LAYOUT FIXES ===== */
        
        /* Center button on desktop */
        @media (min-width: 1025px) {
            #start-button-container {
                display: flex;
                justify-content: center;
                width: 100%;
            }
            
            #start-button {
                width: auto;
                min-width: 200px;
                padding: 0.875rem 2rem;
            }
        }
        
        /* Main content adjustments */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ===== MOBILE RESPONSIVENESS ===== */
        
        @media (max-width: 1024px) {
            .main-content {
                margin-top: 80px;
                margin-left: 0;
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            #webcam-container {
                aspect-ratio: 4/3;
                max-width: 100%;
            }
            
            #start-button {
                width: 100%;
                max-width: 280px;
                margin: 1.25rem auto;
            }
        }
        
        @media (max-width: 480px) {
            #webcam-container {
                min-height: 250px;
                max-height: 300px;
            }
            
            #start-button {
                padding: 0.75rem 1.25rem;
                max-width: 260px;
            }
        }

        /* ===== PRESERVE ORIGINAL STRUCTURE ===== */
        
        .app-container {
            display: flex;
            min-height: 100vh;
            background-color: #f3f4f6;
        }

        .sidebar {
            width: 250px;
            padding-top: 20px;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.6);
            flex-shrink: 0;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
        }

        .sidebar li i {
            margin-right: 12px;
            font-size: 1.4rem;
        }
        
        .submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease-in-out;
        }

        .submenu li a {
            padding-left: 50px;
            font-size: 0.9rem;
        }
        
        .toggle-icon {
            margin-left: auto;
            transition: transform 0.3s ease;
        }

        /* Camera container */
        #webcam-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin: auto;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            aspect-ratio: 800 / 600;
        }
        
        canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* Status message */
        #status-message {
            text-align: center;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }

        /* AR Info Box */
        #ar-info {
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 1rem;
            text-align: left;
            width: 100%;
            max-width: 800px;
        }

        #equipment-display-name {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        #detected-item-name {
            color: #666666;
            font-style: italic;
        }

        #recommended-exercise {
            font-weight: 600;
            color: var(--color-black);
        }

        #ar-info ul {
            margin-top: 0.5rem;
            padding-left: 1rem;
        }

        #ar-info ul li {
            margin-bottom: 0.25rem;
        }

        /* Start Button */
        #start-button {
            padding: 0.875rem 1.75rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Hide sidebar on mobile, show navbar */
        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }
            
            .mobile-navbar {
                display: block;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">

<!-- Mobile Top Navbar -->
<nav class="mobile-navbar" id="mobileNavbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <i class='bx bx-qr-scan text-yellow-500 text-2xl'></i>
            <h2>Equipment Scanner</h2>
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
                    <li><a href="CalorieScanner.php"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                    <li><a href="#" class="active"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
                </ul>
            </li>
            <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Desktop Sidebar - Using Memberstyle.css classes -->
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
                <li><a href="CalorieScanner.php"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                <li><a href="#" class="bg-gray-700"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
            </ul>
        </li>
        <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="w-full max-w-3xl bg-white p-6 md:p-8 rounded-2xl shadow-xl">
        <h1 class="text-3xl font-bold text-black text-center mb-6">Equipment Recognition Scanner</h1>
        
        <div id="webcam-container" class="mb-6">
            <div id="webcam" class="w-full rounded-xl"></div>
        </div>

        <div id="status-message" class="text-center p-3 mb-4 rounded-lg font-semibold">
            Ready! Click 'Start Recognition' and allow camera access.
        </div>
        
        <!-- AR Info Box - HIDDEN INITIALLY -->
        <div id="ar-info" class="hidden">
            <h2 class="font-bold text-xl mb-2 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Equipment Details & Instructions
            </h2>
            
            <p class="mb-1"><strong>Detected Class:</strong> <span id="detected-item-name"></span></p>
            
            <p class="mb-2 text-xl font-bold">Equipment Name: <span id="equipment-display-name"></span></p>
            
            <p class="font-medium text-lg text-black">Recommended Exercise: <span id="recommended-exercise" class="font-extrabold"></span></p>
            
            <p class="text-sm mt-3 font-semibold border-t pt-2 border-gray-300">Execution Instructions:</p>
            <ul class="list-disc list-inside mt-2 space-y-1 text-sm">
                <!-- Instructions will be populated here -->
            </ul>
        </div>

        <div id="label-container" class="mt-4">
        </div>

        <!-- Button Container for Centering -->
        <div id="start-button-container" class="mt-6 text-center">
            <button id="start-button" onclick="init()"
                class="py-3 px-8 bg-black text-white font-bold rounded-xl shadow-lg hover:bg-gray-800 transition duration-300">
                <i class='bx bx-camera'></i>
                Start Recognition
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script> 

<script>
    // ====== MOBILE NAVIGATION TOGGLE ======
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
            
            // Close menu when clicking outside on mobile
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
        }
        
        // ====== DESKTOP SIDEBAR SUBMENU ======
        const moreToggle = document.querySelector('.more-toggle');
        const desktopSubmenu = document.getElementById('desktopSubmenu');
        const desktopToggleIcon = document.querySelector('.more-menu .toggle-icon');

        if (moreToggle && desktopSubmenu && desktopToggleIcon) {
            moreToggle.addEventListener('click', function(e) {
                e.preventDefault(); 
                e.stopPropagation();
                
                const isOpen = desktopSubmenu.style.maxHeight && desktopSubmenu.style.maxHeight !== '0px';
                
                if (isOpen) {
                    desktopSubmenu.style.maxHeight = '0px';
                    desktopToggleIcon.style.transform = 'rotate(0deg)';
                } else {
                    desktopSubmenu.style.maxHeight = desktopSubmenu.scrollHeight + 'px'; 
                    desktopToggleIcon.style.transform = 'rotate(180deg)';
                }
            });
            
            // Initialize submenu as closed
            desktopSubmenu.style.maxHeight = '0px';
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
            
            // Initialize mobile submenu as closed
            mobileSubmenu.style.maxHeight = '0px';
        }
    });

    // ====== ORIGINAL GYM SCANNER JAVASCRIPT - NOT MODIFIED ======
    // --- CONFIGURATION ---
    const MODEL_URL = "https://teachablemachine.withgoogle.com/models/WuwDwx2SF/"; 
    const MAX_PREDICTIONS = 3; 
    const MIN_CONFIDENCE_THRESHOLD = 0.85; // 85% confidence required for AR activation

    // --- EQUIPMENT AND EXERCISE MAPPING ---
    const EXERCISE_MAPPING = {
        "red-dumbell": {
            displayName: "Red Dumbbell 2kg",
            recommendedExercise: "Biceps Curl, Lateral Raise, Triceps Kickback, Front Raise, Overhead Extension, Wrist Curl, Dumbbell Punch, Shrugs, Bent-Over Row (Single Arm), Fly (Supine)", 
            instructions: [
                "Maintain a stable core and neutral spine for all standing movements.",
                "Use smooth, controlled movements; avoid swinging the weight (momentum).",
                "Focus on high repetitions (15-30 reps) due to the light 2kg weight.",
                "If performing exercises that require two dumbbells, use both of the 2kg pair.",
            ]
        },
        "background": {
            displayName: "No Equipment Detected",
            recommendedExercise: "Please focus on an item.",
            instructions: [
                "Ensure the equipment is well-lit.",
                "The camera view should be steady and clear.",
                "The model requires at least 85% confidence for detection.",
            ]
        },
        "treadmill": {
            displayName: "Electric Treadmill",
            recommendedExercise: "Walking, Jogging, Running, Incline Walking, Backwards Walking, Sprint Intervals, Long Distance Running, Hill Training, Fat Burning Program, Random Program",
            instructions: [
                "Always use the safety clip/key.",
                "Start with a slow warm-up pace before increasing speed or incline.",
                "Maintain an upright posture; avoid leaning on the handrails.",
            ]
        },
        "curved-manual-treadmill": {
            displayName: "Curved Manual Treadmill",
            recommendedExercise: "Walking (High Intensity), Jogging, Sprinting (all-out), Sled Push Simulation, Power Walking, Walk-to-Sprint Intervals, Backwards Walking, Lateral Shuffle, Low-Impact Jog, Prowler Push Simulation",
            instructions: [
                "The speed is determined entirely by your effort; be prepared for an intense workout.",
                "Engage your glutes and hamstrings to drive the belt backward.",
                "Avoid looking down; maintain a forward gaze to prevent loss of balance.",
            ]
        },
        "recumbent-exercise-bike": {
            displayName: "Recumbent Exercise Bike",
            recommendedExercise: "Steady-State Cycling, HIIT Sprints, Resistance Hill Climbs, Active Recovery, Backward Pedaling, Interval Training, Seated Sprint, Long Duration Ride, Resistance Program 1, Resistance Program 2",
            instructions: [
                "Adjust the seat so your knees have a slight bend at the full pedal extension.",
                "Maintain a consistent pedal stroke velocity; avoid 'mashing' the pedals.",
                "Use the back support fully to reduce strain on your lower back.",
            ]
        },
        "indoor-cycling": {
            displayName: "Indoor Cycling Bike (Spin)",
            recommendedExercise: "Seated Flat, Standing Climb, Seated Climb, Jumps (Seated to Standing), Sprint Intervals, Active Recovery, Tap Backs, Single-Leg Pedaling, Long Endurance Ride, Resistance Ladder Drill",
            instructions: [
                "Adjust the seat height to be level with your hip bone when standing next to it.",
                "Maintain light hands on the handlebars to keep your core engaged.",
                "Use the resistance knob to challenge yourself but keep a smooth pedal stroke.",
            ]
        },
        "hex-dumbell": {
            displayName: "Hex Dumbbell (General)",
            recommendedExercise: "Dumbbell Bench Press, Dumbbell Squat, Renegade Row, Dumbbell Overhead Press, Goblet Squat, Lunge, Farmer's Carry, Romanian Deadlift, Dumbbell Fly, Incline Press",
            instructions: [
                "Ensure proper grip; the handle should rest diagonally across the palm.",
                "When lifting heavier weight, brace your core tightly before initiation.",
                "Always control the eccentric (lowering) phase of the movement.",
            ]
        },
        "kettle-bell": {
            displayName: "Kettlebell",
            recommendedExercise: "Kettlebell Swing, Goblet Squat, Turkish Get-Up, Clean and Press, Snatch, Windmill, Two-Handed Deadlift, Russian Twist, Kettlebell Row, Farmer's Carry",
            instructions: [
                "Movements should originate from the hips (hip hinge), not the lower back.",
                "Maintain a tight, rigid core, especially during ballistic movements like the swing.",
                "Practice control before increasing the speed or weight of the exercise.",
            ]
        },
        "medicine-bell": {
            displayName: "Medicine Ball",
            recommendedExercise: "Medicine Ball Slams, Russian Twist, Wall Balls, Overhead Throw, Lunge with Twist, Squat with Press, Figure 8, V-Ups with Ball, Push-Up on Ball, Partner Chest Pass",
            instructions: [
                "Focus on explosive, full-body power for throwing/slamming movements.",
                "Catch the ball gently; absorb the impact to protect your joints.",
                "Ensure a clear area before performing throws or slams.",
            ]
        },
        "pre-weighted-fixed-barbell-rack": {
            displayName: "Pre-Weighted Fixed Barbell",
            recommendedExercise: "Overhead Press, Barbell Curl, Triceps Extension (Skullcrusher), Bent-Over Row, Reverse Curl, Front Squat (lighter weight), Static Lunge, Glute Bridge, Upright Row, Thruster",
            instructions: [
                "Choose a weight that allows you to maintain perfect form for 8-15 repetitions.",
                "Keep the bar close to your body during all lifting and lowering phases.",
                "Use a controlled tempo, especially when lowering the bar.",
            ]
        },
        "single-d-handle": {
            displayName: "Single D-Handle (Cable Attachment)",
            recommendedExercise: "Cable Row (Single Arm), Cable Chest Press (Single Arm), Cable Triceps Extension, Cable Biceps Curl, Cable Lateral Raise, Cable Core Rotation, Cable Kickback, Cable Woodchopper, Cable Face Pull, Cable Lunge",
            instructions: [
                "Ensure the handle is securely clipped to the cable machine's carabiner.",
                "Focus on the muscle contraction; the handle is just a connection point.",
                "Keep your elbow close to your side for isolation movements like curls and extensions.",
            ]
        },
        "dumbell-rack": {
            displayName: "Dumbbell Rack",
            recommendedExercise: "N/A (Equipment Storage)",
            instructions: [
                "Always replace dumbbells to their designated spot after use.",
                "Use two hands to safely lift and return heavy dumbbells to the rack.",
                "Ensure the area around the rack is clear of obstructions for safety.",
            ]
        },
        "olympic-barbells": {
            displayName: "Olympic Barbell",
            recommendedExercise: "Squat (High/Low Bar), Deadlift (Conventional/Sumo), Bench Press, Overhead Press, Power Clean, Power Snatch, Barbell Row, Hip Thrust, Rack Pull, Front Squat",
            instructions: [
                "Verify the collars are securely fastened on both ends before lifting.",
                "Always follow a structured warm-up protocol before lifting heavy.",
                "Use spotters or safety pins for heavy bench press and squat movements.",
            ]
        },
        "plate-loaded-flat-chest-press": {
            displayName: "Plate-Loaded Flat Chest Press Machine",
            recommendedExercise: "Flat Chest Press (High Resistance), Flat Chest Press (Explosive), Flat Chest Press (Slow Eccentric), Flat Chest Press (High Rep), Plate-Loaded Fly Simulation, Close-Grip Press, Drop Set Routine, Failure Set Routine, 3-Second Pause Press, Static Hold at Midpoint",
            instructions: [
                "Sit squarely in the seat with your back firmly against the pad.",
                "Ensure an equal amount of weight is loaded on both sides.",
                "Press the weight in a controlled manner, avoiding locking out your elbows.",
            ]
        },
        "aerobic-step": {
            displayName: "Aerobic Step/Platform",
            recommendedExercise: "Step-Up (Forward), Box Jump, Lateral Step-Up, Plyometric Step-Up, Taps (Quick Feet), Incline Push-Up, Decline Push-Up, Step-Over, Triceps Dip, V-Up (feet on step)",
            instructions: [
                "Ensure the step is placed on a non-slip surface.",
                "Confirm all risers are securely locked before use.",
                "Place your entire foot on the step during step-up movements for stability.",
            ]
        },
        "battle-rope": {
            displayName: "Battle Rope",
            recommendedExercise: "Alternating Waves, Double Waves, Slams, Outside Circles, Inside Circles, Hip Toss, Rope Push-Up, Squat to Wave, Sidewinder, Jumping Jack Waves",
            instructions: [
                "Anchor the rope securely to a stable point.",
                "Maintain a slight squat and athletic stance for stability.",
                "Focus on maintaining continuous motion with the ropes for cardiovascular intensity.",
            ]
        },
        "punch-mitt": {
            displayName: "Punch Mitt/Focus Pad",
            recommendedExercise: "Jab, Cross, Hook, Uppercut, Combination 1-2, Elbow Strike Practice, Slip and Counter, Bob and Weave Drill, Footwork Drill, Defensive Shell Practice",
            instructions: [
                "The holder must secure the mitts firmly to absorb the impact.",
                "The striker should aim for the center of the pad.",
                "Only perform this exercise with a trained partner.",
            ]
        },
        "punching-bag": {
            displayName: "Punching Bag (Heavy Bag)",
            recommendedExercise: "Heavy Bag Jabs, Heavy Bag Hooks, Heavy Bag Uppercuts, Combination Drills, Footwork Circles, Kicks (Low/High), Knee Strikes, Clinch Practice, Power Shots, Defensive Head Movement",
            instructions: [
                "Always wrap your hands and wear gloves to protect your wrists and knuckles.",
                "Move your feet; don't just stand in one place and strike.",
                "Keep your hands up to protect your face immediately after striking.",
            ]
        },
        "weight-bench": {
            displayName: "Weight Bench (Adjustable)",
            recommendedExercise: "Dumbbell Bench Press (Flat/Incline/Decline), Single-Arm Dumbbell Row, Step-Up, Box Squat, Bench Dip, Seated Overhead Press, Leg Raise (Lying), L-Sit Practice, Skullcrushers (Lying), Bulgarian Split Squat",
            instructions: [
                "Verify the bench is stable and the adjustment pins are fully engaged.",
                "Avoid placing excessive weight on the very end of the bench to prevent tipping.",
                "Wipe down the bench after use, especially for face-up exercises.",
            ]
        },
        "yoga-mat": {
            displayName: "Yoga Mat",
            recommendedExercise: "Plank, Downward Dog, Warrior II, Child's Pose, Vinyasa Flow, Cat-Cow Stretch, Bridge Pose, Sun Salutation, Savasana (Rest), Core Crunch",
            instructions: [
                "Use the mat on a flat, non-slip surface.",
                "The mat's texture is for grip; focus on engaging your muscles, not gripping with your fingers/toes.",
                "Clean the mat regularly to maintain hygiene and traction.",
            ]
        },
        "warm-up-stick": {
            displayName: "Warm-Up Stick/PVC Pipe",
            recommendedExercise: "Shoulder Dislocations (Pass Throughs), Overhead Squat Practice, Good Mornings (Light), Trunk Twists, Shoulder Circles, Internal/External Rotation Drill, Lat Stretch, Ankle Mobility Drill, Cossack Squat Practice, Press Prep",
            instructions: [
                "Maintain a loose, wide grip for joint mobility exercises.",
                "Move slowly and deliberately; this is for mobility, not strength.",
                "Stop immediately if you feel sharp joint pain.",
            ]
        },
        "vertical-knee-raise": {
            displayName: "Vertical Knee Raise / Captain's Chair",
            recommendedExercise: "Knee Raise, Leg Raise, Hanging Side Crunch (Oblique), Dip (Triceps/Chest), Straight Leg Raise, Hanging L-Sit Hold, Oblique Knee Raise, Hip Flexor Stretch (between sets), Supported Crunch, Elevated Push-Up",
            instructions: [
                "Keep your back firmly pressed against the support pad.",
                "Initiate the movement with your lower abdominal muscles, not momentum.",
                "Control the eccentric (lowering) phase to avoid swinging.",
            ]
        },
        "abdominal-machine": {
            displayName: "Abdominal Crunch Machine",
            recommendedExercise: "Cable Crunch (High Resistance), Crunch (Full Range), Crunch (Isometric Hold), Weighted Crunch, Side Crunch, Reverse Crunch, Drop Set Routine, High Rep Crunch, 3-Second Hold Crunch, Pulse Crunch",
            instructions: [
                "Set the weight and the seat position before starting.",
                "Focus on flexing the spine (bringing ribs toward hips), not pulling with the arms.",
                "Exhale completely at the peak of the contraction.",
            ]
        },
        "leg-extension-curl-machine": {
            displayName: "Leg Extension & Curl Machine (Combo)",
            recommendedExercise: "Leg Extension (Quad Isolation), Leg Curl (Hamstring Isolation), Single Leg Extension, Single Leg Curl, Isometric Extension Hold, Isometric Curl Hold, Drop Set Extension, Drop Set Curl, High Rep Extension, High Rep Curl",
            instructions: [
                "Adjust the pads to align with your knee joint's pivot point.",
                "Keep your back firmly against the seat pad for both movements.",
                "Control the weight on the way down; do not let it drop quickly.",
            ]
        },
        "biceps-curl-machine": {
            displayName: "Biceps Curl Machine",
            recommendedExercise: "Machine Biceps Curl, High Rep Curl, Low Rep Curl (Heavy), Reverse Grip Curl, Hammer Grip Curl (if handles allow), Isometric Hold Curl, Slow Eccentric Curl, Drop Set Curl, Staggered Grip Curl, 3-Second Pause Curl",
            instructions: [
                "Secure your elbows firmly onto the pad.",
                "Keep your shoulders depressed and relaxed; avoid shrugging.",
                "Focus on squeezing the bicep at the top of the movement.",
            ]
        },
        "calf-raise": {
            displayName: "Seated Calf Raise Machine",
            recommendedExercise: "Seated Calf Raise (High Rep), Seated Calf Raise (Heavy), Pause at the Top, Pause at the Bottom (Deep Stretch), Single-Leg Calf Raise, Drop Set Routine, Toes Pointed In, Toes Pointed Out, 3-Second Eccentric Raise, Pulse Raise",
            instructions: [
                "Place your feet so your heels are off the edge of the platform.",
                "Ensure the upper thigh pad is secured and comfortable.",
                "Achieve a full range of motion, stretching at the bottom and squeezing at the top.",
            ]
        },
        "standing-calf-raise-machine": {
            displayName: "Standing Calf Raise Machine",
            recommendedExercise: "Standing Calf Raise (High Rep), Standing Calf Raise (Heavy), Pause at the Top, Pause at the Bottom (Deep Stretch), Single-Leg Standing Calf Raise, Drop Set Routine, Toes Pointed In, Toes Pointed Out, 3-Second Eccentric Raise, Pulse Raise",
            instructions: [
                "Place your shoulder/trap under the pad, not your neck.",
                "Maintain a stable torso; avoid leaning forward or swinging.",
                "Ensure the platform allows for a deep stretch in the Achilles tendon.",
            ]
        },
        "hip-abduction-adduction": {
            displayName: "Hip Abduction/Adduction Machine (Combo)",
            recommendedExercise: "Hip Abduction (Glute/Side), Hip Adduction (Inner Thigh), High Rep Abduction, High Rep Adduction, Isometric Abduction Hold, Isometric Adduction Hold, Slow Eccentric Abduction, Slow Eccentric Adduction, Pulse Abduction, Pulse Adduction",
            instructions: [
                "Sit with your back straight against the pad.",
                "Choose a seat position that allows full range of motion without joint pain.",
                "Control the pads on the return path; do not let the weight stack crash.",
            ]
        },
        "seated-chess-press-machine": {
            displayName: "Seated Chest Press Machine (Selectorized)",
            recommendedExercise: "Seated Chest Press, High Rep Press, Low Rep Press (Heavy), Neutral Grip Press (if handles allow), Slow Eccentric Press, Drop Set Routine, Staggered Grip Press, 3-Second Pause Press, Static Hold at Midpoint, Full Range Press",
            instructions: [
                "Adjust the seat so the handles align with the middle of your chest.",
                "Keep your feet flat on the floor and back against the pad.",
                "Retract and depress your shoulder blades throughout the movement.",
            ]
        },
        "plate-loaded-incline-chest-press": {
            displayName: "Plate-Loaded Incline Chest Press Machine",
            recommendedExercise: "Incline Press (High Resistance), Incline Press (Explosive), Incline Press (Slow Eccentric), Incline Press (High Rep), Plate-Loaded Fly Simulation (Incline), Close-Grip Incline Press, Drop Set Routine, Failure Set Routine, 3-Second Pause Press, Static Hold at Midpoint",
            instructions: [
                "Ensure an equal amount of weight is loaded on both sides.",
                "Keep your lower back pressed into the seat; avoid excessive arching.",
                "Focus on driving the weight up using the upper chest muscles.",
            ]
        },
        "t-bar-row-machine": {
            displayName: "T-Bar Row Machine (Chest Supported)",
            recommendedExercise: "Wide Grip T-Bar Row, Close Grip T-Bar Row, Underhand Grip T-Bar Row, High Rep Row, Heavy Low Rep Row, Isometric Hold Row, Slow Eccentric Row, Single-Arm T-Bar Row (if applicable), Drop Set Routine, Pulse Row",
            instructions: [
                "Keep your chest firmly against the support pad.",
                "Initiate the pull by driving your elbows backward, squeezing your back muscles.",
                "Avoid rounding your upper back during the movement.",
            ]
        },
        "lat-pulldown-seated-cable": {
            displayName: "Lat Pulldown Machine / Seated Cable",
            recommendedExercise: "Wide-Grip Pulldown, Close-Grip Pulldown, Reverse-Grip Pulldown, Single-Arm Pulldown, V-Bar Pulldown, Rope Pulldown, Straight-Arm Pulldown, Drop Set Routine, 3-Second Eccentric Pulldown, Isometric Hold Pulldown",
            instructions: [
                "Adjust the thigh pad to lock your lower body in place.",
                "Lead the pull with your elbows, drawing them down toward your hips.",
                "Keep your torso slightly leaned back (about 20 degrees) and maintain core tension.",
            ]
        },
        "pec-deck-fly": {
            displayName: "Pec Deck Fly Machine",
            recommendedExercise: "Pec Deck Fly, Rear Delt Reverse Fly (Seated Backward), Isometric Fly Hold, High Rep Fly, Low Rep Fly (Heavy), Single-Arm Fly, Slow Eccentric Fly, Drop Set Routine, Pulse Fly, 3-Second Pause Fly",
            instructions: [
                "Adjust the seat so your shoulders are level with the machine's pivot point.",
                "Keep a slight bend in your elbows and focus on bringing your biceps together.",
                "For rear delts, face the machine, keep arms straight, and focus on squeezing the back.",
            ]
        },
        "smith-machine": {
            displayName: "Smith Machine",
            recommendedExercise: "Smith Machine Squat, Smith Machine Bench Press, Smith Machine Shoulder Press, Smith Machine Bent-Over Row, Smith Machine Reverse Lunge, Smith Machine Calf Raise, Smith Machine Deadlift (RDL focus), Smith Machine Shrugs, Smith Machine Reverse Hack Squat, Smith Machine Overhead Triceps Extension",
            instructions: [
                "Always set the safety catches to the appropriate height.",
                "Due to the fixed bar path, be cautious of joint stress; use lighter weight initially.",
                "Unrack and re-rack the bar safely by rotating the hooks correctly.",
            ]
        },
        "linear-hack-squat-machine": {
            displayName: "Linear Hack Squat Machine",
            recommendedExercise: "Hack Squat (High Foot Placement), Hack Squat (Low Foot Placement), Hack Squat (Close Stance), Hack Squat (Wide Stance), Single-Leg Hack Squat (Careful), Calf Raise (on platform), Partial Range Squat, Isometric Hold Squat, High Rep Squat, Slow Eccentric Squat",
            instructions: [
                "Engage the safety levers before loading the weight.",
                "Keep your back firmly against the pad throughout the movement.",
                "Ensure your knees track in line with your toes; do not let them cave inward.",
            ]
        },
        "cable-cross-machine": {
            displayName: "Cable Cross/Dual Cable Machine",
            recommendedExercise: "Cable Chest Fly (High/Mid/Low), Cable Triceps Pushdown, Cable Biceps Curl, Cable Lateral Raise, Cable Woodchopper, Cable Face Pull, Cable Row, Cable Kickback, Cable Core Rotation, Cable Reverse Fly",
            instructions: [
                "Verify the pin is fully inserted and the height is set on both sides.",
                "Step slightly forward from the machine to ensure tension throughout the range of motion.",
                "Maintain a stable core and posture; the cables provide resistance in all directions.",
            ]
        },
        "full-body-electric-massage-chair": {
            displayName: "Full-Body Electric Massage Chair",
            recommendedExercise: "N/A (Recovery & Wellness)",
            instructions: [
                "Consult a doctor if you have pacemakers, metal implants, or acute injuries.",
                "Set the duration to 15-30 minutes for an optimal session.",
                "Ensure the chair's leg/foot components are adjusted to your height for proper pressure.",
            ]
        }
    };
    // -------------------------------------------

    let model, webcam, maxPredictions;
    let isSetup = false;
    
    // Utility function to display messages
    function updateStatus(message, color = 'yellow') {
        const statusDiv = document.getElementById('status-message');
        statusDiv.textContent = message;
        statusDiv.className = `text-center p-3 mb-4 rounded-lg font-semibold bg-${color}-100 text-${color}-700`;
    }
    
    async function init() {
        if (isSetup) {
            updateStatus("Already running!", 'indigo');
            return;
        }

        console.log("LOG 1: Initializing TensorFlow backend...");
        await tf.ready();
        console.log("LOG 2: TensorFlow ready. Starting model load...");
        
        updateStatus("Loading...", 'yellow');
        try {
            const modelURL = MODEL_URL + "model.json";
            const metadataURL = MODEL_URL + "metadata.json";
            model = await tmImage.load(modelURL, metadataURL);
            maxPredictions = model.getTotalClasses();
            console.log("LOG 3: Model loaded successfully.");
        } catch (error) {
            console.error("Critical Model Loading Error:", error); 
            updateStatus("Failed to load model! Error: " + (error.message || "Unknown model loading failure."), 'red');
            return;
        }

        updateStatus("Setting up webcam...", 'yellow');
        try {
            const flip = false; 
            
            console.log("LOG 4: Attempting webcam setup (tmImage.Webcam.setup())...");
            // MODIFIED: Increased resolution to 800x600
            webcam = new tmImage.Webcam(800, 600, flip); // width, height, flip 
            await webcam.setup();
            await webcam.play();
            console.log("LOG 5: Webcam setup successful. Stream running.");
            
            document.getElementById("webcam").appendChild(webcam.canvas); 

            isSetup = true;
            document.getElementById('start-button').style.display = 'none'; 
            updateStatus("Model loaded and webcam running! Classifying...", 'green');
            
            // Start the prediction loop
            window.requestAnimationFrame(loop);

        } catch (error) {
            console.error("Critical Webcam Setup Error:", error); 
            const errorMessage = error.message || "Webcam access failed. Possible issues: device conflict, browser policy (despite permission), or iframe initialization failure.";
            updateStatus("Failed to set up webcam. Error: " + errorMessage, 'red');
        }
    }

    async function loop() {
        if (webcam && isSetup) {
            webcam.update();
            await predict();
        }
        // Only continue the loop if the webcam is still running
        if (isSetup) {
            window.requestAnimationFrame(loop);
        }
    }

    async function predict() {
        const prediction = await model.predict(webcam.canvas);
        const labelContainer = document.getElementById("label-container");
        const arInfo = document.getElementById("ar-info");

        prediction.sort((a, b) => b.probability - a.probability);

        let targetDetected = false;
        let topPrediction = prediction[0];
        let topClassName = topPrediction.className.toLowerCase(); 
        
        const currentEquipment = EXERCISE_MAPPING[topClassName] || EXERCISE_MAPPING["background"];

        // --- Determine Target Detection Status ---
        if (
            topPrediction.probability >= MIN_CONFIDENCE_THRESHOLD && 
            topClassName !== 'background'
        ) {
            targetDetected = true;
        }

        // --- Control Prediction Bars (#label-container) ---
        // Clear content and hide the container at all times for a clean AR view.
        labelContainer.innerHTML = '';
        labelContainer.classList.add('hidden'); 

        // --- AR Logic (Details Box) ---
        if (targetDetected) {
            
            const details = currentEquipment;
            
            // NEW ELEMENT: Equipment Name (User-friendly)
            document.getElementById('equipment-display-name').textContent = details.displayName; 
            
            // EXISTING ELEMENT: Detected Class (Raw Model Class)
            document.getElementById('detected-item-name').textContent = topClassName; 
            
            // UPDATED ELEMENT: Recommended Exercise
            document.getElementById('recommended-exercise').textContent = details.recommendedExercise;
            
            // UPDATED: Instructions List
            const instructionsList = document.querySelector('#ar-info ul');
            instructionsList.innerHTML = details.instructions.map(instruction => `<li>${instruction}</li>`).join('');
            
            // SHOW the AR info box
            arInfo.classList.remove('hidden');
            arInfo.classList.add('visible');
            updateStatus(`Equipment Recognized! Viewing details for ${details.displayName}.`, 'green');
        } else {
            // When nothing is detected, hide the AR info box
            arInfo.classList.add('hidden');
            arInfo.classList.remove('visible');
            updateStatus("Place the camera on a piece of equipment to view the details.", 'gray');
        }
    }

    // Initial check and setup to enable the button if the user clicks
    document.addEventListener('DOMContentLoaded', () => {
        updateStatus("Ready! Click 'Start Recognition' and allow camera access.", 'green');
        
        // Initialize the AR info box as hidden
        const arInfo = document.getElementById('ar-info');
        arInfo.classList.add('hidden');
    });
</script>
</body>
</html>