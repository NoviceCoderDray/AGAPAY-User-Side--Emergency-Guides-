<?php
include('user_session.php');
include('../config/config.php');

$user_first_name = '';
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id) {
  $result = mysqli_query($conn, "SELECT first_name FROM users WHERE id = '$user_id' LIMIT 1");
  if ($row = mysqli_fetch_assoc($result)) {
    $user_first_name = htmlspecialchars($row['first_name']);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>USER - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
      background-color: #f8fafc;
    }

    .main-content {
      flex-grow: 1;
      padding: 0 !important;
      background: #fff;
      height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0 !important;
    }

    .home-header {
      background-color: #8B1E1E;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.5rem 1rem;
      height: 64px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, .08);
      flex-shrink: 0;
    }

    .home-header .logo {
      display: flex;
      align-items: center;
      gap: .75rem;
      flex: 1;
      justify-content: center;
    }

    .home-header .logo img {
      height: 44px;
      width: auto;
      display: block;
    }

    .home-header .actions {
      position: absolute;
      right: 18px;
    }

    .home-header .help-btn {
      background: transparent;
      border: none;
      padding: 0;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.13);
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
      transition: all 0.2s ease;
    }

    .home-header .help-btn:hover {
      background: rgba(255, 255, 255, 0.18);
      transform: scale(1.05);
    }

    .home-header .help-btn i {
      font-size: 1.7rem;
      color: #fff;
    }

    .location-loading {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 2rem;
      border-radius: 10px;
      z-index: 9999;
      display: none;
      text-align: center;
    }

    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 1rem;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    .home-content {
      flex: 1;
      overflow-y: auto;
      padding-bottom: 30px;
      display: flex;
      flex-direction: column;
    }

    /* Updated styles for centered layout */
    .user-welcome {
      text-align: center;
      padding: 2rem 1.5rem 1rem;
      color: #374151;
    }

    .user-welcome h2 {
      font-size: 2rem;
      font-weight: 600;
      margin: 0 0 .5rem;
      color: #1f2937;
    }

    .user-welcome p {
      font-size: 1rem;
      margin: 0;
      color: #6b7280;
    }

    .emergency-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      flex: 1;
      padding: 1rem 1.5rem 2rem;
      max-width: 600px;
      margin: 0 auto;
      width: 100%;
    }

    .emergency-container {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      margin: 0 auto 2rem;
    }

    .main-emergency-btn {
      width: 180px;
      height: 180px;
      border-radius: 50%;
      border: none;
      background: #802108;
      color: #fff;
      font-weight: 700;
      cursor: pointer;
      position: relative;
      z-index: 50;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 28px 50px rgba(0, 0, 0, .36), inset 0 -14px 28px rgba(0, 0, 0, .22);
      transition: transform .18s ease, box-shadow .18s ease;
    }

    .main-emergency-btn:hover {
      transform: translateY(-6px) scale(1.02);
      box-shadow: 0 36px 70px rgba(75, 15, 12, .44);
      background: #6a140f;
    }

    .main-icon {
      font-size: 3.2rem;
      line-height: 1;
      display: block;
      transition: all .18s ease;
    }

    #emergencyOptions {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      pointer-events: none;
    }

    .option-btn {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: #fff;
      border: none;
      color: #374151;
      font-weight: 600;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: .35rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .14), 0 6px 18px rgba(0, 0, 0, .08);
      transform: translate(-50%, -50%) scale(.6);
      opacity: 0;
      pointer-events: none;
      transition: transform 360ms cubic-bezier(.2, .9, .2, 1), opacity 240ms ease, box-shadow 200ms;
    }

    .option-btn i {
      font-size: 1.45rem;
    }

    .option-btn span {
      font-size: .75rem;
    }

    .emergency-container.btn-active .option-btn:hover {
      transform: translate(var(--tx, -50%), var(--ty, -50%)) scale(1.08);
      box-shadow: 0 18px 44px rgba(0, 0, 0, .2);
    }

    .emergency-container.btn-active #emergencyOptions .option-btn {
      opacity: 1;
      pointer-events: auto;
    }

    .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(1) {
      transform: translate(calc(-50% + 0px), calc(-50% - 215px)) scale(1);
      transition-delay: 90ms;
    }

    .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(2) {
      transform: translate(calc(-50% + 144px), calc(-50% - 152px)) scale(1);
      transition-delay: 110ms;
    }

    .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(3) {
      transform: translate(calc(-50% + 232px), calc(-50% - 36px)) scale(1);
      transition-delay: 130ms;
    }

    .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(4) {
      transform: translate(calc(-50% - 232px), calc(-50% - 36px)) scale(1);
      transition-delay: 50ms;
    }

    .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(5) {
      transform: translate(calc(-50% - 144px), calc(-50% - 152px)) scale(1);
      transition-delay: 70ms;
    }

    .emergency-container.btn-active .main-emergency-btn {
      transform: translateY(-6px) scale(1.02);
      box-shadow: 0 40px 80px rgba(75, 15, 12, .36);
    }

    .emergency-text {
      margin: 0 0 2rem;
      color: #9ca3af;
      font-size: 0.95rem;
      text-align: center;
    }

    .action-cards-container {
      width: 100%;
      max-width: 400px;
      margin: 0 auto;
    }

    .action-card {
      background: white;
      border: 1.5px solid #e5e7eb;
      border-radius: 16px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: all 0.2s ease;
      margin-bottom: 14px;
    }

    .action-card:hover {
      border-color: #802108;
      box-shadow: 0 4px 12px rgba(128, 33, 8, 0.15);
      transform: translateY(-2px);
    }

    .action-card-body {
      display: flex;
      align-items: center;
      padding: 1.1rem 1.4rem;
      gap: 14px;
    }

    .action-card-icon {
      font-size: 1.5rem;
      color: #802108;
      flex-shrink: 0;
    }

    .action-card-text {
      font-weight: 500;
      font-size: 1rem;
      color: #374151;
    }

    .action-card-text a {
      color: #802108;
      text-decoration: underline;
      font-weight: 600;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
      .home-content {
        padding-bottom: 140px;
      }

      .user-welcome {
        padding: 1.5rem 1.5rem 0.5rem;
      }

      .user-welcome h2 {
        font-size: 1.75rem;
      }

      .user-welcome p {
        font-size: 0.95rem;
      }

      .emergency-section {
        padding: 1rem 1.5rem 1.5rem;
      }

      .main-emergency-btn {
        width: 156px;
        height: 156px;
      }

      .main-icon {
        font-size: 2.8rem;
      }

      .option-btn {
        width: 82px;
        height: 82px;
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(1) {
        transform: translate(calc(-50% + 0px), calc(-50% - 170px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(2) {
        transform: translate(calc(-50% + 116px), calc(-50% - 122px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(3) {
        transform: translate(calc(-50% + 186px), calc(-50% - 28px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(4) {
        transform: translate(calc(-50% - 186px), calc(-50% - 28px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(5) {
        transform: translate(calc(-50% - 116px), calc(-50% - 122px)) scale(1);
      }
    }

    @media (max-width: 576px) {
      .home-header {
        padding: 0.5rem 0.75rem;
        height: 56px;
      }

      .home-header .logo img {
        height: 38px;
      }

      .home-header .actions {
        right: 12px;
      }

      .user-welcome {
        padding: 1.25rem 1.25rem 0.5rem;
      }

      .user-welcome h2 {
        font-size: 1.5rem;
      }

      .user-welcome p {
        font-size: 0.9rem;
      }

      .main-emergency-btn {
        width: 138px;
        height: 138px;
      }

      .main-icon {
        font-size: 2.5rem;
      }

      .option-btn {
        width: 72px;
        height: 72px;
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(1) {
        transform: translate(calc(-50% + 0px), calc(-50% - 150px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(2) {
        transform: translate(calc(-50% + 100px), calc(-50% - 106px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(3) {
        transform: translate(calc(-50% + 164px), calc(-50% - 25px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(4) {
        transform: translate(calc(-50% - 164px), calc(-50% - 25px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(5) {
        transform: translate(calc(-50% - 100px), calc(-50% - 106px)) scale(1);
      }
    }

    @media (max-width: 390px) {
      .main-emergency-btn {
        width: 124px;
        height: 124px;
      }

      .main-icon {
        font-size: 2.3rem;
      }

      .option-btn {
        width: 66px;
        height: 66px;
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(1) {
        transform: translate(calc(-50% + 0px), calc(-50% - 134px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(2) {
        transform: translate(calc(-50% + 90px), calc(-50% - 96px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(3) {
        transform: translate(calc(-50% + 146px), calc(-50% - 23px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(4) {
        transform: translate(calc(-50% - 146px), calc(-50% - 23px)) scale(1);
      }

      .emergency-container.btn-active #emergencyOptions .option-btn:nth-child(5) {
        transform: translate(calc(-50% - 90px), calc(-50% - 96px)) scale(1);
      }
    }
  </style>
</head>

<body>
  <div class="d-flex">
    <?php $page = 'home';
    include('sidebar.php'); ?>

    <div class="main-content">
      <div class="home-header">
        <div class="logo">
          <img src="../assets/logo.png" alt="Agapay logo">
        </div>
        <div class="actions">
          <button class="help-btn" type="button" aria-label="Help" onclick="window.location.href='user_guide.php'">
            <i class="bi bi-question-circle"></i>
          </button>
        </div>
      </div>

      <div class="home-content">
        <div class="user-welcome">
          <h2>Hello, <?php echo $user_first_name; ?>!</h2>
          <p>We're glad to have you back. How can we assist you today?</p>
        </div>

        <div class="emergency-section">
          <div class="emergency-container" id="emergencyContainer">
            <button id="mainEmergencyBtn" class="main-emergency-btn" aria-label="Emergency">
              <i class="bi bi-telephone-plus-fill main-icon" aria-hidden="true"></i>
            </button>

            <div id="emergencyOptions">
              <button class="option-btn" data-type="medical">
                <i class="bi bi-heart-pulse"></i>
                <span>Medical</span>
              </button>
              <button class="option-btn" data-type="accident">
                <i class="bi bi-car-front"></i>
                <span>Accident</span>
              </button>
              <button class="option-btn" data-type="fire">
                <i class="bi bi-fire"></i>
                <span>Fire</span>
              </button>
              <button class="option-btn" data-type="crime">
                <i class="bi bi-shield-exclamation"></i>
                <span>Crime</span>
              </button>
              <button class="option-btn" data-type="disaster">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Disaster</span>
              </button>
            </div>
          </div>

          <p class="emergency-text">Tap the button to request emergency assistance</p>

          <div class="action-cards-container">
            <div class="action-card" onclick="window.location.href='messages.php';">
              <div class="action-card-body">
                <i class="bi bi-chat-dots action-card-icon"></i>
                <span class="action-card-text">Talk with the MDRRMO</span>
              </div>
            </div>

            <div class="action-card">
              <div class="action-card-body">
                <i class="bi bi-clipboard-data action-card-icon"></i>
                <span class="action-card-text">
                  Can't call? <a href="create_report.php">Submit a report</a>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="location-loading" id="locationLoading">
    <div class="spinner"></div>
    <div>Getting your precise location...</div>
    <small>This may take a few seconds</small>
  </div>

  <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places&callback=initializeGoogleMaps"></script>

  <script>
    let googleMapsLoaded = false;
    let geocoder = null;

    function initializeGoogleMaps() {
      googleMapsLoaded = true;
      geocoder = new google.maps.Geocoder();
      console.log('Google Maps API loaded successfully');
    }

    document.addEventListener('DOMContentLoaded', function() {
      const container = document.getElementById('emergencyContainer');
      const mainBtn = document.getElementById('mainEmergencyBtn');
      const loadingIndicator = document.getElementById('locationLoading');
      let open = false;

      mainBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        open = !open;
        container.classList.toggle('btn-active', open);
      });

      document.addEventListener('click', function(e) {
        if (!e.target.closest('#emergencyContainer') && open) {
          open = false;
          container.classList.remove('btn-active');
        }
      });

      document.querySelectorAll('#emergencyOptions .option-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const type = btn.dataset.type;
          console.log('Selected:', type);
          open = false;
          container.classList.remove('btn-active');
          handleEmergencyCall(type);
        });
      });

      async function cleanupExistingConnections() {
        try {
          console.log('Cleaning up existing connections...');
          if (window.localStream) {
            window.localStream.getTracks().forEach(track => track.stop());
            window.localStream = null;
          }
          if (window.peerConnection) {
            window.peerConnection.close();
            window.peerConnection = null;
          }
          if (window.firebaseCleanup && typeof window.firebaseCleanup === 'function') {
            window.firebaseCleanup();
          }
          if (window.callTimer) {
            clearInterval(window.callTimer);
            window.callTimer = null;
          }
          return Promise.resolve();
        } catch (error) {
          console.error('Error during cleanup:', error);
          return Promise.resolve();
        }
      }

      async function getAccurateAddressFromCoords(latitude, longitude) {
        return new Promise((resolve, reject) => {
          if (!googleMapsLoaded || !geocoder) {
            console.log('Google Maps not loaded, using fallback address');
            resolve({
              full_address: `Lat: ${latitude.toFixed(6)}, Lng: ${longitude.toFixed(6)}`,
              street: 'Unknown location',
              city: 'Unknown city',
              country: 'Unknown country'
            });
            return;
          }

          const latlng = new google.maps.LatLng(latitude, longitude);
          geocoder.geocode({
            location: latlng
          }, (results, status) => {
            if (status === 'OK' && results && results.length > 0) {
              const result = results[0];
              const addressComponents = result.address_components;
              let street = '',
                city = '',
                country = '';

              addressComponents.forEach(component => {
                const types = component.types;
                if (types.includes('street_number') || types.includes('route')) {
                  street += component.long_name + ' ';
                } else if (types.includes('locality') || types.includes('administrative_area_level_2')) {
                  city = component.long_name;
                } else if (types.includes('country')) {
                  country = component.long_name;
                }
              });

              resolve({
                full_address: result.formatted_address,
                street: street.trim() || 'Unknown street',
                city: city || 'Unknown city',
                country: country || 'Unknown country'
              });
            } else {
              console.error('Geocoding failed:', status);
              resolve({
                full_address: `Lat: ${latitude.toFixed(6)}, Lng: ${longitude.toFixed(6)}`,
                street: 'Unable to determine address',
                city: 'Unknown city',
                country: 'Unknown country'
              });
            }
          });
        });
      }

      async function handleEmergencyCall(emergencyType) {
        try {
          await cleanupExistingConnections();

          if (!navigator.geolocation) {
            alert('Geolocation is not supported by this browser.');
            return;
          }

          loadingIndicator.style.display = 'block';
          disableEmergencyButton();

          navigator.geolocation.getCurrentPosition(
            async function(position) {
                try {
                  const latitude = position.coords.latitude;
                  const longitude = position.coords.longitude;
                  console.log('Location obtained:', latitude, longitude);

                  const addressInfo = await getAccurateAddressFromCoords(latitude, longitude);

                  const reportData = {
                    emergency_type: emergencyType,
                    latitude: latitude,
                    longitude: longitude,
                    street_name: addressInfo.full_address,
                    message: `Emergency ${emergencyType} call initiated from mobile app at ${addressInfo.full_address}`,
                    date_of_call: new Date().toISOString().split('T')[0],
                    time_of_call: new Date().toTimeString().split(' ')[0]
                  };

                  console.log('Report data:', reportData);

                  const response = await fetch('emergency_handler.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(reportData)
                  });

                  if (response.ok) {
                    const result = await response.json();
                    console.log('Emergency handler response:', result);

                    loadingIndicator.style.display = 'none';

                    if (result.has_active_call && result.call_status) {
                      showCallInProgressNotification(result);
                    } else {
                      await cleanupExistingConnections();
                      const incidentId = result.type === 'main_report' ? result.incident_id : result.master_incident_id;
                      enableEmergencyButton();

                      sessionStorage.setItem('autoCallIntent', 'true');
                      sessionStorage.setItem('emergencyType', emergencyType);
                      sessionStorage.setItem('incidentId', incidentId);

                      window.location.href = `messages.php?auto_call=1&type=${emergencyType}&incident_id=${incidentId}`;
                    }
                  } else {
                    throw new Error('Failed to create incident report');
                  }
                } catch (error) {
                  console.error('Error processing location:', error);
                  loadingIndicator.style.display = 'none';
                  enableEmergencyButton();
                  alert('Error processing your location. Please try again.');
                }
              },
              function(error) {
                console.error('Error getting location:', error);
                loadingIndicator.style.display = 'none';
                enableEmergencyButton();

                let errorMessage = 'Unable to get your location. ';
                switch (error.code) {
                  case error.PERMISSION_DENIED:
                    errorMessage += 'Please enable location services and allow access.';
                    break;
                  case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Location information is unavailable.';
                    break;
                  case error.TIMEOUT:
                    errorMessage += 'Location request timed out. Please try again.';
                    break;
                  default:
                    errorMessage += 'An unknown error occurred.';
                    break;
                }
                alert(errorMessage);
              }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 30000
              }
          );
        } catch (error) {
          console.error('Error handling emergency call:', error);
          loadingIndicator.style.display = 'none';
          enableEmergencyButton();
          alert('An error occurred. Please try again.');
        }
      }

      function showCallInProgressNotification(result) {
        // Remove any existing modals first
        const existingModal = document.querySelector('.call-in-progress-modal');
        if (existingModal) {
          existingModal.remove();
        }

        const modal = document.createElement('div');
        modal.className = 'call-in-progress-modal';
        modal.innerHTML = `
          <div class="modal-backdrop" onclick="closeMissedCallModal()"></div>
          <div class="modal-content">
            <div class="modal-header">
              <div class="status-icon">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <h3>Help is Already on the Way</h3>
              <button class="modal-close-btn" onclick="closeMissedCallModal()">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
            
            <div class="modal-body">
              <div class="main-message">
                <p><strong>Emergency services have been contacted about this ${result.emergency_type} incident in your area.</strong></p>
                <p>Your report has been recorded and added to the existing incident file to provide additional information to responders.</p>
              </div>
              
              <div class="incident-details">
                <div class="detail-item">
                  <span class="label">Emergency Type:</span>
                  <span class="value">${result.emergency_type.charAt(0).toUpperCase() + result.emergency_type.slice(1)}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Representative:</span>
                  <span class="value">${result.call_status.caller_name}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Status:</span>
                  <span class="value">
                    <span class="status-badge active">
                      <i class="bi bi-telephone-fill"></i>
                      Currently in contact
                    </span>
                  </span>
                </div>
              </div>
              
              <div class="info-sections">
                <div class="info-note success">
                  <i class="bi bi-shield-check"></i>
                  <div>
                    <strong>You're covered!</strong>
                    <span>Emergency services are already responding to this incident.</span>
                  </div>
                </div>
                
                <div class="info-note info">
                  <i class="bi bi-info-circle"></i>
                  <div>
                    <strong>What happens next?</strong>
                    <span>Emergency responders will handle the situation. Your report helps them understand the full scope of the incident.</span>
                  </div>
                </div>
              </div>
              
              <div class="additional-actions">
                <p class="text-muted">If this is a different emergency or you need immediate personal assistance, you can:</p>
                <div class="action-options">
                  <button class="option-link" onclick="contactDifferentEmergency()">
                    <i class="bi bi-telephone"></i>
                    <span>Report Different Emergency</span>
                  </button>
                  <button class="option-link" onclick="viewEmergencyContacts()">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>View Emergency Contacts</span>
                  </button>
                </div>
              </div>
            </div>
            
            <div class="modal-actions">
              <button class="btn-primary-full" onclick="closeMissedCallModal()">
                <i class="bi bi-check-lg"></i>
                <span>Understood</span>
              </button>
            </div>
          </div>
        `;

        // Add improved modal styles
        const style = document.createElement('style');
        style.id = 'call-in-progress-modal-styles';
        style.textContent = `
          .call-in-progress-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: modalFadeIn 0.3s ease-out;
          }

          .call-in-progress-modal .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            cursor: pointer;
          }

          .call-in-progress-modal .modal-content {
            position: relative;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            max-width: 520px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 10001;
          }

          @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
          }

          @keyframes modalSlideUp {
            from {
              opacity: 0;
              transform: translateY(50px) scale(0.9);
            }
            to {
              opacity: 1;
              transform: translateY(0) scale(1);
            }
          }

          .call-in-progress-modal .modal-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
          }

          .call-in-progress-modal .modal-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s;
          }

          .call-in-progress-modal .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
          }

          .call-in-progress-modal .status-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
          }

          .call-in-progress-modal h3 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.2;
          }

          .call-in-progress-modal .modal-body {
            padding: 2rem;
            max-height: 400px;
            overflow-y: auto;
          }

          .main-message {
            margin-bottom: 1.5rem;
          }

          .main-message p {
            margin: 0 0 1rem;
            color: #374151;
            line-height: 1.6;
          }

          .main-message p:last-child {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 0.95rem;
          }

          .incident-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
          }

          .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
          }

          .detail-item:last-child {
            margin-bottom: 0;
          }

          .detail-item .label {
            font-weight: 500;
            color: #6b7280;
            font-size: 0.9rem;
          }

          .detail-item .value {
            font-weight: 600;
            color: #1f2937;
          }

          .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
          }

          .status-badge.active {
            background: #dcfdf7;
            color: #065f46;
            border: 1px solid #a7f3d0;
          }

          .info-sections {
            margin-bottom: 1.5rem;
          }

          .info-note {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
          }

          .info-note:last-child {
            margin-bottom: 0;
          }

          .info-note i {
            margin-top: 0.1rem;
            flex-shrink: 0;
            font-size: 1.1rem;
          }

          .info-note div {
            flex: 1;
          }

          .info-note strong {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
          }

          .info-note span {
            font-size: 0.85rem;
            line-height: 1.4;
          }

          .info-note.success {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #065f46;
          }

          .info-note.info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
          }

          .additional-actions {
            border-top: 1px solid #f3f4f6;
            padding-top: 1.5rem;
          }

          .text-muted {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
          }

          .action-options {
            display: flex;
            gap: 1rem;
          }

          .option-link {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #374151;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            text-align: left;
          }

          .option-link:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
          }

          .option-link i {
            font-size: 0.9rem;
            color: #6b7280;
          }

          .modal-actions {
            padding: 1.5rem 2rem 2rem;
            border-top: 1px solid #f3f4f6;
          }

          .btn-primary-full {
            width: 100%;
            padding: 1rem 1.5rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 1rem;
          }

          .btn-primary-full:hover {
            background: #047857;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
          }

          /* Mobile responsive */
          @media (max-width: 480px) {
            .call-in-progress-modal {
              padding: 0.5rem;
            }
            
            .call-in-progress-modal .modal-content {
              max-height: 95vh;
              border-radius: 16px;
            }

            .call-in-progress-modal .modal-header {
              padding: 2rem 1.5rem 1.5rem;
            }

            .call-in-progress-modal h3 {
              font-size: 1.4rem;
            }

            .call-in-progress-modal .modal-body {
              padding: 1.5rem;
            }

            .action-options {
              flex-direction: column;
            }

            .modal-actions {
              padding: 1rem 1.5rem 1.5rem;
            }
          }

          /* Auto-close animation */
          .call-in-progress-modal.closing {
            animation: modalFadeOut 0.3s ease-out forwards;
          }

          .call-in-progress-modal.closing .modal-content {
            animation: modalSlideDown 0.3s ease-out forwards;
          }

          @keyframes modalFadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
          }

          @keyframes modalSlideDown {
            from {
              opacity: 1;
              transform: translateY(0) scale(1);
            }
            to {
              opacity: 0;
              transform: translateY(30px) scale(0.95);
            }
          }
        `;

        // Remove any existing styles first
        const existingStyle = document.getElementById('call-in-progress-modal-styles');
        if (existingStyle) {
          existingStyle.remove();
        }

        document.head.appendChild(style);
        document.body.appendChild(modal);

        // Add global functions for modal actions with proper cleanup
        window.closeMissedCallModal = function() {
          const modal = document.querySelector('.call-in-progress-modal');
          const style = document.getElementById('call-in-progress-modal-styles');

          if (modal && modal.parentNode) {
            modal.remove();
          }
          if (style && style.parentNode) {
            style.remove();
          }

          // Cleanup global functions
          delete window.closeMissedCallModal;
          delete window.contactDifferentEmergency;
          delete window.viewEmergencyContacts;

          // Re-enable emergency button interactions
          enableEmergencyButton();
        };

        // Add functions for additional actions
        window.contactDifferentEmergency = function() {
          window.closeMissedCallModal();
          // Reset the emergency interface to allow new emergency selection
          setTimeout(() => {
            alert('You can now select a different type of emergency from the main button.');
          }, 300);
        };

        window.viewEmergencyContacts = function() {
          window.closeMissedCallModal();
          // You could redirect to a contacts page or show emergency numbers
          setTimeout(() => {
            alert('Emergency Contacts:\n\nPolice: 911\nFire Department: 911\nMedical: 911\nLocal Emergency: [Your local number]');
          }, 300);
        };

        // Auto-close modal after 45 seconds (longer since it's more informative)
        setTimeout(() => {
          if (window.closeMissedCallModal) {
            window.closeMissedCallModal();
          }
        }, 45000);

        // Disable emergency button while modal is open
        disableEmergencyButton();
      }

      // Helper functions to manage emergency button state
      function disableEmergencyButton() {
        const container = document.getElementById('emergencyContainer');
        const mainBtn = document.getElementById('mainEmergencyBtn');

        if (container && mainBtn) {
          container.style.pointerEvents = 'none';
          container.style.opacity = '0.7';
          mainBtn.style.cursor = 'not-allowed';
        }
      }

      function enableEmergencyButton() {
        const container = document.getElementById('emergencyContainer');
        const mainBtn = document.getElementById('mainEmergencyBtn');

        if (container && mainBtn) {
          container.style.pointerEvents = 'auto';
          container.style.opacity = '1';
          mainBtn.style.cursor = 'pointer';
        }
      }
    });

    async function reportEmergency(emergencyType) {
      try {
        // Get location first
        const position = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
          });
        });

        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;

        // Get address
        const address = await getAddressFromCoords(latitude, longitude);

        // Create incident report
        const response = await fetch('emergency_handler.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            emergency_type: emergencyType,
            latitude: latitude,
            longitude: longitude,
            street_name: address,
            message: `Emergency button pressed - ${emergencyType}`
          })
        });

        const result = await response.json();

        if (result.success) {
          const incidentId = result.type === 'main_report' ? result.incident_id : result.master_incident_id;

          // 🔥 NEW: Check if user can make a call
          if (!result.can_call) {
            // Show notification that someone else is handling it
            showCallBlockedModal(result);
            return;
          }

          // Proceed to messages page with auto-call
          window.location.href = `messages.php?auto_call=1&type=${emergencyType}&incident_id=${incidentId}`;
        } else {
          alert('Failed to create report: ' + (result.error || 'Unknown error'));
        }
      } catch (error) {
        console.error('Emergency report error:', error);
        alert('Failed to report emergency. Please try again.');
      }
    }

    // 🔥 NEW: Function to show call blocked modal
    function showCallBlockedModal(result) {
      const existingModal = document.querySelector('.call-blocked-modal');
      if (existingModal) {
        existingModal.remove();
      }

      const modal = document.createElement('div');
      modal.className = 'call-blocked-modal';

      let message = '';
      if (result.has_active_call && result.call_status) {
        message = result.call_status.message;
      } else if (!result.is_most_recent && result.call_status) {
        message = result.call_status.message;
      } else {
        message = 'Your report has been recorded. Emergency services have been notified about this incident.';
      }

      modal.innerHTML = `
          <div class="modal-backdrop" onclick="closeCallBlockedModal()"></div>
          <div class="modal-content">
              <div class="modal-header">
                  <div class="status-icon">
                      <i class="bi bi-check-circle-fill"></i>
                  </div>
                  <h3>Report Submitted</h3>
                  <button class="modal-close-btn" onclick="closeCallBlockedModal()">
                      <i class="bi bi-x-lg"></i>
                  </button>
              </div>
              
              <div class="modal-body">
                  <div class="main-message">
                      <p><strong>Your emergency report has been successfully recorded.</strong></p>
                      <p>${message}</p>
                  </div>
                  
                  <div class="info-note success">
                      <i class="bi bi-shield-check"></i>
                      <div>
                          <strong>Emergency Response Active</strong>
                          <span>Authorities are already coordinating a response to this ${result.emergency_type} incident in your area.</span>
                      </div>
                  </div>
                  
                  <div class="info-note info">
                      <i class="bi bi-info-circle"></i>
                      <div>
                          <strong>Your Report Matters</strong>
                          <span>Your information has been added to the incident file and helps responders understand the full scope of the situation.</span>
                      </div>
                  </div>
              </div>
              
              <div class="modal-actions">
                  <button class="btn-primary-full" onclick="closeCallBlockedModal()">
                      <i class="bi bi-check-lg"></i>
                      <span>Understood</span>
                  </button>
              </div>
          </div>
      `;

      // Add styles
      const style = document.createElement('style');
      style.textContent = `
          .call-blocked-modal {
              position: fixed;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              z-index: 10000;
              display: flex;
              align-items: center;
              justify-content: center;
              padding: 1rem;
              animation: modalFadeIn 0.3s ease-out;
          }

          .call-blocked-modal .modal-backdrop {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background: rgba(0, 0, 0, 0.5);
              cursor: pointer;
          }

          .call-blocked-modal .modal-content {
              position: relative;
              background: white;
              border-radius: 20px;
              box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
              max-width: 520px;
              width: 100%;
              max-height: 90vh;
              overflow: hidden;
              animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
              z-index: 10001;
          }

          @keyframes modalFadeIn {
              from { opacity: 0; }
              to { opacity: 1; }
          }

          @keyframes modalSlideUp {
              from {
                  opacity: 0;
                  transform: translateY(50px) scale(0.9);
              }
              to {
                  opacity: 1;
                  transform: translateY(0) scale(1);
              }
          }

          .call-blocked-modal .modal-header {
              background: linear-gradient(135deg, #059669 0%, #047857 100%);
              color: white;
              padding: 2.5rem 2rem 2rem;
              text-align: center;
              position: relative;
          }

          .call-blocked-modal .modal-close-btn {
              position: absolute;
              top: 1rem;
              right: 1rem;
              background: rgba(255, 255, 255, 0.2);
              color: white;
              border: none;
              border-radius: 50%;
              width: 36px;
              height: 36px;
              cursor: pointer;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 1rem;
              transition: all 0.2s;
          }

          .call-blocked-modal .modal-close-btn:hover {
              background: rgba(255, 255, 255, 0.3);
              transform: scale(1.1);
          }

          .call-blocked-modal .status-icon {
              width: 70px;
              height: 70px;
              border-radius: 50%;
              background: rgba(255, 255, 255, 0.2);
              display: flex;
              align-items: center;
              justify-content: center;
              margin: 0 auto 1.5rem;
              font-size: 2rem;
          }

          .call-blocked-modal h3 {
              margin: 0;
              font-size: 1.6rem;
              font-weight: 700;
              line-height: 1.2;
          }

          .call-blocked-modal .modal-body {
              padding: 2rem;
              max-height: 400px;
              overflow-y: auto;
          }

          .main-message {
              margin-bottom: 1.5rem;
          }

          .main-message p {
              margin: 0 0 1rem;
              color: #374151;
              line-height: 1.6;
          }

          .main-message p:last-child {
              margin-bottom: 0;
              color: #6b7280;
              font-size: 0.95rem;
          }

          .info-note {
              display: flex;
              align-items: flex-start;
              gap: 1rem;
              padding: 1rem;
              border-radius: 12px;
              margin-bottom: 1rem;
          }

          .info-note:last-child {
              margin-bottom: 0;
          }

          .info-note i {
              margin-top: 0.1rem;
              flex-shrink: 0;
              font-size: 1.1rem;
          }

          .info-note div {
              flex: 1;
          }

          .info-note strong {
              display: block;
              margin-bottom: 0.25rem;
              font-size: 0.9rem;
          }

          .info-note span {
              font-size: 0.85rem;
              line-height: 1.4;
          }

          .info-note.success {
              background: #ecfdf5;
              border: 1px solid #bbf7d0;
              color: #065f46;
          }

          .info-note.info {
              background: #eff6ff;
              border: 1px solid #bfdbfe;
              color: #1e40af;
          }

          .modal-actions {
              padding: 1.5rem 2rem 2rem;
              border-top: 1px solid #f3f4f6;
          }

          .btn-primary-full {
              width: 100%;
              padding: 1rem 1.5rem;
              background: #059669;
              color: white;
              border: none;
              border-radius: 12px;
              font-weight: 600;
              cursor: pointer;
              display: flex;
              align-items: center;
              justify-content: center;
              gap: 0.5rem;
              transition: all 0.2s;
              font-size: 1rem;
          }

          .btn-primary-full:hover {
              background: #047857;
              transform: translateY(-1px);
              box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
          }

          @media (max-width: 480px) {
            .call-blocked-modal {
                padding: 0.5rem;
            }
            
            .call-blocked-modal .modal-content {
                max-height: 95vh;
                border-radius: 16px;
            }

            .call-blocked-modal .modal-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .call-blocked-modal h3 {
                font-size: 1.4rem;
            }

            .call-blocked-modal .modal-body {
                padding: 1.5rem;
            }

            .modal-actions {
                padding: 1rem 1.5rem 1.5rem;
            }
          }
      `;

      document.head.appendChild(style);
      document.body.appendChild(modal);

      // Auto-close after 30 seconds
      setTimeout(() => {
        closeCallBlockedModal();
      }, 30000);
    }

    window.closeCallBlockedModal = function() {
      const modal = document.querySelector('.call-blocked-modal');
      if (modal) {
        modal.remove();
      }
    };
  </script>
</body>

</html>