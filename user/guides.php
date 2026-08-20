<?php
include('user_session.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User - Guides</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    .main-content {
      flex-grow: 1;
      padding: 0 !important;
      background-color: #ffffff;
      border-top-right-radius: 0 !important;
      margin: 0 !important;
      box-shadow: none !important;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .home-header {
      background-color: #8B1E1E;
      color: white;
      padding: 2rem;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .home-header h1 {
      margin: 0;
      font-size: 2rem;
      font-weight: bold;
    }

    .home-content {
      flex: 1;
      padding: 2rem;
      padding-bottom: 100px;
      overflow-y: auto;
      background-color: #f8f9fa;
    }

    /* --- Guest Tips Styles --- */
    :root {
      --primary: #802108;
      --accent: #a7432d;
      --bg-light: #ffffff;
    }

    .guest-container {
      max-width: 800px;
      margin: auto;
      padding-bottom: 20px;
    }

    .guest-container h2 {
      text-align: center;
      font-size: 1.5rem;
      font-weight: bold;
    }

    .guest-container h2 span {
      color: var(--primary);
    }

    .intro {
      text-align: center;
      margin-bottom: 32px;
      margin-left: 16px;
      margin-right: 16px;
    }

    .intro strong {
      display: block;
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 10px;
      line-height: 1.1;
    }

    .intro p {
      font-size: 1.35rem;
      margin-bottom: 0;
      line-height: 1.2;
    }

    .cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 32px;
      padding-bottom: 20px;
      margin-left: 32px;
      margin-right: 32px;
    }

    .card-tip {
      background-color: #802108;
      border-radius: 32px;
      padding: 32px 0 32px 0;
      text-align: center;
      color: white;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
      margin: 0;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.10);
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .card-tip:hover {
      background-color: var(--accent);
      color: white;
      text-decoration: none;
    }

    .card-tip img {
      width: 90px;
      height: 90px;
      margin-bottom: 10px;
    }

    .card-tip p {
      margin-top: 10px;
      font-weight: bold;
      font-size: 1.1rem;
    }

    /* Back arrow */
    .back-arrow {
      color: white;
      font-size: 1.5rem;
      position: absolute;
      left: 1rem;
      top: 1rem;
    }

    @media (max-width: 768px) {
      .home-content {
        padding: 1rem;
        padding-bottom: 120px;
      }

      .intro {
        margin-left: 8px;
        margin-right: 8px;
        margin-bottom: 24px;
      }

      .cards {
        margin-left: 16px;
        margin-right: 16px;
        gap: 18px;
      }

      .card-tip {
        padding: 18px 0 18px 0;
        border-radius: 24px;
      }

      .card-tip img {
        width: 70px;
        height: 70px;
      }
    }

    @media (max-width: 480px) {
      .home-content {
        padding: 0.5rem;
        padding-bottom: 140px;
      }

      .guest-container {
        padding-bottom: 40px;
      }

      .intro {
        margin-left: 4px;
        margin-right: 4px;
        margin-bottom: 18px;
      }

      .cards {
        grid-template-columns: 1fr 1fr;
        margin-left: 8px;
        margin-right: 8px;
        gap: 16px;
      }

      .card-tip {
        padding: 14px 0 14px 0;
        border-radius: 20px;
      }

      .card-tip img {
        width: 60px;
        height: 60px;
      }

      .intro strong {
        font-size: 1.7rem;
      }

      .intro p {
        font-size: 1.1rem;
      }
    }
  </style>
</head>

<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <?php
    $page = 'guides'; // Set current page for active navigation
    include('sidebar.php');
    ?>

    <!-- Main Content -->
    <div class="main-content">
      <div class="home-header">
        <!-- Back Arrow (visible on mobile only) - positioned at leftmost side -->
        <a href="home.php" class="back-arrow text-decoration-none d-block d-md-none" style="top: 1.8rem; position: absolute;"> 
          &larr;
        </a>
        <h2>Welcome to Agapay</h2>
        <h6 class="mb-0">Safety Guides</h6>
        <!-- Clickable Icon for Emergency Hotlines Modal - positioned at top right -->
        <i class="bi bi-person-vcard" id="hotlineModalBtn"
          style="font-size:2rem; cursor:pointer; position: absolute; top: 1.65rem; right: 1rem;"
          title="View Emergency Hotlines"></i>

        <!-- Emergency Hotlines Modal -->
        <div class="modal fade" id="hotlineModal" tabindex="-1" aria-labelledby="hotlineModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; background: #fff7f5; box-shadow: 0 4px 24px rgba(128,33,8,0.15); border: 2px solid #802108;">
              <div class="modal-header" style="background: #8B1E1E; color: white; border-top-left-radius: 20px; border-top-right-radius: 20px; border-bottom: none;">
                <h5 class="modal-title" id="hotlineModalLabel" style="font-weight: bold;">Cuenca Emergency Hotlines</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
              </div>
              <div class="modal-body" style="padding: 2rem;">
                <style>
                  .copy-btn {
                    background: #a7432d;
                    color: white;
                    border: none;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 0.8rem;
                    cursor: pointer;
                    margin-left: 8px;
                    transition: background-color 0.3s;
                  }

                  .copy-btn:hover {
                    background: #802108;
                  }

                  .copy-btn.copied {
                    background: #28a745;
                  }

                  .call-btn {
                    background: #198754;
                    color: white;
                    border: none;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 0.8rem;
                    cursor: pointer;
                    margin-left: 6px;
                    transition: background-color 0.3s;
                    text-decoration: none;
                  }

                  .call-btn:hover {
                    background: #146c43;
                  }
                </style>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 1.1rem; color: #802108;">
                  <li style="margin-bottom: 1.2rem;">
                    <span style="font-weight: bold; color: #a7432d; display: block; text-align: center;">PNP</span>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">09985985688</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('09985985688', this)">Copy</button>
                          <a href="tel:09985985688" class="call-btn">Call</a>
                        </div>
                      </div>
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">09159547571</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('09159547571', this)">Copy</button>
                          <a href="tel:09159547571" class="call-btn">Call</a>
                        </div>
                      </div>
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">(043) 342-1887</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('(043) 342-1887', this)">Copy</button>
                          <a href="tel:0433421887" class="call-btn">Call</a>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li style="margin-bottom: 1.2rem;">
                    <span style="font-weight: bold; color: #a7432d; display: block; text-align: center;">BFP</span>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">09983960089</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('09983960089', this)">Copy</button>
                          <a href="tel:09983960089" class="call-btn">Call</a>
                        </div>
                      </div>
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">740-6367</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('740-6367', this)">Copy</button>
                          <a href="tel:7406367" class="call-btn">Call</a>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li>
                    <span style="font-weight: bold; color: #a7432d; display: block; text-align: center;">MDRRMO</span>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">09178342607</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('09178342607', this)">Copy</button>
                          <a href="tel:09178342607" class="call-btn">Call</a>
                        </div>
                      </div>
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">(043) 774-3376</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('(043) 774-3376', this)">Copy</button>
                          <a href="tel:0437743376" class="call-btn">Call</a>
                        </div>
                      </div>
                    </div>
                  </li>
                  <li style="margin-top: 1.2rem;">
                    <span style="font-weight: bold; color: #a7432d; display: block; text-align: center;">Hospitals</span>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">(043) 740-1381</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('(043) 740-1381', this)">Copy</button>
                          <a href="tel:0437401381" class="call-btn">Call</a>
                        </div>
                      </div>
                      <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-family: monospace;">09285167872</span>
                        <div>
                          <button class="copy-btn" onclick="copyToClipboard('09285167872', this)">Copy</button>
                          <a href="tel:09285167872" class="call-btn">Call</a>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="home-content">
        <!-- Guest Tips Content -->
        <div class="guest-container">

          <div class="intro">
            <br>
            <strong>We care about your safety!</strong>
            <p>Here are some safety tips for different kinds of emergencies!</p><br>
          </div>

          <div class="cards">
            <a href="guidesfolder/earthquake.html" class="card-tip">
              <img src="guidesfolder/assets/earthquake.png" alt="Earthquake">
              <p>Earthquake</p>
            </a>
            <a href="guidesfolder/volcano.html" class="card-tip">
              <img src="guidesfolder/assets/volcano.png" alt="Volcano">
              <p>Volcanic Eruption</p>
            </a>
            <a href="guidesfolder/firstaid.html" class="card-tip">
              <img src="guidesfolder/assets/firstaid.png" alt="First Aid">
              <p>First Aid</p>
            </a>
            <a href="guidesfolder/fire.html" class="card-tip">
              <img src="guidesfolder/assets/fire.png" alt="Fire">
              <p>Fire</p>
            </a>
            <a href="guidesfolder/landslides.html" class="card-tip">
              <img src="guidesfolder/assets/landslide.png" alt="Landslide">
              <p>Landslide</p>
            </a>
            <a href="guidesfolder/typhoon.html" class="card-tip">
              <img src="guidesfolder/assets/typhoon.png" alt="Typhoon">
              <p>Typhoon</p>
            </a>
            <a href="guidesfolder/accident.html" class="card-tip">
              <img src="guidesfolder/assets/accident.png" alt="Accident">
              <p>Road Accidents</p>
            </a>
            <a href="guidesfolder/flood.html" class="card-tip">
              <img src="guidesfolder/assets/flood.png" alt="Flood">
              <p>Flood</p>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Show modal when icon is clicked
    document.addEventListener('DOMContentLoaded', function() {
      var hotlineBtn = document.getElementById('hotlineModalBtn');
      var hotlineModal = new bootstrap.Modal(document.getElementById('hotlineModal'));
      if (hotlineBtn) {
        hotlineBtn.addEventListener('click', function() {
          hotlineModal.show();
        });
      }
    });

    // Function to copy number to clipboard
    function copyToClipboard(text, button) {
      navigator.clipboard.writeText(text).then(function() {
        // Visual feedback
        button.textContent = 'Copied!';
        button.classList.add('copied');

        // Reset button after 2 seconds
        setTimeout(function() {
          button.textContent = 'Copy';
          button.classList.remove('copied');
        }, 2000);
      }).catch(function(err) {
        console.error('Failed to copy text: ', err);
        button.textContent = 'Failed!';
        setTimeout(function() {
          button.textContent = 'Copy';
        }, 2000);
      });
    }
  </script>
</body>

</html>