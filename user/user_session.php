<?php
// filepath: c:\wamp64\www\Agapay-Remastered\user\user_session.php
session_set_cookie_params(60 * 60 * 24 * 90);
session_start();

// Check if user is logged in - Agapay auth pattern
if (!isset($_SESSION['user_logged_in'])) {
    header("Location: ../user_login.php");
    exit();
}

// Include database connection following Agapay patterns
include(__DIR__ . '/../connect.php');

// Initialize default values to prevent undefined variable warnings
$first_name = 'User';
$last_name = '';
$gender = 'male';
$user_email = '';
$profile_pic = '../assets/boy.png'; // Default profile picture

// Validate user_id exists in session
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $user_sql = "SELECT first_name, last_name, gender, email, status FROM users WHERE id = '$user_id' LIMIT 1";
    $user_result = mysqli_query($conn, $user_sql);

    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);
        
        // Check if user account has been disabled by admin
        if ($user['status'] === 'disabled') {
            // Set a flag for disabled account to show modal
            $_SESSION['account_disabled'] = true;
        }
        
        $first_name = htmlspecialchars($user['first_name']);
        $last_name = htmlspecialchars($user['last_name']);
        $gender = strtolower($user['gender']);
        $user_email = htmlspecialchars($user['email']);

        // Store in session
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['user_email'] = $user_email;

        // Set profile picture based on gender
        if ($gender === 'female') {
            $profile_pic = '../assets/girl.png';
        } else {
            $profile_pic = '../assets/boy.png';
        }
    }
}

// Add modal and JavaScript for disabled account handling
if (isset($_SESSION['account_disabled']) && $_SESSION['account_disabled'] === true) {
    // Clear the flag to prevent repeated modals
    unset($_SESSION['account_disabled']);
    ?>
    <!-- Account Disabled Modal -->
    <div class="modal fade" id="accountDisabledModal" tabindex="-1" aria-labelledby="accountDisabledModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="accountDisabledModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Account Disabled
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-person-x-fill text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="mb-3">Your account has been disabled by the administrator.</h6>
                    <p class="text-muted mb-0">You will be logged out automatically. Please check your email for more details.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger" id="logoutBtn">
                        Okay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show the disabled account modal
            var accountDisabledModal = new bootstrap.Modal(document.getElementById('accountDisabledModal'));
            accountDisabledModal.show();
            
            // Handle logout button click
            document.getElementById('logoutBtn').addEventListener('click', function() {
                // Destroy session and redirect to login
                window.location.href = '../user_login.php?disabled=1';
            });
            
            // Auto logout after 10 seconds if user doesn't click
            setTimeout(function() {
                window.location.href = '../user_login.php?disabled=1';
            }, 10000);
        });
    </script>
    <?php
}
