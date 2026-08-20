<?php
// filepath: c:\wamp64\www\agapay\user\log_active_call.php
session_start();
include('../connect.php');
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action'])) {
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit();
    }

    switch ($input['action']) {
        case 'start_call':
            $incident_id = isset($input['incident_id']) ? (int)$input['incident_id'] : null;
            $call_type = mysqli_real_escape_string($conn, $input['call_type'] ?? 'emergency');

            // Insert new active call
            $incident_id_sql = $incident_id ? $incident_id : 'NULL';
            $insert_sql = "INSERT INTO active_calls (user_id, incident_id, call_type, status, created_at) 
                          VALUES ($user_id, $incident_id_sql, '$call_type', 'active', NOW())";

            if (mysqli_query($conn, $insert_sql)) {
                echo json_encode([
                    'success' => true,
                    'call_id' => mysqli_insert_id($conn),
                    'message' => 'Call logged successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            break;

        case 'end_call':
            // End all active calls for this user
            $update_sql = "UPDATE active_calls 
                          SET status = 'ended', ended_at = NOW() 
                          WHERE user_id = $user_id 
                          AND status = 'active'";

            if (mysqli_query($conn, $update_sql)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Call ended successfully',
                    'affected_rows' => mysqli_affected_rows($conn)
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
