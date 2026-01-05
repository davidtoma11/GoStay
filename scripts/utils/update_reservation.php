<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Processes status updates, date modifications, discounts, and penalties via AJAX
header('Content-Type: application/json');

// Security Check: Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Retrieve POST data
$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$action = $_POST['action'] ?? 'status'; // Default action is a simple status change

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing reservation ID.']);
    exit;
}

try {
    $success = false;
    $message = '';

    if ($action === 'edit') {
        // Logic for updating calendar dates (check-in/check-out)
        $check_in = $_POST['check_in'] ?? '';
        $check_out = $_POST['check_out'] ?? '';
        
        $sql = "UPDATE reservations SET check_in = ?, check_out = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$check_in, $check_out, $id]);
        $message = 'Dates updated successfully.';

    } elseif ($action === 'discount') {
        // Logic for applying a percentage discount based on the current total price
        $pct = intval($_POST['value'] ?? 0);
        if ($pct <= 0 || $pct > 100) {
            throw new Exception("Invalid discount percentage.");
        }

        $sql = "UPDATE reservations SET total_price = total_price - (total_price * (? / 100)) WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$pct, $id]);
        $message = "Discount of $pct% applied successfully.";

    } elseif ($action === 'penalty') {
        // Logic for applying a fixed amount penalty (adds to the total price)
        $amount = floatval($_POST['value'] ?? 0);
        $reason = $_POST['reason'] ?? 'No reason provided';

        if ($amount <= 0) {
            throw new Exception("Invalid penalty amount.");
        }

        $sql = "UPDATE reservations SET total_price = total_price + ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$amount, $id]);
        $message = "Penalty of $amount RON applied.";

    } else {
        // Standard logic for status updates (e.g., confirmed, cancelled, completed)
        $sql = "UPDATE reservations SET status = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([$status, $id]);
        $message = "Reservation status updated to $status.";
    }

    echo json_encode([
        'success' => $success, 
        'message' => $success ? $message : 'A database error occurred.'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}