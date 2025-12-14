<?php
session_start();
include 'connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_email = $_SESSION['email'];

// Get user ID
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_id = $user['id'];
$stmt->close();

// Get POST data
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$day = $data['day'] ?? '';
$description = $data['description'] ?? '';
$exercises = $data['exercises'] ?? [];

if (empty($day)) {
    echo json_encode(['success' => false, 'message' => 'Day not specified']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete existing workout for this day and user
    $delete_sql = "DELETE FROM workout_journal WHERE user_id = ? AND day_of_week = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    if (!$delete_stmt) {
        throw new Exception("Delete prepare failed: " . $conn->error);
    }
    $delete_stmt->bind_param("is", $user_id, $day);
    $delete_stmt->execute();
    $delete_stmt->close();

    // Save description as a separate row (optional)
    if (!empty(trim($description))) {
        $desc_sql = "INSERT INTO workout_journal (user_id, day_of_week, description, exercise_name, sets, reps_time) VALUES (?, ?, ?, '', 0, '')";
        $desc_stmt = $conn->prepare($desc_sql);
        if (!$desc_stmt) {
            throw new Exception("Description prepare failed: " . $conn->error);
        }
        $desc_stmt->bind_param("iss", $user_id, $day, $description);
        $desc_stmt->execute();
        $desc_stmt->close();
    }

    // Insert each exercise as a separate row
    if (!empty($exercises)) {
        $insert_sql = "INSERT INTO workout_journal (user_id, day_of_week, description, exercise_name, sets, reps_time) VALUES (?, ?, '', ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            throw new Exception("Insert prepare failed: " . $conn->error);
        }
        
        foreach ($exercises as $exercise) {
            $exercise_name = $exercise['name'] ?? '';
            $sets = $exercise['sets'] ?? 0;
            $reps = $exercise['reps'] ?? '';
            
            if (empty($exercise_name)) {
                continue;
            }
            
            // Convert sets to integer
            $sets_int = intval($sets);
            
            $insert_stmt->bind_param("issis", $user_id, $day, $exercise_name, $sets_int, $reps);
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Execute failed for exercise '$exercise_name': " . $insert_stmt->error);
            }
        }
        $insert_stmt->close();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Workout saved successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to save workout: ' . $e->getMessage()]);
}

$conn->close();
?>