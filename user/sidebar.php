<?php
// filepath: c:\wamp64\www\agapay\user\sidebar.php
if (!isset($page) || $page != 'messages') {
    include('call_handler.php');
}

// Fetch count of unread admin messages for this user
$unread_msg_count = 0;
if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $count_query = "SELECT COUNT(*) as total 
                    FROM chat_logs 
                    WHERE receiver_id = ? 
                      AND is_admin = 1 
                      AND is_read = 0";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $current_user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $unread_msg_count = (int)$count_row['total'];
    $count_stmt->close();
}
?>

<style>
    body {
        background-color: #f8f9fa;
        color: #3B3838;
    }

    .sidebar {
        width: 280px;
        min-height: 100vh;
        position: relative;
        background-color: #8B1E1E;
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 0;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .logo-section {
        z-index: 2;
        padding-top: 40px;
        padding-bottom: 30px;
    }

    .sidebar .profile-circle {
        background-color: #f8f9fa;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        color: #8B1E1E;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .sidebar h4 {
        font-weight: bold;
        color: #fff;
        margin-top: 10px;
    }

    .sidebar .greeting {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 500;
        margin-top: 10px;
        margin-bottom: 0;
    }

    .nav-wrapper {
        background-color: #fff;
        border-top-right-radius: 80px;
        padding: 40px 15px 15px;
        width: 100%;
        height: 100%;
        z-index: 1;
        position: relative;
    }

    .nav-link {
        color: #3B3838;
        font-weight: 500;
        padding: 10px 15px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 0.95rem;
        margin-bottom: 12px;
        transition: background 0.2s, color 0.2s;
        position: relative;
    }

    .nav-link.active,
    .nav-link:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: #8B1E1E;
    }

    .nav-link .msg-badge {
        margin-left: auto;
        background-color: #dc3545;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
        line-height: 1;
    }

    .main-content {
        flex-grow: 1;
        padding: 30px;
        background-color: #ffffff;
        border-top-right-radius: 20px;
        margin: 30px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
        .sidebar {
            display: none;
        }

        .main-content {
            margin: 0;
            padding: 15px;
            border-radius: 0;
            padding-bottom: 120px;
        }

        .bottom-navbar {
            display: flex;
            justify-content: space-around;
            align-items: center;
            position: fixed;
            left: 24px;
            right: 24px;
            bottom: 24px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.10);
            z-index: 9999;
            padding: 18px 0;
        }

        .bottom-navbar .nav-link {
            background: none !important;
            color: #3B3838;
            font-size: 1.2rem;
            margin-bottom: 0;
            padding: 0;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            margin: 0 16px;
            transition: background 0.2s;
            position: relative;
        }

        .bottom-navbar .nav-link.active,
        .bottom-navbar .nav-link:hover {
            background: #f5eaea !important;
            color: #3B3838;
        }

        .bottom-navbar .nav-link span {
            display: none;
        }

        .bottom-navbar .nav-link .msg-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background-color: #dc3545;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.2rem 0.35rem;
            border-radius: 8px;
            min-width: 18px;
            text-align: center;
            line-height: 1;
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 8px;
            padding-bottom: 120px;
        }

        .bottom-navbar {
            left: 8px;
            right: 8px;
            bottom: 8px;
            padding: 10px 0;
            border-radius: 14px;
        }

        .bottom-navbar .nav-link {
            width: 44px;
            height: 44px;
            margin: 0 8px;
            font-size: 1.05rem;
            border-radius: 8px;
        }
    }
</style>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center logo-section">
            <div class="profile-circle">
                <?php
                echo strtoupper(substr($first_name, 0, 1)) . strtoupper(substr($last_name, 0, 1));
                ?>
            </div>
            <div class="greeting">Hi, <?php echo $first_name; ?></div>
            <br>
        </div>
        <div class="nav-wrapper">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'home') ? 'active' : ''; ?>" href="home.php">
                        <i class="bi bi-house-door"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'create_report') ? 'active' : ''; ?>" href="create_report.php">
                        <i class="bi bi-exclamation-triangle"></i> Report Incident
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'guides') ? 'active' : ''; ?>" href="guides.php">
                        <i class="bi bi-journal-text"></i> Safety Guides
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'messages') ? 'active' : ''; ?>" href="messages.php">
                        <i class="bi bi-envelope"></i> Messages
                        <span id="msgBadgeDesktop" class="msg-badge" style="<?php echo ($unread_msg_count > 0) ? '' : 'display:none;'; ?>">
                            <?php echo ($unread_msg_count > 99) ? '99+' : $unread_msg_count; ?>
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'account') ? 'active' : ''; ?>" href="account.php">
                        <i class="bi bi-person"></i> Account
                    </a>
                </li>

                <!-- Logout link now triggers modal (keeps page visible behind modal) -->
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutModalUser">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Bottom Navbar for Mobile -->
<nav class="bottom-navbar d-md-none d-lg-none">
    <a class="nav-link <?php echo ($page == 'home') ? 'active' : ''; ?>" href="home.php" title="Home">
        <i class="bi bi-house-door"></i>
    </a>
    <a class="nav-link <?php echo ($page == 'report') ? 'active' : ''; ?>" href="create_report.php" title="Create Report">
        <i class="bi bi-exclamation-triangle"></i>
    </a>
    <a class="nav-link <?php echo ($page == 'guides') ? 'active' : ''; ?>" href="guides.php" title="Safety Guides">
        <i class="bi bi-journal-text"></i>
    </a>
    <a class="nav-link <?php echo ($page == 'messages') ? 'active' : ''; ?>" href="messages.php" title="Messages">
        <i class="bi bi-envelope"></i>
        <span id="msgBadgeMobile" class="msg-badge" style="<?php echo ($unread_msg_count > 0) ? '' : 'display:none;'; ?>">
            <?php echo ($unread_msg_count > 99) ? '99+' : $unread_msg_count; ?>
        </span>
    </a>
    <a class="nav-link <?php echo ($page == 'account') ? 'active' : ''; ?>" href="account.php" title="Account">
        <i class="bi bi-person"></i>
    </a>

    <!-- mobile logout also opens modal -->
    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutModalUser" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</nav>

<!-- Logout modal (user) - placed in sidebar so it overlays current page -->
<div class="modal fade" id="logoutModalUser" tabindex="-1" aria-labelledby="logoutModalUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="logout.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalUserLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="confirm" value="1" class="btn btn-danger">Logout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- 🔔 Notification Sound (plays once when new message arrives) -->
<audio id="messageNotificationSound" preload="auto">
    <source src="ringtones/notif-que.mp3" type="audio/mpeg">
</audio>

<script>
    (function() {
        const d = document.getElementById('msgBadgeDesktop');
        const m = document.getElementById('msgBadgeMobile');
        const notificationSound = document.getElementById('messageNotificationSound');

        // Track the previous count and highest count seen
        let previousCount = <?php echo $unread_msg_count; ?>;
        let highestCountSeen = previousCount;

        // Track if we've played sound in this session
        const SESSION_KEY = 'notif_played_for_count';
        let playedForCount = parseInt(sessionStorage.getItem(SESSION_KEY) || '0');

        function setCount(n) {
            const txt = n > 99 ? '99+' : String(n);

            [d, m].forEach(el => {
                if (!el) return;
                if (n > 0) {
                    el.textContent = txt;
                    el.style.display = 'inline-block';
                } else {
                    el.textContent = '';
                    el.style.display = 'none';
                }
            });
        }

        function playNotificationSound() {
            // Reset and play
            notificationSound.currentTime = 0;
            notificationSound.play().catch(err => {
                console.warn('Could not play notification sound:', err);
            });
        }

        async function refresh() {
            try {
                const res = await fetch('get_unread_count.php', {
                    cache: 'no-store',
                    credentials: 'same-origin'
                });

                if (!res.ok) return;

                const data = await res.json();
                const count = Number(data && data.count ? data.count : 0);

                // Update badge display
                setCount(count);

                // 🔔 Play sound ONLY when:
                // 1. Count increased (new message arrived)
                // 2. Count is higher than what we've seen before
                // 3. We haven't played sound for this count yet
                // 4. Not on messages page (no sound when already chatting)
                const isMessagesPage = window.location.pathname.includes('/messages.php');

                if (count > previousCount &&
                    count > highestCountSeen &&
                    count > playedForCount &&
                    !isMessagesPage) {

                    playNotificationSound();
                    playedForCount = count;
                    sessionStorage.setItem(SESSION_KEY, String(count));
                    highestCountSeen = count;
                }

                // Update tracking
                previousCount = count;
                if (count > highestCountSeen) {
                    highestCountSeen = count;
                }

            } catch (e) {
                // Silent fail
            }
        }

        // Initial load
        refresh();

        // Poll every 2 seconds
        setInterval(refresh, 2000);

        // Refresh when tab becomes visible
        window.addEventListener('focus', refresh);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refresh();
        });

        // Reset session storage when user reads messages
        // (optional: clear when navigating to messages page)
        if (window.location.pathname.includes('/messages.php')) {
            sessionStorage.removeItem(SESSION_KEY);
        }
    })();
</script>