<?php
session_start();
header('Content-Type: application/json');

// Check if it's a POST request and user is logged in
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to reserve a room.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';
$total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;

if (!$room_id || empty($check_in) || empty($check_out) || $total_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing reservation details.']);
    exit;
}

try {
    // 1. Double check availability (Optional but recommended)
    $stmt_check = $db->prepare("
        SELECT id FROM reservations 
        WHERE room_id = ? 
        AND status IN ('confirmed', 'pending')
        AND ((check_in <= ? AND check_out >= ?) OR (check_in <= ? AND check_out >= ?))
    ");
    $stmt_check->execute([$room_id, $check_out, $check_in, $check_in, $check_out]);
    
    if ($stmt_check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Sorry, these dates were just taken.']);
        exit;
    }

    // 2. Insert the reservation
    $query = "INSERT INTO reservations (user_id, room_id, check_in, check_out, total_price, status) 
              VALUES (:uid, :rid, :cin, :cout, :price, 'pending')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $user_id);
    $stmt->bindParam(':rid', $room_id);
    $stmt->bindParam(':cin', $check_in);
    $stmt->bindParam(':cout', $check_out);
    $stmt->bindParam(':price', $total_price);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reservation successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save reservation.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}