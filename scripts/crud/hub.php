<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/index.php");
    exit;
}   

require_once '../config/database.php';
include_once '../utils/tracker.php';
$database = new Database();
$db = $database->getConnection();

$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    header("Location: ../pages/home.php?error=unauthorized");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay CRUD Panel</title>
    <link rel="stylesheet" href="../styles/crud.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="crud-container">
        <div class="crud-header">
            <div class="header-left">
                <div class="logo-title">
                    <img src="../../assets/img/logo.png" alt="GoStay Logo" class="header-logo">
                    <h1>Admin Panel</h1>
                </div>
                <p class="crud-subtitle">Manage platform efficiently</p>
            </div>
            <div class="user-info">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong>
                <a href="../pages/home.php" class="btn btn-outline">← Back to Dashboard</a>
                <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="crud-grid">
            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/users.png" alt="Users"></div>
                <h3>Users</h3>
                <p>Manage admins, managers & clients</p>
                <div class="card-stats">
                    <?php $user_count = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count']; ?>
                    <span class="stat"><?php echo $user_count; ?> users</span>
                </div>
                <a href="operations.php?table=users" class="btn btn-primary">Manage Users</a>
            </div>

            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/hotels.png" alt="Cities"></div>
                <h3>Cities</h3>
                <p>Manage available locations</p>
                <div class="card-stats">
                    <?php $city_count = $db->query("SELECT COUNT(*) as count FROM cities")->fetch()['count']; ?>
                    <span class="stat"><?php echo $city_count; ?> cities</span>
                </div>
                <a href="operations.php?table=cities" class="btn btn-primary">Manage Cities</a>
            </div>

            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/rooms.png" alt="Rooms"></div>
                <h3>Rooms</h3>
                <p>Manage listings & details</p>
                <div class="card-stats">
                    <?php $room_count = $db->query("SELECT COUNT(*) as count FROM rooms")->fetch()['count']; ?>
                    <span class="stat"><?php echo $room_count; ?> rooms</span>
                </div>
                <a href="operations.php?table=rooms" class="btn btn-primary">Manage Rooms</a>
            </div>

            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/rooms.png" alt="Facilities"></div>
                <h3>Facilities</h3>
                <p>Manage room amenities</p>
                <div class="card-stats">
                    <?php $fac_count = $db->query("SELECT COUNT(*) as count FROM facilities")->fetch()['count']; ?>
                    <span class="stat"><?php echo $fac_count; ?> records</span>
                </div>
                <a href="operations.php?table=facilities" class="btn btn-primary">Manage Facilities</a>
            </div>

            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/photos.png" alt="Photos"></div>
                <h3>Photos</h3>
                <p>Manage room galleries</p>
                <div class="card-stats">
                    <?php $photo_count = $db->query("SELECT COUNT(*) as count FROM room_photos")->fetch()['count']; ?>
                    <span class="stat"><?php echo $photo_count; ?> photos</span>
                </div>
                <a href="operations.php?table=room_photos" class="btn btn-primary">Manage Photos</a>
            </div>

            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/reservations.png" alt="Reservations"></div>
                <h3>Reservations</h3>
                <p>Manage bookings</p>
                <div class="card-stats">
                    <?php $res_count = $db->query("SELECT COUNT(*) as count FROM reservations")->fetch()['count']; ?>
                    <span class="stat"><?php echo $res_count; ?> bookings</span>
                </div>
                <a href="operations.php?table=reservations" class="btn btn-primary">Manage Reservations</a>
            </div>

            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/reviews.png" alt="Reviews"></div>
                <h3>Reviews</h3>
                <p>Manage feedback</p>
                <div class="card-stats">
                    <?php $review_count = $db->query("SELECT COUNT(*) as count FROM reviews")->fetch()['count']; ?>
                    <span class="stat"><?php echo $review_count; ?> reviews</span>
                </div>
                <a href="operations.php?table=reviews" class="btn btn-primary">Manage Reviews</a>
            </div>

            <div class="crud-card">
                <div class="card-icon"><img src="../../assets/img/crud-hub-icons/messages.png" alt="Messages"></div>
                <h3>Messages</h3>
                <p>View user messages</p>
                <div class="card-stats">
                    <?php $msg_count = $db->query("SELECT COUNT(*) as count FROM messages")->fetch()['count']; ?>
                    <span class="stat"><?php echo $msg_count; ?> messages</span>
                </div>
                <a href="operations.php?table=messages" class="btn btn-primary">View Messages</a>
            </div>

            <div class="crud-card" style="border: 2px solid #7b2bd4; background: #fdfbff;">
                <div class="card-icon" style="background: #f0e6ff;">
                    <img src="../../assets/img/crud-hub-icons/analytics.png" alt="Analytics" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3502/3502135.png'">
                </div>
                <h3 style="color: #7b2bd4;">Platform Analytics</h3>
                <p>Traffic logs, revenue growth & user activity</p>
                <div class="card-stats">
                    <?php 
                    // Counting from your actual table name 'analytics'
                    $visit_count = $db->query("SELECT COUNT(*) as count FROM analytics")->fetch()['count']; 
                    ?>
                    <span class="stat" style="color: #7b2bd4;"><?php echo number_format($visit_count); ?> total visits</span>
                </div>
                <a href="analytics.php" class="btn btn-primary" style="background: #7b2bd4;">Open Analytics Hub</a>
            </div>

        </div>
    </div>
</body>
</html>