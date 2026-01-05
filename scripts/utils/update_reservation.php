<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include_once '../utils/tracker.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$current_user_id = $_SESSION['user_id'];

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$action = $_POST['action'] ?? 'status';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Reservation ID is missing.']);
    exit;
}

try {
    $db->beginTransaction();
    $success = false;
    $message = '';
    $auto_reply = "";

    // Fetch reservation details to personalize the message
    $stmt_info = $db->prepare("
        SELECT res.user_id as client_id, r.name as room_name, res.total_price 
        FROM reservations res 
        JOIN rooms r ON res.room_id = r.id 
        WHERE res.id = ?
    ");
    $stmt_info->execute([$id]);
    $res_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    if (!$res_info) throw new Exception("Reservation details not found.");
    
    $client_id = $res_info['client_id'];
    $room_name = $res_info['room_name'];

    if ($action === 'edit') {
        $check_in = $_POST['check_in'] ?? '';
        $check_out = $_POST['check_out'] ?? '';
        $sql = "UPDATE reservations SET check_in = ?, check_out = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$check_in, $check_out, $id]);
        $message = 'Dates updated successfully.';
        $auto_reply = "Formal Notification: The temporal parameters for your residency at '$room_name' have been adjusted. Your revised itinerary is now scheduled from $check_in until $check_out. We look forward to your arrival.";

    } elseif ($action === 'discount') {
        $pct = intval($_POST['value'] ?? 0);
        if ($pct <= 0 || $pct > 100) throw new Exception("Invalid discount.");
        $sql = "UPDATE reservations SET total_price = total_price - (total_price * (? / 100)) WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$pct, $id]);
        $message = "Discount applied.";
        $auto_reply = "Complimentary Adjustment: It is our pleasure to inform you that a preferential discount of $pct% has been applied to your reservation at '$room_name'. This gesture reflects our commitment to providing you with an exceptional experience.";

    } elseif ($action === 'penalty') {
        $amount = floatval($_POST['value'] ?? 0);
        if ($amount <= 0) throw new Exception("Invalid penalty.");
        $sql = "UPDATE reservations SET total_price = total_price + ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$amount, $id]);
        $message = "Penalty applied.";
        $auto_reply = "Financial Notice: Please be advised that a supplementary charge of $amount RON has been appended to your account regarding the reservation at '$room_name', in accordance with our established protocols.";

    } else {
        $sql = "UPDATE reservations SET status = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$status, $id]);
        
        if ($status === 'confirmed') {
            $message = "Reservation confirmed.";
            $auto_reply = "Confirmation of Stay: We are delighted to formally confirm your reservation for '$room_name'. All arrangements have been finalized, and we await the pleasure of welcoming you to our establishment.";
        } elseif ($status === 'cancelled') {
            $message = "Reservation cancelled.";
            $auto_reply = "Cancellation Notice: We regret to inform you that your reservation for '$room_name' has been formally rescinded. Should you require further assistance or wish to re-evaluate your travel plans, we remain at your disposal.";
        } elseif ($status === 'completed') {
            $message = "Reservation completed.";
            $auto_reply = "Departure & Appreciation: As your sojourn at '$room_name' concludes, we extend our gratitude for choosing us. It would be a profound honor if you would share your impressions of the experience via a formal review.";
        }
    }

    // Insert the sophisticated automated message into the database
    if ($success && !empty($auto_reply)) {
        $stmt_msg = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message_body) VALUES (?, ?, ?)");
        $stmt_msg->execute([$current_user_id, $client_id, $auto_reply]);
    }

    $db->commit();
    echo json_encode(['success' => $success, 'message' => $message]);

} catch (Exception $e) {
    if($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}