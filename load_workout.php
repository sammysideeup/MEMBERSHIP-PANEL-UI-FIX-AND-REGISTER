<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate'); // Prevent caching
header('Pragma: no-cache');
header('Expires: 0');

// --- 1. Authentication and User ID Retrieval ---

if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit();
}

$user_email = $_SESSION['email'];
$current_user_id = null;

// Add user-specific identifier for debugging
error_log("=== LOAD WORKOUT REQUEST ===");
error_log("User email: " . $user_email);
error_log("Session ID: " . session_id());

try {
    // 1a. Retrieve user_id from the database
    $sql_user = "SELECT id FROM users WHERE email = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $user_email);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($row_user = $result_user->fetch_assoc()) {
        $current_user_id = $row_user['id'];
        error_log("User ID found: " . $current_user_id);
    } else {
        error_log("User ID NOT FOUND for email: " . $user_email);
        echo json_encode(["success" => false, "message" => "User ID not found, invalid session data."]);
        exit();
    }
    $stmt_user->close();

    // 2. Get the day to load from the GET request
    if (!isset($_GET['day'])) {
        error_log("Day not specified in request");
        echo json_encode(["success" => false, "message" => "Day not specified."]);
        exit();
    }
    $day_of_week = $_GET['day'];
    error_log("Requested day: " . $day_of_week);

    // 3. Check if description column exists and load description
    $description = '';
    $check_column_sql = "SHOW COLUMNS FROM workout_journal LIKE 'description'";
    $column_result = $conn->query($check_column_sql);
    $has_description_column = ($column_result->num_rows > 0);
    $column_result->close();
    
    if ($has_description_column) {
        $desc_sql = "SELECT description FROM workout_journal WHERE user_id = ? AND day_of_week = ? AND exercise_name = '_DESCRIPTION_'";
        $desc_stmt = $conn->prepare($desc_sql);
        $desc_stmt->bind_param("is", $current_user_id, $day_of_week);
        $desc_stmt->execute();
        $desc_result = $desc_stmt->get_result();
        
        if ($desc_row = $desc_result->fetch_assoc()) {
            $description = $desc_row['description'] ?? '';
            error_log("Description found: " . substr($description, 0, 50));
        }
        $desc_stmt->close();
    }

    // 4. Retrieve exercises for that user and day (EXCLUDING description row)
    $sql_select = "SELECT exercise_name, sets, reps_time FROM workout_journal WHERE user_id = ? AND day_of_week = ? AND exercise_name != '_DESCRIPTION_' ORDER BY id";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("is", $current_user_id, $day_of_week);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    $exercises = [];
    $index = 0;
    $exercise_count = 0;
    
    while ($row = $result_select->fetch_assoc()) {
        $exercises[] = [
            'id' => 'saved-' . $current_user_id . '-' . $day_of_week . '-' . $index++,
            'name' => $row['exercise_name'],
            'sets' => $row['sets'],
            'reps' => $row['reps_time'],
            'group' => 'General'
        ];
        $exercise_count++;
    }

    $stmt_select->close();
    $conn->close();

    error_log("Loaded $exercise_count exercises for user $current_user_id, day $day_of_week");

    // Success response: returns the array (even if empty)
    echo json_encode([
        "success" => true, 
        "user_id" => $current_user_id, // Add user_id to response for debugging
        "description" => $description,
        "exercises" => $exercises
    ]);

} catch (Exception $e) {
    // Catch any unexpected database or connection errors
    error_log("Exception in load_workout.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>