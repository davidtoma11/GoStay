<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include_once '../utils/tracker.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$roomId = $_GET['room_id'] ?? null;
$resId = $_GET['res_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$roomId || !$resId) {
    header("Location: ../pages/reservations_dashboard.php");
    exit;
}

// Validation: Check if the user is the owner of the property
$stmt_owner = $db->prepare("SELECT user_id, name FROM rooms WHERE id = ?");
$stmt_owner->execute([$roomId]);
$roomData = $stmt_owner->fetch(PDO::FETCH_ASSOC);

if ($roomData['user_id'] == $userId) {
    die("Error: You cannot leave a review for your own property.");
}

// Validation: Check if user already reviewed THIS specific room
$stmt_check_room = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND room_id = ?");
$stmt_check_room->execute([$userId, $roomId]);
if ($stmt_check_room->rowCount() > 0) {
    die("Error: You have already submitted a review for this property.");
}

// Validation: Check global limit (Maximum 2 reviews per person across the platform)
$stmt_limit = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
$stmt_limit->execute([$userId]);
$totalReviews = $stmt_limit->fetchColumn();

if ($totalReviews >= 2) {
    die("Error: You have reached the maximum limit of 2 reviews allowed per account.");
}

$roomName = $roomData['name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'];
    $comment = htmlspecialchars(strip_tags($_POST['comment']));

    $stmt_insert = $db->prepare("INSERT INTO reviews (user_id, room_id, rating, comment) VALUES (?, ?, ?, ?)");
    if ($stmt_insert->execute([$userId, $roomId, $rating, $comment])) {
        header("Location: ../pages/reservations_dashboard.php?success=review_added");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Your Stay - <?= htmlspecialchars($roomName) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/manager.css">
    <link rel="stylesheet" href="../styles/search_results.css">
    <link rel="stylesheet" href="../styles/footer.css">
</head>
<body style="background: #f4f7ff;">
    <nav class="results-nav">
        <div class="nav-left">
            <div class="nav-logo"></div>
            <h2 class="manager-header-text">Rate Your Experience</h2>
        </div>
        <div class="nav-icons">
            <a href="../pages/reservations_dashboard.php"><i class="fa-solid fa-xmark"></i></a>
        </div>
    </nav>

    <div class="results-wrapper">
        <div class="property-form-container" style="max-width: 600px;">
            <div class="manager-card-section">
                <div class="modal-main-icon"><i class="fa-solid fa-star" style="color: #FFD700;"></i></div>
                <h2 style="text-align: center; margin-bottom: 10px;">How was your stay at <?= htmlspecialchars($roomName) ?>?</h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">Your feedback helps others find the perfect place.</p>

                <form method="POST">
                    <div class="input-group">
                        <label>Rating</label>
                        <select name="rating" required style="font-size: 1.1rem; padding: 12px; border-radius: 12px; border: 1px solid #ddd; width: 100%;">
                            <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                            <option value="4">⭐⭐⭐⭐ (Very Good)</option>
                            <option value="3">⭐⭐⭐ (Good)</option>
                            <option value="2">⭐⭐ (Fair)</option>
                            <option value="1">⭐ (Poor)</option>
                        </select>
                    </div>

                    <div class="input-group" style="margin-top: 20px;">
                        <label>Review details</label>
                        <textarea name="comment" rows="5" placeholder="What did you like or dislike?..." required style="width: 100%; padding: 15px; border-radius: 12px; border: 1px solid #ddd;"></textarea>
                    </div>

                    <div style="display: flex; justify-content: center; width: 100%;">
                        <button type="submit" class="btn" style="width: 100%; margin-top: 30px; padding: 18px; font-size: 1rem;">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../utils/includes/footer.php'; ?>
</body>
</html>