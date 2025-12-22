<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); 
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['user_name'];

$features = [];
$page_title = "Welcome";

if ($role === 'manager') {
    $page_title = "Manager Dashboard";
    $features = [
        ["title" => "Property Overview", "desc" => "Holistic view of all your listed properties and their current status."],
        ["title" => "Dynamic Pricing", "desc" => "Set automated pricing rules based on seasonality and demand."],
        ["title" => "Reservation Calendar", "desc" => "Drag-and-drop calendar to manage bookings and block dates."],
        ["title" => "Financial Analytics", "desc" => "Deep dive into revenue, taxes, and commission reports."],
        ["title" => "Guest Communication", "desc" => "Unified inbox for messaging guests before and during their stay."],
        ["title" => "Maintenance Logs", "desc" => "Track repairs and scheduled maintenance for every room."],
        ["title" => "Promotions Engine", "desc" => "Create discount codes and special offers to boost occupancy."]
    ];
} elseif ($role === 'client') {
    $page_title = "Client Portal";
    $features = [
        ["title" => "Filtered Search", "desc" => "Search to find the perfect stay based on your preferences."],
        ["title" => "Loyalty Program", "desc" => "Earn points for every night and redeem them for free upgrades."],
        ["title" => "Mobile Check-in", "desc" => "Skip the front desk queue and access your room via the app."],
        ["title" => "Booking History", "desc" => "Download invoices and manage past or future reservations."],
        ["title" => "Verified Reviews", "desc" => "Read and write honest feedback to help the community."],
        ["title" => "Wishlist Collections", "desc" => "Create multiple lists for different vacation ideas."],
        ["title" => "24/7 Support Chat", "desc" => "Direct line to our support team for any issues during your trip."]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay - <?php echo $page_title; ?></title>
    
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="stylesheet" href="../styles/role_landing.css">
</head>
<body>
    <div class="landing-container">
        <div class="info-card fade-in">
            
            <header class="card-header">
                <span class="role-badge"><?php echo strtoupper($role); ?> WORKSPACE</span>
                <h1 class="message-title">Welcome back, <?php echo htmlspecialchars($name); ?></h1>
                <p class="message-subtitle">Your personalized dashboard is currently under construction.</p>
            </header>



            <h2 class="section-title">Upcoming Features</h2>
            
            <div class="features-grid">
                <?php foreach($features as $feat): ?>
                <div class="feature-item">
                    <h3 class="feature-title"><?php echo $feat['title']; ?></h3>
                    <p class="feature-desc"><?php echo $feat['desc']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="action-area">
                <a href="auth/logout.php" class="btn btn-outline logout-btn">Sign Out</a>
            </div>
        </div>
    </div>
</body>
</html>