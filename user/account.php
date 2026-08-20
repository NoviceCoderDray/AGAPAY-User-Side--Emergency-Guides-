<?php
include('user_session.php');

$error = '';
$success = '';

// Form data initialization using user_session data
$form = array(
    'contactnumber' => '',
    'address' => '',
    'email' => $user_email,
    'first_name' => $first_name,
    'last_name' => $last_name
);

$full_name = $first_name . ' ' . $last_name;

// Get additional user data (contact and address)
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $contact = mysqli_real_escape_string($conn, $_POST['contactnumber']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);

        $can_update = true;

        // Server-side validation: contact must be exactly 11 digits
        if (!preg_match('/^\d{11}$/', $contact)) {
            $error = 'Contact number must be exactly 11 digits.';
            $can_update = false;
        }

        // Handle password change if any password field is filled
        if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Password length check
            if (strlen($new_password) < 8) {
                $error = 'New password must be at least 8 characters long.';
                $can_update = false;
            } else {
                // Fetch current password hash from DB
                $check_query = mysqli_query($conn, "SELECT password FROM users WHERE id = '$user_id'");
                if ($check_query && $row = mysqli_fetch_assoc($check_query)) {
                    $stored_hash = $row['password'];
                    $current_valid = false;

                    // Check if password is hashed with password_hash or legacy MD5
                    if (strlen($stored_hash) === 60 && password_verify($current_password, $stored_hash)) {
                        $current_valid = true;
                    } elseif (strlen($stored_hash) === 32 && md5($current_password) === $stored_hash) {
                        $current_valid = true;
                    }

                    if ($current_valid) {
                        if ($new_password === $confirm_password) {
                            // Prevent updating to the same password
                            $is_same = false;
                            // If stored hash looks like bcrypt
                            if (strlen($stored_hash) === 60) {
                                if (password_verify($new_password, $stored_hash)) $is_same = true;
                            } elseif (strlen($stored_hash) === 32) {
                                if (md5($new_password) === $stored_hash) $is_same = true;
                            }

                            if ($is_same) {
                                $error = 'New password cannot be the same as the current password.';
                                $can_update = false;
                            } else {
                                // Hash new password using password_hash (bcrypt)
                                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                                $new_password_hashed_safe = mysqli_real_escape_string($conn, $new_password_hashed);
                                try {
                                    mysqli_query($conn, "UPDATE users SET password = '$new_password_hashed_safe' WHERE id = '$user_id'");
                                    // Show modal and redirect after 2 seconds
                                    echo '
                                    <div id="passwordModal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:99999;display:flex;align-items:center;justify-content:center;">
                                        <div style="background:#fff;padding:36px 32px;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.18);max-width:400px;text-align:center;">
                                            <div style="font-size:2.5rem;color:#8B1E1E;margin-bottom:12px;">
                                                <i class="fa-solid fa-shield-halved"></i>
                                            </div>
                                            <h3 style="color:#8B1E1E;font-weight:700;margin-bottom:10px;">Password Changed</h3>
                                            <p style="font-size:1.1rem;color:#333;margin-bottom:18px;">
                                                Your password has been changed successfully.<br>
                                                You will be logged out and redirected to the login page.
                                            </p>
                                            <div class="spinner-border text-danger" role="status" style="margin-bottom:10px;">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <div style="font-size:0.95rem;color:#888;">Redirecting...</div>
                                        </div>
                                    </div>
                                    <script>
                                        setTimeout(function(){
                                            window.location.href = "../user_login.php";
                                        }, 2000);
                                    </script>
                                    ';
                                    session_destroy();
                                    exit;
                                } catch (Exception $e) {
                                    $error = 'Password update failed: ' . $e->getMessage();
                                    $can_update = false;
                                }
                            }
                        } else {
                            $error = 'New password and confirm password do not match.';
                            $can_update = false;
                        }
                    } else {
                        $error = 'Current password is incorrect.';
                        $can_update = false;
                    }
                } else {
                    $error = 'Unable to verify current password.';
                    $can_update = false;
                }
            }
        }

        // Update profile information only if something changed
        if ($can_update) {
            // fetch current values to compare
            $cur_q = mysqli_query($conn, "SELECT email, contactnumber, address FROM users WHERE id = '$user_id' LIMIT 1");
            $current = $cur_q ? mysqli_fetch_assoc($cur_q) : null;

            $changed = true; // assume changed unless we detect equality
            if ($current) {
                if ($current['email'] === $email && $current['contactnumber'] === $contact && $current['address'] === $address) {
                    $changed = false;
                }
            }

            if ($changed) {
                $update_sql = "UPDATE users SET 
                    email = '$email',
                    contactnumber = '$contact',
                    address = '$address'
                    WHERE id = '$user_id'";

                if (mysqli_query($conn, $update_sql)) {
                    // Only show success when rows affected
                    if (mysqli_affected_rows($conn) > 0) {
                        $success .= 'Profile updated successfully.';
                        // Update session email if changed
                        $_SESSION['user_email'] = $email;
                    }
                } else {
                    $error = 'Update failed: ' . mysqli_error($conn);
                }
            } else {
                // nothing changed; show informational message so user knows save did nothing
                $success = 'No changes detected.';
            }
        }
    }

    // Fetch current user data
    $sql = "SELECT contactnumber, address, email, first_name, last_name FROM users WHERE id = '$user_id' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $form['contactnumber'] = $row['contactnumber'];
        $form['address'] = $row['address'];
        $form['email'] = $row['email'];
        $form['first_name'] = $row['first_name'];
        $form['last_name'] = $row['last_name'];
        $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USER - Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .main-content {
            flex-grow: 1;
            padding: 0 !important;
            background-color: #ffffff;
            border-top-right-radius: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
            min-height: 100vh; /* <-- use min-height instead of height */
            display: flex;
            flex-direction: column;
        }

        :root {
            --main-color: #8B1E1E;
            --hover-color: #721818;
            --bg-color: #fff;
            --text-color: #2e2e2e;
            --border-color: #e5e5e5;
            --input-bg: #f9f9f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .profile-card {
            display: flex;
            width: 100%;
            min-height: 100vh; 
        }
        .avatar-section {
            background: #fff;
            padding: 50px 30px 30px 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 350px;
            margin: 0 auto;
        }

        .avatar {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden;
            border: 6px solid #fff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            background-color: #8B1E1E;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
        }

        .avatar-name {
            font-size: 22px;
            font-weight: 700;
            color: #2e2e2e;
            margin-bottom: 6px;
            margin-top: 10px;
            text-align: center;
        }

        .edit-text {
            font-size: 14px;
            opacity: 0.7;
            color: #2e2e2e;
            text-align: center;
            margin-bottom: 10px;
        }

        .info-section {
            padding: 32px 18px; /* Default: moderate side margin */
            background: var(--bg-color);
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
    .info-section {
        padding: 18px 45px 120px 45px; 
    }
}

@media (max-width: 480px) {
    .info-section {
        padding: 12px 30px 120px 30px;
    }
}

        h2 {
            font-size: 26px;
            color: var(--main-color);
            font-weight: 700;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-color);
            margin-bottom: 6px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 48px 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--input-bg);
            font-size: 15px;
            color: var(--text-color);
            transition: border-color 0.3s;
        }

        .input-wrapper input:focus {
            border-color: var(--main-color);
            background: #fff;
            outline: none;
        }

        .input-wrapper input:disabled {
            background-color: #f0f0f0;
            color: #666;
            cursor: not-allowed;
        }

        .input-wrapper i {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
        }

        .button {
            padding: 10px 18px;
            margin-top: 12px;
            margin-right: 10px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            color: var(--main-color);
            border: 1px solid var(--main-color);
            cursor: pointer;
            transition: background 0.3s, color 0.3s;
        }

        .button:hover {
            background: var(--main-color);
            color: #fff;
        }

        .edit-btn {
            padding: 14px 20px;
            margin-top: 24px;
            margin-right: 10px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            color: var(--main-color);
            border: 2px solid var(--main-color);
            cursor: pointer;
            transition: background 0.3s, color 0.3s;
            width: calc(50% - 5px);
        }

        .edit-btn:hover {
            background: var(--main-color);
            color: #fff;
        }

        .save-btn {
            padding: 14px 20px;
            margin-top: 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background: var(--main-color);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
            width: calc(50% - 5px);
        }

        .save-btn:hover {
            background: var(--hover-color);
        }

        .save-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .button-container {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Modal Styles */
        #termsModal {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
        }

        #termsModal .modal-content {
            background: #fff;
            margin: 5% auto;
            padding: 30px;
            width: 80%;
            max-height: 80%;
            overflow-y: auto;
            border-radius: 12px;
            position: relative;
        }

        #termsModal h3 {
            margin-top: 20px;
            color: var(--main-color);
        }

        #termsModal button {
            padding: 10px 20px;
            background: var(--main-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
        }

        #termsModal ul {
            padding-left: 20px;
        }

        /* Make Terms & Conditions button full width */
.terms-btn-container {
    width: 100%;
    margin-top: 24px;
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.terms-btn-container .button {
    width: 100%;
    padding: 16px 0;
    font-size: 16px;
    font-weight: 600;
    border-radius: 10px;
    background: #fff;
    color: var(--main-color);
    border: 2px solid var(--main-color);
    text-align: center;
    transition: background 0.3s, color 0.3s;
    margin: 0;
    display: block;
}

.terms-btn-container .button:hover {
    background: var(--main-color);
    color: #fff;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .main-content {
        min-height: calc(100vh - 80px);
        padding-bottom: 150px;
    }
    .profile-card {
        flex-direction: column;
        min-height: calc(100vh - 80px);
    }
    .info-section {
        padding-bottom: 120px; 
    }
    .terms-btn-container {
        margin-top: 18px;
        margin-bottom: 8px;
    }
    .terms-btn-container .button {
        width: 100%;
        padding: 14px 0;
        font-size: 15px;
    }
}
    </style>

    <script>
        // Improved edit/cancel behavior
        let isEditing = false;
        const originalValues = {};

        function setProfileReadonly(readonly) {
            // Only toggle profile inputs that are not marked as no-edit
            document.querySelectorAll('.profile-input:not(.no-edit)').forEach(input => {
                if (readonly) {
                    input.setAttribute('readonly', 'readonly');
                } else {
                    input.removeAttribute('readonly');
                }
            });
        }

        function setPasswordDisabled(disabled) {
            document.querySelectorAll('.password-input').forEach(input => {
                input.disabled = !!disabled;
                // clear password fields when disabling for safety
                if (disabled) input.value = '';
            });
        }

        function toggleEdit() {
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const profileInputs = document.querySelectorAll('.profile-input:not(.no-edit)');

            if (!isEditing) {
                // Begin editing: store original values (exclude no-edit inputs)
                profileInputs.forEach(input => {
                    originalValues[input.name] = input.value;
                });

                setProfileReadonly(false);
                setPasswordDisabled(false);
                // Make email clickable (visual cue)
                var emailField = document.getElementById('email');
                var emailIcon = document.getElementById('email_icon');
                if (emailField) emailField.style.cursor = 'pointer';
                if (emailIcon) emailIcon.style.cursor = 'pointer';

                editBtn.innerHTML = 'Cancel Edit';
                editBtn.style.backgroundColor = '#dc3545';
                editBtn.style.borderColor = '#dc3545';
                editBtn.style.color = '#fff';
                saveBtn.disabled = false;
                isEditing = true;
            } else {
                // Cancel editing: restore original values
                profileInputs.forEach(input => {
                    if (originalValues.hasOwnProperty(input.name)) {
                        input.value = originalValues[input.name];
                    }
                });

                setProfileReadonly(true);
                setPasswordDisabled(true);

                editBtn.innerHTML = 'Edit Profile';
                editBtn.style.backgroundColor = '#fff';
                editBtn.style.borderColor = 'var(--main-color)';
                editBtn.style.color = 'var(--main-color)';
                saveBtn.disabled = true;
                isEditing = false;
                // Remove clickable cursor when not editing
                var emailFieldOff = document.getElementById('email');
                var emailIconOff = document.getElementById('email_icon');
                if (emailFieldOff) emailFieldOff.style.cursor = 'default';
                if (emailIconOff) emailIconOff.style.cursor = 'default';
            }
        }

        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = document.getElementById(id + '_icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        // Open email verification page when user clicks the email field/icon
        function openEmailVerification() {
            // Only allow opening the email verification page while in edit mode
            if (isEditing) {
                window.location.href = 'email_address.php';
            }
        }

        function showTerms() {
            document.getElementById('termsModal').style.display = 'block';
        }

        function closeTerms() {
            document.getElementById('termsModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('termsModal');
            if (event.target == modal) {
                closeTerms();
            }
        };

        // Initialize form on page load
        window.addEventListener('DOMContentLoaded', function() {
            const profileInputs = document.querySelectorAll('.profile-input:not(.no-edit)');
            const passwordInputs = document.querySelectorAll('.password-input');
            const saveBtn = document.getElementById('saveBtn');

            // Store original values and make profile inputs readonly initially
            profileInputs.forEach(input => {
                originalValues[input.name] = input.value;
            });

            setProfileReadonly(true);
            setPasswordDisabled(true);
            saveBtn.disabled = true;

            // Ensure email input/icon cursor is default until edit mode is active
            var emailFieldInit = document.getElementById('email');
            var emailIconInit = document.getElementById('email_icon');
            if (emailFieldInit) emailFieldInit.style.cursor = 'default';
            if (emailIconInit) emailIconInit.style.cursor = 'default';

            // Contact number validation
            const contactInput = document.getElementById('contactnumber');
            const contactError = document.getElementById('contactError');

            function validateContact() {
                const val = contactInput.value.replace(/\D/g, ''); // digits only
                if (contactInput.value !== val) contactInput.value = val;
                if (val.length === 11) {
                    contactError.style.display = 'none';
                    return true;
                } else {
                    contactError.style.display = 'block';
                    return false;
                }
            }

            contactInput.addEventListener('input', function() {
                validateContact();
                // if currently editing, disable save when invalid
                if (isEditing) {
                    saveBtn.disabled = !validateContact();
                }
            });

            // Auto-fade and remove alerts after 2 seconds (include info alerts)
            setTimeout(function() {
                ['success', 'danger', 'info'].forEach(function(type) {
                    var el = document.querySelector('.alert-' + type);
                    if (el) {
                        el.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-6px)';
                        setTimeout(function() { el.remove(); }, 500);
                    }
                });
            }, 2000);
        });
    </script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php 
            $page = 'account'; // Set current page for active navigation
            include('sidebar.php'); 
        ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="profile-card">
                <div class="avatar-section">
                    <div class="avatar">
                        <?php
                            $initials = strtoupper(substr($form['first_name'], 0, 1) . substr($form['last_name'], 0, 1));
                            echo htmlspecialchars($initials);
                        ?>
                    </div>
                    <div class="avatar-name"><?php echo $full_name; ?></div>
                    <div class="edit-text">Edit your profile information</div>
                </div>

                <div class="info-section">
                    <h2>Profile Information</h2>

                    <?php
                        // Show any success messages from this page or redirected flows (e.g., email verification)
                        if (!empty($_SESSION['profile_success'])) {
                            $success_msg = $_SESSION['profile_success'];
                            // clear it so it doesn't persist on refresh
                            unset($_SESSION['profile_success']);
                            $alert_class = (strpos($success_msg, 'No changes detected') !== false) ? 'alert-info' : 'alert-success';
                            echo '<div class="alert ' . $alert_class . '">' . htmlspecialchars($success_msg) . '</div>';
                        } elseif ($success) {
                            // allow different alert types: success or info
                            $alert_class = (strpos($success, 'No changes detected') !== false) ? 'alert-info' : 'alert-success';
                            echo '<div class="alert ' . $alert_class . '">' . htmlspecialchars($success) . '</div>';
                        }
                    ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Contact Number</label>
                            <div class="input-wrapper">
                                <input type="text" name="contactnumber" id="contactnumber" class="profile-input" value="<?php echo htmlspecialchars($form['contactnumber']); ?>" required maxlength="11" pattern="\d{11}" inputmode="numeric" title="Enter 11 digit contact number">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div id="contactError" style="color:#b02a37;font-size:0.95rem;display:none;margin-top:6px;">Contact number must be 11 digits.</div>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <div class="input-wrapper">
                                <input type="text" name="address" class="profile-input" value="<?php echo htmlspecialchars($form['address']); ?>" required>
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <div class="input-wrapper">
                    <!-- Email remains readonly but is clickable to open verification page -->
                    <input type="email" name="email" id="email" class="profile-input no-edit clickable" value="<?php echo htmlspecialchars($form['email']); ?>" required readonly
                        role="button" tabindex="0" aria-label="Change email address" onclick="openEmailVerification()" onkeydown="if(event.key === 'Enter' || event.key === ' ') openEmailVerification();">
                                <i class="fas fa-envelope" id="email_icon" onclick="openEmailVerification()" title="Click to change email"></i>
                            </div>
                        </div>

                        <h2>Change Password</h2>

                        <div class="form-group">
                            <label>Current Password</label>
                            <div class="input-wrapper">
                                <input type="password" name="current_password" id="current_password" class="password-input">
                                <i class="fas fa-eye-slash" id="current_password_icon" onclick="togglePassword('current_password')"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>New Password</label>
                            <div class="input-wrapper">
                                <input type="password" name="new_password" id="new_password" class="password-input">
                                <i class="fas fa-eye-slash" id="new_password_icon" onclick="togglePassword('new_password')"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" name="confirm_password" id="confirm_password" class="password-input">
                                <i class="fas fa-eye-slash" id="confirm_password_icon" onclick="togglePassword('confirm_password')"></i>
                            </div>
                        </div>

                        <div class="button-container">
                            <button type="button" class="edit-btn" id="editBtn" onclick="toggleEdit()">
                                Edit Profile
                            </button>
                            <button class="save-btn" id="saveBtn" type="submit">
                                Save Changes
                            </button>
                        </div>
                    </form>

                    <div class="terms-btn-container">
                        <button class="button" onclick="showTerms()">
                            Terms & Conditions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Terms and Conditions -->
    <div id="termsModal">
        <div class="modal-content">
            <h2 style="color: var(--main-color); font-weight: 700; margin-bottom: 18px;">AGAPAY TERMS AND CONDITIONS OF USE</h2>
            <p><strong>Effective Date:</strong> January 1, 2025</p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">1. Agreement to Terms</h3>
            <p>
                By accessing or utilizing the <strong>AGAPAY</strong> mobile application (“the Application”), the User agrees to be bound by these Terms and Conditions of Use (“Terms”). These Terms constitute a binding agreement between the User and the application administrators of AGAPAY, operated under the authority of the <strong>Municipality of Cuenca, Batangas</strong> and its <strong>Municipal Disaster Risk Reduction and Management Office (MDRRMO)</strong>.<br><br>
                Use of the Application signifies acceptance of all provisions stated herein. Users who do not agree with these Terms are advised to discontinue use immediately.
            </p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">2. Eligibility Requirements</h3>
            <p>
                2.1 The User affirms that they are at least sixteen (16) years of age or have obtained parental or guardian consent to use the Application.<br>
                2.2 The Application is intended for residents, visitors, and authorized personnel within the jurisdiction of the Municipality of Cuenca, Batangas.<br>
                2.3 The User must have an active internet connection to access AGAPAY’s services and features.
            </p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">3. User Obligations</h3>
            <ul>
                <li>Utilize the Application solely for its intended purpose of emergency communication, reporting, and coordination.</li>
                <li>Maintain the confidentiality of their account credentials and avoid sharing login details with others.</li>
                <li>Ensure the accuracy and completeness of all information submitted through the Application, especially during emergency reporting.</li>
                <li>Respect other users, responders, and MDRRMO staff when using AGAPAY’s messaging or reporting systems.</li>
            </ul>
            <p>Failure to comply with these obligations may result in suspension or termination of access privileges.</p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">4. Data Privacy Provisions</h3>
            <p>
                The Application collects and processes personal information such as name, contact number, address, geolocation data, and emergency details to facilitate faster and more efficient response during emergencies.<br><br>
                All personal data are handled in compliance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>. The Application ensures that collected data are:
            </p>
            <ul>
                <li>Used solely for emergency response, verification, and coordination with authorized agencies;</li>
                <li>Protected using reasonable physical, technical, and organizational safeguards;</li>
                <li>Not shared with third parties without the User’s consent, except when required by law or for urgent life-saving purposes.</li>
            </ul>
            <p>Users may request access, correction, or deletion of their personal data by contacting the Data Protection Officer.</p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">5. Emergency Services Disclaimer</h3>
            <p>
                The Application serves as a communication and reporting tool to assist the MDRRMO and other responding agencies. However, AGAPAY does not guarantee:
            </p>
            <ul>
                <li>Immediate response to all emergency reports;</li>
                <li>Uninterrupted service availability;</li>
                <li>Accuracy of information provided by third-party users or systems.</li>
            </ul>
            <p>
                The Application is an aid to emergency response but does not replace direct calls to emergency hotlines in life-threatening situations.
            </p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">6. Prohibited Activities</h3>
            <ul>
                <li>6.1 Transmitting or submitting false or misleading emergency reports;</li>
                <li>6.2 Attempting unauthorized access to AGAPAY systems, databases, or administrative portals;</li>
                <li>6.3 Using the Application to harass, threaten, or abuse MDRRMO personnel or other users;</li>
                <li>6.4 Sharing, misusing, or disclosing another User’s personal information without authorization;</li>
                <li>6.5 Uploading malicious software, viruses, or scripts intended to disrupt Application functionality.</li>
            </ul>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">7. Violations and Sanctions</h3>
            <h4 style="margin-top: 10px;">7.1 Violation Categories</h4>
            <ul>
                <li><strong>Category 1:</strong> Minor infractions (e.g., accidental misuse, incomplete reports)</li>
                <li><strong>Category 2:</strong> Substantial violations (e.g., deliberate false emergency reports, repeated misconduct)</li>
                <li><strong>Category 3:</strong> Criminal offenses (e.g., data breaches, identity theft, cyberattacks, malicious misuse of information)</li>
            </ul>
            <h4 style="margin-top: 10px;">7.2 Sanction Framework</h4>
            <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-top: 10px;">
                <thead>
                    <tr style="background-color: #f0f0f0;">
                        <th>Violation Severity</th>
                        <th>Administrative Action</th>
                        <th>Legal Consequences</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Category 1</td>
                        <td>Written warning</td>
                        <td>None</td>
                    </tr>
                    <tr>
                        <td>Category 2</td>
                        <td>Account suspension (30–90 days)</td>
                        <td>Possible civil liability</td>
                    </tr>
                    <tr>
                        <td>Category 3</td>
                        <td>Permanent termination</td>
                        <td>Criminal prosecution under applicable laws</td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top: 10px;">
                7.3 The Application administrators reserve the right to:
            </p>
            <ul>
                <li>Immediately suspend or terminate accounts for severe or repeated violations;</li>
                <li>Refer cases to the appropriate law enforcement agencies for further investigation and action.</li>
            </ul>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">8. Intellectual Property Rights</h3>
            <p>
                All Application content, including software architecture, source code, graphics, user interface design, databases, and proprietary algorithms, are the exclusive property of the <strong>Municipality of Cuenca, Batangas</strong>, and its development partners.<br><br>
                Unauthorized reproduction, modification, distribution, or reverse engineering of the Application or any of its components is strictly prohibited and may result in legal action.
            </p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">9. Termination of Access</h3>
            <p>
                The AGAPAY administrators may revoke or suspend a User’s access to the Application without prior notice in the event of:
            </p>
            <ul>
                <li>Violation of these Terms and Conditions;</li>
                <li>Fraudulent or malicious activity;</li>
                <li>Technical issues or security threats requiring immediate mitigation.</li>
            </ul>
            <p>Users may request reinstatement after evaluation, except for Category 3 violations.</p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">10. Limitation of Liability</h3>
            <p>
                To the maximum extent permitted by law, the Application provider and its affiliated agencies shall not be held liable for:
            </p>
            <ul>
                <li>Any indirect, incidental, or consequential damages arising from the use or inability to use the Application;</li>
                <li>Delays or failures caused by network outages or external service interruptions;</li>
                <li>Errors or omissions resulting from User negligence or misinformation;</li>
                <li>Acts of God or events beyond reasonable control.</li>
            </ul>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">11. Governing Law and Dispute Resolution</h3>
            <p>
                These Terms shall be governed by and construed in accordance with the <strong>laws of the Republic of the Philippines</strong>.<br><br>
                Any dispute or claim arising out of or in connection with the use of AGAPAY shall first be resolved through <strong>conciliation or arbitration</strong> facilitated by the <strong>Municipal Legal Office of Cuenca, Batangas</strong>.<br>
                If unresolved, such disputes may be elevated to the proper courts of Batangas Province.
            </p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">12. Amendments to Terms</h3>
            <p>
                The Application administrators reserve the right to modify or update these Terms and Conditions at any time to reflect legal, operational, or technological changes.<br><br>
                Users will be notified of any material updates through in-app announcements, email, or SMS prior to enforcement. Continued use of the Application after such notification constitutes acceptance of the revised Terms.
            </p>
            <hr>
            <h3 style="color: var(--main-color); margin-top: 20px;">13. Contact Information</h3>
            <p>
                For questions, feedback, or reports concerning these Terms and the Application’s operation, Users may contact:
            </p>
            <ul>
                <li><strong>Terms and Policy Inquiries:</strong> <a href="mailto:officialagapay@gmail.com">officialagapay@gmail.com</a></li>
            </ul>
            <hr>
            <p style="font-weight: 600; color: var(--main-color); text-align: center;">
                By continuing to use the AGAPAY Application, you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.
            </p>
            <button onclick="closeTerms()" style="width:100%;margin-top:18px;padding:12px 0;font-size:16px;border-radius:8px;background:var(--main-color);color:#fff;border:none;cursor:pointer;">Close</button>
        </div>
    </div>
</body>
</html>