<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay CRUD Panel</title>
    <link rel="stylesheet" href="../../styles/crud.css">
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
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                <a href="../dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
                <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div class="crud-grid">
            <div class="crud-card">
                <div class="card-icon">
                    <img src="../../assets/img/crud-hub-icons/users.png" alt="Users Management">
                </div>
                <h3>Users Management</h3>
                <p>Manage all system users and permissions</p>
                <div class="card-stats">
                    <?php
                    $user_count = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
                    ?>
                    <span class="stat"><?php echo $user_count; ?> users</span>
                </div>
                <a href="operations.php?table=users" class="btn btn-primary">Manage Users</a>
            </div>

            <div class="crud-card">
                <div class="card-icon">
                    <img src="../../assets/img/crud-hub-icons/hotels.png" alt="Hotels Management">
                </div>
                <h3>Hotels Management</h3>
                <p>Manage hotels and properties</p>
                <div class="card-stats">
                    <?php
                    $hotel_count = $db->query("SELECT COUNT(*) as count FROM hotels")->fetch()['count'];
                    ?>
                    <span class="stat"><?php echo $hotel_count; ?> hotels</span>
                </div>
                <a href="operations.php?table=hotels" class="btn btn-primary">Manage Hotels</a>
            </div>

            <div class="crud-card">
                <div class="card-icon">
                    <img src="../../assets/img/crud-hub-icons/rooms.png" alt="Rooms Management">
                </div>
                <h3>Rooms Management</h3>
                <p>Manage hotel rooms and pricing</p>
                <div class="card-stats">
                    <?php
                    $room_count = $db->query("SELECT COUNT(*) as count FROM rooms")->fetch()['count'];
                    ?>
                    <span class="stat"><?php echo $room_count; ?> rooms</span>
                </div>
                <a href="operations.php?table=rooms" class="btn btn-primary">Manage Rooms</a>
            </div>

            <div class="crud-card">
                <div class="card-icon">
                    <img src="../../assets/img/crud-hub-icons/photos.png" alt="Room Photos">
                </div>
                <h3>Photos Management</h3>
                <p>Manage room photos and galleries</p>
                <div class="card-stats">
                    <?php
                    $photo_count = $db->query("SELECT COUNT(*) as count FROM room_photos")->fetch()['count'];
                    ?>
                    <span class="stat"><?php echo $photo_count; ?> photos</span>
                </div>
                <a href="operations.php?table=room_photos" class="btn btn-primary">Manage Photos</a>
            </div>

            <div class="crud-card">
                <div class="card-icon">
                    <img src="../../assets/img/crud-hub-icons/reservations.png" alt="Reservations">
                </div>
                <h3>Reservations Management</h3>
                <p>Manage bookings</p>
                <div class="card-stats">
                    <?php
                    $res_count = $db->query("SELECT COUNT(*) as count FROM reservations")->fetch()['count'];
                    ?>
                    <span class="stat"><?php echo $res_count; ?> reservations</span>
                </div>
                <a href="operations.php?table=reservations" class="btn btn-primary">Manage Reservations</a>
            </div>

            <div class="crud-card">
                <div class="card-icon">
                    <img src="../../assets/img/crud-hub-icons/reviews.png" alt="Reviews Management">
                </div>
                <h3>Reviews Management</h3>
                <p>Manage user reviews and ratings</p>
                <div class="card-stats">
                    <?php
                    $review_count = $db->query("SELECT COUNT(*) as count FROM reviews")->fetch()['count'];
                    ?>
                    <span class="stat"><?php echo $review_count; ?> reviews</span>
                </div>
                <a href="operations.php?table=reviews" class="btn btn-primary">Manage Reviews</a>
            </div>

            <div class="crud-card">
                <div class="card-icon">
                    <img src="../../assets/img/crud-hub-icons/messages.png" alt="Messages">
                </div>
                <h3>Messages Management</h3>
                <p>View and manage user messages</p>
                <div class="card-stats">
                    <?php
                    $msg_count = $db->query("SELECT COUNT(*) as count FROM messages")->fetch()['count'];
                    ?>
                    <span class="stat"><?php echo $msg_count; ?> messages</span>
                </div>
                <a href="operations.php?table=messages" class="btn btn-primary">View Messages</a>
            </div>
        </div>
    </div>
</body>

</html>