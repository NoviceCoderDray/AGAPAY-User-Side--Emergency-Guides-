<?php
date_default_timezone_set('Asia/Manila');
include('user_session.php');
include('../config/config.php');

// Get user info including user_id
$user_id = null;
if (!isset($first_name) || !isset($last_name) || !isset($user_id)) {
  if (isset($_SESSION['user_email'])) {
    $conn = isset($conn) ? $conn : (include 'connect.php');
    $user_query = "SELECT id, first_name, last_name FROM users WHERE email = '" . mysqli_real_escape_string($conn, $_SESSION['user_email']) . "'";
    $user_result = mysqli_query($conn, $user_query);
    if ($user_result && $user_data = mysqli_fetch_assoc($user_result)) {
      $user_id = $user_data['id'];
      $first_name = $user_data['first_name'];
      $last_name = $user_data['last_name'];
    }
  }
}

// Also check session for user_id as fallback
if (!$user_id && isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
}

$success = '';
$error = '';

// Handle media upload from camera
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
  if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }

    $media_type = $_POST['media_type'] ?? 'image';
    $extension = $media_type === 'video' ? 'webm' : 'jpg';
    $file_name = time() . '_' . uniqid() . '.' . $extension;
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
      echo $file_path;
    } else {
      echo 'ERROR';
    }
  } else {
    echo 'ERROR';
  }
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emergency_type'])) {
  $emergency_type = trim($_POST['emergency_type']);
  $street_name = trim($_POST['street_name']);
  $message = trim($_POST['message']);
  $reported_by = isset($first_name) && isset($last_name) ? $first_name . ' ' . $last_name : '';
  $latitude = isset($_POST['latitude']) && !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
  $longitude = isset($_POST['longitude']) && !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;

  // Handle captured media path
  $files_db = '';
  if (isset($_POST['captured_media_path']) && !empty($_POST['captured_media_path'])) {
    $files_db = trim($_POST['captured_media_path']);
  }

  if (empty($emergency_type) || empty($street_name) || empty($message) || empty($reported_by)) {
    $error = "Complete the form.";
  } else {
    $date_of_call = date('Y-m-d');
    $time_of_call = date('H:i:s');

    // Deduplication logic
    $duplicate_found = false;
    $master_incident_id = null;

    $active_incidents_query = "SELECT id, latitude, longitude, created_at FROM incident_reports 
                            WHERE emergency_type = '" . mysqli_real_escape_string($conn, $emergency_type) . "' 
                            AND status = 'pending' 
                            AND latitude IS NOT NULL 
                            AND longitude IS NOT NULL";
    $active_incidents_result = mysqli_query($conn, $active_incidents_query);

    if ($active_incidents_result && mysqli_num_rows($active_incidents_result) > 0 && $latitude && $longitude) {
      while ($incident = mysqli_fetch_assoc($active_incidents_result)) {
        $incident_time = strtotime($incident['created_at']);
        $current_time = time();
        $time_diff_minutes = ($current_time - $incident_time) / 60;
        $distance_meters = calculateDistance($latitude, $longitude, $incident['latitude'], $incident['longitude']);

        $max_distance = 0;
        $max_time = 0;

        switch (strtolower($emergency_type)) {
          case 'crime':
            $max_distance = 200;
            $max_time = 5;
            break;
          case 'disaster':
            $max_distance = 600;
            $max_time = 15;
            break;
          case 'medical':
            $max_distance = 200;
            $max_time = 5;
            break;
          case 'accident':
            $max_distance = 400;
            $max_time = 10;
            break;
          case 'fire':
            $max_distance = 800;
            $max_time = 15;
            break;
          default:
            continue 2;
        }

        if ($distance_meters <= $max_distance && $time_diff_minutes <= $max_time) {
          $duplicate_found = true;
          $master_incident_id = $incident['id'];
          break;
        }
      }
    }

    if ($duplicate_found) {
      // UPDATED: Include user_id in sub-report
      $sub_report_sql = "INSERT INTO incident_sub_reports 
                          (master_incident_id, reported_by, user_id, street_name, emergency_type, date_of_call, time_of_call, latitude, longitude, message, files, created_at) 
                          VALUES 
                          ('$master_incident_id', '" . mysqli_real_escape_string($conn, $reported_by) . "', " . ($user_id ? $user_id : 'NULL') . ", '" . mysqli_real_escape_string($conn, $street_name) . "', '" . mysqli_real_escape_string($conn, $emergency_type) . "', '$date_of_call', '$time_of_call', " . ($latitude ? $latitude : 'NULL') . ", " . ($longitude ? $longitude : 'NULL') . ", '" . mysqli_real_escape_string($conn, $message) . "', '" . mysqli_real_escape_string($conn, $files_db) . "', NOW())";

      if (mysqli_query($conn, $sub_report_sql)) {
        $success = "Report successfully linked to existing incident as additional report.";
      } else {
        $error = "Error creating sub-report: " . mysqli_error($conn);
      }
    } else {
      // UPDATED: Include user_id in main report
      $emergency_type_db = mysqli_real_escape_string($conn, $emergency_type);
      $street_name_db = mysqli_real_escape_string($conn, $street_name);
      $message_db = mysqli_real_escape_string($conn, $message);
      $reported_by_db = mysqli_real_escape_string($conn, $reported_by);
      $files_db_escaped = mysqli_real_escape_string($conn, $files_db);
      $status = 'pending';

      $lat_db = $latitude !== null ? $latitude : 'NULL';
      $lng_db = $longitude !== null ? $longitude : 'NULL';
      $user_id_db = $user_id ? $user_id : 'NULL';

      $sql = "INSERT INTO incident_reports (reported_by, user_id, street_name, emergency_type, message, files, status, date_of_call, time_of_call, latitude, longitude, created_at) 
              VALUES ('{$reported_by_db}', {$user_id_db}, '{$street_name_db}', '{$emergency_type_db}', '{$message_db}', '{$files_db_escaped}', '{$status}', '{$date_of_call}', '{$time_of_call}', {$lat_db}, {$lng_db}, NOW())";

      if (mysqli_query($conn, $sql)) {
        $success = "Report submitted successfully.";
      } else {
        $error = "Failed to submit report. Please try again.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AGAPAY - Create Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #fff;
      margin: 0;
      padding: 0;
    }

    .container-wrapper {
      max-width: 1200px;
      margin: auto;
      padding: 2rem 1rem;
      padding-bottom: 100px;
    }

    @media (max-width: 768px) {
      .container-wrapper {
        padding: 1rem;
        padding-bottom: 100px;
      }

      .btn-submit {
        margin-bottom: 20px;
      }

      body {
        padding-bottom: 80px;
      }
    }

    @media (max-width: 576px) {
      .container-wrapper {
        padding: 0.5rem;
        padding-bottom: 120px;
      }

      .btn-submit {
        margin-bottom: 30px;
        position: relative;
        z-index: 1;
      }
    }

    .form-title {
      font-size: 1.8rem;
      font-weight: bold;
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .form-control,
    .form-select {
      background-color: #eee;
      border: none;
      border-radius: 10px;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
    }

    .form-label {
      font-weight: 500;
      margin-top: 0.5rem;
    }

    .btn-submit {
      background-color: #802108;
      color: white;
      border-radius: 15px;
      padding: 0.8rem;
      width: 100%;
      font-weight: bold;
      border: none;
      margin-top: 1rem;
    }

    .btn-submit:hover {
      background-color: #5e1606;
    }

    .btn-submit:disabled {
      background-color: #9ca3af;
      cursor: not-allowed;
    }

    .form-section {
      margin-bottom: 2rem;
      padding: 1.5rem;
      background-color: #fafafa;
      border-radius: 15px;
      border: 1px solid #e9ecef;
    }

    .section-title {
      color: #802108;
      font-weight: 600;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #802108;
      font-size: 1.1rem;
    }

    .emergency-types-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .emergency-card {
      background: white;
      border: 2px solid #e5e7eb;
      border-radius: 15px;
      padding: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
      position: relative;
      min-height: 100px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }

    .emergency-card:hover {
      border-color: #802108;
      background-color: #fef5f5;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(128, 33, 8, 0.15);
    }

    .emergency-card.selected {
      border-color: #802108;
      background-color: #802108;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(128, 33, 8, 0.3);
    }

    .emergency-card .icon {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      display: block;
    }

    .emergency-card .title {
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 0.25rem;
    }

    .emergency-card .description {
      font-size: 0.7rem;
      opacity: 0.8;
      line-height: 1.2;
    }

    .emergency-card[data-type="fire"] .icon {
      color: #dc2626;
    }

    .emergency-card[data-type="medical"] .icon {
      color: #16a34a;
    }

    .emergency-card[data-type="accident"] .icon {
      color: #d97706;
    }

    .emergency-card[data-type="crime"] .icon {
      color: #7c3aed;
    }

    .emergency-card[data-type="disaster"] .icon {
      color: #2563eb;
    }

    .emergency-card.selected .icon,
    .emergency-card.selected .title,
    .emergency-card.selected .description {
      color: white !important;
    }

    .location-status {
      font-size: 0.875rem;
      margin-bottom: 1rem;
      padding: 1rem;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .location-status.loading {
      background-color: #fefce8;
      color: #ca8a04;
      border: 1px solid #fef3c7;
    }

    .location-status.success {
      background-color: #dcfce7;
      color: #166534;
      border: 1px solid #bbf7d0;
    }

    .location-status.error {
      background-color: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }

    .location-spinner {
      width: 16px;
      height: 16px;
      border: 2px solid rgba(202, 138, 4, 0.3);
      border-top: 2px solid #ca8a04;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    .auto-location-info {
      background: linear-gradient(135deg, #4285F4, #34A853);
      color: white;
      padding: 1rem;
      border-radius: 10px;
      margin-bottom: 1rem;
      text-align: center;
      font-size: 0.9rem;
    }

    /* Camera Section Styles */
    .camera-btn {
      background-color: #802108;
      color: white;
      border: none;
      border-radius: 12px;
      padding: 1rem;
      width: 100%;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      cursor: pointer;
    }

    .camera-btn:hover {
      background-color: #5e1606;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(128, 33, 8, 0.3);
    }

    .camera-btn i {
      font-size: 1.5rem;
    }

    .captured-media-preview {
      background: white;
      border-radius: 12px;
      padding: 1rem;
      border: 2px solid #e5e7eb;
      margin-top: 1rem;
    }

    .media-filename {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
      background-color: #f3f4f6;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .media-filename i {
      font-size: 1.5rem;
      color: #802108;
    }

    .media-filename-text {
      flex: 1;
      font-weight: 500;
      color: #374151;
      font-size: 0.9rem;
      word-break: break-all;
    }

    .media-actions {
      display: flex;
      gap: 0.5rem;
    }

    .btn-retake,
    .btn-delete-media {
      flex: 1;
      padding: 0.75rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-retake {
      background-color: #3b82f6;
      color: white;
    }

    .btn-retake:hover {
      background-color: #2563eb;
    }

    .btn-delete-media {
      background-color: #ef4444;
      color: white;
    }

    .btn-delete-media:hover {
      background-color: #dc2626;
    }

    @media (max-width: 768px) {
      .emergency-types-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 576px) {

      .container-wrapper,
      .form-title,
      .form-grid,
      .form-col,
      .logo {
        margin-left: 7px !important;
        margin-right: 7px !important;
      }

      body,
      .form-title,
      .form-control,
      .form-select,
      .btn-submit,
      .form-label {
        font-size: 0.95rem !important;
      }

      .form-title {
        font-size: 1.2rem !important;
      }

      .emergency-types-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 400px) {

      body,
      .form-title,
      .form-control,
      .form-select,
      .btn-submit,
      .form-label {
        font-size: 0.85rem !important;
      }

      .form-title {
        font-size: 1rem !important;
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
  </style>
</head>

<body>
  <div class="d-flex">
    <?php
    $page = 'create_report';
    include('sidebar.php');
    ?>

    <div class="container-wrapper" style="flex-grow:1;">
      <div class="form-title">Report an Emergency</div>

      <?php if ($error): ?>
        <div class="alert alert-danger" style="max-width:1000px;margin:20px auto;"> <?php echo $error; ?> </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success" style="max-width:1000px;margin:20px auto;"> <?php echo $success; ?> </div>
      <?php endif; ?>

      <div class="auto-location-info">
        <i class="bi bi-info-circle-fill"></i>
        <strong>Auto-Detection Active:</strong> We're automatically detecting your location to provide the fastest emergency response.
      </div>

      <div id="locationStatus" class="location-status loading">
        <div class="location-spinner"></div>
        <span>Getting your location automatically...</span>
      </div>

      <form id="createReportForm" method="post" action="">
        <input type="hidden" name="latitude" id="latitudeInput">
        <input type="hidden" name="longitude" id="longitudeInput">
        <input type="hidden" name="emergency_type" id="emergencyTypeInput">
        <input type="hidden" name="captured_media_path" id="capturedMediaPath">

        <div class="form-section">
          <h5 class="section-title">Select Emergency Type</h5>

          <div class="emergency-types-grid">
            <div class="emergency-card" data-type="fire" onclick="selectEmergency('fire', this)">
              <i class="bi bi-fire icon"></i>
              <div class="title">Fire</div>
              <div class="description">Building fires, forest fires</div>
            </div>

            <div class="emergency-card" data-type="medical" onclick="selectEmergency('medical', this)">
              <i class="bi bi-heart-pulse-fill icon"></i>
              <div class="title">Medical</div>
              <div class="description">Heart attack, injuries</div>
            </div>

            <div class="emergency-card" data-type="accident" onclick="selectEmergency('accident', this)">
              <i class="bi bi-exclamation-triangle-fill icon"></i>
              <div class="title">Accident</div>
              <div class="description">Vehicle accidents</div>
            </div>

            <div class="emergency-card" data-type="crime" onclick="selectEmergency('crime', this)">
              <i class="bi bi-shield-exclamation icon"></i>
              <div class="title">Crime</div>
              <div class="description">Robbery, assault</div>
            </div>

            <div class="emergency-card" data-type="disaster" onclick="selectEmergency('disaster', this)">
              <i class="bi bi-cloud-lightning-rain-fill icon"></i>
              <div class="title">Disaster</div>
              <div class="description">Floods, earthquakes</div>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Location</label>
              <input type="text" class="form-control" name="street_name" id="streetNameInput"
                placeholder="Automatically detected from your GPS location..." readonly>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Additional Details</label>
              <textarea class="form-control" name="message" rows="3"
                placeholder="Describe what happened, number of people involved, severity, etc..." required></textarea>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label class="form-label">Capture Evidence <span style="color: #6b7280; font-size: 0.85rem;"></span></label>

              <div id="cameraButtonContainer">
                <button type="button" class="camera-btn" onclick="openCameraModal()">
                  <i class="bi bi-camera-fill"></i>
                  <span>Take Photo or Record Video</span>
                </button>
              </div>

              <div id="capturedMediaContainer" style="display: none;" class="captured-media-preview">
                <div class="media-filename">
                  <i class="bi bi-file-earmark-check-fill"></i>
                  <span class="media-filename-text" id="mediaFileName"></span>
                </div>
                <div class="media-actions">
                  <button type="button" class="btn-retake" onclick="retakeMedia()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Retake
                  </button>
                  <button type="button" class="btn-delete-media" onclick="deleteMedia()">
                    <i class="bi bi-trash me-1"></i>Remove
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn" disabled>
          <i class="bi bi-send-fill"></i> Submit Emergency Report
        </button>
      </form>
    </div>
  </div>

  <!-- Camera Modal -->
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
                  stroke="#802108" stroke-width="4"
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

  <!-- Outside Cuenca warning modal (used by the submit check) -->
  <div class="modal fade" id="outsideCuencaModal" tabindex="-1" aria-labelledby="outsideCuencaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="outsideCuencaModalLabel">Location outside Cuenca</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="outsideCuencaModalBody">
          <!-- Message will be filled by existing JS -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places&callback=initializeGoogleMaps">
  </script>

  <script>
    let googleMapsLoaded = false;
    let geocoder = null;
    let selectedEmergencyType = null;
    let locationObtained = false;
    let capturedMediaPath = '';

    // Camera variables
    let cameraModal = null;
    let cameraStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let isRecording = false;
    let recordingTimer = null;
    let recordingStartTime = null;
    let currentFacingMode = 'environment';
    let captureTimeout = null;
    let progressInterval = null;
    let captureHandlersBound = false;
    const MAX_RECORDING_TIME = 15000;

    function initializeGoogleMaps() {
      googleMapsLoaded = true;
      geocoder = new google.maps.Geocoder();
      console.log('Google Maps API loaded successfully');
    }

    function selectEmergency(type, cardElement) {
      document.querySelectorAll('.emergency-card').forEach(card => {
        card.classList.remove('selected');
      });

      cardElement.classList.add('selected');
      selectedEmergencyType = type;
      document.getElementById('emergencyTypeInput').value = type;

      console.log('Selected emergency type:', type);
      checkFormReadiness();
    }

    function checkFormReadiness() {
      const submitBtn = document.getElementById('submitBtn');

      if (selectedEmergencyType && locationObtained) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send-fill"></i> Submit Emergency Report';
      } else {
        submitBtn.disabled = true;
        let message = 'Submit Emergency Report';
        if (!selectedEmergencyType) message = 'Select Emergency Type First';
        else if (!locationObtained) message = 'Getting Location...';
        submitBtn.innerHTML = `<i class="bi bi-send-fill"></i> ${message}`;
      }
    }

    // Camera Modal Functions
    async function openCameraModal() {
      if (!cameraModal) {
        cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
      }

      try {
        // Hide sidebar when opening camera - multiple selectors
        const sidebarSelectors = [
          '.sidebar',
          '.offcanvas',
          '[class*="sidebar"]',
          'nav',
          '.d-flex > *:first-child', // First child of d-flex container
          '.container-wrapper ~ *', // Siblings of container
          '.d-flex > nav',
          '#sidebar',
          '.nav'
        ];

        sidebarSelectors.forEach(selector => {
          const elements = document.querySelectorAll(selector);
          elements.forEach(el => {
            // Don't hide the modal or camera components
            if (!el.closest('#cameraModal') &&
              !el.classList.contains('container-wrapper') &&
              !el.classList.contains('modal')) {
              el.style.display = 'none';
              el.setAttribute('data-hidden-by-camera', 'true');
            }
          });
        });

        // Also hide the entire d-flex parent's first child (likely the sidebar)
        const dFlexContainer = document.querySelector('.d-flex');
        if (dFlexContainer && dFlexContainer.children.length > 0) {
          const firstChild = dFlexContainer.children[0];
          if (!firstChild.classList.contains('container-wrapper')) {
            firstChild.style.display = 'none';
            firstChild.setAttribute('data-hidden-by-camera', 'true');
          }
        }

        await startCameraStream();
        cameraModal.show();
        setupCaptureButton();
      } catch (err) {
        console.error('Error accessing camera:', err);
        alert('Failed to access camera. Please check camera permissions.');

        // Show sidebar again if camera fails
        restoreSidebar();
      }
    }

    function closeCameraModal() {
      if (cameraModal) {
        cameraModal.hide();
        stopCameraStream();

        // Show sidebar again when closing camera
        restoreSidebar();
      }
    }

    function restoreSidebar() {
      // Restore all elements hidden by camera
      const hiddenElements = document.querySelectorAll('[data-hidden-by-camera="true"]');
      hiddenElements.forEach(el => {
        el.style.display = '';
        el.removeAttribute('data-hidden-by-camera');
      });
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
      await video.play();
      video.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'none';
    }

    function stopCameraStream() {
      if (cameraStream) {
        cameraStream.getTracks().forEach(t => t.stop());
        cameraStream = null;
      }
      const video = document.getElementById('cameraPreview');
      if (video) {
        video.srcObject = null;
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

      if (currentFacingMode === 'user') {
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
      }
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

      canvas.toBlob(async (blob) => {
        if (!blob) return alert('Failed to capture image.');
        await sendCameraMedia(blob, 'image');
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

        try {
          mediaRecorder = new MediaRecorder(combinedStream, {
            mimeType: 'video/webm;codecs=vp8'
          });
        } catch (e) {
          try {
            mediaRecorder = new MediaRecorder(combinedStream);
          } catch (err) {
            alert('Video recording is not supported on this device/browser.');
            isRecording = false;
            audioTrack.stop();
            return;
          }
        }

        mediaRecorder.ondataavailable = (event) => {
          if (event.data && event.data.size > 0) recordedChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
          const blob = new Blob(recordedChunks, {
            type: mediaRecorder.mimeType || 'video/webm'
          });
          audioTrack.stop();
          await sendCameraMedia(blob, 'video');
        };

        mediaRecorder.start();
        setTimeout(() => {
          if (isRecording) stopVideoRecording();
        }, MAX_RECORDING_TIME);
      }).catch(() => {
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
        document.getElementById('switchCameraBtn').disabled = false;
        resetProgress();
      }
    }

    async function sendCameraMedia(blob, type) {
      try {
        const formData = new FormData();
        formData.append('media_type', type);
        formData.append('media', blob, type === 'image' ? 'report_photo.jpg' : 'report_video.webm');
        formData.append('upload_media', '1');

        const response = await fetch('create_report.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.text();

        if (result && result !== 'ERROR') {
          capturedMediaPath = result;
          document.getElementById('capturedMediaPath').value = result;

          const fileName = result.split('/').pop();
          document.getElementById('mediaFileName').textContent = fileName;
          document.getElementById('cameraButtonContainer').style.display = 'none';
          document.getElementById('capturedMediaContainer').style.display = 'block';

          closeCameraModal();
        } else {
          alert('Failed to save media. Please try again.');
        }
      } catch (error) {
        console.error('Error sending media:', error);
        alert('Failed to send media');
      }
    }

    function retakeMedia() {
      deleteMedia();
      openCameraModal();
    }

    function deleteMedia() {
      capturedMediaPath = '';
      document.getElementById('capturedMediaPath').value = '';
      document.getElementById('cameraButtonContainer').style.display = 'block';
      document.getElementById('capturedMediaContainer').style.display = 'none';
    }

    // Location Detection
    document.addEventListener('DOMContentLoaded', function() {
      const locationStatus = document.getElementById('locationStatus');
      const streetNameInput = document.getElementById('streetNameInput');
      const latitudeInput = document.getElementById('latitudeInput');
      const longitudeInput = document.getElementById('longitudeInput');

      startLocationDetection();

      async function startLocationDetection() {
        if (!navigator.geolocation) {
          showLocationStatus('Geolocation not supported. Please enter location manually.', 'error');
          streetNameInput.placeholder = 'Enter your location manually';
          streetNameInput.readOnly = false;
          return;
        }

        navigator.geolocation.getCurrentPosition(
          async function(position) {
              const latitude = position.coords.latitude;
              const longitude = position.coords.longitude;

              latitudeInput.value = latitude;
              longitudeInput.value = longitude;
              locationObtained = true;

              try {
                const addressInfo = await getAccurateAddressFromCoords(latitude, longitude);
                streetNameInput.value = addressInfo.full_address;
                showLocationStatus(`✅ Location detected: ${addressInfo.full_address.substring(0, 50)}...`, 'success');
                checkFormReadiness();
              } catch (error) {
                showLocationStatus('Location detected, but address lookup failed. GPS coordinates will be used.', 'success');
                streetNameInput.value = `GPS: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                checkFormReadiness();
              }
            },
            function(error) {
              let errorMessage = 'Location access denied. Please allow location access and refresh the page.';
              showLocationStatus(errorMessage, 'error');
              streetNameInput.placeholder = 'Enter your exact location manually';
              streetNameInput.readOnly = false;
              streetNameInput.required = true;
              locationObtained = true;
              checkFormReadiness();
            }, {
              enableHighAccuracy: true,
              timeout: 10000,
              maximumAge: 30000
            }
        );
      }

      function showLocationStatus(message, type) {
        const statusIcon = {
          loading: '<div class="location-spinner"></div>',
          success: '<i class="bi bi-check-circle-fill"></i>',
          error: '<i class="bi bi-exclamation-circle-fill"></i>'
        };

        locationStatus.innerHTML = `${statusIcon[type]} <span>${message}</span>`;
        locationStatus.className = `location-status ${type}`;

        if (type === 'success') {
          setTimeout(() => {
            locationStatus.style.display = 'none';
          }, 3000);
        }
      }

      async function getAccurateAddressFromCoords(lat, lng) {
        return new Promise((resolve, reject) => {
          if (!googleMapsLoaded || !geocoder) {
            const checkGoogleMaps = setInterval(() => {
              if (googleMapsLoaded && geocoder) {
                clearInterval(checkGoogleMaps);
                performGeocode();
              }
            }, 100);

            setTimeout(() => {
              clearInterval(checkGoogleMaps);
              reject(new Error('Google Maps API not loaded'));
            }, 10000);
          } else {
            performGeocode();
          }

          function performGeocode() {
            const latlng = {
              lat: parseFloat(lat),
              lng: parseFloat(lng)
            };
            geocoder.geocode({
              location: latlng
            }, (results, status) => {
              if (status === 'OK' && results[0]) {
                resolve({
                  full_address: results[0].formatted_address
                });
              } else {
                reject(new Error('Geocoding failed'));
              }
            });
          }
        });
      }

      // Form submission
      document.getElementById('createReportForm').addEventListener('submit', function(e) {

        /* ----------------- BEGIN CUENCA/BATANGAS SUBMISSION CHECK -----------------
           To allow submissions from anywhere again, simply comment out (or remove)
           the entire block between BEGIN and END (including these comment lines).
        
        (function() {
          // read the location field that's auto-populated (readonly by default)
          const streetField = document.getElementById('streetNameInput');
          const addr = (streetField && streetField.value) ? streetField.value : '';

          // case-insensitive word checks
          const hasCuenca = /\bcuenca\b/i.test(addr);
          const hasBatangas = /\bbatangas\b/i.test(addr);

          if (!hasCuenca || !hasBatangas) {
            // block submit and notify user
            e.preventDefault();

            // prefer a Bootstrap modal if present (non-invasive to UI)
            const modalEl = document.getElementById('outsideCuencaModal');
            const message = 'Your detected location appears to be outside Cuenca, Batangas. Reports can only be submitted inside Cuenca, Batangas.';
            if (modalEl) {
              // if modal has a body element id 'outsideCuencaModalBody' use it, otherwise set innerText
              const bodyEl = document.getElementById('outsideCuencaModalBody');
              if (bodyEl) bodyEl.textContent = message;
              new bootstrap.Modal(modalEl).show();
            } else {
              // fallback simple alert (no UI file changes required)
              alert(message);
            }

            return false; // stop submission
          }
        })();
         /*----------------- END CUENCA/BATANGAS SUBMISSION CHECK ----------------- */

        if (!selectedEmergencyType) {
          e.preventDefault();
          alert('Please select an emergency type before submitting.');
          return false;
        }

        if (!locationObtained) {
          e.preventDefault();
          alert('Please wait for location detection or enter your location manually.');
          return false;
        }

        const message = document.querySelector('textarea[name="message"]').value.trim();
        if (!message) {
          e.preventDefault();
          alert('Please provide additional details about the emergency.');
          return false;
        }

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="location-spinner"></div> Submitting Emergency Report...';
      });

      streetNameInput.addEventListener('click', function() {
        if (this.readOnly) {
          const userConfirm = confirm('Do you want to manually edit your location?');
          if (userConfirm) {
            this.readOnly = false;
            this.placeholder = 'Enter your exact location';
            this.focus();
          }
        }
      });

      checkFormReadiness();
    });
  </script>
</body>

</html>

<?php
function calculateDistance($lat1, $lng1, $lat2, $lng2)
{
  $google_distance = getGoogleMapsDistance($lat1, $lng1, $lat2, $lng2);
  if ($google_distance !== false) return $google_distance;
  return getStraightLineDistance($lat1, $lng1, $lat2, $lng2);
}

function getGoogleMapsDistance($lat1, $lng1, $lat2, $lng2)
{
  $api_key = GOOGLE_MAPS_API_KEY;
  $origin = $lat1 . ',' . $lng1;
  $destination = $lat2 . ',' . $lng2;
  $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($origin) . "&destinations=" . urlencode($destination) . "&key=" . $api_key . "&mode=driving";
  $response = @file_get_contents($url);
  if ($response === false) return false;
  $data = json_decode($response, true);
  if ($data && isset($data['status']) && $data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['status']) && $data['rows'][0]['elements'][0]['status'] === 'OK') {
    return $data['rows'][0]['elements'][0]['distance']['value'];
  }
  return false;
}

function getStraightLineDistance($lat1, $lng1, $lat2, $lng2)
{
  $earth_radius = 6371000;
  $lat1_rad = deg2rad($lat1);
  $lng1_rad = deg2rad($lng1);
  $lat2_rad = deg2rad($lat2);
  $lng2_rad = deg2rad($lng2);
  $delta_lat = $lat2_rad - $lat1_rad;
  $delta_lng = $lng2_rad - $lng1_rad;
  $a = sin($delta_lat / 2) * sin($delta_lat / 2) + cos($lat1_rad) * cos($lat2_rad) * sin($delta_lng / 2) * sin($delta_lng / 2);
  $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
  return $earth_radius * $c;
}
?>