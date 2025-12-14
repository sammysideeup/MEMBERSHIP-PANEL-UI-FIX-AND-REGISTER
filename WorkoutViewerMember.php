<?php 
// At the top of WorkoutViewerMember.php, check user status and get ID
// ... (Your existing PHP code) ...
$current_user_id = $row['id'] ?? 'null';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Viewer</title>
    
    <!-- Design System Stylesheets -->
    <link rel="stylesheet" href="Suggestiondesign.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Additional custom styles matching Recommended-workout-viewer.php */
        .bg-blue-500 {
            background-color: #000000 !important;
        }
        
        .hover\:bg-blue-700:hover {
            background-color: #FFD700 !important;
            color: #000000 !important;
        }
        
        /* Color overrides */
        .bg-gray-100 {
            background-color: #f8f8f8 !important;
        }
        
        .text-gray-900 {
            color: #000000 !important;
        }
        
        .text-gray-400 {
            color: #666 !important;
        }
        
        /* Button colors */
        .bg-red-500 {
            background-color: #dc2626 !important;
        }
        
        .bg-green-500 {
            background-color: #16a34a !important;
        }
        
        .bg-orange-500 {
            background-color: #f97316 !important;
        }
        
        .bg-yellow-400 {
            background-color: #FFD700 !important;
            color: #000000 !important;
        }
        
        /* Ensure proper background colors */
        body {
            background-color: #ffffff;
        }
        
        /* Custom styles for workout viewer */
        .workout-viewer-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin: 2rem auto;
        }
        
        .back-button-viewer {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #000000;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-button-viewer:hover {
            background-color: #FFD700;
            color: #000000;
            transform: translateX(-5px);
        }
        
        .workout-image-container {
            width: 100%;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f8f8;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 2px dashed #e5e5e5;
        }
        
        .workout-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .workout-image-placeholder {
            color: #666;
            text-align: center;
            padding: 2rem;
        }
        
        .workout-image-placeholder i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #FFD700;
        }
        
        .controls-container {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .control-button {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            min-width: 100px;
            text-align: center;
        }
        
        .control-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .progress-container {
            width: 100%;
            background-color: #e5e5e5;
            height: 6px;
            border-radius: 3px;
            margin: 1.5rem 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #FFD700;
            transition: width 0.3s ease;
        }
        
        .timer-display {
            font-size: 2rem;
            font-weight: 800;
            color: #000000;
            margin: 1rem 0;
            padding: 1rem;
            background-color: #f8f8f8;
            border-radius: 1rem;
            border: 2px solid #FFD700;
        }
        
        .rest-timer-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: #16a34a;
            margin: 1rem 0;
            padding: 1rem;
            background-color: #f0fdf4;
            border-radius: 1rem;
            border: 2px solid #16a34a;
        }
        
        .total-time-display {
            font-size: 1rem;
            color: #666;
            margin: 0.5rem 0;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 640px) {
            .workout-viewer-container {
                padding: 1.5rem;
                margin: 1rem;
                margin-top: 80px;
            }
            
            .workout-image-container {
                height: 200px;
            }
            
            .controls-container {
                flex-direction: column;
            }
            
            .control-button {
                width: 100%;
            }
            
            .timer-display {
                font-size: 1.75rem;
            }
            
            .rest-timer-display {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 480px) {
            .workout-viewer-container {
                padding: 1.25rem;
            }
            
            .workout-image-container {
                height: 180px;
            }
            
            .timer-display {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body class="min-h-screen">

<!-- Mobile Top Navbar -->
<nav class="mobile-navbar" id="mobileNavbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <i class='bx bx-dumbbell text-yellow-500 text-2xl'></i>
            <h2>Workout Viewer</h2>
        </div>
        <button class="navbar-toggle" id="navbarToggle">
            <i class='bx bx-menu'></i>
        </button>
    </div>
    <div class="navbar-menu" id="navbarMenu">
        <ul>
            <li><a href="WorkoutJournal.php"><i class='bx bx-dumbbell'></i> Back to Workouts</a></li>
            <li><a href="Membership.php"><i class='bx bxs-user-badge'></i> Profile</a></li>
            <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Desktop Sidebar -->
<div class="sidebar">
    <h2>Workout Viewer</h2>
    <ul>
        <li><a href="WorkoutJournal.php"><i class='bx bx-dumbbell'></i> Back to Workouts</a></li>
        <li><a href="Membership.php"><i class='bx bxs-user-badge'></i> Profile</a></li>
        <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<main class="main-content">
    <div class="workout-viewer-container">
        <!-- Back Button -->
        <button onclick="history.back()" class="back-button-viewer">
            <i class='bx bx-arrow-back'></i>
            Back
        </button>
        
        <!-- Progress Bar -->
        <div class="progress-container">
            <div id="progress-bar" class="progress-bar" style="width: 0%;"></div>
        </div>
        
        <!-- Workout Image/Placeholder -->
        <div id="workout-image-container" class="workout-image-container">
            <div id="workout-image-placeholder" class="workout-image-placeholder">
                <i class='bx bx-dumbbell'></i>
                <p>No workout image available</p>
            </div>
            <img id="workout-gif" src="" alt="Workout Image" class="workout-image hidden"/>
        </div>
        
        <!-- Workout Info -->
        <div class="text-center">
            <h2 id="workout-name" class="text-2xl font-bold mb-2">Workout Name</h2>
            <p id="workout-info" class="text-gray-600 mb-4">Sets: 3 | Reps: 10</p>
            
            <!-- Total Time Display -->
            <p id="exercise-time" class="total-time-display">Total Time: 0m 0s</p>
            
            <!-- Timer Display (Exercise Timer) - Only for timed exercises -->
            <div id="timer-display" class="timer-display hidden"></div>
            
            <!-- Start Timer Button - Only for timed exercises -->
            <button id="start-timer-btn" onclick="startTimer()" class="control-button bg-yellow-400 hidden">
                <i class='bx bx-play'></i> Start Timer
            </button>
            
            <!-- Rest Timer Display -->
            <div id="rest-timer-display" class="rest-timer-display hidden">Rest: 3:00</div>
            
            <!-- Skip Rest Button -->
            <button id="skip-rest-btn" onclick="skipRest()" class="control-button bg-orange-500 text-white hidden">
                <i class='bx bx-skip-next'></i> Skip Rest
            </button>
        </div>
        
        <!-- Controls -->
        <div class="controls-container" id="button-group">
            <button onclick="prevExercise()" class="control-button bg-gray-300 text-gray-700">
                <i class='bx bx-chevron-left'></i> Previous
            </button>
            <button onclick="skipExercise()" class="control-button bg-red-500 text-white">
                <i class='bx bx-skip-forward'></i> Skip
            </button>
            <button onclick="nextExercise()" class="control-button bg-blue-500 text-white">
                Next <i class='bx bx-chevron-right'></i>
            </button>
        </div>
        
        <!-- Finish Button -->
        <button id="finish-button" onclick="finishWorkout()" class="control-button bg-green-500 text-white hidden w-full mt-4">
            <i class='bx bx-check'></i> Finish Workout
        </button>
    </div>
</main>

<!-- Alarm Sound -->
<audio id="alarm-sound" src="https://www.soundjay.com/button/beep-07.wav" preload="auto"></audio>

<script>
    // Mobile navbar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarMenu = document.getElementById('navbarMenu');
        
        if (navbarToggle && navbarMenu) {
            navbarToggle.addEventListener('click', function() {
                navbarMenu.classList.toggle('active');
                navbarToggle.classList.toggle('active');
                
                // Toggle menu icon
                const icon = navbarToggle.querySelector('i');
                if (navbarMenu.classList.contains('active')) {
                    icon.classList.remove('bx-menu');
                    icon.classList.add('bx-x');
                } else {
                    icon.classList.remove('bx-x');
                    icon.classList.add('bx-menu');
                }
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isMobile = window.innerWidth <= 1024;
                const isClickInsideNavbar = document.getElementById('mobileNavbar').contains(event.target);
                
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
            }
        }
        
        // Initialize workout viewer
        initializeWorkoutViewer();
    });
    
    // ====== WORKOUT VIEWER LOGIC (YOUR ORIGINAL JAVASCRIPT - PRESERVED) ======
    const exercises = JSON.parse(localStorage.getItem('selectedExercises')) || [];
    let currentIndex = 0;
    let timerInterval;
    let timerSeconds = 0;
    let totalTime = 0;
    let trackingTime = null; // Used for the global workout duration
    let isResting = false;
    let restInterval;

    const timerDisplay = document.getElementById("timer-display");
    const startButton = document.getElementById("start-timer-btn");
    const alarm = document.getElementById("alarm-sound");
    const restDisplay = document.getElementById("rest-timer-display");
    const progressBar = document.getElementById("progress-bar");
    const workoutImage = document.getElementById("workout-gif");
    const workoutImagePlaceholder = document.getElementById("workout-image-placeholder");

    // Helper to format total duration display (seconds to M:S)
    function updateExerciseTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        document.getElementById("exercise-time").textContent = `Total Time: ${m}m ${s}s`;
    }

    // --- GLOBAL TIME TRACKING (Total Workout Duration) ---
    function trackTimeStart() {
        if (trackingTime !== null) return; // Prevent multiple intervals
        
        trackingTime = setInterval(() => {
            totalTime++;
            updateExerciseTime(totalTime);
        }, 1000);
    }

    function trackTimeStop() {
        clearInterval(trackingTime);
        trackingTime = null;
    }
    // ----------------------------------------------------

    // Check if exercise needs a timer (ONLY for specific timed exercises)
    function needsTimer(exercise) {
        const repsText = exercise.reps.toLowerCase();
        const exerciseName = exercise.name.toLowerCase();
        
        // Timer ONLY for exercises that contain time units or are specifically timed exercises
        const hasTimeUnit = repsText.includes('secs') || 
                           repsText.includes('seconds') || 
                           repsText.includes('mins') || 
                           repsText.includes('minutes');
        
        // Specific exercises that should have timer regardless of reps text
        const timedExercises = [
            'treadmill', 'plank', 'hold', 'isometric', 'burnout',
            'farmer', 'carry', 'wall sit', 'hollow hold', 'arch hold'
        ];
        
        const isSpecificTimedExercise = timedExercises.some(timedEx => 
            exerciseName.includes(timedEx)
        );
        
        // Timer shows if: has time unit OR is a specific timed exercise
        return hasTimeUnit || isSpecificTimedExercise;
    }

    function parseTimeToSeconds(text) {
        const textLower = text.toLowerCase();
        
        // Handle seconds
        if (textLower.includes('secs') || textLower.includes('seconds')) {
            const match = textLower.match(/(\d+)\s*(?:secs|seconds)/);
            if (match) return parseInt(match[1]);
        }
        
        // Handle minutes
        if (textLower.includes('mins') || textLower.includes('minutes')) {
            const match = textLower.match(/(\d+)\s*(?:mins|minutes)/);
            if (match) return parseInt(match[1]) * 60;
        }
        
        // Default for timed exercises without specific time (e.g., just "plank")
        return 60; // 60 seconds default
    }

    function updateProgressBar() {
        const percent = ((currentIndex + 1) / exercises.length) * 100;
        progressBar.style.width = percent + "%";
    }

    function startTimer() {
        startButton.disabled = true;
        timerDisplay.classList.remove("hidden");
        updateTimerDisplay(timerSeconds);

        timerInterval = setInterval(() => {
            timerSeconds--;
            updateTimerDisplay(timerSeconds);
            if (timerSeconds <= 0) {
                clearInterval(timerInterval);
                timerDisplay.textContent = "Done!";
                if (alarm) alarm.play();
                // Auto-proceed to rest after timed exercise
                setTimeout(() => {
                    nextExercise();
                }, 1000);
            }
        }, 1000);
    }

    function updateTimerDisplay(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        timerDisplay.textContent = `${m > 0 ? m + 'm ' : ''}${s}s`;
    }

    function clearTimer() {
        clearInterval(timerInterval);
        timerDisplay.classList.add("hidden");
        timerDisplay.textContent = "";
        startButton.classList.add("hidden");
        startButton.disabled = false;
        restDisplay.classList.add("hidden");
    }

    function clearRestTimer() {
        clearInterval(restInterval);
        isResting = false;
    }

    function loadExercise(index) {
        clearTimer();
        clearRestTimer();
        
        // Reset UI states
        restDisplay.classList.add("hidden");
        document.getElementById('skip-rest-btn').classList.add("hidden");
        startButton.classList.add("hidden");
        startButton.disabled = false;

        const ex = exercises[index];
        if (!ex) return;

        document.getElementById("workout-name").textContent = ex.name;
        document.getElementById("workout-info").textContent = `Sets: ${ex.sets} | Reps/Time: ${ex.reps}`;
        
        // Handle workout image
        if (ex.gif && ex.gif !== "WorkoutGifs/.gif") {
            workoutImage.src = ex.gif;
            workoutImage.classList.remove("hidden");
            workoutImagePlaceholder.classList.add("hidden");
        } else {
            workoutImage.classList.add("hidden");
            workoutImagePlaceholder.classList.remove("hidden");
        }

        // Check if this exercise needs a timer
        if (needsTimer(ex)) {
            const timeInSeconds = parseTimeToSeconds(ex.reps);
            timerSeconds = timeInSeconds;
            timerDisplay.textContent = updateTimerDisplay(timerSeconds);
            startButton.classList.remove("hidden");
        } else {
            // Rep-based exercise - no timer needed
            // Just show the normal controls
        }

        if (index === exercises.length - 1) {
            document.getElementById('button-group').classList.add("hidden");
            document.getElementById('finish-button').classList.remove("hidden");
        } else {
            document.getElementById('button-group').classList.remove("hidden");
            document.getElementById('finish-button').classList.add("hidden");
        }

        updateExerciseTime(totalTime); // Update display with accumulated total
        updateProgressBar();
    }

    function startRestTimer() {
        isResting = true;
        restDisplay.classList.remove("hidden");
        document.getElementById('skip-rest-btn').classList.remove("hidden");
        document.getElementById('button-group').classList.add("hidden");
        startButton.classList.add("hidden");

        // Set rest time to 3 minutes (180 seconds)
        let restTime = 180;
        restDisplay.textContent = `Rest: ${formatTime(restTime)}`;

        restInterval = setInterval(() => {
            restTime--;
            restDisplay.textContent = `Rest: ${formatTime(restTime)}`;

            if (restTime <= 0 || !isResting) {
                clearRestTimer();
                restDisplay.classList.add("hidden");
                document.getElementById('skip-rest-btn').classList.add("hidden");
                document.getElementById('button-group').classList.remove("hidden");
                isResting = false;
                currentIndex++;
                loadExercise(currentIndex);
            }
        }, 1000);
    }

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${m}:${s < 10 ? '0' : ''}${s}`;
    }

    function nextExercise(skipRest = false) {
        if (!skipRest && !isResting && currentIndex < exercises.length - 1) {
            startRestTimer();
            return;
        }

        // Already resting or skipping manually
        if (currentIndex < exercises.length - 1) {
            currentIndex++;
            loadExercise(currentIndex);
        }
    }

    function skipRest() {
        isResting = false;
        clearRestTimer();
        restDisplay.classList.add("hidden");
        document.getElementById('skip-rest-btn').classList.add("hidden");
        document.getElementById('button-group').classList.remove("hidden");
        
        currentIndex++;
        loadExercise(currentIndex);
    }

    function prevExercise() {
        if (currentIndex > 0) {
            currentIndex--;
            loadExercise(currentIndex);
        }
    }

    function skipExercise() {
        if (isResting) {
            skipRest();
        } else {
            nextExercise(true);
        }
    }

    // --- NEW PROGRESS LOGGING FUNCTION ---
    function finishWorkout() {
        trackTimeStop(); // Stop the global timer
        clearTimer(); // Clear any exercise timer

        // 2. Prepare data to send
        const workoutData = {
            // NOTE: user_id logic must be handled by PHP session in the PHP block before this script runs.
            total_duration_seconds: totalTime, 
            session_date: new Date().toISOString().slice(0, 10), 
            workout_day: new Date().toLocaleDateString('en-US', { weekday: 'long' }), 
            total_exercises: exercises.length
        };

        // 3. Send data to server
        fetch('log_progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(workoutData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Alert removed for cleaner user experience
            } else {
                console.error("Error logging workout:", data.message);
            }
        })
        .catch(error => {
            console.error('Network error during progress log:', error);
        })
        .finally(() => {
            window.location.href = "WorkoutJournal.php"; 
        });
    }

    function initializeWorkoutViewer() {
        if (exercises.length === 0) {
            document.getElementById("workout-name").textContent = "No exercises selected";
            document.getElementById("workout-info").textContent = "Please select exercises from a workout plan";
            document.getElementById('button-group').classList.add("hidden");
            document.getElementById('finish-button').classList.remove("hidden");
            document.getElementById('finish-button').textContent = "Go Back";
            document.getElementById('finish-button').onclick = () => history.back();
            return;
        }
        
        loadExercise(currentIndex);
        trackTimeStart(); // 🔑 START THE ACCUMULATOR ONCE HERE
    }
</script>
</body>
</html>