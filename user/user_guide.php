<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Guide | AGAPAY</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background: #fff;
      font-family: 'Segoe UI', Arial, sans-serif;
      color: #222;
      margin: 0;
      padding: 0;
    }
    .guide-header {
      background: #8B1E1E;
      color: #fff;
      padding: 1.1rem 0.7rem 1.1rem 0.7rem;
      display: flex;
      align-items: center;
      justify-content: center;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0 2px 8px rgba(139,30,30,0.07);
    }
    .guide-header img {
      height: 38px;
      margin-right: 12px;
    }
    .guide-header-title {
      font-size: 1.45rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      margin: 0;
      flex: 1;
      text-align: center;
    }
    .guide-content {
      max-width: 700px;
      margin: 0 auto;
      padding: 2.2rem 1.2rem 1.2rem 1.2rem;
      background: #fff;
    }
    .guide-content h2 {
      color: #8B1E1E;
      font-size: 1.5rem;
      font-weight: 700;
      margin-top: 2.2rem;
      margin-bottom: 1.1rem;
    }
    .guide-content h3 {
      color: #802108;
      font-size: 1.18rem;
      font-weight: 600;
      margin-top: 1.7rem;
      margin-bottom: 0.7rem;
    }
    .guide-content h4 {
      color: #802108;
      font-size: 1.08rem;
      font-weight: 600;
      margin-top: 1.2rem;
      margin-bottom: 0.5rem;
    }
    .guide-content p, .guide-content li {
      font-size: 1.07rem;
      color: #222;
      line-height: 1.7;
    }
    .guide-content ul {
      margin-bottom: 1.2rem;
    }
    .guide-content strong {
      color: #8B1E1E;
    }
    .guide-content .section-divider {
      border: none;
      border-top: 1.5px solid #eee;
      margin: 2.2rem 0 2.2rem 0;
    }
    .guide-content .step {
      background: #fff7f5;
      border-left: 4px solid #8B1E1E;
      padding: 0.85rem 1.1rem;
      border-radius: 8px;
      margin-bottom: 1.1rem;
    }
    @media (max-width: 600px) {
      .guide-content {
        padding: 1.2rem 0.7rem 1.2rem 0.7rem;
      }
      .guide-header-title {
        font-size: 1.1rem;
      }
      .guide-header img {
        height: 30px;
        margin-right: 8px;
      }
      .guide-content h2 {
        font-size: 1.15rem;
      }
      .guide-content h3 {
        font-size: 1.01rem;
      }
      .guide-content p, .guide-content li {
        font-size: 0.98rem;
      }
    }
  </style>
</head>
<body>
  <div class="guide-header">
    <a href="home.php" style="color:#fff; text-decoration:none; margin-right:12px; display:flex; align-items:center;">
      <i class="bi bi-arrow-left" style="font-size:1rem;"></i>
    </a>
    <span class="guide-header-title" style="flex:1; text-align:center;">AGAPAY User Guide</span>
  </div>
  <div class="guide-content">
    <h2><strong>AGAPAY Mobile Application – User Guide for Residents of Cuenca</strong></h2>

    <h3>About AGAPAY</h3>
    <p>
      <strong>AGAPAY</strong> is a mobile emergency response and management application developed for the Municipality of Cuenca, Batangas.<br>
      The term “Agapay” means <em>“to assist”</em> or <em>“to support”</em>, reflecting the app’s purpose — to help residents during emergencies by providing a fast, reliable, and direct communication link between the <strong>residents</strong> and the <strong>Municipal Disaster Risk Reduction and Management Office (MDRRMO)</strong>.
    </p>
    <ul>
      <li><strong>Send SOS alerts</strong> directly to MDRRMO with their real-time location.</li>
      <li><strong>Report incidents</strong> such as crimes, accidents, medical emergencies, fires, and natural disasters.</li>
      <li><strong>Access safety tips</strong> for different emergencies.</li>
      <li><strong>Receive updates and guidance</strong> from MDRRMO via the built-in messaging system.</li>
    </ul>
    <p>This guide will help residents understand how to use the app effectively.</p>

    <hr class="section-divider">

    <h3>Getting Started with AGAPAY</h3>

    <h4>1. Opening the App</h4>
    <div class="step">
      When you open AGAPAY, you’ll first see the <strong>splash screen</strong> featuring the app logo and name. This page establishes the app’s identity and prepares you to proceed.
    </div>

    <h4>2. Account Type Selection</h4>
    <div class="step">
      After the splash screen, you will be asked to select your account type:
      <ul>
        <li><strong>Guest</strong> – Allows you to access limited features without logging in.</li>
        <li><strong>Resident</strong> – Full access to all app features after logging in or signing up.</li>
        <li><strong>MDRRMO</strong> – For authorized disaster response personnel only.</li>
      </ul>
      Select <strong>Resident</strong> to proceed with the full version of the app.
    </div>

    <h4>3. Logging In</h4>
    <div class="step">
      The <strong>Login Page</strong> allows residents to securely access their accounts.<br>
      You can:
      <ul>
        <li>Enter your <strong>username or email</strong> and <strong>password</strong>.</li>
        <li>Tap <strong>“Forgot Password?”</strong> if you can’t remember your credentials.</li>
      </ul>
    </div>

    <h4>4. Resetting Your Password</h4>
    <div class="step">
      If you forgot your password:
      <ol>
        <li>Enter your <strong>email address</strong> on the Reset Password page.</li>
        <li>Check your email for a <strong>One-Time Password (OTP)</strong> and enter it on the OTP Verification page.</li>
        <li>Once verified, create and confirm a <strong>new password</strong> on the New Password page.</li>
      </ol>
    </div>

    <h4>5. Creating an Account</h4>
    <div class="step">
      If you’re a new user:
      <ol>
        <li>Tap <strong>“Sign Up”</strong> to create an account.</li>
        <li>Fill out your personal details, such as:
          <ul>
            <li>Full name</li>
            <li>Date of birth</li>
            <li>Address</li>
            <li>Contact number and email</li>
            <li>Password (and confirmation)</li>
          </ul>
        </li>
        <li>Select the <strong>type of ID</strong> you will use and <strong>upload a photo</strong> of it for verification.</li>
        <li>Tap <strong>“Submit”</strong> to complete your registration.</li>
      </ol>
    </div>

    <hr class="section-divider">

    <h3>Using AGAPAY Features</h3>

    <h4>6. Choosing an Emergency Type</h4>
    <div class="step">
      When an emergency occurs, you can report it directly through the app.<br>
      Select from the available emergency categories:
      <ul>
        <li><strong>Crime</strong></li>
        <li><strong>Medical</strong></li>
        <li><strong>Natural Disaster</strong></li>
        <li><strong>Fire</strong></li>
        <li><strong>Accident</strong></li>
      </ul>
    </div>

    <h4>7. Submitting a Report</h4>
    <div class="step">
      The <strong>Report Submission Module</strong> allows you to report emergencies quickly and accurately:
      <ol>
        <li>Choose the <strong>type of emergency</strong>.</li>
        <li>Enter a <strong>brief description</strong> of the incident.</li>
        <li>Optionally, upload a <strong>photo</strong> for reference.</li>
        <li>Your <strong>current location</strong> will be automatically captured using GPS.</li>
        <li>Tap <strong>Submit</strong> to send your report to MDRRMO.</li>
      </ol>
      This ensures that responders receive the exact location and nature of your emergency.
    </div>

    <h4>8. Accessing Emergency Tips</h4>
    <div class="step">
      The <strong>Emergency Tips Page</strong> provides safety guides for different situations.
      <ul>
        <li>Tap a category (e.g., fire, earthquake, flood) to see <strong>practical safety instructions</strong>.</li>
        <li>These guidelines help you <strong>prepare for</strong>, <strong>respond to</strong>, and <strong>stay safe during</strong> emergencies.</li>
      </ul>
    </div>

    <h4>9. Managing Your Account</h4>
    <div class="step">
      In the <strong>Account Personalization Page</strong>, you can:
      <ul>
        <li><strong>Edit your contact details</strong> (email, phone number, and address).</li>
        <li><strong>Log out</strong> when you’re done using the app.</li>
      </ul>
      Keeping your information updated ensures accurate communication during emergencies.
    </div>

    <h4>10. Messaging MDRRMO</h4>
    <div class="step">
      The <strong>Messaging Module</strong> allows you to <strong>communicate directly with MDRRMO staff</strong>:
      <ul>
        <li>Ask for <strong>real-time assistance</strong> during emergencies.</li>
        <li>Get <strong>updates</strong> on your submitted reports.</li>
        <li>Receive <strong>instructions or alerts</strong> from the disaster response team.</li>
      </ul>
      This feature makes it easier for residents to stay informed and connected in critical situations.
    </div>

    <hr class="section-divider">

    <h3>Summary</h3>
    <p>
      AGAPAY is designed to <strong>empower residents of Cuenca</strong> to act quickly and stay informed during emergencies.<br>
      By following this guide, users can:
    </p>
    <ul>
      <li>Create and manage their accounts.</li>
      <li>Report emergencies effectively.</li>
      <li>Access safety information.</li>
      <li>Communicate directly with MDRRMO for fast response.</li>
    </ul>
    <p>
      Together, AGAPAY strengthens community resilience and promotes safety for all Cuenca residents.
    </p>
  </div>
</body>
</html>