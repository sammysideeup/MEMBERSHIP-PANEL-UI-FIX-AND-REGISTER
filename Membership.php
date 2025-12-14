<?php 
// Membership.php (Finalized Code with Dietary Plan Logic)
session_start();
include 'connection.php';

// Clear logs cache if reset parameter is present
if (isset($_GET['reset']) && isset($_SESSION['recent_logs_cache'])) {
    unset($_SESSION['recent_logs_cache']);
}

if (isset($_SESSION['email'])) {
    $emailToFetch = $_SESSION['email'];
    
    // Get user ID
    $user_sql = "SELECT id FROM users WHERE email = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $emailToFetch);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_stmt->close();
    
    if ($user_data) {
        $user_id = $user_data['id'];
        
        // Check if we've already processed today's reset
        if (!isset($_SESSION['last_reset_check']) || $_SESSION['last_reset_check'] != date('Y-m-d')) {
            // Get the date of the most recent log
            $last_log_sql = "SELECT MAX(log_date) as last_log_date FROM dietary_logs WHERE user_id = ?";
            $last_log_stmt = $conn->prepare($last_log_sql);
            $last_log_stmt->bind_param("i", $user_id);
            $last_log_stmt->execute();
            $last_log_result = $last_log_stmt->get_result()->fetch_assoc();
            $last_log_stmt->close();
            $last_log_date = $last_log_result['last_log_date'] ?? null;
            $today = date('Y-m-d');
            
            // If last log is from a previous day, auto-reset is not needed because
            // the system already filters by today's date
            // But we can add a cleanup for old temp data if needed
            
            // Mark that we've checked today
            $_SESSION['last_reset_check'] = $today;
        }
    }
}

// Check for and display session messages before anything else
$message = ''; 
if (isset($_SESSION['success_message'])) {
    $message = '<div class="bg-green-100 text-green-700 p-3 rounded-lg">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $message = '<div class="bg-red-100 text-red-700 p-3 rounded-lg">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

if (!isset($_SESSION['email'])) {
    header("Location: Loginpage.php");
    exit();
}

$emailToFetch = $_SESSION['email'];

// --- START: Calorie Calculation Functions (Based on Harris-Benedict) ---

function calculateBMR($weight_kg, $height_cm, $age, $gender) {
    if (empty($weight_kg) || empty($height_cm) || empty($age)) return 0;

    if ($gender === 'Male') {
        return 88.362 + (13.397 * $weight_kg) + (4.799 * $height_cm) - (5.677 * $age);
    } else {
        return 447.593 + (9.247 * $weight_kg) + (3.098 * $height_cm) - (4.330 * $age);
    }
}
    
function calculateTDEE($bmr, $activity_level) {
    if ($bmr === 0) return 0;
    
    $multiplier = 1.2; 
    if ($activity_level === 'Moderate') {
        $multiplier = 1.55; 
    } elseif ($activity_level === 'High') {
        $multiplier = 1.9; 
    }
    return $bmr * $multiplier;
}

function calculateDailyCalorieGoal($tdee, $goal) {
    if ($tdee === 0) return 0;
    
    $adjustment = 0;
    
    // Adjust goal based on user selection
    if ($goal === 'Gain Muscle') {
        $adjustment = 300; 
    } elseif ($goal === 'Lose Weight') {
        $adjustment = -500; 
    } elseif ($goal === 'Stay Fit' || $goal === 'Maintain') {
        $adjustment = 0;
    }
    
    // Set a minimum calorie intake for safety
    return max(1500, round($tdee + $adjustment));
}

// --- NEW FUNCTION: MACRONUTRIENT CALCULATION ---
function getMacroSplit($goal) {
    $splits = [
        'Gain Muscle' => ['Protein' => 30, 'Carbs' => 50, 'Fat' => 20],
        'Lose Weight' => ['Protein' => 40, 'Carbs' => 35, 'Fat' => 25],
        'Stay Fit' => ['Protein' => 25, 'Carbs' => 45, 'Fat' => 30],
        'Maintain' => ['Protein' => 25, 'Carbs' => 45, 'Fat' => 30],
        // Default for any other goal
        'Default' => ['Protein' => 25, 'Carbs' => 50, 'Fat' => 25],
    ];

    return $splits[$goal] ?? $splits['Default'];
}

function getBMIStatus($bmi) {
    if ($bmi <= 0) return "Not Available";

    if ($bmi < 18.5) return "Underweight";
    if ($bmi < 24.9) return "Normal";
    if ($bmi < 29.9) return "Overweight";
    return "Obese";
}

function calculateMacroGrams($calorie_goal, $macro_split) {
    $protein_percent = $macro_split['Protein'] / 100;
    $carbs_percent = $macro_split['Carbs'] / 100;
    $fat_percent = $macro_split['Fat'] / 100;

    // Grams = (Calories * Percentage) / Calorie density (4 for Protein/Carbs, 9 for Fat)
    return [
        'ProteinGrams' => round(($calorie_goal * $protein_percent) / 4),
        'CarbGrams' => round(($calorie_goal * $carbs_percent) / 4),
        'FatGrams' => round(($calorie_goal * $fat_percent) / 9),
    ];
}

// --- FUNCTION: FORMAT TRAINING DAYS ---
function formatTrainingDays($training_days) {
    if (empty($training_days) || $training_days === '0' || $training_days === '' || $training_days === 'NULL') {
        return 'Not set';
    }
    
    // Handle NULL or empty strings
    if (is_null($training_days) || trim($training_days) === '') {
        return 'Not set';
    }
    
    // Clean up the training days string
    $training_days = trim($training_days);
    
    // Split by comma (with or without space)
    if (strpos($training_days, ',') !== false) {
        $days_array = preg_split('/\s*,\s*/', $training_days);
    } else {
        $days_array = [$training_days];
    }
    
    if (empty($days_array)) {
        return 'Not set';
    }
    
    $valid_days = [];
    $day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    // Filter only valid day names
    foreach ($days_array as $day) {
        $day = trim($day);
        if (in_array($day, $day_names)) {
            $valid_days[] = $day;
        }
    }
    
    if (empty($valid_days)) {
        return 'Not set';
    }
    
    $training_days = implode(', ', $valid_days);
    
    // If it's all 7 days, show "Every day"
    if (count($valid_days) === 7) {
        return 'Every day';
    }
    
    // If multiple days, show count
    if (count($valid_days) > 1) {
        return $training_days . ' (' . count($valid_days) . ' days / week)';
    }
    
    // Single day
    return $training_days . ' (1 day / week)';
}
// --- END: Calorie & Macro Calculation Functions ---


// --- 1. HANDLE FORM SUBMISSION (UPDATE DETAILS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_details'])) {
    // Debug: Log what's being submitted
    error_log("Training days submitted: " . print_r($_POST['training_days'] ?? [], true));
    error_log("Training days hidden: " . ($_POST['training_days_hidden'] ?? 'empty'));
    
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $focus = filter_input(INPUT_POST, 'focus', FILTER_SANITIZE_STRING);
    $goal = filter_input(INPUT_POST, 'goal', FILTER_SANITIZE_STRING);
    $activity = filter_input(INPUT_POST, 'activity', FILTER_SANITIZE_STRING);
    
    // Handle training days checkboxes - FIXED
    $training_days_selected = isset($_POST['training_days']) ? $_POST['training_days'] : [];
    if (empty($training_days_selected)) {
        // If no checkboxes selected, use empty string
        $training_days = '';
    } else {
        // Convert array to comma-separated string - MATCHING REGISTER.PHP FORMAT
        $training_days = implode(", ", $training_days_selected);
    }
    
    $weight_kg = filter_input(INPUT_POST, 'weight_kg', FILTER_VALIDATE_FLOAT);
    $height_cm = filter_input(INPUT_POST, 'height_cm', FILTER_VALIDATE_FLOAT);

    if ($age === false || $age < 1 || empty($gender) || empty($focus) || empty($goal) || empty($activity) || $weight_kg === false || $weight_kg <= 0 || $height_cm === false || $height_cm <= 0) {
        $_SESSION['error_message'] = 'Error: Please provide valid input for all fields.';
        header("Location: Membership.php");
        exit();
    } else {
        $height_m = $height_cm / 100;
        $bmi = $weight_kg / ($height_m * $height_m);

        $update_sql = "UPDATE users SET age=?, gender=?, focus=?, goal=?, activity=?, training_days=?, weight_kg=?, height_cm=?, bmi=? WHERE email=?";
        include 'connection.php'; 
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt) {
            // CORRECTED: 10 parameters with correct type string "isssssddds"
            // i = age (integer)
            // s = gender (string)
            // s = focus (string)
            // s = goal (string)
            // s = activity (string)
            // s = training_days (string)
            // d = weight_kg (double)
            // d = height_cm (double)
            // d = bmi (double)
            // s = email (string)
            $update_stmt->bind_param("isssssddds", $age, $gender, $focus, $goal, $activity, $training_days, $weight_kg, $height_cm, $bmi, $emailToFetch);
            
            if ($update_stmt->execute()) {
                $update_stmt->close(); 
                $_SESSION['success_message'] = 'Success! Your details have been updated.';
                header("Location: Membership.php");
                exit(); 
            } else {
                $_SESSION['error_message'] = 'Database Error: Could not update details. ' . $update_stmt->error;
                $update_stmt->close(); 
                header("Location: Membership.php");
                exit();
            }
        } else {
            $_SESSION['error_message'] = 'Error preparing update query.';
            header("Location: Membership.php");
            exit();
        }
    }
}


// --- 1.5. HANDLE FORM SUBMISSION (LOG MEAL) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_calories'])) {
    // Check if user has exceeded calorie intake before processing
    $user_id_log = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    
    // First, fetch today's total calories to check if user has exceeded
    $today = date("Y-m-d");
    $check_sql = "SELECT SUM(calories) AS total_calories FROM dietary_logs WHERE user_id = ? AND log_date = ?";
    include 'connection.php';
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $user_id_log, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    $current_total_calories = $check_result['total_calories'] ?? 0;
    $check_stmt->close();
    
    // Fetch user's daily calorie goal
    $goal_sql = "SELECT weight_kg, height_cm, age, gender, activity, goal FROM users WHERE id = ?";
    $goal_stmt = $conn->prepare($goal_sql);
    $goal_stmt->bind_param("i", $user_id_log);
    $goal_stmt->execute();
    $goal_result = $goal_stmt->get_result()->fetch_assoc();
    $goal_stmt->close();
    
    // Calculate daily calorie goal
    if ($goal_result && $goal_result['weight_kg'] > 0 && $goal_result['height_cm'] > 0 && $goal_result['age'] > 0) {
        $bmr = calculateBMR($goal_result['weight_kg'], $goal_result['height_cm'], $goal_result['age'], $goal_result['gender']);
        $tdee = calculateTDEE($bmr, $goal_result['activity']);
        $daily_calorie_goal = calculateDailyCalorieGoal($tdee, $goal_result['goal']);
        
        // Check if user has already exceeded their daily calorie goal
        if ($current_total_calories >= $daily_calorie_goal) {
            $_SESSION['error_message'] = 'Error: You have already exceeded your daily calorie intake. Cannot log more meals today.';
            header("Location: Membership.php");
            exit();
        }
    }
    
    // If not exceeded, continue with normal logging process
    $meal_type = filter_input(INPUT_POST, 'meal_type', FILTER_SANITIZE_STRING);
    $calories_intake = filter_input(INPUT_POST, 'calories_intake', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $log_date = date("Y-m-d"); 

    $image_path = NULL; // Default to NULL

    if ($user_id_log === false || $calories_intake === false || $calories_intake <= 0 || empty($meal_type) || empty($description)) {
        $_SESSION['error_message'] = 'Error: Please provide valid input for Meal Type, Description, and Calories.';
    } else {
        // Handle File Upload
        if (isset($_FILES['food_picture']) && $_FILES['food_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/food_logs/'; 
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); 
            }
            
            $file_extension = pathinfo($_FILES['food_picture']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('log_', true) . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['food_picture']['type'], $allowed_types) && $_FILES['food_picture']['size'] < 5000000) { 
                if (move_uploaded_file($_FILES['food_picture']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $_SESSION['error_message'] = 'Warning: File upload failed (move error/permissions issue). Log saved without picture.';
                }
            } else {
                $_SESSION['error_message'] = 'Warning: File type or size invalid. Log saved without picture.';
            }
        }
        
        // Prepare INSERT query
        $insert_sql = "INSERT INTO dietary_logs (user_id, log_date, meal_type, description, calories, image_path) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        if ($insert_stmt) {
            $insert_stmt->bind_param("isssds", $user_id_log, $log_date, $meal_type, $description, $calories_intake, $image_path);
            
            if ($insert_stmt->execute()) {
                if (!isset($_SESSION['error_message'])) {
                    $_SESSION['success_message'] = 'Meal successfully logged!';
                }
                $insert_stmt->close();
                header("Location: Membership.php");
                exit(); 
            } else {
                $_SESSION['error_message'] = 'Database Error: Could not log meal. ' . $insert_stmt->error;
                $insert_stmt->close();
            }
        } else {
            $_SESSION['error_message'] = 'Error preparing log query.';
        }
    }
    header("Location: Membership.php");
    exit();
}

// --- 1.6. HANDLE FORM SUBMISSION (RESET TODAY'S LOGS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_logs'])) {
    $user_id_reset = filter_input(INPUT_POST, 'user_id_reset', FILTER_VALIDATE_INT);
    $today = date("Y-m-d");

    if ($user_id_reset !== false) {
        include 'connection.php'; 

        $delete_sql = "DELETE FROM dietary_logs WHERE user_id = ? AND log_date = ?";
        $delete_stmt = $conn->prepare($delete_sql);

        if ($delete_stmt) {
            $delete_stmt->bind_param("is", $user_id_reset, $today);
            
            if ($delete_stmt->execute()) {
                // Clear any cached logs data
                if (isset($_SESSION['recent_logs_cache'])) {
                    unset($_SESSION['recent_logs_cache']);
                }
                
                $_SESSION['success_message'] = "All logs for today ({$today}) have been reset successfully.";
                $delete_stmt->close();
                
                // Redirect with cache busting parameter
                header("Location: Membership.php?reset=" . time());
                exit();
            } else {
                $_SESSION['error_message'] = 'Database Error: Could not reset logs.';
                $delete_stmt->close();
            }
        } else {
            $_SESSION['error_message'] = 'Error preparing reset query.';
        }
    } else {
        $_SESSION['error_message'] = 'Error: Invalid user ID for reset.';
    }
    header("Location: Membership.php");
    exit();
}


// --- 2. FETCH CURRENT USER DETAILS & CALCULATE GOAL & MACROS ---
$sql = "SELECT id, fullname, email, age, gender, focus, goal, activity, training_days, bmi, weight_kg, height_cm FROM users WHERE email = ?";
if (!isset($conn) || !$conn->ping()) {
    include 'connection.php';
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $emailToFetch);
$stmt->execute();
$result = $stmt->get_result();
$user = $result && $result->num_rows > 0 ? $result->fetch_assoc() : null;

// Debug: Check training days value
if ($user) {
    error_log("User ID: " . $user['id']);
    error_log("Training days from DB raw: '" . ($user['training_days'] ?? 'NULL') . "'");
    error_log("Training days formatted: '" . formatTrainingDays($user['training_days'] ?? '') . "'");
}

$bmi_status = getBMIStatus($user['bmi']);
$stmt->close();

$daily_calorie_goal = 0;
$total_today_calories = 0;
$calorie_percent = 0;
$recent_logs = []; 
$user_id = $user['id'] ?? null; 
$macro_grams = ['ProteinGrams' => 0, 'CarbGrams' => 0, 'FatGrams' => 0];
$macro_split = ['Protein' => 0, 'Carbs' => 0, 'Fat' => 0];


// Track if user has exceeded calorie intake
$has_exceeded_calories = false;

if ($user && $user_id) {
    // 2.1 Calculate Calorie Goal
    $weight_kg = $user['weight_kg'];
    $height_cm = $user['height_cm'];
    $age = $user['age'];
    $gender = $user['gender'];
    $activity = $user['activity'];
    $goal = $user['goal']; // Ensure this is available

    
    if ($weight_kg > 0 && $height_cm > 0 && $age > 0) {
        $bmr = calculateBMR($weight_kg, $height_cm, $age, $gender);
        $tdee = calculateTDEE($bmr, $activity);
        $daily_calorie_goal = calculateDailyCalorieGoal($tdee, $goal);

        // 2.2 Calculate Macros based on Goal
        $macro_split = getMacroSplit($goal);
        $macro_grams = calculateMacroGrams($daily_calorie_goal, $macro_split);
        
    } else {
        if ($daily_calorie_goal == 0 && empty($message)) {
            $message = '<div class="bg-yellow-100 text-yellow-700 p-3 rounded-lg">Warning: Please update your Height and Weight to calculate your Calorie Goal and Dietary Plan.</div>';
        }
    }


    // 2.3 Fetch Today's Logged Calories
    $today = date("Y-m-d");
    $log_sql = "SELECT SUM(calories) AS total_calories FROM dietary_logs WHERE user_id = ? AND log_date = ?";
    
    if (!isset($conn) || !$conn->ping()) {
        include 'connection.php'; 
    }

    $log_stmt = $conn->prepare($log_sql);
    if ($log_stmt) {
        $log_stmt->bind_param("is", $user_id, $today);
        $log_stmt->execute();
        $log_result = $log_stmt->get_result()->fetch_assoc();
        $total_today_calories = $log_result['total_calories'] ?? 0;
        $log_stmt->close();
    }
    
    // 2.4 Calculate Progress Percentage
    if ($daily_calorie_goal > 0) {
        $calorie_percent = round(($total_today_calories / $daily_calorie_goal) * 100);
    }
    
    // 2.5 Check if user has exceeded calorie intake
    if ($daily_calorie_goal > 0 && $total_today_calories > $daily_calorie_goal) {
        $has_exceeded_calories = true;
    }
    
    // --- 2.6 Fetch Recent Dietary Logs ---
    // FIXED: Now only shows today's logs
    $logs_sql = "SELECT log_date, meal_type, description, calories, image_path FROM dietary_logs WHERE user_id = ? AND log_date = ? ORDER BY log_id DESC LIMIT 10";
    
    if (!isset($conn) || !$conn->ping()) {
        include 'connection.php'; 
    }

    $logs_stmt = $conn->prepare($logs_sql);
    if ($logs_stmt) {
        $logs_stmt->bind_param("is", $user_id, $today); // Added date parameter
        $logs_stmt->execute();
        $logs_result = $logs_stmt->get_result();
        while ($row = $logs_result->fetch_assoc()) {
            $recent_logs[] = $row;
        }
        $logs_stmt->close();
    }
}

// Close the connection
if (isset($conn)) {
    $conn->close();
}

// Prepare details for the view mode (card layout)
$view_details = [
    ['label' => 'Age', 'value' => $user['age'] . ' years', 'icon' => 'bx-calendar-alt', 'color' => 'blue'],
    ['label' => 'Gender', 'value' => $user['gender'], 'icon' => 'bx-male-female', 'color' => 'pink'],
    ['label' => 'Focus Area', 'value' => $user['focus'], 'icon' => 'bx-target-lock', 'color' => 'purple'],
    ['label' => 'Goal', 'value' => $user['goal'], 'icon' => 'bx-run', 'color' => 'green'],
    ['label' => 'Activity Level', 'value' => $user['activity'], 'icon' => 'bx-trending-up', 'color' => 'red'],
    ['label' => 'Training Days', 'value' => formatTrainingDays($user['training_days']), 'icon' => 'bx-dumbbell', 'color' => 'orange'],
    ['label' => 'BMI', 'value' => number_format($user['bmi'], 2), 'icon' => 'bx-body', 'color' => 'yellow'],
    ['label' => 'BMI Status','value' => $bmi_status,'icon' => 'bx-health', 'color' => 'teal'],
];


// --- Dietary Plan Suggestions based on Goal ---
$diet_plan_suggestions = [
    'Gain Muscle' => [
        'title' => 'Muscle Gain Plan',
        'recommendation' => 'Focus on hitting your high protein target using affordable meats and beans. Consume rice or Saging Saba pre and post-workout to fuel growth.',
        'meals' => [
            ['meal' => 'Breakfast', 'cal_percent' => '25%', 'suggestion' => 'Oatmeal , Eggs, and Saging Saba (boiled/steamed banana, high carbs)'],
            ['meal' => 'Lunch', 'cal_percent' => '35%', 'suggestion' => 'Pork Adobo (lean cut), Rice, and Ginisang Ampalaya (Sautéed Bitter Gourd)'],
            ['meal' => 'Dinner', 'cal_percent' => '30%', 'suggestion' => 'Grilled Chicken Leg or Breast, Sweet Potato, Sweet Potato Leaf Salad'],
            ['meal' => 'Snack/Post-Workout', 'cal_percent' => '10%', 'suggestion' => 'Taho (Silken Tofu/Soya), or Protein Shake with Mango'],
        ]
    ],
    'Lose Weight' => [
        'title' => 'Weight Loss Plan',
        'recommendation' => 'Prioritize protein for satiety using lean fish and beans. Focus on high-volume, low-calorie vegetables. Reduce rice portion size.',
        'meals' => [
            ['meal' => 'Breakfast', 'cal_percent' => '20%', 'suggestion' => 'Boiled Eggs, Ginisang Sitaw at Kalabasa (Sautéed String Beans & Squash)'],
            ['meal' => 'Lunch', 'cal_percent' => '30%', 'suggestion' => 'Tilapia or Galunggong, large serving of steamed vegetables'],
            ['meal' => 'Dinner', 'cal_percent' => '35%', 'suggestion' => 'Monggo (Mung Bean Soup) with Malunggay leaves, small portion of rice'],
            ['meal' => 'Snack', 'cal_percent' => '15%', 'suggestion' => 'Singkamas, Cucumber slices, or a small bowl of Kalamansi (Calamansi) juice'],
        ]
    ],
    'Stay Fit' => [
        'title' => 'Balanced Plan',
        'recommendation' => 'A balanced approach using variety and whole common foods. Moderate rice intake and include a variety of fruits and vegetables.',
        'meals' => [
            ['meal' => 'Breakfast', 'cal_percent' => '25%', 'suggestion' => 'Pandesal (whole-wheat if available) with Kesong Puti (White Cheese), and fresh Papaya'],
            ['meal' => 'Lunch', 'cal_percent' => '35%', 'suggestion' => 'Tinola (Chicken soup with Sayote and Malunggay), moderate rice'],
            ['meal' => 'Dinner', 'cal_percent' => '30%', 'suggestion' => 'Ginataang Hipon (Shrimp in Coconut Milk), Brown Rice (if preferred/available) or white rice, Mixed Vegetables'],
            ['meal' => 'Snack', 'cal_percent' => '10%', 'suggestion' => 'Handful of Mani (Peanuts) or a piece of Bayabas (Guava)'],
        ]
    ],
    'Maintain' => [
        'title' => 'Maintenance Plan',
        'recommendation' => 'A balanced approach using variety and whole common foods. Moderate rice intake and include a variety of fruits and vegetables.',
        'meals' => [
            ['meal' => 'Breakfast', 'cal_percent' => '25%', 'suggestion' => 'Pandesal (whole-wheat if available) with Kesong Puti (White Cheese), and fresh Papaya'],
            ['meal' => 'Lunch', 'cal_percent' => '35%', 'suggestion' => 'Tinola (Chicken soup with Sayote and Malunggay), moderate rice'],
            ['meal' => 'Dinner', 'cal_percent' => '30%', 'suggestion' => 'Ginataang Hipon (Shrimp in Coconut Milk), Brown Rice (if preferred/available) or white rice, Mixed Vegetables'],
            ['meal' => 'Snack', 'cal_percent' => '10%', 'suggestion' => 'Handful of Mani (Peanuts) or a piece of Bayabas (Guava)'],
        ]
    ],
    'Default' => [
        'title' => 'Balanced Plan',
        'recommendation' => 'A balanced approach using variety and whole common foods. Moderate rice intake and include a variety of fruits and vegetables.',
        'meals' => [
            ['meal' => 'Breakfast', 'cal_percent' => '25%', 'suggestion' => 'Pandesal (whole-wheat if available) with Kesong Puti (White Cheese), and fresh Papaya'],
            ['meal' => 'Lunch', 'cal_percent' => '35%', 'suggestion' => 'Tinola (Chicken soup with Sayote and Malunggay), moderate rice'],
            ['meal' => 'Dinner', 'cal_percent' => '30%', 'suggestion' => 'Ginataang Hipon (Shrimp in Coconut Milk), Brown Rice (if preferred/available) or white rice, Mixed Vegetables'],
            ['meal' => 'Snack', 'cal_percent' => '10%', 'suggestion' => 'Handful of Mani (Peanuts) or a piece of Bayabas (Guava)'],
        ]
    ]
];

$plan_key = $user['goal'] ?? 'Default';
if (!array_key_exists($plan_key, $diet_plan_suggestions)) {
    $plan_key = 'Default';
}
$current_plan = $diet_plan_suggestions[$plan_key];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="Memberstyle.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
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
        
        /* Animated progress bar for calorie intake */
        @keyframes progressAnimation {
            0% { width: 0%; }
            100% { width: var(--progress-width); }
        }
        
        .progress-animated {
            animation: progressAnimation 1.5s ease-out forwards;
        }
        
        /* Header with notification bell */
        .header-with-notification {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin-bottom: 2rem;
        }
        
        .notification-bell-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-bell {
            cursor: pointer;
            font-size: 26px;
            color: #000000;
            position: relative;
        }
        
        .notification-count {
            background: red;
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 12px;
            position: absolute;
            top: -8px;
            right: -8px;
            display: none;
        }
        
        .notification-panel {
            position: absolute;
            top: 40px;
            right: 0;
            width: 260px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px;
            display: none;
            z-index: 1000;
        }
        
        .notification-panel h4 {
            margin: 0 0 10px;
            font-weight: bold;
            color: #000;
            font-size: 1.1rem;
        }
        
        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .notification-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 0.9rem;
        }
        
        .notification-list li:last-child {
            border-bottom: none;
        }
        
        /* FIX: Edit button should work in one click */
        #edit-button {
            cursor: pointer;
        }
        
        /* FIX: Align Dietary Plan heading to left */
        .dietary-plan-heading {
            text-align: left !important;
        }
    </style>
</head>
<body class="min-h-screen">

<!-- Mobile Top Navbar -->
<nav class="mobile-navbar" id="mobileNavbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <i class='bx bx-user text-yellow-500 text-2xl'></i>
            <h2>Member Panel</h2>
        </div>
        <button class="navbar-toggle" id="navbarToggle">
            <i class='bx bx-menu'></i>
        </button>
    </div>
    <div class="navbar-menu" id="navbarMenu">
        <ul>
            <li><a href="#" class="active"><i class='bx bx-user'></i> User Details</a></li>
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
        <li><a href="#" class="bg-gray-700 text-white"><i class='bx bx-user'></i> User Details</a></li>
        <li><a href="WorkoutJournal.php"><i class='bx bx-notepad'></i> Workout Journal</a></li>
        <li><a href="Progress.php"><i class='bx bx-line-chart'></i> Progress</a></li>
        <li><a href="TrainerBooking.php"><i class='bx bxs-user-pin'></i> Trainers</a></li>
        <li class="more-menu">
            <a href="#" class="more-toggle">
                <i class='bx bx-dots-horizontal-rounded'></i> More 
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

<!-- Main Content -->
<main class="main-content">
    <div class="cards-container">
        <!-- Header with Title and Notification Bell -->
        <div class="header-with-notification">
            <h1 class="main-heading">User Profile & Details</h1>
            
            <div class="notification-bell-container">
                <div class="notification-bell" id="notifBell">
                    <i class='bx bx-bell'></i>
                    <span class="notification-count" id="notifCount">0</span>
                    
                    <div class="notification-panel" id="notifPanel">
                        <h4>Notifications</h4>
                        <ul class="notification-list" id="notifList"></ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message-container mb-6">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <!-- User Profile Card -->
            <div class="bg-gray-50 p-6 rounded-xl shadow-2xl border-t-4 border-black w-full relative card-container">
                
                <!-- Edit Button - Responsive Positioning -->
                <div class="flex justify-end mb-4 md:absolute md:top-6 md:right-6">
                    <button id="edit-button" onclick="toggleEditMode()" class="bg-black hover:bg-yellow-500 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out z-10">
                    <i class='bx bx-edit-alt mr-1'></i> Edit Details
                </button>
                </div>
                
                <!-- User Name & Email -->
                <div class="text-center pb-6 border-b border-gray-200 mb-6 md:mt-0">
                    <p class="text-3xl font-extrabold text-black"><?= htmlspecialchars($user['fullname']) ?></p>
                    <p class="text-gray-500 italic mt-1"><?= htmlspecialchars($user['email']) ?></p>
                </div>
                
                <!-- View Mode Details (Grid layout) -->
                <div id="view-details-container" class="grid">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="flex items-center space-x-4 bg-white p-4 rounded-lg shadow-md">
                             <div class="p-3 rounded-full bg-red-100 text-red-600">
                                 <i class='bx bx-line-chart-down text-2xl'></i>
                             </div>
                             <div>
                                 <p class="text-sm font-medium text-gray-500">Weight</p>
                                 <p class="text-lg font-bold text-gray-800"><?= number_format($user['weight_kg'], 1) ?> kg</p>
                             </div>
                        </div>
                        <div class="flex items-center space-x-4 bg-white p-4 rounded-lg shadow-md">
                             <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                 <i class='bx bx-ruler text-2xl'></i>
                             </div>
                             <div>
                                 <p class="text-sm font-medium text-gray-500">Height</p>
                                 <p class="text-lg font-bold text-gray-800"><?= number_format($user['height_cm'], 0) ?> cm</p>
                             </div>
                        </div>
                        
                        <?php foreach ($view_details as $detail):
                            $bgColor = "bg-{$detail['color']}-100";
                            $iconColor = "text-{$detail['color']}-600";
                        ?>
                            <div class="flex items-center space-x-4 bg-white p-4 rounded-lg shadow-md">
                                <div class="p-3 rounded-full <?= $bgColor ?> <?= $iconColor ?>">
                                    <i class='bx <?= $detail['icon'] ?> text-2xl'></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500"><?= $detail['label'] ?></p>
                                    <p class="text-lg font-bold text-gray-800"><?= $detail['value'] ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Edit Form (Hidden by default) -->
                <div id="edit-form-container" style="display: none;">
                    <form method="POST" action="Membership.php">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="flex flex-col">
                                <label for="weight_kg" class="text-sm font-medium text-gray-500 mb-1">Weight (kg)</label>
                                <input type="number" name="weight_kg" id="weight_kg" value="<?= htmlspecialchars($user['weight_kg']) ?>" 
                                    class="p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-gray-800" step="0.1" required>
                            </div>
                            <div class="flex flex-col">
                                <label for="height_cm" class="text-sm font-medium text-gray-500 mb-1">Height (cm)</label>
                                <input type="number" name="height_cm" id="height_cm" value="<?= htmlspecialchars($user['height_cm']) ?>" 
                                    class="p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-gray-800" required>
                            </div>
                            
                            <div class="flex flex-col">
                                <label for="age" class="text-sm font-medium text-gray-500 mb-1">Age</label>
                                <input type="number" name="age" id="age" value="<?= htmlspecialchars($user['age']) ?>" 
                                    class="p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-gray-800" required>
                            </div>
                            
                            <div class="flex flex-col">
                                <label for="gender" class="text-sm font-medium text-gray-500 mb-1">Gender</label>
                                <select name="gender" id="gender" class="p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-gray-800" required>
                                    <option value="Male" <?= ($user['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($user['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($user['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="flex flex-col">
                                <label for="focus" class="text-sm font-medium text-gray-500 mb-1">Focus Area</label>
                                <select name="focus" id="focus" class="p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-gray-800" required>
                                    <option value="Arms" <?= ($user['focus'] == 'Arms') ? 'selected' : '' ?>>Arms</option>
                                    <option value="Chest" <?= ($user['focus'] == 'Chest') ? 'selected' : '' ?>>Chest</option>
                                    <option value="Legs" <?= ($user['focus'] == 'Legs') ? 'selected' : '' ?>>Legs</option>
                                    <option value="Full Body" <?= ($user['focus'] == 'Full Body') ? 'selected' : '' ?>>Full Body</option>
                                </select>
                            </div>
                            
                            <div class="flex flex-col">
                                <label for="goal" class="text-sm font-medium text-gray-500 mb-1">Main Goal</label>
                                <select name="goal" id="goal" class="p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-gray-800" required>
                                    <option value="Lose Weight" <?= ($user['goal'] == 'Lose Weight') ? 'selected' : '' ?>>Lose Weight</option>
                                    <option value="Gain Muscle" <?= ($user['goal'] == 'Gain Muscle') ? 'selected' : '' ?>>Gain Muscle</option>
                                    <option value="Stay Fit" <?= ($user['goal'] == 'Stay Fit') ? 'selected' : '' ?>>Stay Fit</option>
                                    <option value="Maintain" <?= ($user['goal'] == 'Maintain') ? 'selected' : '' ?>>Maintain</option>
                                </select>
                            </div>

                            <div class="flex flex-col">
                                <label for="activity" class="text-sm font-medium text-gray-500 mb-1">Activity Level</label>
                                <select name="activity" id="activity" class="p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-gray-800" required>
                                    <option value="Low" <?= ($user['activity'] == 'Low') ? 'selected' : '' ?>>Low</option>
                                    <option value="Moderate" <?= ($user['activity'] == 'Moderate') ? 'selected' : '' ?>>Moderate</option>
                                    <option value="High" <?= ($user['activity'] == 'High') ? 'selected' : '' ?>>High</option>
                                </select>
                            </div>

                            <div class="flex flex-col">
                                <label for="training_days" class="text-sm font-medium text-gray-500 mb-1">Training Days</label>
                                <div class="training-days-checkboxes grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mt-2">
                                    <?php
                                    // Parse current training days from the database - FIXED VERSION
                                    $current_days_string = $user['training_days'] ?? '';
                                    $current_days = [];
                                    
                                    if (!empty($current_days_string) && $current_days_string !== 'NULL') {
                                        // Split by comma (with optional space) - matches Register.php format
                                        $current_days = preg_split('/\s*,\s*/', $current_days_string);
                                    }
                                    
                                    $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    
                                    foreach ($days_of_week as $day) {
                                        // Check if day is in current days array
                                        $is_checked = in_array($day, $current_days);
                                        $checked_attr = $is_checked ? 'checked' : '';
                                        echo "
                                        <div class='flex items-center'>
                                            <input type='checkbox' name='training_days[]' id='day_{$day}' value='{$day}' {$checked_attr} 
                                                class='h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500'>
                                            <label for='day_{$day}' class='ml-2 text-sm text-gray-700'>{$day}</label>
                                        </div>";
                                    }
                                    ?>
                                </div>
                                <input type="hidden" name="training_days_hidden" id="training_days_hidden" value="<?= htmlspecialchars($user['training_days'] ?? '') ?>">
                            </div>
                            
                            <div class="flex flex-col md:col-span-2">
                                <label class="text-sm font-medium text-gray-500 mb-1">BMI (Automatically Recalculated)</label>
                                <input type="text" value="<?= number_format($user['bmi'], 2) ?>" 
                                    class="p-2 border border-gray-200 bg-gray-100 rounded-lg text-gray-600 cursor-not-allowed" readonly>
                            </div>

                        </div> 
                        <div class="mt-8 text-center space-x-4">
                            <button type="submit" name="update_details" class="bg-black hover:bg-yellow-500 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-150 ease-in-out">
                                <i class='bx bx-save mr-2'></i> Save Changes
                            </button>
                            <button type="button" id="cancel-edit-button" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-150 ease-in-out">
                                <i class='bx bx-x mr-2'></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div> 
            
            <?php if ($daily_calorie_goal > 0): ?>
                <!-- Dietary Plan Card -->
                <div class="bg-gray-50 p-6 rounded-xl shadow-2xl border-t-4 border-black w-full relative mt-8 card-container">
                    <h2 class="main-heading dietary-plan-heading text-black">
                        Suggested Dietary Plan
                    </h2>

                    <div class="mb-6 p-4 border rounded-lg bg-white shadow-inner">
                        <p class="text-xl font-semibold text-gray-700 mb-2">Daily Targets:</p>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="p-2 bg-blue-100 rounded-md">
                                <p class="text-sm text-blue-600 font-medium">Protein (<?= $macro_split['Protein'] ?>%)</p>
                                <p class="text-lg font-bold text-gray-800"><?= number_format($macro_grams['ProteinGrams']) ?> g</p>
                            </div>
                            <div class="p-2 bg-yellow-100 rounded-md">
                                <p class="text-sm text-yellow-600 font-medium">Carbs (<?= $macro_split['Carbs'] ?>%)</p>
                                <p class="text-lg font-bold text-gray-800"><?= number_format($macro_grams['CarbGrams']) ?> g</p>
                            </div>
                            <div class="p-2 bg-red-100 rounded-md">
                                <p class="text-sm text-red-600 font-medium">Fat (<?= $macro_split['Fat'] ?>%)</p>
                                <p class="text-lg font-bold text-gray-800"><?= number_format($macro_grams['FatGrams']) ?> g</p>
                            </div>
                        </div>
                        <p class="text-sm italic text-center mt-3 text-black">Goal: <?= htmlspecialchars($user['goal']) ?> (<?= number_format($daily_calorie_goal) ?> kcal)</p>
                    </div>

                    <h3 class="text-xl font-semibold mb-3 border-b pb-2">Meal Distribution</h3>
                    <p class="text-sm text-gray-600 mb-4 italic"><?= $current_plan['recommendation'] ?></p>

                    <div class="space-y-3">
                        <?php foreach ($current_plan['meals'] as $meal): ?>
                            <div class="bg-white p-3 rounded-lg border-l-4 border-gray-300 flex items-center shadow-sm">
                                <div class="w-24 flex-shrink-0">
                                    <p class="font-bold text-gray-800"><?= $meal['meal'] ?></p>
                                    <p class="text-xs text-gray-500"><?= $meal['cal_percent'] ?> of Calories</p>
                                </div>
                                <div class="ml-4 flex-grow">
                                    <p class="text-sm text-gray-700"><?= $meal['suggestion'] ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Calorie Tracker Card -->
            <div class="bg-gray-50 p-6 rounded-xl shadow-2xl border-t-4 border-black w-full relative mt-8 card-container">
                <h2 class="main-heading text-black flex items-center">
                    <i class='bx bxs-food-menu mr-2'></i> Daily Calorie Tracker
                </h2>

                <div class="mb-6 p-4 border rounded-lg bg-white shadow-inner">
                    <div class="flex justify-between items-end mb-2">
                        <p class="text-xl font-semibold text-gray-700">Today's Intake: <span class="text-emerald-600"><?= number_format($total_today_calories) ?></span> kcal</p>
                        <p class="text-md font-medium text-gray-500">Goal: <?= number_format($daily_calorie_goal) ?> kcal</p>
                    </div>

                    <?php 
                    // Calculate calories remaining
                    $calories_remaining = $daily_calorie_goal - $total_today_calories;
                    
                    // Determine progress bar color and message based on conditions
                    if ($total_today_calories > $daily_calorie_goal) {
                        // Exceeded goal - RED
                        $progressBarColor = 'bg-red-500';
                        $messageClass = 'text-red-600 font-bold';
                        $messageText = '⚠️ You have exceeded your daily calorie intake!';
                    } elseif ($calories_remaining <= 300 && $calories_remaining > 0) {
                        // Within 300 calories of goal - ORANGE
                        $progressBarColor = 'bg-orange-500';
                        $messageClass = 'text-orange-600 font-bold';
                        $messageText = '⚠️ You are close to your daily calorie goal (' . number_format($calories_remaining) . ' calories remaining)';
                    } elseif ($calories_remaining <= 0) {
                        // At or above goal (but not exceeded - this shouldn't happen due to first condition, but as fallback)
                        $progressBarColor = 'bg-green-500';
                        $messageClass = 'text-green-600 font-bold';
                        $messageText = '🎉 Daily calorie goal reached!';
                    } else {
                        // Normal progress - BLUE
                        $progressBarColor = 'bg-yellow-500';
                        $messageClass = 'text-gray-500';
                        $messageText = 'Keep going...';
                    }
                    
                    // Calculate percentage (cap at 100% for display when exceeded)
                    $display_percent = min(100, $calorie_percent);
                    ?>

                    <div class="w-full bg-gray-200 rounded-full h-4 relative overflow-hidden">
                        <div class="h-4 rounded-full transition-all duration-500 <?= $progressBarColor ?> progress-animated" 
                             style="width: <?= $display_percent ?>%; --progress-width: <?= $display_percent ?>%;">
                            <span class="text-xs font-medium text-white absolute inset-0 flex items-center justify-center">
                                <?= $display_percent ?>%
                            </span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-center <?= $messageClass ?>"><?= $messageText ?></p>
                </div>

                <h3 class="text-xl font-semibold mb-3 border-b pb-2">Log Your Meal</h3>
                
                <?php if ($has_exceeded_calories): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class='bx bx-error-circle text-red-400 text-2xl'></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-red-800 font-bold">Logging Disabled</p>
                                <p class="text-red-700 mt-1">You have exceeded your daily calorie intake of <?= number_format($daily_calorie_goal) ?> kcal. You cannot log more meals today.</p>
                                <p class="text-red-600 text-sm mt-2">Please use the "Reset Today's Logs" button below to start fresh tomorrow.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="Membership.php" enctype="multipart/form-data" id="log-meal-form" class="<?= $has_exceeded_calories ? 'disabled-form' : '' ?>">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="flex flex-col">
                            <label for="meal_type" class="text-sm font-medium text-black mb-1">Meal Type</label>
                            <select name="meal_type" id="meal_type" class="p-2 border border-gray-300 rounded-lg" required <?= $has_exceeded_calories ? 'disabled' : '' ?>>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Snack">Snack</option>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label for="calories_intake" class="text-sm font-medium text-black mb-1">Calories (kcal)</label>
                            <input type="number" name="calories_intake" id="calories_intake" min="1" step="0.1"
                                class="p-2 border border-gray-300 rounded-lg" placeholder="e.g. 500.5" required <?= $has_exceeded_calories ? 'disabled' : '' ?>>
                        </div>
                    </div>
                    
                    <div class="flex flex-col mb-4">
                        <label for="description" class="text-sm font-medium text-black mb-1">Description</label>
                        <input type="text" name="description" id="description" 
                            class="p-2 border border-gray-300 rounded-lg" placeholder="What did you eat?" required <?= $has_exceeded_calories ? 'disabled' : '' ?>>
                    </div>
                    
                    <div class="flex flex-col mb-4">
                        <label for="food_picture" class="text-sm font-medium text-black mb-1">Upload Picture (Optional)</label>
                        <input type="file" name="food_picture" id="food_picture" accept="image/jpeg, image/png, image/gif"
                            class="p-2 border border-gray-300 rounded-lg bg-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" <?= $has_exceeded_calories ? 'disabled' : '' ?>>
                    </div>
                    
                    <?php if ($has_exceeded_calories): ?>
                        <button type="button" class="w-full bg-gray-400 text-white font-bold py-2 px-6 rounded-lg shadow-md cursor-not-allowed" disabled>
                            <i class='bx bx-block mr-2'></i> Cannot Log Meal (Calorie Limit Exceeded)
                        </button>
                    <?php else: ?>
                        <button type="submit" name="log_calories" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-150 ease-in-out">
                            <i class='bx bx-check-circle mr-2'></i> Log Meal
                        </button>
                    <?php endif; ?>
                </form>

                <h3 class="text-xl font-semibold mb-3 border-b pb-2 mt-8 flex justify-between items-center">
                    Recent Logs
                    <form id="reset-logs-form" method="POST" action="Membership.php">
                        <input type="hidden" name="reset_logs" value="1">
                        <input type="hidden" name="user_id_reset" value="<?= htmlspecialchars($user['id']) ?>">
                        <button type="button" onclick="showResetModal()" class="text-sm text-red-600 hover:text-red-800 font-normal py-1 px-3 border border-red-300 rounded-lg transition duration-150">
                            Reset Today's Logs
                        </button>
                    </form>
                </h3>

                <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                    <?php if (!empty($recent_logs)): ?>
                        <?php foreach ($recent_logs as $log): ?>
                            <div class="bg-white p-3 rounded-lg border-l-4 border-emerald-400 shadow-sm flex justify-between items-center group hover:bg-gray-50 transition duration-150">
                                <div class="flex-grow min-w-0">
                                    <p class="font-medium text-gray-800 flex items-center">
                                        <?= htmlspecialchars($log['meal_type']) ?> 
                                        <span class="text-sm text-gray-500 italic ml-2">
                                            (<?= date('M d', strtotime($log['log_date'])) ?>)
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-600 truncate max-w-full">
                                        <?= htmlspecialchars($log['description']) ?>
                                    </p>
                                    <?php if ($log['image_path']): ?>
                                        <div class="mt-2 flex items-center space-x-2">
                                            <a href="<?= htmlspecialchars($log['image_path']) ?>" target="_blank" class="text-xs text-indigo-600 hover:text-black flex items-center">
                                                <i class='bx bx-image mr-1'></i> View Picture
                                            </a>
                                            <img src="<?= htmlspecialchars($log['image_path']) ?>" alt="Meal Picture" 
                                                class="w-10 h-10 object-cover rounded-md border border-gray-200"
                                                onerror="this.style.display='none';"> 
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-shrink-0 text-right ml-4">
                                    <span class="text-lg font-bold text-emerald-600"><?= number_format($log['calories'], 0) ?></span> 
                                    <span class="text-sm text-gray-500">kcal</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-gray-500 italic p-4">No meals logged yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-red-100 p-6 rounded-lg border border-red-400 card-container">
                <p class="text-red-700 font-semibold">Error: User details could not be loaded.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Reset Modal -->
<div id="reset-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 transition-opacity duration-300 opacity-0 pointer-events-none">
    <div class="bg-white p-8 rounded-xl shadow-2xl max-w-sm w-full transform transition-transform duration-300 scale-95">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class='bx bx-trash text-3xl text-red-600'></i>
            </div>
            <h3 class="mt-4 text-xl font-bold text-gray-900">Confirm Reset</h3>
            <div class="mt-2">
                <p class="text-sm text-gray-500">Are you sure you want to permanently delete ALL of your dietary logs for today? This action cannot be undone.</p>
            </div>
        </div>
        <div class="mt-5 sm:mt-6 space-y-3">
            <button id="confirm-reset-button" class="w-full justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-150">
                Yes, Reset Logs
            </button>
            <button id="cancel-reset-button" class="w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150">
                Cancel
            </button>
        </div>
    </div>
</div>

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
    
    // ====== 3. MOBILE SUBMENU TOGGLE (FIXED - NO DOUBLE CLICK) ======
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
    
    // ====== 4. RESET MODAL FUNCTIONS ======
    const resetModal = document.getElementById('reset-modal');
    const confirmButton = document.getElementById('confirm-reset-button');
    const cancelButton = document.getElementById('cancel-reset-button');
    const resetForm = document.getElementById('reset-logs-form');

    // Make showResetModal globally accessible
    window.showResetModal = function() {
        if (!resetModal) return; 
        resetModal.classList.remove('opacity-0', 'pointer-events-none');
        resetModal.querySelector('.bg-white').classList.remove('scale-95');
        resetModal.querySelector('.bg-white').classList.add('scale-100');
    }

    function hideResetModal() {
        if (!resetModal) return; 
        resetModal.classList.add('opacity-0', 'pointer-events-none');
        resetModal.querySelector('.bg-white').classList.remove('scale-100');
        resetModal.querySelector('.bg-white').classList.add('scale-95');
    }

    if (cancelButton && confirmButton) {
        cancelButton.addEventListener('click', hideResetModal);

        confirmButton.addEventListener('click', function() {
            hideResetModal();
            if (resetForm) {
                resetForm.submit();
            }
        });
    }

    if (resetModal) {
        resetModal.addEventListener('click', function(e) {
            if (e.target === resetModal) {
                hideResetModal();
            }
        });
    }
    
    // ====== 5. NOTIFICATION BELL FUNCTIONALITY ======
    const notifBell = document.getElementById("notifBell");
    const notifPanel = document.getElementById("notifPanel");
    const notifCount = document.getElementById("notifCount");
    const notifList = document.getElementById("notifList");

    // TEMPORARY SAMPLE NOTIFICATIONS
    const notifications = [
        "Your booking has been confirmed.",
        "Trainer updated availability.",
        "Payment successfully processed."
    ];

    // Load notifications
    function loadNotifications() {
        notifList.innerHTML = "";
        notifications.forEach(n => {
            const li = document.createElement("li");
            li.textContent = n;
            li.style.padding = "8px 0";
            li.style.borderBottom = "1px solid #eee";
            li.style.color = "#333";
            li.style.fontSize = "0.9rem";
            notifList.appendChild(li);
        });

        if (notifications.length > 0) {
            notifCount.style.display = "inline-block";
            notifCount.textContent = notifications.length;
        }
    }

    // Toggle panel
    if (notifBell) {
        notifBell.addEventListener("click", function(e) {
            e.stopPropagation();
            notifPanel.style.display = notifPanel.style.display === "none" ? "block" : "none";
            if (notifPanel.style.display === "block") {
                notifCount.style.display = "none";
            }
        });
    }

    // Close panel when clicking outside
    document.addEventListener("click", function(event) {
        if (notifPanel && notifPanel.style.display === "block" && 
            !notifBell.contains(event.target) && 
            !notifPanel.contains(event.target)) {
            notifPanel.style.display = "none";
        }
    });

    // Load on page start
    loadNotifications();
    
    // ====== 6. PROGRESS BAR ANIMATION ======
    const progressBars = document.querySelectorAll('.progress-animated');
    progressBars.forEach(bar => {
        setTimeout(() => {
            bar.style.animation = 'progressAnimation 1.5s ease-out forwards';
        }, 300);
    });
    
    // ====== 7. TRAINING DAYS CHECKBOXES HANDLING ======
    const trainingDaysCheckboxes = document.querySelectorAll('input[name="training_days[]"]');
    const trainingDaysHidden = document.getElementById('training_days_hidden');
    
    if (trainingDaysCheckboxes.length > 0 && trainingDaysHidden) {
        // Update hidden field when checkboxes change
        trainingDaysCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const selectedDays = Array.from(trainingDaysCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                trainingDaysHidden.value = selectedDays.join(', ');
            });
        });
        
        // Initialize hidden field value
        const initialSelectedDays = Array.from(trainingDaysCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        trainingDaysHidden.value = initialSelectedDays.join(', ');
    }
    
    // ====== 8. CANCEL EDIT BUTTON HANDLER ======
    const cancelEditButton = document.getElementById('cancel-edit-button');
    if (cancelEditButton) {
        cancelEditButton.addEventListener('click', function() {
            toggleEditMode();
        });
    }
    
    // ====== 9. INITIALIZE EDIT FORM STATE ======
    const viewContainer = document.getElementById('view-details-container');
    const editContainer = document.getElementById('edit-form-container');
    const editButton = document.getElementById('edit-button');
    
    if (viewContainer && editContainer && editButton) {
        // Initialize data attribute state
        editButton.setAttribute('data-edit-mode', 'false');
        
        // Force set initial display states
        viewContainer.style.display = 'grid';
        editContainer.style.display = 'none';
    }
    
    // ====== 10. PREVENT FORM SUBMISSION IF CALORIE LIMIT EXCEEDED ======
    const logForm = document.getElementById('log-meal-form');
    if (logForm) {
        logForm.addEventListener('submit', function(e) {
            <?php if ($has_exceeded_calories): ?>
                e.preventDefault();
                alert('You have exceeded your daily calorie intake. Cannot log more meals today.');
                return false;
            <?php endif; ?>
        });
    }
});

// ====== 11. EDIT DETAILS TOGGLE FUNCTION (GLOBAL) - FIXED VERSION ======
window.toggleEditMode = function() {
    const viewContainer = document.getElementById('view-details-container');
    const editContainer = document.getElementById('edit-form-container');
    const editButton = document.getElementById('edit-button');
    
    if (viewContainer && editContainer && editButton) {
        // Use data attribute to track state reliably
        const isEditMode = editButton.getAttribute('data-edit-mode') === 'true';
        
        if (isEditMode) {
            // Switch to view mode
            viewContainer.style.display = 'grid';
            editContainer.style.display = 'none';
            editButton.innerHTML = "<i class='bx bx-edit-alt mr-1'></i> Edit Details";
            editButton.classList.remove('bg-red-600', 'hover:bg-red-700');
            editButton.classList.add('bg-black', 'hover:bg-yellow-500');
            editButton.setAttribute('data-edit-mode', 'false');
        } else {
            // Switch to edit mode
            viewContainer.style.display = 'none';
            editContainer.style.display = 'block';
            editButton.innerHTML = "<i class='bx bx-x-circle mr-1'></i> Exit Edit";
            editButton.classList.remove('bg-black', 'hover:bg-yellow-500');
            editButton.classList.add('bg-red-600', 'hover:bg-red-700');
            editButton.setAttribute('data-edit-mode', 'true');
        }
    }
}

</script>
</body>
</html>