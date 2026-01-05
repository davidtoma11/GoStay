<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include_once '../utils/tracker.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$userId = $_SESSION['user_id'];

function daysUntil($date) {
    $now = new DateTime();
    $future = new DateTime($date);
    $interval = $now->diff($future);
    return $interval->invert ? 0 : $interval->days;
}

function getUserReservations($db, $userId, $status) {
    $sql = "SELECT res.*, r.name as room_name, r.address, r.id as room_real_id,
            (SELECT photo_url FROM room_photos rp WHERE rp.room_id = r.id ORDER BY is_primary DESC LIMIT 1) as main_photo
            FROM reservations res
            JOIN rooms r ON res.room_id = r.id
            WHERE res.user_id = ? AND res.status = ?
            ORDER BY res.check_in ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId, $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pending = getUserReservations($db, $userId, 'pending');
$confirmed = getUserReservations($db, $userId, 'confirmed');
$completed = getUserReservations($db, $userId, 'completed');
$cancelled = getUserReservations($db, $userId, 'cancelled');

$total_spent = $db->prepare("SELECT SUM(total_price) FROM reservations WHERE user_id = ? AND status = 'completed'");
$total_spent->execute([$userId]);
$spent_value = $total_spent->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Trips - GoStay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/search_results.css">
    <link rel="stylesheet" href="../styles/manager.css">
    <link rel="stylesheet" href="../styles/footer.css">
</head>
<body style="background: #f4f7ff;">
    <nav class="results-nav">
        <div class="nav-left">
            <div class="nav-logo"></div>
            <h2 class="manager-header-text">My Trips</h2>
        </div>
        <div class="nav-icons">
            <a href="home.php"><i class="fa-solid fa-house"></i></a>
        </div>
    </nav>

    <div class="results-wrapper">
        <div class="stats-grid-dashboard">
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Spent</span>
                    <span class="stat-value"><?= number_format($spent_value, 2) ?> RON</span>
                </div>
            </div>
            <div class="stat-item highlight">
                <div class="stat-icon"><i class="fa-solid fa-suitcase"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Trips</span>
                    <span class="stat-value"><?= count($confirmed) + count($pending) ?> Bookings</span>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <div class="stat-info">
                    <span class="stat-label">History</span>
                    <span class="stat-value"><?= count($completed) ?> Stays</span>
                </div>
            </div>
        </div>

        <div class="layout-grid">
            <div class="main-content">
                
                <h2 class="form-section-title">Current & Upcoming</h2>
                <div class="property-grid-manager">
                    <?php foreach(array_merge($confirmed, $pending) as $res): ?>
                    <div class="mini-property-card clickable-card" onclick="window.location.href='room_details.php?id=<?= $res['room_real_id'] ?>'">
                        <div class="mini-img" style="background-image: url('../../assets/<?= $res['main_photo'] ?>');"></div>
                        <div class="mini-details">
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 5px;">
                                <span class="res-id-badge">#<?= $res['id'] ?></span>
                                <span class="status-pill status-<?= $res['status'] ?>"><?= $res['status'] ?></span>
                            </div>
                            <h4><?= htmlspecialchars($res['room_name']) ?></h4>
                            <span><?= $res['check_in'] ?> to <?= $res['check_out'] ?></span>
                        </div>
                        <div style="text-align: right; margin-left: 20px;">
                            <div class="price-tag"><?= number_format($res['total_price'], 2) ?> RON</div>
                            <?php if($res['status'] === 'pending'): ?>
                                <button class="action-btn cancel" style="margin-top: 10px;" onclick="event.stopPropagation(); confirmCancel(<?= $res['id'] ?>)"><i class="fa-solid fa-xmark"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <h2 class="form-section-title" style="margin-top: 40px;">Travel History</h2>
                <div class="property-grid-manager">
                    <?php foreach($completed as $res): ?>
                    <div class="mini-property-card clickable-card" onclick="window.location.href='room_details.php?id=<?= $res['room_real_id'] ?>'">
                        <div class="mini-img" style="background-image: url('../../assets/<?= $res['main_photo'] ?>');"></div>
                        <div class="mini-details">
                            <div style="margin-bottom: 5px;"><span class="status-pill status-completed">Completed</span></div>
                            <h4><?= htmlspecialchars($res['room_name']) ?></h4>
                            <span style="color: #27ae60; font-weight: 700;"><?= number_format($res['total_price'], 2) ?> RON</span>
                        </div>
                        <a href="../utils/add_review.php?room_id=<?= $res['room_real_id'] ?>&res_id=<?= $res['id'] ?>" 
                           class="btn" style="padding: 10px 20px; font-size: 0.8rem; background: #7b2bd4; color: white;"
                           onclick="event.stopPropagation();">
                            <i class="fa-solid fa-star"></i> Rate Stay
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="sidebar-column">
                <section class="manager-card-section">
                    <div class="section-header"><h3><i class="fa-solid fa-ban"></i> Cancelled</h3></div>
                    <?php foreach($cancelled as $res): ?>
                    <div class="mini-property-card" style="opacity: 0.6; margin-bottom: 15px; cursor: default;">
                         <div class="mini-img" style="background-image: url('../../assets/<?= $res['main_photo'] ?>'); width: 40px; height: 40px;"></div>
                         <div class="mini-details">
                             <h4 style="font-size: 0.9rem;"><?= htmlspecialchars($res['room_name']) ?></h4>
                             <small class="status-pill status-cancelled">Cancelled</small>
                         </div>
                    </div>
                    <?php endforeach; ?>
                </section>
            </aside>
        </div>
    </div>
    <?php include '../utils/includes/footer.php'; ?>
    </body>
</html>