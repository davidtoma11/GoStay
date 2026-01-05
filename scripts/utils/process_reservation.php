<?php
session_start();
header('Content-Type: application/json');

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

$user_id = $_SESSION['user_id']; // The Client
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';
$total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0;

if (!$room_id || empty($check_in) || empty($check_out) || $total_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing reservation details.']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Check availability
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
    $stmt->execute([
        ':uid'   => $user_id,
        ':rid'   => $room_id,
        ':cin'   => $check_in,
        ':cout'  => $check_out,
        ':price' => $total_price
    ]);

    // 3. Automated Message: Client to Host
    // Get host ID and room name
    $stmt_host = $db->prepare("SELECT user_id, name FROM rooms WHERE id = ?");
    $stmt_host->execute([$room_id]);
    $roomData = $stmt_host->fetch(PDO::FETCH_ASSOC);
    $host_id = $roomData['user_id'];
    $room_name = $roomData['name'];

    $welcome_msg = "Hello! I have just requested a reservation for '$room_name' from $check_in to $check_out. Looking forward to your approval!";
    
    $stmt_msg = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message_body) VALUES (?, ?, ?)");
    $stmt_msg->execute([$user_id, $host_id, $welcome_msg]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation successful and host notified!']);

} catch (Exception $e) {
    if($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}