<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();

try {
    session_start();
    require_once('../connect.php');

    ob_end_clean();
    header('Content-Type: application/json');

    $count = 0;

    if (isset($_SESSION['user_id']) && isset($conn) && $conn) {
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM chat_logs WHERE receiver_id = ? AND is_admin = 1 AND is_read = 0");

        if ($stmt) {
            $stmt->bind_param("i", $uid);

            if ($stmt->execute()) {
                $res = $stmt->get_result();

                if ($res && $row = $res->fetch_assoc()) {
                    $count = (int)$row['total'];
                }
            }

            $stmt->close();
        }
    }

    echo json_encode(['count' => $count]);
} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
}

exit;
