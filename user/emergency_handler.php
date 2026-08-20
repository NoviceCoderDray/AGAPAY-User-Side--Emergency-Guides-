<?php
// filepath: c:\wamp64\www\agapay\user\emergency_handler.php

date_default_timezone_set('Asia/Manila');
include('user_session.php');
include('../config/config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

// Get user info
$user_query = "SELECT id, first_name, last_name FROM users WHERE email = '" . mysqli_real_escape_string($conn, $_SESSION['user_email']) . "'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

if (!$user_data) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user_id = $user_data['id'];
$reported_by = $user_data['first_name'] . ' ' . $user_data['last_name'];

// Validate required fields
$required_fields = ['emergency_type', 'latitude', 'longitude', 'street_name', 'message'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit();
    }
}

// Sanitize inputs
$emergency_type = mysqli_real_escape_string($conn, $input['emergency_type']);
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$street_name = mysqli_real_escape_string($conn, $input['street_name']);
$message = mysqli_real_escape_string($conn, $input['message']);

$status = 'pending';
$date_of_call = date('Y-m-d');
$time_of_call = date('H:i:s');
$current_time = time();

// DEDUPLICATION LOGIC
$duplicate_found = false;
$master_incident_id = null;
$has_active_call = false;
$caller_name = null;
$is_most_recent = false;

// 🔥 IMPORTANT: Only check incidents of the SAME emergency type
// Different emergency types at the same location are separate incidents
$active_incidents_query = "SELECT id, latitude, longitude, created_at, reported_by, user_id 
                          FROM incident_reports 
                          WHERE emergency_type = '$emergency_type'
                          AND status = 'pending' 
                          AND latitude IS NOT NULL 
                          AND longitude IS NOT NULL
                          ORDER BY created_at DESC";
$active_incidents_result = mysqli_query($conn, $active_incidents_query);

if ($active_incidents_result && mysqli_num_rows($active_incidents_result) > 0) {
    while ($incident = mysqli_fetch_assoc($active_incidents_result)) {
        // Calculate time difference in minutes (from FIRST report creation time)
        $incident_time = strtotime($incident['created_at']);
        $time_diff_minutes = ($current_time - $incident_time) / 60;

        // Calculate distance from FIRST report location
        $distance_meters = calculateDistance($latitude, $longitude, $incident['latitude'], $incident['longitude']);

        // Set thresholds based on emergency type
        $max_distance = 0;
        $max_time = 0;

        switch (strtolower($emergency_type)) {
            case 'crime':
                $max_distance = 300;
                $max_time = 10;
                break;
            case 'disaster':
                $max_distance = 600;
                $max_time = 15;
                break;
            case 'medical':
                $max_distance = 150;
                $max_time = 8;
                break;
            case 'accident':
                $max_distance = 500;
                $max_time = 12;
                break;
            case 'fire':
                $max_distance = 800;
                $max_time = 15;
                break;
            default:
                continue 2;
        }

        // Check if within threshold (distance AND time AND same type)
        if ($distance_meters <= $max_distance && $time_diff_minutes <= $max_time) {
            $duplicate_found = true;
            $master_incident_id = $incident['id'];

            // Check if there's an active call for this incident
            $call_status = checkActiveCallForIncident($master_incident_id, $conn);
            $has_active_call = $call_status['has_active_call'];
            $caller_name = $call_status['caller_name'];

            // Check if this report is the most recent (will be allowed to call if no active call)
            $most_recent_check = checkIfMostRecent($master_incident_id, $conn);
            $is_most_recent = $most_recent_check['is_most_recent'];

            break;
        }
    }
}

if ($duplicate_found) {
    // Insert into sub-reports table with user_id
    $sub_report_sql = "INSERT INTO incident_sub_reports 
                      (master_incident_id, reported_by, user_id, street_name, emergency_type, date_of_call, time_of_call, latitude, longitude, message, files, created_at) 
                      VALUES 
                      ('$master_incident_id', '" . mysqli_real_escape_string($conn, $reported_by) . "', $user_id, '$street_name', '$emergency_type', '$date_of_call', '$time_of_call', $latitude, $longitude, '$message', '', NOW())";

    if (mysqli_query($conn, $sub_report_sql)) {
        $response_data = [
            'success' => true,
            'message' => 'Report linked to existing incident as sub-report',
            'type' => 'sub_report',
            'master_incident_id' => $master_incident_id,
            'sub_report_id' => mysqli_insert_id($conn),
            'emergency_type' => $emergency_type,
            'has_active_call' => $has_active_call,
            'is_most_recent' => $is_most_recent,
            'can_call' => !$has_active_call && $is_most_recent // Can call if no active call AND is most recent
        ];

        // Add call status information if someone is already on call
        if ($has_active_call) {
            $response_data['call_status'] = [
                'caller_name' => $caller_name,
                'message' => "{$caller_name} is already in contact with emergency services about this {$emergency_type} incident."
            ];
        } elseif (!$is_most_recent) {
            $response_data['call_status'] = [
                'message' => "Another person has reported this {$emergency_type} incident more recently. They will contact emergency services."
            ];
        }

        echo json_encode($response_data);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create sub-report: ' . mysqli_error($conn)]);
    }
} else {
    // Create new main incident with user_id
    $lat_db = $latitude !== null ? $latitude : 'NULL';
    $lng_db = $longitude !== null ? $longitude : 'NULL';

    $sql = "INSERT INTO incident_reports (reported_by, user_id, street_name, emergency_type, message, files, status, date_of_call, time_of_call, latitude, longitude, created_at) 
            VALUES ('" . mysqli_real_escape_string($conn, $reported_by) . "', $user_id, '$street_name', '$emergency_type', '$message', '', '$status', '$date_of_call', '$time_of_call', $lat_db, $lng_db, NOW())";

    if (mysqli_query($conn, $sql)) {
        $incident_id = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true,
            'message' => 'New incident report created successfully',
            'type' => 'main_report',
            'incident_id' => $incident_id,
            'emergency_type' => $emergency_type,
            'has_active_call' => false,
            'can_call' => true // First report always can call
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create incident report: ' . mysqli_error($conn)]);
    }
}

// Function to check if there's an active call for this incident
function checkActiveCallForIncident($incident_id, $conn)
{
    // Check main incident's user
    $main_user_query = "SELECT user_id, reported_by FROM incident_reports WHERE id = $incident_id";
    $main_user_result = mysqli_query($conn, $main_user_query);
    $main_user_data = mysqli_fetch_assoc($main_user_result);

    $user_ids_to_check = [];
    if ($main_user_data && $main_user_data['user_id']) {
        $user_ids_to_check[] = $main_user_data['user_id'];
    }

    // Get all sub-report users
    $sub_users_query = "SELECT user_id, reported_by FROM incident_sub_reports WHERE master_incident_id = $incident_id";
    $sub_users_result = mysqli_query($conn, $sub_users_query);

    while ($sub_user = mysqli_fetch_assoc($sub_users_result)) {
        if ($sub_user['user_id']) {
            $user_ids_to_check[] = $sub_user['user_id'];
        }
    }

    // Check if any of these users have an active call
    if (!empty($user_ids_to_check)) {
        $user_ids_str = implode(',', $user_ids_to_check);

        $call_query = "SELECT ac.*, u.first_name, u.last_name 
                       FROM active_calls ac
                       JOIN users u ON ac.user_id = u.id
                       WHERE ac.user_id IN ($user_ids_str)
                       AND ac.status = 'active' 
                       AND ac.created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                       ORDER BY ac.created_at DESC 
                       LIMIT 1";

        $call_result = mysqli_query($conn, $call_query);

        if ($call_result && mysqli_num_rows($call_result) > 0) {
            $call_data = mysqli_fetch_assoc($call_result);
            return [
                'has_active_call' => true,
                'caller_name' => $call_data['first_name'] . ' ' . $call_data['last_name'],
                'call_id' => $call_data['id'],
                'started_at' => $call_data['created_at']
            ];
        }
    }

    return ['has_active_call' => false, 'caller_name' => null];
}

// Function to check if this is the most recent report
function checkIfMostRecent($master_incident_id, $conn)
{
    // Get the most recent sub-report timestamp
    $recent_query = "SELECT created_at FROM incident_sub_reports 
                    WHERE master_incident_id = $master_incident_id 
                    ORDER BY created_at DESC 
                    LIMIT 1";

    $recent_result = mysqli_query($conn, $recent_query);

    if ($recent_result && mysqli_num_rows($recent_result) > 0) {
        // There are sub-reports, this new report will be the most recent
        return ['is_most_recent' => true];
    }

    // No sub-reports yet, so main report is still most recent
    return ['is_most_recent' => false];
}

// Distance calculation functions
function calculateDistance($lat1, $lng1, $lat2, $lng2)
{
    $google_distance = getGoogleMapsDistance($lat1, $lng1, $lat2, $lng2);
    if ($google_distance !== false) {
        return $google_distance;
    }
    return getStraightLineDistance($lat1, $lng1, $lat2, $lng2);
}

function getGoogleMapsDistance($lat1, $lng1, $lat2, $lng2)
{
    $api_key = GOOGLE_MAPS_API_KEY;
    $origin = $lat1 . ',' . $lng1;
    $destination = $lat2 . ',' . $lng2;

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($origin) .
        "&destinations=" . urlencode($destination) . "&key=" . $api_key . "&mode=driving";

    $response = @file_get_contents($url);

    if ($response === false) {
        return false;
    }

    $data = json_decode($response, true);

    if (
        $data &&
        isset($data['status']) && $data['status'] === 'OK' &&
        isset($data['rows'][0]['elements'][0]['status']) && $data['rows'][0]['elements'][0]['status'] === 'OK'
    ) {
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

    $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
        cos($lat1_rad) * cos($lat2_rad) *
        sin($delta_lng / 2) * sin($delta_lng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c;
}
