<?php
// include site-wide DB connect from parent directory
include_once __DIR__ . '/../connect.php';
session_set_cookie_params(60 * 60 * 24 * 90);
session_start();

// Email setup
date_default_timezone_set('Asia/Manila');
// require PHPMailer from parent-level PHPMailer directory
require_once __DIR__ . '/../PHPMailer/PHPMailerAutoload.php';

function sendOTP($otp, $name, $email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 587;
        $mail->SMTPSecure = "tls";
        $mail->SMTPAuth = true;
        $mail->Username = "agapay111203@gmail.com";
        $mail->Password = "exbgyhpxnrftgygz";
        $mail->setFrom("agapay4321@gmail.com", "Agapay");

        $mail->addAddress($email, $name);
        $mail->Subject = "Email Verification - AGAPAY";
        
        $msg = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <h2 style='color: #802108; margin: 0;'>Email Verification</h2>
            </div>
            <div style='background: #f8f9fa; padding: 30px; border-radius: 10px; text-align: center;'>
                <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                    Your OTP code for email verification is:
                </p>
                <div style='font-size: 32px; font-weight: bold; color: #802108; background: white; padding: 15px; border-radius: 8px; display: inline-block; letter-spacing: 5px; margin: 20px 0;'>
                    $otp
                </div>
                <p style='font-size: 14px; color: #666; margin-top: 20px;'>
                    This code will expire in <strong>3 minutes</strong>.<br>
                    Please do not share this code with anyone.
                </p>
            </div>
            <div style='text-align: center; margin-top: 30px;'>
                <p style='font-size: 12px; color: #888;'>
                    This is an automated message from AGAPAY. Please do not reply to this email.
                </p>
            </div>
        </div>";
        
        $mail->msgHTML($msg);
        $mail->send();
        return true;
    } catch (phpmailerException $e) {
        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Generate 6-digit OTP
function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999));
}

// Get email from session or redirect back
if (!isset($_SESSION['verification_email'])) {
    header("Location: email_address.php");
    exit();
}

$email = $_SESSION['verification_email'];
$success = '';
$error = '';

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp1'])) {
    $entered_otp = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];
    
    // Check if OTP is valid and not expired
    if (isset($_SESSION['verification_otp']) && isset($_SESSION['otp_expiry'])) {
        $current_time = date('Y-m-d H:i:s');
        
        if ($current_time <= $_SESSION['otp_expiry']) {
            if ($entered_otp === $_SESSION['verification_otp']) {
                // OTP is correct, mark as verified
                $_SESSION['email_verified'] = true;

                // If user is logged in, persist the verified email to the users table
                if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                    $user_id = intval($_SESSION['user_id']);

                    // Use prepared statement when possible
                    if ($stmt = mysqli_prepare($conn, "UPDATE users SET email = ? WHERE id = ?")) {
                        mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    } else {
                        // Fallback to escaped query
                        $email_safe = mysqli_real_escape_string($conn, $email);
                        $user_id_safe = mysqli_real_escape_string($conn, (string)$user_id);
                        mysqli_query($conn, "UPDATE users SET email = '$email_safe' WHERE id = '$user_id_safe'");
                    }
                }

                // Update database to mark OTP as used
                $email_safe = mysqli_real_escape_string($conn, $email);
                $update_query = "UPDATE email_otp SET is_used = 1 WHERE email = '$email_safe'";
                mysqli_query($conn, $update_query);

                // Clear verification-related session data
                unset($_SESSION['verification_email']);
                unset($_SESSION['verification_otp']);
                unset($_SESSION['otp_expiry']);
                unset($_SESSION['otp_generated_time']);

                // Optional: set a short-lived session message for account.php to display
                $_SESSION['profile_success'] = 'Email verified and saved to your account.';

                // Redirect back to the account page where the updated email will be displayed
                header("Location: account.php");
                exit();
            } else {
                $error = "Invalid OTP. Please try again.";
            }
        } else {
            $error = "OTP has expired. Please request a new one.";
            // Clear expired OTP
            unset($_SESSION['verification_otp']);
            unset($_SESSION['otp_expiry']);
        }
    } else {
        $error = "No OTP found. Please request a new one.";
    }
}

// Handle resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    // Check cooldown (170 seconds)
    $current_time = time();
    $last_sent = isset($_SESSION['otp_generated_time']) ? $_SESSION['otp_generated_time'] : 0;
    
    if ($current_time - $last_sent >= 170) {
        // Generate new OTP
        $otp = generateOTP();
        $expiry_time = date('Y-m-d H:i:s', strtotime('+3 minutes'));
        
        // Update session
        $_SESSION['verification_otp'] = $otp;
        $_SESSION['otp_expiry'] = $expiry_time;
        $_SESSION['otp_generated_time'] = $current_time;
        
        // Send new OTP
        if (sendOTP($otp, "User", $email)) {
            // Update database
            $email_safe = mysqli_real_escape_string($conn, $email);
            $insert_otp = "INSERT INTO email_otp (email, otp, expiry_time, created_at) VALUES ('$email_safe', '$otp', '$expiry_time', NOW())
                           ON DUPLICATE KEY UPDATE otp = '$otp', expiry_time = '$expiry_time', created_at = NOW(), is_used = 0";
            mysqli_query($conn, $insert_otp);
            
            $success = "New OTP sent successfully!";
        } else {
            $error = "Failed to send OTP. Please try again.";
        }
    } else {
        $remaining = 170 - ($current_time - $last_sent);
        $error = "Please wait " . $remaining . " seconds before requesting a new OTP.";
    }
}

// Calculate remaining time for timer
$remaining_cooldown = 0;
if (isset($_SESSION['otp_generated_time'])) {
    $elapsed = time() - $_SESSION['otp_generated_time'];
    $remaining_cooldown = max(0, 170 - $elapsed);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - AGAPAY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #802108;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .back-arrow {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-size: 1.5rem;
            text-decoration: none;
        }

        .logo {
            text-align: center;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .logo img {
            max-height: 80px;
        }

        .login-card {
            background-color: #fff;
            border-radius: 25px;
            padding: 2rem 1.5rem;
            max-width: 400px;
            margin: 0 auto 30px auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 1;
            position: relative;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
        }

        .btn-custom {
            background-color: #802108;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            width: 100%;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .btn-custom:hover {
            background-color: #5e1606;
        }

        .back-link {
            text-align: center;
            font-size: 0.9rem;
        }

        .back-link b {
            cursor: pointer;
        }

        .email-display {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .otp-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 1.5rem 0;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: bold;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .otp-input:focus {
            border-color: #802108;
            box-shadow: 0 0 0 0.2rem rgba(128, 33, 8, 0.25);
        }

        .resend-section {
            text-align: center;
            margin-top: 1rem;
        }

        .resend-form {
            display: inline-block;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #802108;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0;
        }

        .resend-btn:hover:not(:disabled) {
            text-decoration: underline;
        }

        .resend-btn:disabled {
            color: #6c757d;
            cursor: not-allowed;
        }

        .timer {
            color: #6c757d;
            font-size: 0.85rem;
            margin-left: 8px;
        }

        .alert {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 576px) {
            .logo,
            .login-card {
                margin-left: 16px !important;
                margin-right: 16px !important;
            }
            
            .otp-input {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .otp-container {
                gap: 8px;
            }
            
            body,
            .login-card,
            .form-control,
            .btn-custom,
            .back-link,
            .form-label,
            .login-card h5,
            .login-card p,
            .email-display,
            .resend-btn,
            .timer {
                font-size: 0.95rem !important;
            }
            .login-card h5 {
                font-size: 1.1rem !important;
            }
        }

        @media (max-width: 400px) {
            .otp-input {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .otp-container {
                gap: 6px;
            }
            
            body,
            .login-card,
            .form-control,
            .btn-custom,
            .back-link,
            .form-label,
            .login-card h5,
            .login-card p,
            .email-display,
            .resend-btn,
            .timer {
                font-size: 0.85rem !important;
            }
            .login-card h5 {
                font-size: 1rem !important;
            }
        }
    </style>
</head>
<body>
    <!-- Back Arrow -->
    <div class="d-block d-md-none">
        <a href="email_address.php" class="back-arrow ms-2 text-decoration-none">&larr;</a>
    </div>

    <div class="logo">
        <img src="../assets/logo.png" alt="AGAPAY Logo">
    </div>

    <div class="login-card">
        <h5 class="text-center fw-bold">Verify your Email</h5>
        <p class="text-center text-muted mb-4">We have sent an OTP to</p>
        
        <div class="email-display">
            <?php echo htmlspecialchars($email); ?>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="otp" class="form-label text-center w-100">Enter your OTP to update your email</label>
                <div class="otp-container">
                    <input type="text" class="otp-input" maxlength="1" name="otp1" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp2" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp3" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp4" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp5" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp6" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-custom">Update Email</button>
        </form>
        
        <div class="resend-section">
            <span style="color: #6c757d; font-size: 0.9rem;">Didn't receive the code?</span>
            <form method="post" action="" class="resend-form">
                <button type="submit" name="resend_otp" class="resend-btn" id="resend-btn">
                    Resend
                </button>
                <span class="timer" id="timer"></span>
            </form>
        </div>
        
        <p class="back-link mt-3">
            Use different email? <b id="back-link">Change Email</b>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // OTP input functionality
        const otpInputs = document.querySelectorAll('.otp-input');
        
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });

        // Timer functionality
        let remainingTime = <?php echo $remaining_cooldown; ?>;
        const resendBtn = document.getElementById('resend-btn');
        const timer = document.getElementById('timer');

        function updateTimer() {
            if (remainingTime > 0) {
                const minutes = Math.floor(remainingTime / 60);
                const seconds = remainingTime % 60;
                timer.textContent = `(${minutes}:${seconds.toString().padStart(2, '0')})`;
                resendBtn.disabled = true;
                remainingTime--;
                setTimeout(updateTimer, 1000);
            } else {
                timer.textContent = '';
                resendBtn.disabled = false;
            }
        }

        // Start timer if cooldown is active
        if (remainingTime > 0) {
            updateTimer();
        }

        // Navigation
        document.getElementById('back-link').onclick = function() {
            window.location.href = 'email_address.php';
        };
    </script>
</body>
</html>