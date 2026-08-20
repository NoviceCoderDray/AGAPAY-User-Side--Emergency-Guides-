<?php
// filepath: c:\wamp64\www\agapay\user\call_handler.php
?>

<!-- 🔔 Ringtone Audio Element -->
<audio id="incomingCallRingtone" loop preload="auto">
    <source src="ringtones/call-ringtone.mp3" type="audio/mpeg">
</audio>

<!-- Call Notification Modal for call_handler.php -->
<div id="globalCallNotificationModal" class="call-notification-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 10001; animation: fadeIn 0.3s ease;">
    <div class="call-notification-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(139, 30, 30, 0.3); padding: 32px; min-width: 320px; max-width: 400px; text-align: center; animation: slideDown 0.3s ease;">
        <div class="call-notification-icon" style="width: 64px; height: 64px; margin: 0 auto 20px; background: #8B1E1E; border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulse 1.5s ease-in-out infinite;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 32px; height: 32px; fill: white;">
                <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56-.35-.12-.74-.03-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z" />
            </svg>
        </div>
        <div class="call-notification-title" style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 8px;">Admin is calling</div>
        <div class="call-notification-subtitle" style="font-size: 14px; color: #666; margin-bottom: 28px;">Incoming Call</div>
        <div class="call-notification-actions" style="display: flex; gap: 12px; justify-content: center;">
            <button class="call-notification-btn call-notification-btn-decline" onclick="globalDeclineCall()" style="padding: 12px 28px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; min-width: 110px; background: #f5f5f5; color: #666;">Decline</button>
            <button class="call-notification-btn call-notification-btn-accept" onclick="globalAcceptCall()" style="padding: 12px 28px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; min-width: 110px; background: #8B1E1E; color: white;">Accept</button>
        </div>
    </div>
</div>

<style>
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

    .call-notification-btn-accept:hover {
        background: #6d1717;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 30, 30, 0.3);
    }

    .call-notification-btn-decline:hover {
        background: #e0e0e0;
        transform: translateY(-1px);
    }
</style>

<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>

<script>
    // Initialize Firebase (check if not already initialized)
    if (!firebase.apps.length) {
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
    }

    const database = firebase.database();
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;

    // 🔔 Ringtone management
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

    // Store current call reference globally
    let globalPendingCallRef = null;
    let globalPendingCallKey = null;

    function showGlobalCallModal() {
        document.getElementById('globalCallNotificationModal').style.display = 'block';
        // 🔔 Start playing ringtone
        playRingtone();
    }

    function hideGlobalCallModal() {
        document.getElementById('globalCallNotificationModal').style.display = 'none';
        // 🔔 Stop playing ringtone
        stopRingtone();
    }

    async function globalAcceptCall() {
        if (!globalPendingCallRef) return;

        // 🔔 Stop ringtone when accepting
        stopRingtone();
        hideGlobalCallModal();

        await globalPendingCallRef.update({
            status: 'answering'
        });

        // Pass the call id to messages.php to avoid re-prompt
        sessionStorage.setItem('acceptedCallId', globalPendingCallKey);
        window.location.href = 'messages.php';
    }

    async function globalDeclineCall() {
        if (!globalPendingCallRef) return;

        // 🔔 Stop ringtone when declining
        stopRingtone();
        hideGlobalCallModal();

        await globalPendingCallRef.update({
            status: 'rejected',
            rejectedAt: firebase.database.ServerValue.TIMESTAMP
        });

        // Clear references
        globalPendingCallRef = null;
        globalPendingCallKey = null;
    }

    // Skip registering the global listener on the messages page
    if (!/\/user\/messages\.php$/i.test(window.location.pathname)) {
        const pageLoadTime = Date.now(); // 🔥 Capture when THIS user's page loaded

        const callsRef = database.ref('calls')
            .orderByChild('userId')
            .equalTo(currentUserId);

        // Track already processed calls to avoid duplicates
        const processedCalls = new Set();

        // Only listen for NEW calls added after page load
        callsRef.on('child_added', async snapshot => {
            const call = snapshot.val();
            const callKey = snapshot.key;

            // Skip if already processed
            if (processedCalls.has(callKey)) return;

            // 🔥 FIX: Only show calls created AFTER this specific user loaded the page
            const callCreatedAt = call.createdAt || 0;
            if (callCreatedAt < pageLoadTime) {
                console.log('Ignoring call from before page load:', callKey);
                return;
            }

            // Only show modal if status is 'calling'
            if (call.status === 'calling') {
                processedCalls.add(callKey);
                console.log('New call for current user:', callKey);

                globalPendingCallRef = database.ref('calls/' + callKey);
                globalPendingCallKey = callKey;

                // Monitor status changes to auto-hide modal if call ends
                globalPendingCallRef.child('status').on('value', (statusSnapshot) => {
                    const status = statusSnapshot.val();
                    if (status === 'ended' || status === 'rejected' || status === 'connected' || status === 'answering') {
                        // 🔔 Stop ringtone if call status changes
                        stopRingtone();
                        hideGlobalCallModal();
                        globalPendingCallRef.child('status').off();
                        globalPendingCallRef = null;
                        globalPendingCallKey = null;
                    }
                });

                // Show styled modal with ringtone
                showGlobalCallModal();
            }
        });
    }

    // 🔔 Stop ringtone when page is hidden/closed
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopRingtone();
        }
    });

    window.addEventListener('beforeunload', () => {
        stopRingtone();
    });
</script>