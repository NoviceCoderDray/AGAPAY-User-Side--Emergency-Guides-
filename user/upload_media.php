<?php
// filepath: c:\wamp64\www\test\user\upload_media.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('user_session.php');

// Log everything to a file
$log_file = '../uploads/upload_debug.log';

function log_debug($message)
{
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

log_debug("=== Upload Started ===");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = intval($_POST['receiver_id']);
    $media_type = $_POST['media_type']; // 'image' or 'video'

    log_debug("Sender ID: $sender_id, Receiver ID: $receiver_id, Media Type: $media_type");

    $upload_dir = '../uploads/chat_media/';

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        log_debug("Creating directory: $upload_dir");
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['media'];

        $file_extension = $media_type === 'image' ? '.jpg' : '.webm';
        $file_name = 'media_' . time() . '_' . uniqid() . $file_extension;
        $file_path = $upload_dir . $file_name;

        log_debug("Moving file to: $file_path");

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            log_debug("File moved successfully");

            // Get file size
            $file_size = intval(filesize($file_path));
            $media_duration = 0;
            $message = '[Media]';
            $is_admin = 0;

            log_debug("Inserting to database - file: $file_name, size: $file_size");

            // Simple insert without prepared statement to debug
            $file_name_escaped = mysqli_real_escape_string($conn, $file_name);
            $message_escaped = mysqli_real_escape_string($conn, $message);

            $query = "INSERT INTO chat_logs 
                     (sender_id, receiver_id, message, sent_at, is_admin, media_type, media_path, media_size, media_duration) 
                     VALUES 
                     ($sender_id, $receiver_id, '$message_escaped', NOW(), $is_admin, '$media_type', '$file_name_escaped', $file_size, $media_duration)";

            log_debug("Query: $query");

            $result = mysqli_query($conn, $query);

            if ($result) {
                $insert_id = mysqli_insert_id($conn);
                log_debug("Insert successful! ID: $insert_id");

                // Verify
                $verify = mysqli_query($conn, "SELECT media_type, media_path FROM chat_logs WHERE message_id = $insert_id");
                $row = mysqli_fetch_assoc($verify);
                log_debug("Verified - type: {$row['media_type']}, path: {$row['media_path']}");

                echo 'OK';
            } else {
                $error = mysqli_error($conn);
                log_debug("Insert failed: $error");
                echo "ERROR: $error";
            }
        } else {
            log_debug("File move failed");
            echo 'ERROR: File upload failed';
        }
    } else {
        $error_code = isset($_FILES['media']) ? $_FILES['media']['error'] : 'No file';
        log_debug("No valid file. Error: $error_code");
        echo 'ERROR: No file received or upload error';
    }
} else {
    log_debug("Invalid request method");
    echo 'ERROR: Invalid request';
}

log_debug("=== Upload Ended ===\n");
