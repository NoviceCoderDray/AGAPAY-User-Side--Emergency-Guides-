<?php
include('user_session.php');
date_default_timezone_set('Asia/Manila');

// Mark admin messages as read when user opens the messages page
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $mark_read_query = "UPDATE chat_logs 
                        SET is_read = 1 
                        WHERE receiver_id = ? 
                          AND is_admin = 1 
                          AND is_read = 0";
    $mark_stmt = $conn->prepare($mark_read_query);
    $mark_stmt->bind_param("i", $user_id);
    $mark_stmt->execute();
    $mark_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'];
    $is_admin = 0;

    $query = "INSERT INTO chat_logs (sender_id, receiver_id, message, sent_at, is_admin) 
              VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisi", $sender_id, $receiver_id, $message, $is_admin);

    if ($stmt->execute()) {
        if (isset($_POST['ajax'])) {
            echo 'OK';
            exit();
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        if (isset($_POST['ajax'])) {
            echo 'ERROR';
            exit();
        }
    }
}

if (isset($_GET['fetch'])) {
    $user_id = $_SESSION['user_id'];

    // Mark as read during fetch as well
    $mark_read_query = "UPDATE chat_logs 
                        SET is_read = 1 
                        WHERE receiver_id = ? 
                          AND is_admin = 1 
                          AND is_read = 0";
    $mark_stmt = $conn->prepare($mark_read_query);
    $mark_stmt->bind_param("i", $user_id);
    $mark_stmt->execute();
    $mark_stmt->close();

    $query = "SELECT chat_logs.message, chat_logs.sent_at, chat_logs.is_admin, chat_logs.media_type, chat_logs.media_path,
             CASE 
                 WHEN chat_logs.is_admin = 1 THEN 'Admin'
                 ELSE CONCAT(users.first_name, ' ', users.last_name)
             END as sender_name
      FROM chat_logs 
      LEFT JOIN users ON chat_logs.sender_id = users.id 
      WHERE (chat_logs.sender_id = ? AND chat_logs.receiver_id = 0)
         OR (chat_logs.receiver_id = ? AND chat_logs.is_admin = 1)
      ORDER BY chat_logs.sent_at ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $is_admin_message = $row['is_admin'];
            $justify = $is_admin_message ? 'justify-content-start' : 'justify-content-end';
            $bg_color = $is_admin_message ? 'bg-light text-dark' : 'text-white';
            $style = $is_admin_message ? '' : 'style="background-color: #8B1E1E;"';

            echo '<div class="d-flex mb-3 ' . $justify . '">';
            echo '<div class="' . $bg_color . ' p-2 rounded" ' . $style . ' style="max-width: 70%;">';
            echo '<strong>' . htmlspecialchars($row['sender_name']) . ':</strong><br>';

            if ($row['media_type'] === 'image' && !empty($row['media_path'])) {
                echo '<img src="../uploads/chat_media/' . htmlspecialchars($row['media_path']) . '" class="img-fluid rounded mt-2" style="max-width: 300px; cursor: pointer;" onclick="window.open(this.src)">';
            } elseif ($row['media_type'] === 'video' && !empty($row['media_path'])) {
                echo '<video controls class="rounded mt-2" style="max-width: 300px; width: 100%;">';
                echo '<source src="../uploads/chat_media/' . htmlspecialchars($row['media_path']) . '" type="video/webm">';
                echo 'Your browser does not support the video tag.';
                echo '</video>';
            } else {
                echo htmlspecialchars($row['message']);
            }

            echo '<br><small class="' . ($is_admin_message ? 'text-muted' : 'text-light') . '">' . date('M j, Y g:i A', strtotime($row['sent_at'])) . '</small>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<div class="text-center text-muted mt-5">';
        echo '<i class="bi bi-chat-dots display-1 mb-3"></i>';
        echo '<h4>No messages yet</h4>';
        echo '<p>Start a conversation with the admin!</p>';
        echo '</div>';
    }
    exit();
}
?>

<?php
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message - USER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        .main-content {
            flex-grow: 1;
            padding: 0 !important;
            background: #8b1e1e;
            margin: 0 !important;
            box-shadow: none !important;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .page-header {
            background: #8b1e1e;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 1.5rem 2rem;
            flex-shrink: 0;
        }

        .page-header h5 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Call Timer Bar */
        #callTimerBar {
            background-color: #28a745;
            color: white;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Messages Container */
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            background-color: #f8fafc;
        }

        /* Message Input */
        .message-input-area {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 1.5rem 2rem;
            flex-shrink: 0;
        }

        @media (max-width: 767.98px) {
            .sidebar {
                display: none !important;
            }

            .main-content {
                width: 100% !important;
            }

            .page-header {
                padding: 1rem 1.5rem;
            }

            .messages-area {
                padding: 1rem;
            }

            .message-input-area {
                padding: 1rem 1.5rem;
            }
        }

        @media (min-width: 768px) {
            .mobile-back-button {
                display: none !important;
            }
        }

        @keyframes blink {

            0%,
            50% {
                opacity: 1;
            }

            51%,
            100% {
                opacity: 0;
            }
        }

        /* Call Modal Styles */
        .call-notification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10001;
            animation: fadeIn 0.3s ease;
        }

        .call-notification-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(139, 30, 30, 0.3);
            padding: 32px;
            min-width: 320px;
            max-width: 400px;
            text-align: center;
            animation: slideDown 0.3s ease;
        }

        .call-notification-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: #8B1E1E;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s ease-in-out infinite;
        }

        .call-notification-icon svg {
            width: 32px;
            height: 32px;
            fill: white;
        }

        .call-notification-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .call-notification-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 28px;
        }

        .call-notification-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .call-notification-btn {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 110px;
        }

        .call-notification-btn-accept {
            background: #8B1E1E;
            color: white;
        }

        .call-notification-btn-accept:hover {
            background: #6d1717;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 30, 30, 0.3);
        }

        .call-notification-btn-decline {
            background: #f5f5f5;
            color: #666;
        }

        .call-notification-btn-decline:hover {
            background: #e0e0e0;
            transform: translateY(-1px);
        }

        /* Loading Overlay */
        .call-loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10002;
            animation: fadeIn 0.3s ease;
        }

        .call-loading-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .call-loading-spinner {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: #8B1E1E;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .call-loading-text {
            color: white;
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 12px;
        }

        .call-loading-subtext {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 24px;
        }

        .call-loading-cancel {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .call-loading-cancel:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(139, 30, 30, 0.5);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(139, 30, 30, 0);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        #videoContainer {
            position: relative;
            width: 100%;
            height: 70vh;
            background: #000;
        }

        #remoteVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #localVideo {
            position: absolute;
            bottom: 100px;
            right: 20px;
            width: 150px;
            height: 112px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #fff;
        }
    </style>
</head>

<body>
    <div class="d-flex" style="height: 100vh;">
        <!-- Sidebar -->
        <div class="sidebar d-none d-md-block">
            <?php $page = 'messages';
            include('sidebar.php'); ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <a href="home.php" class="mobile-back-button btn btn-link text-decoration-none text-white p-0 me-3 d-md-none">
                            <i class="bi bi-arrow-left fs-4"></i>
                        </a>
                        <h5 class="mb-0">Chat with admin</h5>
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-light" type="button" title="Start Call" onclick="startCall()">
                            <i class="bi bi-telephone"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Call Timer Bar -->
            <div id="callTimerBar" class="d-none" onclick="openCallModal()">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <strong id="callTimer">00:00</strong>
                    <span style="font-size: 0.85rem; opacity: 0.9;">• Tap to expand</span>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="messages-area" id="messagesContainer">
                <?php
                $user_id = $_SESSION['user_id'];
                $query = "SELECT chat_logs.message, chat_logs.sent_at, chat_logs.is_admin, chat_logs.media_type, chat_logs.media_path,
                     CASE 
                         WHEN chat_logs.is_admin = 1 THEN 'Admin'
                         ELSE CONCAT(users.first_name, ' ', users.last_name)
                     END as sender_name
              FROM chat_logs 
              LEFT JOIN users ON chat_logs.sender_id = users.id 
              WHERE (chat_logs.sender_id = ? AND chat_logs.receiver_id = 0)
                 OR (chat_logs.receiver_id = ? AND chat_logs.is_admin = 1)
              ORDER BY chat_logs.sent_at ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $is_admin_message = $row['is_admin'];
                        $justify = $is_admin_message ? 'justify-content-start' : 'justify-content-end';
                        $bg_color = $is_admin_message ? 'bg-light text-dark' : 'text-white';
                        $style = $is_admin_message ? '' : 'style="background-color: #8B1E1E;"';

                        echo '<div class="d-flex mb-3 ' . $justify . '">';
                        echo '<div class="' . $bg_color . ' p-2 rounded" ' . $style . ' style="max-width: 70%;">';
                        echo '<strong>' . htmlspecialchars($row['sender_name']) . ':</strong><br>';

                        if ($row['media_type'] === 'image' && !empty($row['media_path'])) {
                            echo '<img src="../uploads/chat_media/' . htmlspecialchars($row['media_path']) . '" class="img-fluid rounded mt-2" style="max-width: 300px; cursor: pointer;" onclick="window.open(this.src)">';
                        } elseif ($row['media_type'] === 'video' && !empty($row['media_path'])) {
                            echo '<video controls class="rounded mt-2" style="max-width: 300px; width: 100%;">';
                            echo '<source src="../uploads/chat_media/' . htmlspecialchars($row['media_path']) . '" type="video/webm">';
                            echo 'Your browser does not support the video tag.';
                            echo '</video>';
                        } else {
                            echo htmlspecialchars($row['message']);
                        }

                        echo '<br><small class="' . ($is_admin_message ? 'text-muted' : 'text-light') . '">' . date('M j, Y g:i A', strtotime($row['sent_at'])) . '</small>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="text-center text-muted mt-5">';
                    echo '<i class="bi bi-chat-dots display-1 mb-3"></i>';
                    echo '<h4>No messages yet</h4>';
                    echo '<p>Start a conversation with the admin!</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- Message Input Area -->
            <div class="message-input-area">
                <form id="messageForm" class="d-flex align-items-center" method="POST" action="" autocomplete="off">
                    <input type="hidden" name="receiver_id" value="0">
                    <input type="text" class="form-control me-2" name="message" placeholder="Type a message..." required>
                    <button class="btn text-white me-2" type="button" title="Take Photo/Video" onclick="openCameraModal()" style="background-color: #8B1E1E; border: none;">
                        <i class="bi bi-camera-fill"></i>
                    </button>
                    <button class="btn text-white" type="submit" name="send_message" style="background-color: #8B1E1E;">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Call Notification Modal -->
    <div id="callNotificationModal" class="call-notification-modal">
        <div class="call-notification-content">
            <div class="call-notification-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56-.35-.12-.74-.03-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z" />
                </svg>
            </div>
            <div class="call-notification-title">Admin is calling</div>
            <div class="call-notification-subtitle">Incoming Call</div>
            <div class="call-notification-actions">
                <button class="call-notification-btn call-notification-btn-decline" onclick="declineIncomingCall()">Decline</button>
                <button class="call-notification-btn call-notification-btn-accept" onclick="acceptIncomingCall()">Accept</button>
            </div>
        </div>
    </div>

    <!-- Call Loading Overlay -->
    <div id="callLoadingOverlay" class="call-loading-overlay">
        <div class="call-loading-content">
            <div class="call-loading-spinner"></div>
            <div class="call-loading-text">Calling Admin...</div>
            <div class="call-loading-subtext">Waiting for response</div>
            <button class="call-loading-cancel" onclick="cancelOutgoingCall()">Cancel Call</button>
        </div>
    </div>

    <!-- Call Modal - Full Screen -->
    <div class="modal fade" id="callModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content" style="background-color: #333131;">
                <div class="modal-header border-0">
                    <button type="button" class="btn btn-link text-white text-decoration-none p-0" onclick="closeCallModal()">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </button>
                </div>
                <div class="modal-body d-flex flex-column align-items-center justify-content-center p-0" style="position: relative; height: 100%;">
                    <video id="remoteVideo" autoplay playsinline
                        style="width: 100%; height: 100%; object-fit: cover; background: #000;"></video>

                    <video id="localVideo" autoplay muted playsinline
                        style="position: absolute; bottom: 100px; right: 20px; width: 150px; height: 112px; 
                                  border-radius: 12px; object-fit: cover; border: 2px solid #fff; background: #000;"></video>

                    <div id="localVideoFallback" style="position: absolute; bottom: 100px; right: 20px; width: 150px; height: 112px; display: none; align-items: center; justify-content: center; background: #222; border-radius: 12px; border: 2px solid #fff;">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: #8B1E1E; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: auto;">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                        <div style="text-align: center; color: #fff; font-size: 1rem; margin-top: 4px;">
                            <?php echo htmlspecialchars($user_name); ?>
                        </div>
                    </div>

                    <div id="adminVideoFallback" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none; align-items: center; justify-content: center; background: transparent; z-index: 5; pointer-events: none;">
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #222; border-radius: 16px; border: 2px solid #fff; padding: 24px 32px; box-shadow: 0 2px 16px rgba(0,0,0,0.3);">
                            <img src="../assets/mdrrmo_logo.png" alt="MDRRMO Logo"
                                style="width: 90px; height: 90px; border-radius: 50%; object-fit: cover;">
                            <div style="color: #fff; font-size: 2rem; font-weight: bold; margin-top: 16px;">
                                MDRRMO
                            </div>
                        </div>
                    </div>

                    <div style="position: absolute; top: 20px; left: 50%; transform: translateX(-50%);">
                        <h1 class="text-white mb-0" id="modalCallTimer"
                            style="text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">00:00</h1>
                    </div>

                    <div style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);">
                        <div class="d-flex gap-3">
                            <button class="btn rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 60px; height: 60px; background-color: #858585;"
                                id="videoToggleBtn"
                                onclick="toggleVideo()"
                                title="Toggle Video">
                                <i class="bi bi-camera-video text-white fs-4" id="videoIcon"></i>
                            </button>

                            <button class="btn rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 60px; height: 60px; background-color: #858585; display: none;"
                                id="switchCameraCallBtn"
                                onclick="switchCameraDuringCall()"
                                title="Switch Camera">
                                <i class="bi bi-arrow-repeat text-white fs-4"></i>
                            </button>

                            <button class="btn rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 60px; height: 60px; background-color: #858585;"
                                id="muteButton"
                                onclick="toggleMute()"
                                title="Mute">
                                <i class="bi bi-mic-fill text-white fs-4" id="muteIcon"></i>
                            </button>

                            <button class="btn rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 60px; height: 60px; background-color: #8B1E1E;"
                                onclick="endCallFromModal()"
                                title="End Call">
                                <i class="bi bi-telephone-x-fill text-white fs-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Camera Modal - Full Screen -->
    <div class="modal fade" id="cameraModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content" style="background-color: #000;">
                <div class="modal-header border-0">
                    <button type="button" class="btn btn-link text-white text-decoration-none p-0" onclick="closeCameraModal()">
                        <i class="bi bi-x-lg fs-4"></i>
                    </button>
                </div>
                <div class="modal-body d-flex flex-column align-items-center justify-content-center p-0">
                    <video id="cameraPreview" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>

                    <div id="recordingIndicator" class="position-absolute top-0 start-0 m-3 d-none">
                        <span class="badge bg-danger d-flex align-items-center gap-2">
                            <i class="bi bi-circle-fill" style="animation: blink 1s infinite;"></i>
                            REC
                        </span>
                    </div>

                    <div class="position-absolute bottom-0 w-100 p-4 d-flex justify-content-around align-items-center">
                        <button class="btn rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 50px; height: 50px; background-color: rgba(255,255,255,0.3);"
                            onclick="switchCamera()"
                            id="switchCameraBtn"
                            title="Switch Camera">
                            <i class="bi bi-arrow-repeat text-white fs-5"></i>
                        </button>

                        <div style="position: relative; width: 80px; height: 80px;">
                            <svg id="progressCircle" style="position: absolute; top: 0; left: 0; width: 80px; height: 80px; transform: rotate(-90deg);">
                                <circle cx="40" cy="40" r="35" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="4" />
                                <circle id="progressCircleFill" cx="40" cy="40" r="35" fill="none"
                                    stroke="#8B1E1E" stroke-width="4"
                                    stroke-dasharray="219.91"
                                    stroke-dashoffset="219.91"
                                    style="transition: stroke-dashoffset 0.1s linear;" />
                            </svg>

                            <button class="btn rounded-circle d-flex align-items-center justify-content-center"
                                style="position: absolute; top: 10px; left: 10px; width: 60px; height: 60px; background-color: rgba(255,255,255,0.8); border: none;"
                                id="captureBtn"
                                title="Tap to capture photo, Hold for video">
                            </button>
                        </div>

                        <div style="width: 50px; height: 50px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Cancelled Call Modal -->
    <div class="modal fade" id="userCancelledModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-x-circle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">Call Cancelled</h5>
                    <p class="text-muted mb-4">You have successfully cancelled the call to MDRRMO.</p>
                    <button type="button" class="btn text-white" style="background-color: #8B1E1E; min-width: 120px;" onclick="redirectToHome()">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Unavailable Modal -->
    <div class="modal fade" id="adminUnavailableModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-telephone-x text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">Admin Unavailable</h5>
                    <p class="text-muted mb-4">The MDRRMO admin is currently unavailable. Please try again later or submit a report for assistance.</p>
                    <button type="button" class="btn text-white" style="background-color: #8B1E1E; min-width: 120px;" onclick="redirectToHome()">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- End Call Warning Modal -->
    <div class="modal fade" id="endCallWarningModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">End Call Confirmation</h5>
                    <p class="text-muted mb-4">Going back will end your current call with MDRRMO. Do you wish to continue?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary" style="min-width: 100px;" onclick="cancelEndCallWarning()">
                            Cancel
                        </button>
                        <button type="button" class="btn text-white" style="background-color: #8B1E1E; min-width: 100px;" onclick="confirmEndCall()">
                            End Call
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal animations */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            transform: scale(0.9);
            opacity: 0;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
            opacity: 1;
        }

        /* Icon pulse animation */
        @keyframes iconPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        #userCancelledModal .bi-x-circle,
        #adminUnavailableModal .bi-telephone-x,
        #endCallWarningModal .bi-exclamation-triangle {
            animation: iconPulse 2s ease-in-out infinite;
        }
    </style>
    <!-- 🔔 Ringtone Audio Element -->
    <audio id="incomingCallRingtone" loop preload="auto">
        <source src="ringtones/call-ringtone.mp3" type="audio/mpeg">
    </audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<script>
    const form = document.getElementById('messageForm');
    const input = document.querySelector('#messageForm input[name="message"]');
    const container = document.getElementById('messagesContainer');
    let isAtBottom = true;
    let isFirstLoad = true;
    let lastMessageCount = 0;

    function checkIfAtBottom() {
        const threshold = 50;
        isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < threshold;
    }

    function loadMessages() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '?fetch=1', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = xhr.responseText;
                const newMessageCount = tempDiv.querySelectorAll('.d-flex.mb-3').length;

                if (isFirstLoad || newMessageCount !== lastMessageCount) {
                    const previousScrollHeight = container.scrollHeight;
                    container.innerHTML = xhr.responseText;

                    if (isFirstLoad || isAtBottom) {
                        container.scrollTop = container.scrollHeight;
                        isFirstLoad = false;
                    } else {
                        container.scrollTop += container.scrollHeight - previousScrollHeight;
                    }

                    lastMessageCount = newMessageCount;
                }
            }
        };
        xhr.send();
    }

    container.addEventListener('scroll', checkIfAtBottom);

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const msg = (input?.value || '').trim();
            if (!msg) return;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    if (input) input.value = '';
                    isAtBottom = true;
                    loadMessages();
                }
            };
            xhr.send('receiver_id=0&message=' + encodeURIComponent(msg) + '&send_message=1&ajax=1');
        });
    }

    loadMessages();
    setInterval(loadMessages, 2000);
</script>

<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script>
    const firebaseConfig = {
        apiKey: "AIzaSyABng3fjrkzX-iMAKD5mvaavIadxbK12Wg",
        authDomain: "agapay-webrtc.firebaseapp.com",
        databaseURL: "https://agapay-webrtc-default-rtdb.asia-southeast1.firebasedatabase.app",
        projectId: "agapay-webrtc",
        storageBucket: "agapay-webrtc.firebasestorage.app",
        messagingSenderId: "183768246676",
        appId: "1:183768246676:web:0c00827899f5fe661b8781"
    };

    firebase.initializeApp(firebaseConfig);
    const database = firebase.database();

    //basta
    const ringtone = document.getElementById('incomingCallRingtone');
    let isRingtonePlaying = false;

    function playRingtone() {
        if (!isRingtonePlaying) {
            ringtone.currentTime = 0;
            ringtone.play().catch(err => {
                console.warn('Could not play ringtone (user interaction may be required):', err);
            });
            isRingtonePlaying = true;
        }
    }

    function stopRingtone() {
        if (isRingtonePlaying) {
            ringtone.pause();
            ringtone.currentTime = 0;
            isRingtonePlaying = false;
        }
    }
    //basta

    const userId = <?php echo $user_id; ?>;
    const fromRedirectCallId = sessionStorage.getItem('acceptedCallId') || null;

    let localStream = null;
    let peerConnection = null;
    let currentCallRef = null;
    let processedCalls = new Set();
    let renegotiationInProgress = false;
    let pendingIncomingCall = null;

    const configuration = {
        iceServers: [{
            urls: "stun:stun.l.google.com:19302"
        }, {
            urls: "stun:stun1.l.google.com:19302"
        }]
    };

    database.ref('calls').once('value', snapshot => {
        const now = Date.now();
        snapshot.forEach(childSnapshot => {
            const call = childSnapshot.val();
            const callAge = now - (call.createdAt || 0);
            if (callAge > 300000 || call.status === 'ended' || call.status === 'rejected') {
                childSnapshot.ref.remove();
            }
        });
    });

    function setupRenegotiationListeners(callRef) {
        callRef.child('adminOffer').on('value', async (snapshot) => {
            const newOffer = snapshot.val();
            if (!newOffer || !peerConnection || renegotiationInProgress) return;

            if (peerConnection.signalingState !== 'stable') {
                console.log('Not in stable state, skipping offer');
                return;
            }

            console.log('Received admin offer for renegotiation');
            renegotiationInProgress = true;

            try {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(newOffer));
                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);

                await callRef.child('userAnswer').set({
                    type: answer.type,
                    sdp: answer.sdp,
                    timestamp: Date.now()
                });

                console.log('Sent answer to admin offer');
                renegotiationInProgress = false;
            } catch (error) {
                console.error('Error handling admin offer:', error);
                renegotiationInProgress = false;
            }
        });

        callRef.child('adminAnswer').on('value', async (snapshot) => {
            const answer = snapshot.val();
            if (!answer || !peerConnection) return;

            if (peerConnection.signalingState === 'have-local-offer') {
                console.log('Received admin answer to our offer');

                try {
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
                    renegotiationInProgress = false;
                    console.log('Renegotiation complete');
                } catch (error) {
                    console.error('Error setting admin answer:', error);
                    renegotiationInProgress = false;
                }
            }
        });
    }

    function showIncomingCallModal() {
        document.getElementById('callNotificationModal').style.display = 'block';
        playRingtone();
    }

    function hideIncomingCallModal() {
        document.getElementById('callNotificationModal').style.display = 'none';
        stopRingtone();
    }

    function showLoadingOverlay() {
        document.getElementById('callLoadingOverlay').style.display = 'block';
        playRingtone();
    }

    function hideLoadingOverlay() {
        document.getElementById('callLoadingOverlay').style.display = 'none';
        stopRingtone();
    }

    async function acceptIncomingCall() {
        if (!pendingIncomingCall) return;
        stopRingtone();
        hideIncomingCallModal();
        const {
            callKey,
            callRef,
            call
        } = pendingIncomingCall;
        pendingIncomingCall = null;

        try {
            currentCallRef = callRef;

            localStream = await navigator.mediaDevices.getUserMedia({
                audio: true
            });
            peerConnection = new RTCPeerConnection(configuration);

            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });

            peerConnection.ontrack = event => {
                console.log('Received remote track:', event.track.kind);
                const remoteVideo = document.getElementById('remoteVideo');

                if (event.streams && event.streams[0]) {
                    remoteVideo.srcObject = event.streams[0];
                } else {
                    if (!remoteVideo.srcObject) {
                        remoteVideo.srcObject = new MediaStream();
                    }
                    remoteVideo.srcObject.addTrack(event.track);
                }
            };

            peerConnection.onicecandidate = event => {
                if (event.candidate) {
                    callRef.child('userCandidates').push(event.candidate.toJSON());
                }
            };

            await peerConnection.setRemoteDescription(new RTCSessionDescription(call.offer));

            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            await callRef.child('answer').set({
                type: answer.type,
                sdp: answer.sdp
            });

            await callRef.update({
                status: 'connected'
            });

            setupRenegotiationListeners(callRef);
            startCallTimer();

            callRef.child('adminCandidates').on('child_added', async snapshot => {
                const candidate = snapshot.val();
                if (peerConnection && candidate) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                }
            });

            callRef.child('status').on('value', snapshot => {
                const status = snapshot.val();
                if (status === 'ended') {
                    endCall();
                }
            });

        } catch (error) {
            console.error("Error accepting call:", error);
            await callRef.update({
                status: 'rejected'
            });
            processedCalls.delete(callKey);
        }
    }

    async function declineIncomingCall() {
        if (!pendingIncomingCall) return;

        hideIncomingCallModal();
        const {
            callKey,
            callRef
        } = pendingIncomingCall;
        pendingIncomingCall = null;

        // 🔥 FIX: Set status to 'rejected' instead of just updating
        await callRef.update({
            status: 'rejected',
            rejectedAt: firebase.database.ServerValue.TIMESTAMP
        });

        processedCalls.delete(callKey);

        console.log('Call rejected by user');
    }

    database.ref('calls')
        .orderByChild('userId')
        .equalTo(userId)
        .on('child_added', async snapshot => {
            const call = snapshot.val();
            const callKey = snapshot.key;

            if (processedCalls.has(callKey)) return;

            const autoAccept = (fromRedirectCallId && callKey === fromRedirectCallId) || call.status === 'answering';

            if ((call.status === 'calling' || call.status === 'answering') && !currentCallRef) {
                processedCalls.add(callKey);
                const callRef = database.ref('calls/' + callKey);

                if (autoAccept) {
                    if (fromRedirectCallId === callKey) sessionStorage.removeItem('acceptedCallId');
                    pendingIncomingCall = {
                        callKey,
                        callRef,
                        call
                    };
                    await acceptIncomingCall();
                } else {
                    pendingIncomingCall = {
                        callKey,
                        callRef,
                        call
                    };
                    showIncomingCallModal();
                }
            }
        });

    let callTimeoutTimer = null;

    async function startCall() {
        if (currentCallRef) {
            return;
        }

        // Location restriction check - Cuenca only
        /*Comment out the block below to allow calls from outside Cuenca BEGIN HERE
        
        console.log('Checking location for call access...');
        
        try {
            const position = await new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation is not supported by this browser.'));
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000
                });
            });

            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            
            console.log('User coordinates for call:', { latitude, longitude });

            // Check location using reverse geocoding
            const isInCuenca = await checkCuencaLocationForCall(latitude, longitude);
            
            if (!isInCuenca) {
                hideLoadingOverlay();
                console.log('Call denied: User is outside Cuenca area');
                
                // Show location restriction modal
                showLocationRestrictionModal();
                return;
            }
            
            console.log('Location check passed - User is in Cuenca, proceeding with call');
            
        } catch (locationError) {
            hideLoadingOverlay();
            console.error('Location error for call:', locationError);
            
            // Show location error modal
            showLocationErrorModal();
            return;
        }
        /* END HEREEEE */
        try {
            showLoadingOverlay();

            localStream = await navigator.mediaDevices.getUserMedia({
                audio: true
            });
            peerConnection = new RTCPeerConnection(configuration);

            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });

            peerConnection.ontrack = event => {
                console.log('Received remote track:', event.track.kind);
                const remoteVideo = document.getElementById('remoteVideo');

                if (event.streams && event.streams[0]) {
                    remoteVideo.srcObject = event.streams[0];
                } else {
                    if (!remoteVideo.srcObject) {
                        remoteVideo.srcObject = new MediaStream();
                    }
                    remoteVideo.srcObject.addTrack(event.track);
                }
            };

            currentCallRef = database.ref('calls').push();

            peerConnection.onicecandidate = event => {
                if (event.candidate) {
                    currentCallRef.child('userCandidates').push(event.candidate.toJSON());
                }
            };

            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            await currentCallRef.set({
                userId: userId,
                userName: '<?php echo addslashes($user_name); ?>',
                adminId: 0,
                offer: {
                    type: offer.type,
                    sdp: offer.sdp
                },
                status: 'calling',
                createdAt: firebase.database.ServerValue.TIMESTAMP
            });

            // 🔥 NEW: Log call in active_calls table
            const urlParams = new URLSearchParams(window.location.search);
            const incidentId = urlParams.get('incident_id');

            if (incidentId) {
                try {
                    await fetch('log_active_call.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'start_call',
                            incident_id: parseInt(incidentId),
                            call_type: urlParams.get('type') || 'emergency'
                        })
                    });
                    console.log('Active call logged in database');
                } catch (error) {
                    console.error('Failed to log active call:', error);
                }
            }

            // Start 30-second timeout
            callTimeoutTimer = setTimeout(() => {
                if (currentCallRef && !peerConnection.currentRemoteDescription) {
                    console.log('Call timeout: Admin did not answer within 30 seconds');
                    hideLoadingOverlay();
                    endCall();
                    adminUnavailableModal.show();
                }
            }, 30000);

            setupRenegotiationListeners(currentCallRef);

            currentCallRef.child('answer').on('value', async snapshot => {
                const answer = snapshot.val();
                if (answer && peerConnection && !peerConnection.currentRemoteDescription) {
                    // Clear timeout when admin answers
                    if (callTimeoutTimer) {
                        clearTimeout(callTimeoutTimer);
                        callTimeoutTimer = null;
                    }
                    hideLoadingOverlay();
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
                    startCallTimer();
                }
            });

            currentCallRef.child('adminCandidates').on('child_added', async snapshot => {
                const candidate = snapshot.val();
                if (peerConnection && candidate) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                }
            });

            currentCallRef.child('status').on('value', snapshot => {
                const status = snapshot.val();
                if (status === 'ended') {
                    // Clear timeout
                    if (callTimeoutTimer) {
                        clearTimeout(callTimeoutTimer);
                        callTimeoutTimer = null;
                    }
                    hideLoadingOverlay();
                    endCall();
                } else if (status === 'rejected') {
                    // Clear timeout
                    if (callTimeoutTimer) {
                        clearTimeout(callTimeoutTimer);
                        callTimeoutTimer = null;
                    }
                    hideLoadingOverlay();
                    endCall();
                }
            });

        } catch (error) {
            console.error("Error starting call:", error);
            hideLoadingOverlay();
            if (currentCallRef) {
                currentCallRef.remove();
                currentCallRef = null;
            }
        }
    }

    function cancelOutgoingCall() {
        // Clear timeout
        if (callTimeoutTimer) {
            clearTimeout(callTimeoutTimer);
            callTimeoutTimer = null;
        }

        hideLoadingOverlay();

        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        if (currentCallRef) {
            const callKey = currentCallRef.key;
            currentCallRef.update({
                status: 'ended'
            });
            currentCallRef = null;
            processedCalls.delete(callKey);
        }

        isVideoEnabled = false;
        renegotiationInProgress = false;
        const localVideo = document.getElementById('localVideo');
        if (localVideo) {
            localVideo.style.display = 'none';
            localVideo.srcObject = null;
        }

        if (typeof callModal !== 'undefined') {
            callModal.hide();
        }

        // Show user cancelled modal
        userCancelledModal.show();
    }

    // Modify endCall to clear timeout
    endCall = function() {
        // Clear timeout
        if (callTimeoutTimer) {
            clearTimeout(callTimeoutTimer);
            callTimeoutTimer = null;
        }

        let wasRejected = false;

        // Check if call was rejected by admin before cleaning up
        if (currentCallRef) {
            currentCallRef.once('value', (snapshot) => {
                const callData = snapshot.val();
                if (callData && callData.status === 'rejected') {
                    wasRejected = true;
                }
            });
        }

        // 🔥 NEW: Log call end in database
        try {
            fetch('log_active_call.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'end_call'
                })
            });
            console.log('Call ended in database');
        } catch (error) {
            console.error('Failed to log call end:', error);
        }

        stopCallTimer();
        hideLoadingOverlay();

        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        if (currentCallRef) {
            const callKey = currentCallRef.key;

            currentCallRef.update({
                status: 'ended'
            });
            currentCallRef = null;
            processedCalls.delete(callKey);
        }

        isVideoEnabled = false;
        renegotiationInProgress = false;
        const localVideo = document.getElementById('localVideo');
        if (localVideo) {
            localVideo.style.display = 'none';
            localVideo.srcObject = null;
        }

        if (typeof callModal !== 'undefined') {
            callModal.hide();
        }

        // Show admin unavailable modal if rejected
        if (wasRejected) {
            setTimeout(() => {
                if (!userCancelledModal._isShown) {
                    adminUnavailableModal.show();
                }
            }, 300);
        }
    };
</script>

<script>
    let isVideoEnabled = false;

    async function toggleVideo() {
        if (!currentCallRef || !peerConnection) {
            return;
        }

        if (renegotiationInProgress) {
            console.log('Renegotiation in progress, please wait...');
            return;
        }

        const localVideo = document.getElementById('localVideo');
        const localVideoFallback = document.getElementById('localVideoFallback');
        const videoToggleBtn = document.getElementById('videoToggleBtn');
        const videoIcon = document.getElementById('videoIcon');
        const switchCameraCallBtn = document.getElementById('switchCameraCallBtn');

        if (!isVideoEnabled) {
            try {
                renegotiationInProgress = true;
                const videoStream = await navigator.mediaDevices.getUserMedia({
                    video: true
                });
                const videoTrack = videoStream.getVideoTracks()[0];
                localStream.addTrack(videoTrack);
                peerConnection.addTrack(videoTrack, localStream);

                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                await currentCallRef.child('userOffer').set({
                    type: offer.type,
                    sdp: offer.sdp,
                    timestamp: Date.now()
                });

                localVideo.srcObject = localStream;
                localVideo.style.display = 'block';
                localVideo.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';
                localVideoFallback.style.display = 'none';

                videoToggleBtn.style.backgroundColor = '#28a745';
                videoIcon.className = 'bi bi-camera-video-fill text-white fs-4';

                if (switchCameraCallBtn) {
                    switchCameraCallBtn.style.display = 'flex';
                }

                isVideoEnabled = true;
                setTimeout(() => {
                    renegotiationInProgress = false;
                }, 1500);
            } catch (error) {
                console.error('Failed to enable camera:', error);
                renegotiationInProgress = false;
            }
        } else {
            try {
                renegotiationInProgress = true;
                const videoTracks = localStream.getVideoTracks();
                videoTracks.forEach(track => {
                    track.stop();
                    localStream.removeTrack(track);
                    const senders = peerConnection.getSenders();
                    const videoSender = senders.find(s => s.track === track);
                    if (videoSender) {
                        peerConnection.removeTrack(videoSender);
                    }
                });

                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);
                await currentCallRef.child('userOffer').set({
                    type: offer.type,
                    sdp: offer.sdp,
                    timestamp: Date.now()
                });

                localVideo.style.display = 'none';
                localVideo.srcObject = null;
                localVideoFallback.style.display = 'none';

                videoToggleBtn.style.backgroundColor = '#858585';
                videoIcon.className = 'bi bi-camera-video-off text-white fs-4';

                if (switchCameraCallBtn) {
                    switchCameraCallBtn.style.display = 'none';
                }

                isVideoEnabled = false;
                setTimeout(() => {
                    renegotiationInProgress = false;
                }, 1500);
            } catch (error) {
                console.error('Failed to disable video:', error);
                renegotiationInProgress = false;
            }
        }
    }
</script>
<script>
    let callTimerInterval = null;
    let callStartTime = null;

    function startCallTimer() {
        const timerBar = document.getElementById('callTimerBar');
        const timerDisplay = document.getElementById('callTimer');
        const modalTimerDisplay = document.getElementById('modalCallTimer');

        timerBar.classList.remove('d-none');

        callStartTime = Date.now();

        if (callTimerInterval) {
            clearInterval(callTimerInterval);
        }

        callTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
            const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
            const seconds = String(elapsed % 60).padStart(2, '0');
            const timeString = `${minutes}:${seconds}`;

            if (timerDisplay) timerDisplay.textContent = timeString;
            if (modalTimerDisplay) modalTimerDisplay.textContent = timeString;
        }, 1000);

        console.log('Call timer started');
    }

    function stopCallTimer() {
        const timerBar = document.getElementById('callTimerBar');

        timerBar.classList.add('d-none');

        if (callTimerInterval) {
            clearInterval(callTimerInterval);
            callTimerInterval = null;
        }

        const timerDisplay = document.getElementById('callTimer');
        const modalTimerDisplay = document.getElementById('modalCallTimer');
        if (timerDisplay) timerDisplay.textContent = '00:00';
        if (modalTimerDisplay) modalTimerDisplay.textContent = '00:00';

        console.log('Call timer stopped');
    }

    let callModal = new bootstrap.Modal(document.getElementById('callModal'));

    function openCallModal() {
        if (currentCallRef) {
            callModal.show();
        }
    }

    function closeCallModal() {
        callModal.hide();
    }

    function endCallFromModal() {
        endCall();
        callModal.hide();
    }

    function toggleMute() {
        if (!localStream) return;

        const audioTrack = localStream.getAudioTracks()[0];
        if (!audioTrack) return;

        audioTrack.enabled = !audioTrack.enabled;

        const muteButton = document.getElementById('muteButton');
        const muteIcon = document.getElementById('muteIcon');

        if (audioTrack.enabled) {
            muteButton.style.backgroundColor = '#858585';
            muteIcon.className = 'bi bi-mic-fill text-white fs-4';
        } else {
            muteButton.style.backgroundColor = '#dc3545';
            muteIcon.className = 'bi bi-mic-mute-fill text-white fs-4';
        }
    }

    async function switchCameraDuringCall() {
        if (!currentCallRef || !peerConnection || !isVideoEnabled || renegotiationInProgress) {
            return;
        }

        try {
            renegotiationInProgress = true;

            // Toggle facing mode
            currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

            // Stop current video tracks
            const videoTracks = localStream.getVideoTracks();
            videoTracks.forEach(track => {
                track.stop();
                localStream.removeTrack(track);
                const senders = peerConnection.getSenders();
                const videoSender = senders.find(s => s.track === track);
                if (videoSender) {
                    peerConnection.removeTrack(videoSender);
                }
            });

            // Get new video stream with switched camera
            const videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: currentFacingMode
                }
            });
            const newVideoTrack = videoStream.getVideoTracks()[0];

            // Add new video track
            localStream.addTrack(newVideoTrack);
            peerConnection.addTrack(newVideoTrack, localStream);

            // Update local video preview
            const localVideo = document.getElementById('localVideo');
            localVideo.srcObject = localStream;
            localVideo.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';

            // Send renegotiation offer
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);
            await currentCallRef.child('userOffer').set({
                type: offer.type,
                sdp: offer.sdp,
                timestamp: Date.now()
            });

            setTimeout(() => {
                renegotiationInProgress = false;
            }, 1500);

        } catch (error) {
            console.error('Failed to switch camera:', error);
            renegotiationInProgress = false;
        }
    }
</script>
<script>
    function updateAdminPreviewFallback() {
        const remoteVideo = document.getElementById('remoteVideo');
        const adminVideoFallback = document.getElementById('adminVideoFallback');
        if (!remoteVideo.srcObject) {
            adminVideoFallback.style.display = 'flex';
            remoteVideo.style.display = 'none';
            return;
        }
        const videoTracks = remoteVideo.srcObject.getVideoTracks();
        const hasLiveVideo = videoTracks.some(t => t.enabled && t.readyState === 'live');
        if (hasLiveVideo) {
            adminVideoFallback.style.display = 'none';
            remoteVideo.style.display = 'block';
        } else {
            adminVideoFallback.style.display = 'flex';
            remoteVideo.style.display = 'none';
        }
    }

    setInterval(updateAdminPreviewFallback, 500);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const shouldAutoCall = urlParams.get('auto_call') === '1';
        const emergencyType = urlParams.get('type');
        const incidentId = urlParams.get('incident_id');

        const autoCallIntent = sessionStorage.getItem('autoCallIntent');

        if (shouldAutoCall && autoCallIntent === 'true' && emergencyType && incidentId) {
            console.log('Auto-calling initiated from home page');
            console.log('Emergency Type:', emergencyType);
            console.log('Incident ID:', incidentId);

            sessionStorage.removeItem('autoCallIntent');
            sessionStorage.removeItem('emergencyType');
            sessionStorage.removeItem('incidentId');

            setTimeout(() => {
                startCall();

                setTimeout(() => {
                    openCallModal();
                }, 1000);
            }, 500);
        }
    });
</script>

<script>
    // Camera modal + capture for user
    let cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
    const cameraModalEl = document.getElementById('cameraModal');
    let cameraStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let isRecording = false;
    let recordingTimer = null;
    let recordingStartTime = null;
    let currentFacingMode = 'user';
    let captureTimeout = null;
    let progressInterval = null;
    let captureHandlersBound = false;
    const MAX_RECORDING_TIME = 15000;

    // Cleanup when modal closes
    let cameraModalEventsBound = false;
    if (!cameraModalEventsBound) {
        cameraModalEl.addEventListener('hidden.bs.modal', () => {
            try {
                stopCameraStream();
            } catch (e) {}
            if (isRecording && mediaRecorder) {
                try {
                    mediaRecorder.stop();
                } catch (e) {}
                isRecording = false;
            }
            resetProgress();
        }, {
            once: false
        });
        cameraModalEventsBound = true;
    }

    async function openCameraModal() {
        try {
            await startCameraStream();
            cameraModal.show();
            setupCaptureButton();
        } catch (err) {
            console.error('Error accessing camera:', err);
            alert('Failed to access camera. Please check camera permissions and use HTTPS.');
        }
    }

    async function startCameraStream() {
        const constraints = {
            video: {
                facingMode: currentFacingMode,
                width: {
                    ideal: 1920
                },
                height: {
                    ideal: 1080
                }
            },
            audio: false
        };

        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);

        const video = document.getElementById('cameraPreview');
        video.srcObject = cameraStream;

        if (!video.videoWidth || !video.videoHeight) {
            await new Promise(resolve => {
                video.onloadedmetadata = () => resolve();
            });
        }
        try {
            await video.play();
        } catch (_) {}

        // Mirror front camera, normal for back camera
        video.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';

        // Enable switch camera button when camera is active
        const switchCameraBtn = document.getElementById('switchCameraBtn');
        if (switchCameraBtn) {
            switchCameraBtn.disabled = false;
        }
    }

    function stopCameraStream() {
        if (cameraStream) {
            try {
                cameraStream.getTracks().forEach(t => t.stop());
            } catch (e) {}
            cameraStream = null;
        }
        const video = document.getElementById('cameraPreview');
        if (video) {
            video.srcObject = null;
            video.removeAttribute('src');
        }

        // Disable switch camera button when camera is off
        const switchCameraBtn = document.getElementById('switchCameraBtn');
        if (switchCameraBtn) {
            switchCameraBtn.disabled = true;
        }
    }

    async function switchCamera() {
        currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
        stopCameraStream();
        await startCameraStream();
    }

    function setupCaptureButton() {
        if (captureHandlersBound) return;
        captureHandlersBound = true;

        const captureBtn = document.getElementById('captureBtn');

        captureBtn.addEventListener('mousedown', handleCaptureStart, {
            passive: false
        });
        captureBtn.addEventListener('touchstart', handleCaptureStart, {
            passive: false
        });

        captureBtn.addEventListener('mouseup', handleCaptureEnd, {
            passive: false
        });
        captureBtn.addEventListener('touchend', handleCaptureEnd, {
            passive: false
        });

        captureBtn.addEventListener('mouseleave', handleCaptureCancel, {
            passive: false
        });
        captureBtn.addEventListener('touchcancel', handleCaptureCancel, {
            passive: false
        });
    }

    function handleCaptureStart(e) {
        e.preventDefault();
        captureTimeout = setTimeout(() => {
            startVideoRecording();
            startProgressAnimation();
        }, 500);
    }

    function handleCaptureEnd(e) {
        e.preventDefault();

        if (captureTimeout) {
            clearTimeout(captureTimeout);
            captureTimeout = null;

            if (!isRecording) {
                capturePhoto();
            } else {
                stopVideoRecording();
            }
        } else if (isRecording) {
            stopVideoRecording();
        }
    }

    function handleCaptureCancel() {
        if (captureTimeout) {
            clearTimeout(captureTimeout);
            captureTimeout = null;
        }
        if (isRecording) {
            stopVideoRecording();
        }
        resetProgress();
    }

    function startProgressAnimation() {
        const progressCircle = document.getElementById('progressCircleFill');
        const circumference = 2 * Math.PI * 35;
        let progress = 0;

        progressInterval = setInterval(() => {
            progress += 100;
            const percentage = Math.min(progress / MAX_RECORDING_TIME, 1);
            const offset = circumference * (1 - percentage);
            progressCircle.style.strokeDashoffset = offset;

            if (progress >= MAX_RECORDING_TIME) {
                stopVideoRecording();
            }
        }, 100);
    }

    function resetProgress() {
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
        const progressCircle = document.getElementById('progressCircleFill');
        if (progressCircle) progressCircle.style.strokeDashoffset = '219.91';
    }

    async function capturePhoto() {
        const video = document.getElementById('cameraPreview');

        if (!video.videoWidth || !video.videoHeight) {
            await new Promise(resolve => {
                video.onloadedmetadata = () => resolve();
            });
        }

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');

        // Mirror the canvas for front camera so saved image matches preview
        if (currentFacingMode === 'user') {
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
        }
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(async (blob) => {
            if (!blob) return alert('Failed to capture image.');
            await sendCameraMedia(blob, 'image');
            closeCameraModal();
        }, 'image/jpeg', 0.95);
    }

    function startVideoRecording() {
        if (!window.MediaRecorder) {
            alert('Video recording is not supported on this device/browser.');
            resetProgress();
            return;
        }

        isRecording = true;
        recordedChunks = [];

        document.getElementById('recordingIndicator').classList.remove('d-none');
        // Disable switch camera during recording
        document.getElementById('switchCameraBtn').disabled = true;

        navigator.mediaDevices.getUserMedia({
            audio: true
        }).then(audioStream => {
            const videoTrack = cameraStream?.getVideoTracks()[0];
            const audioTrack = audioStream.getAudioTracks()[0];
            if (!videoTrack) {
                alert('No video track available.');
                document.getElementById('recordingIndicator').classList.add('d-none');
                document.getElementById('switchCameraBtn').disabled = false;
                isRecording = false;
                return;
            }
            const combinedStream = new MediaStream([videoTrack, audioTrack]);

            let options = {};
            const candidates = ['video/webm;codecs=vp8', 'video/webm;codecs=vp9', 'video/webm'];
            if (MediaRecorder.isTypeSupported) {
                for (const c of candidates) {
                    if (MediaRecorder.isTypeSupported(c)) {
                        options.mimeType = c;
                        break;
                    }
                }
            }

            try {
                mediaRecorder = new MediaRecorder(combinedStream, options);
            } catch (e) {
                try {
                    mediaRecorder = new MediaRecorder(combinedStream);
                } catch (err) {
                    console.error('MediaRecorder unsupported:', err);
                    alert('Video recording is not supported on this device/browser.');
                    document.getElementById('recordingIndicator').classList.add('d-none');
                    document.getElementById('switchCameraBtn').disabled = false;
                    isRecording = false;
                    audioTrack.stop();
                    return;
                }
            }

            mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) recordedChunks.push(event.data);
            };

            mediaRecorder.onstop = async () => {
                const type = mediaRecorder.mimeType || 'video/webm';
                const blob = new Blob(recordedChunks, {
                    type
                });
                audioTrack.stop();
                await sendCameraMedia(blob, 'video');
                closeCameraModal();
            };

            mediaRecorder.start();
            recordingStartTime = Date.now();

            setTimeout(() => {
                if (isRecording) stopVideoRecording();
            }, MAX_RECORDING_TIME);
        }).catch(error => {
            console.error("Error accessing microphone:", error);
            alert("Failed to access microphone for video recording.");
            isRecording = false;
            document.getElementById('recordingIndicator').classList.add('d-none');
            document.getElementById('switchCameraBtn').disabled = false;
            resetProgress();
        });
    }

    function stopVideoRecording() {
        if (mediaRecorder && isRecording) {
            isRecording = false;
            try {
                mediaRecorder.stop();
            } catch (e) {}
            document.getElementById('recordingIndicator').classList.add('d-none');
            // Re-enable switch camera after recording
            document.getElementById('switchCameraBtn').disabled = false;
            resetProgress();
        }
    }

    async function sendCameraMedia(blob, type) {
        try {
            const formData = new FormData();
            formData.append('receiver_id', 0);
            formData.append('media_type', type);
            formData.append('media', blob, type === 'image' ? 'user_photo.jpg' : 'user_video.webm');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_media.php', true);

            xhr.onload = function() {
                if (xhr.status === 200 && xhr.responseText === 'OK') {
                    isAtBottom = true;
                    loadMessages();
                } else {
                    alert('Failed to send media: ' + xhr.responseText);
                }
            };

            xhr.send(formData);
        } catch (error) {
            console.error('Error sending media:', error);
            alert('Failed to send media');
        }
    }

    function closeCameraModal() {
        cameraModal.hide();
    }
</script>

<script>
    // Initialize new modals
    const userCancelledModal = new bootstrap.Modal(document.getElementById('userCancelledModal'));
    const adminUnavailableModal = new bootstrap.Modal(document.getElementById('adminUnavailableModal'));
    const endCallWarningModal = new bootstrap.Modal(document.getElementById('endCallWarningModal'));

    // Redirect to home function
    function redirectToHome() {
        window.location.href = 'home.php';
    }

    // Store original cancelOutgoingCall function
    const originalCancelOutgoingCall = cancelOutgoingCall;

    // Override cancelOutgoingCall function - show user cancelled modal
    cancelOutgoingCall = function() {
        // Clear timeout
        if (callTimeoutTimer) {
            clearTimeout(callTimeoutTimer);
            callTimeoutTimer = null;
        }

        hideLoadingOverlay();

        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        if (currentCallRef) {
            const callKey = currentCallRef.key;

            currentCallRef.update({
                status: 'ended'
            });
            currentCallRef = null;
            processedCalls.delete(callKey);
        }

        isVideoEnabled = false;
        renegotiationInProgress = false;
        const localVideo = document.getElementById('localVideo');
        if (localVideo) {
            localVideo.style.display = 'none';
            localVideo.srcObject = null;
        }

        if (typeof callModal !== 'undefined') {
            callModal.hide();
        }

        stopCallTimer();

        // Show user cancelled modal
        userCancelledModal.show();
    };

    // Store original endCall function
    const originalEndCall = endCall;

    // Override endCall function to detect if admin rejected/declined
    endCall = function() {
        // Clear timeout
        if (callTimeoutTimer) {
            clearTimeout(callTimeoutTimer);
            callTimeoutTimer = null;
        }

        let wasRejected = false;

        // Check if call was rejected by admin before cleaning up
        if (currentCallRef) {
            currentCallRef.once('value', (snapshot) => {
                const callData = snapshot.val();
                if (callData && callData.status === 'rejected') {
                    wasRejected = true;
                }
            });
        }

        // 🔥 NEW: Log call end in database
        try {
            fetch('log_active_call.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'end_call'
                })
            });
            console.log('Call ended in database');
        } catch (error) {
            console.error('Failed to log call end:', error);
        }

        stopCallTimer();
        hideLoadingOverlay();

        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        if (currentCallRef) {
            const callKey = currentCallRef.key;

            currentCallRef.update({
                status: 'ended'
            });
            currentCallRef = null;
            processedCalls.delete(callKey);
        }

        isVideoEnabled = false;
        renegotiationInProgress = false;
        const localVideo = document.getElementById('localVideo');
        if (localVideo) {
            localVideo.style.display = 'none';
            localVideo.srcObject = null;
        }

        if (typeof callModal !== 'undefined') {
            callModal.hide();
        }

        // Show admin unavailable modal if rejected
        if (wasRejected) {
            setTimeout(() => {
                if (!userCancelledModal._isShown) {
                    adminUnavailableModal.show();
                }
            }, 300);
        }
    };

    // End call warning modal functions
    function cancelEndCallWarning() {
        endCallWarningModal.hide();
    }

    function confirmEndCall() {
        endCallWarningModal.hide();
        endCall();
        redirectToHome();
    }

    // Modified mobile back button to check for active call
    document.addEventListener('DOMContentLoaded', function() {
        const mobileBackButton = document.querySelector('.mobile-back-button');

        if (mobileBackButton) {
            // Remove existing href
            mobileBackButton.removeAttribute('href');

            // Add click handler
            mobileBackButton.addEventListener('click', function(e) {
                e.preventDefault();

                // Check if there's an active call
                if (currentCallRef) {
                    endCallWarningModal.show();
                } else {
                    redirectToHome();
                }
            });
        }

        // Auto-call logic (existing code remains untouched)
        const urlParams = new URLSearchParams(window.location.search);
        const shouldAutoCall = urlParams.get('auto_call') === '1';
        const emergencyType = urlParams.get('type');
        const incidentId = urlParams.get('incident_id');

        const autoCallIntent = sessionStorage.getItem('autoCallIntent');

        if (shouldAutoCall && autoCallIntent === 'true' && emergencyType && incidentId) {
            console.log('Auto-calling initiated from home page');
            console.log('Emergency Type:', emergencyType);
            console.log('Incident ID:', incidentId);

            sessionStorage.removeItem('autoCallIntent');
            sessionStorage.removeItem('emergencyType');
            sessionStorage.removeItem('incidentId');

            setTimeout(() => {
                startCall();

                setTimeout(() => {
                    openCallModal();
                }, 1000);
            }, 500);
        }
    });

    // Location checking functions for call access
    async function checkCuencaLocationForCall(latitude, longitude) {
        console.log('Checking if coordinates are in Cuenca for call access:', {
            latitude,
            longitude
        });

        try {
            // Try Google Maps Geocoding API first
            const response = await fetch(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${latitude},${longitude}&key=AIzaSyAhOGLKjz6HyKhGCa1K4Mk2xdCkTDBzKLE`);
            const data = await response.json();

            console.log('Google Maps API response for call check:', data);

            if (data.status === 'OK' && data.results && data.results.length > 0) {
                const result = data.results[0];
                console.log('Formatted address for call check:', result.formatted_address);

                // Check all address components
                const addressComponents = result.address_components || [];
                console.log('Address components for call check:', addressComponents);

                for (const component of addressComponents) {
                    const types = component.types || [];
                    const longName = (component.long_name || '').toLowerCase();
                    const shortName = (component.short_name || '').toLowerCase();

                    console.log('Checking component for call:', {
                        types,
                        longName,
                        shortName
                    });

                    // Check if any component mentions Cuenca
                    if (longName.includes('cuenca') || shortName.includes('cuenca')) {
                        console.log('Found Cuenca in address component for call - Access granted');
                        return true;
                    }

                    // Check for Batangas (province)
                    if ((types.includes('administrative_area_level_1') || types.includes('administrative_area_level_2')) &&
                        (longName.includes('batangas') || shortName.includes('batangas'))) {
                        console.log('Found Batangas province for call check');

                        // If in Batangas, check if specifically in Cuenca
                        const cuencaFound = addressComponents.some(comp => {
                            const cLongName = (comp.long_name || '').toLowerCase();
                            const cShortName = (comp.short_name || '').toLowerCase();
                            return cLongName.includes('cuenca') || cShortName.includes('cuenca');
                        });

                        if (cuencaFound) {
                            console.log('Confirmed in Cuenca, Batangas for call - Access granted');
                            return true;
                        }
                    }
                }

                // Also check the full formatted address
                const fullAddress = (result.formatted_address || '').toLowerCase();
                console.log('Checking full address for call:', fullAddress);

                if (fullAddress.includes('cuenca') && fullAddress.includes('batangas')) {
                    console.log('Found Cuenca and Batangas in full address for call - Access granted');
                    return true;
                }

                console.log('Google Maps check: Not in Cuenca for call');
                return false;
            }
        } catch (error) {
            console.error('Google Maps API error for call check:', error);
        }

        // Fallback to Nominatim API
        try {
            console.log('Trying Nominatim API for call location check...');
            const nominatimResponse = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=10&addressdetails=1`);
            const nominatimData = await nominatimResponse.json();

            console.log('Nominatim API response for call check:', nominatimData);

            if (nominatimData && nominatimData.address) {
                const address = nominatimData.address;
                const displayName = (nominatimData.display_name || '').toLowerCase();

                console.log('Nominatim address for call:', address);
                console.log('Nominatim display name for call:', displayName);

                // Check various address fields
                const fieldsToCheck = [
                    address.city, address.town, address.village, address.municipality,
                    address.county, address.state, address.province
                ];

                for (const field of fieldsToCheck) {
                    if (field && field.toLowerCase().includes('cuenca')) {
                        console.log('Found Cuenca in Nominatim field for call:', field);

                        // Verify it's in Philippines/Batangas
                        if (address.country && address.country.toLowerCase().includes('philippines')) {
                            console.log('Confirmed in Philippines - Cuenca access granted for call');
                            return true;
                        }
                    }
                }

                // Check display name
                if (displayName.includes('cuenca') && (displayName.includes('batangas') || displayName.includes('philippines'))) {
                    console.log('Found Cuenca in Nominatim display name for call - Access granted');
                    return true;
                }
            }
        } catch (error) {
            console.error('Nominatim API error for call check:', error);
        }

        console.log('Final decision: Not in Cuenca for call - Access denied');
        return false;
    }

    function showLocationRestrictionModal() {
        // Create modal HTML if it doesn't exist
        if (!document.getElementById('locationRestrictionModal')) {
            const modalHTML = `
                <div class="modal fade" id="locationRestrictionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                            <div class="modal-body text-center p-4">
                                <div class="mb-3">
                                    <i class="bi bi-geo-alt-fill text-danger" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="mb-3">Location Restricted</h5>
                                <p class="text-muted mb-4">Emergency calls are only available within Cuenca, Batangas. Please ensure you are within the service area to access this feature.</p>
                                <button type="button" class="btn text-white" style="background-color: #8B1E1E; min-width: 120px;" onclick="closeLocationRestrictionModal()">
                                    Understood
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        const modal = new bootstrap.Modal(document.getElementById('locationRestrictionModal'));
        modal.show();
    }

    function showLocationErrorModal() {
        // Create modal HTML if it doesn't exist
        if (!document.getElementById('locationErrorModal')) {
            const modalHTML = `
                <div class="modal fade" id="locationErrorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                            <div class="modal-body text-center p-4">
                                <div class="mb-3">
                                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="mb-3">Location Access Required</h5>
                                <p class="text-muted mb-4">Please enable location access and ensure you are within Cuenca, Batangas to make emergency calls.</p>
                                <button type="button" class="btn text-white" style="background-color: #8B1E1E; min-width: 120px;" onclick="closeLocationErrorModal()">
                                    Try Again
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        const modal = new bootstrap.Modal(document.getElementById('locationErrorModal'));
        modal.show();
    }

    function closeLocationRestrictionModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('locationRestrictionModal'));
        if (modal) modal.hide();
    }

    function closeLocationErrorModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('locationErrorModal'));
        if (modal) modal.hide();
    }
</script>