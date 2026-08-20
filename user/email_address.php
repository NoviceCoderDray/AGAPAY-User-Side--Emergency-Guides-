<?php
// include the site-wide DB connect from parent directory
include_once __DIR__ . '/../connect.php';

// Email setup
date_default_timezone_set('Asia/Manila');
// require PHPMailer from the sibling PHPMailer directory
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

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email already exists in database
        $email_safe = mysqli_real_escape_string($conn, $email);
        $check_query = "SELECT id FROM users WHERE email = '$email_safe'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email already exists. Please use a different email address.";
        } else {
            // Generate OTP and expiry time
            $otp = generateOTP();
            $expiry_time = date('Y-m-d H:i:s', strtotime('+3 minutes'));
            
            // Start session and store OTP data
            session_set_cookie_params(60 * 60 * 24 * 90);
            session_start();
            $_SESSION['verification_email'] = $email;
            $_SESSION['verification_otp'] = $otp;
            $_SESSION['otp_expiry'] = $expiry_time;
            $_SESSION['otp_generated_time'] = time();
            
            // Send OTP email
            if (sendOTP($otp, "User", $email)) {
                // Store OTP in database for additional security (optional)
                $insert_otp = "INSERT INTO email_otp (email, otp, expiry_time, created_at) VALUES ('$email_safe', '$otp', '$expiry_time', NOW())
                               ON DUPLICATE KEY UPDATE otp = '$otp', expiry_time = '$expiry_time', created_at = NOW()";
                mysqli_query($conn, $insert_otp);
                
                // Redirect to OTP verification page (parent directory)
                header("Location: email_otp.php");
                exit();
            } else {
                $error = "Failed to send OTP. Please try again.";
            }
        }
    } else {
        $error = "Please enter a valid email address.";
    }
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
            body,
            .login-card,
            .form-control,
            .btn-custom,
            .back-link,
            .form-label,
            .login-card h5,
            .login-card p {
                font-size: 0.95rem !important;
            }
            .login-card h5 {
                font-size: 1.1rem !important;
            }
        }

        @media (max-width: 400px) {
            body,
            .login-card,
            .form-control,
            .btn-custom,
            .back-link,
            .form-label,
            .login-card h5,
            .login-card p {
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


        <div class="logo">
        <img src="../assets/logo.png" alt="AGAPAY Logo">
    </div>

    <div class="login-card">
        <h5 class="text-center fw-bold">Verify Your New Email Account</h5>
        <p class="text-center text-muted mb-4">We will send you an OTP to verify your email account before assigning it to your Agapay account.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    placeholder="you@example.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-custom">Send OTP</button>
        </form>
        
        <p class="back-link mt-3">
            Would you like to keep your current email address? <b id="login-link">Accounts</b>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Redirect to user_login.php when "Sign In" is clicked
        document.getElementById('login-link').onclick = function() {
            window.location.href = 'account.php';
        };
    </script>
</body>
</html>