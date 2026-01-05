<?php
session_start();

// Security check: Redirect if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Room.php';

$database = new Database();
$conn = $database->getConnection();
$roomModel = new Room($conn);

// Get and sanitize room ID
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$room = $roomModel->getDetails($room_id);

if (!$room) {
    header("Location: home.php");
    exit;
}

// Fetch all photos for the slider
$stmt_photos = $conn->prepare("SELECT photo_url FROM room_photos WHERE room_id = ? ORDER BY is_primary DESC");
$stmt_photos->execute([$room_id]);
$photos = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);

// Get available amenities data
$facilities_data = $roomModel->getFacilities($room_id);

// Get booked ranges for calendar disabling
$stmt_booked = $conn->prepare("SELECT check_in, check_out FROM reservations WHERE room_id = ? AND status IN ('confirmed', 'pending')");
$stmt_booked->execute([$room_id]);
$booked_dates = $stmt_booked->fetchAll(PDO::FETCH_ASSOC);

// Fetch reviews with user details
$stmt_reviews = $conn->prepare("
    SELECT r.*, u.first_name, u.last_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.room_id = ? 
    ORDER BY r.created_at DESC
");
$stmt_reviews->execute([$room_id]);
$reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$total_rating = 0;
foreach ($reviews as $rev) { $total_rating += $rev['rating']; }
$avg_rating = count($reviews) > 0 ? round($total_rating / count($reviews), 1) : 0;

// Address cleaning logic: removes country codes like "FRA" or "ITA"
$raw_city = $room['city_name'] ?? ""; 
$raw_address = $room['address'] ?? ""; 
$city_parts = explode(',', $raw_city);
$clean_city = trim($city_parts[0]); 

$real_address = $raw_address . ", " . $clean_city;
$real_address = trim($real_address, ", ");

// Google Maps Embed URL
$map_url = "https://maps.google.com/maps?q=" . urlencode($real_address) . "&t=&z=15&ie=UTF8&iwloc=&output=embed";

// Facilities mapping
$facilities_map = [
    'has_wifi' => ['label' => 'Wi-Fi', 'icon' => 'fa-wifi'],
    'has_workspace' => ['label' => 'Workspace', 'icon' => 'fa-laptop'],
    'has_ac' => ['label' => 'Air Conditioning', 'icon' => 'fa-snowflake'],
    'has_heating' => ['label' => 'Heating', 'icon' => 'fa-temperature-arrow-up'],
    'has_parking' => ['label' => 'Free Parking', 'icon' => 'fa-square-parking'],
    'has_self_checkin' => ['label' => 'Self Check-in', 'icon' => 'fa-key'],
    'has_elevator' => ['label' => 'Elevator', 'icon' => 'fa-elevator'],
    'has_kitchen' => ['label' => 'Kitchen', 'icon' => 'fa-kitchen-set'],
    'has_fridge' => ['label' => 'Refrigerator', 'icon' => 'fa-box'],
    'has_microwave' => ['label' => 'Microwave', 'icon' => 'fa-fire'],
    'has_cooking_basics' => ['label' => 'Cooking Basics', 'icon' => 'fa-utensils'],
    'has_dishes' => ['label' => 'Dishes', 'icon' => 'fa-plate-wheat'],
    'has_stove' => ['label' => 'Stove', 'icon' => 'fa-fire-burner'],
    'has_coffee_maker' => ['label' => 'Coffee Maker', 'icon' => 'fa-mug-hot'],
    'has_washing_machine' => ['label' => 'Washing Machine', 'icon' => 'fa-soap'],
    'has_dryer' => ['label' => 'Dryer', 'icon' => 'fa-shirt'],
    'has_iron' => ['label' => 'Iron', 'icon' => 'fa-shirt'],
    'has_hairdryer' => ['label' => 'Hair Dryer', 'icon' => 'fa-wind'],
    'has_hot_water' => ['label' => 'Hot Water', 'icon' => 'fa-faucet-drip'],
    'has_essentials' => ['label' => 'Essentials', 'icon' => 'fa-pump-soap'],
    'has_tv' => ['label' => 'TV', 'icon' => 'fa-tv'],
    'has_balcony' => ['label' => 'Balcony', 'icon' => 'fa-cloud-sun'],
    'has_pool' => ['label' => 'Pool', 'icon' => 'fa-person-swimming'],
    'has_jacuzzi' => ['label' => 'Jacuzzi', 'icon' => 'fa-hot-tub-person'],
    'has_smoke_alarm' => ['label' => 'Smoke Alarm', 'icon' => 'fa-bell'],
    'has_first_aid' => ['label' => 'First Aid', 'icon' => 'fa-suitcase-medical'],
    'is_pet_friendly' => ['label' => 'Pet Friendly', 'icon' => 'fa-paw'],
    'is_smoking_allowed' => ['label' => 'Smoking Allowed', 'icon' => 'fa-smoking']
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> - GoStay Premium</title>
    <link rel="stylesheet" href="../styles/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../styles/search_results.css">
    <link rel="stylesheet" href="../styles/room_details.css">
</head>
<body class="details-page-body">

    <nav class="results-nav">
        <div class="nav-left">
            <a href="search_results.php?city_id=<?php echo $room['city_id']; ?>" class="nav-logo"></a>
            <div class="nav-header-info">
                <h2 class="room-nav-title"><?php echo htmlspecialchars($room['name']); ?></h2>
                <div class="nav-header-meta">
                    <span class="nav-rating"><i class="fa-solid fa-star"></i> <?php echo $avg_rating; ?> (<?php echo count($reviews); ?> reviews)</span>
                    <span class="nav-divider">|</span>
                    <span class="nav-fast-booking"><i class="fa-solid fa-bolt"></i> Fast Booking</span>
                </div>
            </div>
        </div>
        <div class="nav-icons">
            <a href="home.php" title="Home"><i class="fa-solid fa-house"></i></a>
            <a href="../auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <div class="content-wrapper results-wrapper">
        <div class="top-split-layout">
            <div class="main-image-display">
                <?php if (!empty($photos)): ?>
                    <div class="slider-wrapper" id="sliderWrapper">
                        <?php foreach ($photos as $index => $photo): ?>
                            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>"
                                style="background-image: url('../../assets/<?php echo htmlspecialchars($photo['photo_url']); ?>');">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="slider-controls">
                        <button onclick="moveSlide(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                        <button onclick="moveSlide(1)"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="booking-column">
                <div class="booking-card-premium">
                    <div class="price-header-row">
                        <div class="price-main">
                            <span class="vibrant-price">
                                <?php echo number_format($room['price']); ?> RON
                            </span>
                            <span class="unit">/ night</span>
                        </div>
                        <div class="discount-tag">10% Off</div>
                    </div>

                    <div class="calendar-inline-container">
                        <label class="calendar-label">Select Stay Dates</label>
                        <div id="inlineCalendar"></div>
                    </div>

                    <div id="bookingSummary" class="booking-summary-box" style="display:none;">
                        <div class="calc-row"><span id="nightsCalc">0 nights</span><span id="subtotalVal">0 RON</span></div>
                        <div class="calc-row"><span>Service fee (5%)</span><span id="serviceFeeVal">0 RON</span></div>
                        <div class="calc-row discount"><span>Discount (10%)</span><span id="discountVal">-0 RON</span></div>
                        <div class="divider-summary"></div>
                        <div class="calc-row total"><span>Total Cost:</span><span id="totalVal">0 RON</span></div>
                    </div>

                    <button class="btn" id="reserveBtn" disabled>Check Availability</button>
                    <p class="safe-note">No payment needed now</p>
                </div>
            </aside>
        </div>

        <div class="details-bottom-grid">
            <div class="info-content">
                <h1 class="room-hero-title"><?php echo htmlspecialchars($room['name']); ?></h1>
                <p class="address-text"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($real_address); ?></p>

                <div class="amenities-comprehensive">
                    <h3>What this place offers</h3>
                    <div class="features-icon-grid">
                        <?php foreach ($facilities_map as $key => $f): ?>
                            <?php $is_available = isset($facilities_data[$key]) && $facilities_data[$key] == 1; ?>
                            <div class="feature-item <?php echo $is_available ? 'available' : 'unavailable'; ?>">
                                <i class="fa-solid <?php echo $f['icon']; ?>"></i>
                                <span><?php echo $f['label']; ?></span>
                                <?php if (!$is_available): ?><i class="fa-solid fa-xmark status-icon"></i><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="room-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($room['description'] ?? "No description available.")); ?></p>
                </div>

                <div class="reviews-section">
                    <h3>Guest Reviews (<?php echo count($reviews); ?>)</h3>
                    <div class="reviews-grid-details">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-card-mini">
                                    <div class="rev-header">
                                        <strong><?php echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']); ?></strong>
                                        <span class="rev-stars"><?php for($i=0; $i < $rev['rating']; $i++) echo '<i class="fa-solid fa-star"></i>'; ?></span>
                                    </div>
                                    <p class="rev-comment"><?php echo htmlspecialchars($rev['comment']); ?></p>
                                    <small class="rev-date"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-reviews-msg">No reviews yet for this stay.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="map-column">
                <div class="map-container-premium">
                    <iframe width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" src="<?php echo $map_url; ?>"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div id="reservationOverlay" class="res-overlay" style="display: none;">
        <div class="res-overlay-content">
            <div class="success-checkmark">
                <div class="check-icon">
                    <span class="icon-line line-tip"></span>
                    <span class="icon-line line-long"></span>
                    <div class="icon-circle"></div>
                    <div class="icon-fix"></div>
                </div>
            </div>
            <h2 class="res-confirm-title">Reservation Confirmed!</h2>
            <p class="res-confirm-text">You will be contacted by the host shortly.</p>
            <div class="res-timer-bar"></div>
        </div>
    </div>

    <script>
        const ROOM_CONF = {
            id: <?php echo $room_id; ?>,
            price: <?php echo $room['price']; ?>,
            bookedDates: [
                <?php foreach ($booked_dates as $r): ?>
                    { from: "<?php echo $r['check_in']; ?>", to: "<?php echo $r['check_out']; ?>" },
                <?php endforeach; ?>
            ]
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/room_details.js"></script>
    <?php include '../utils/includes/footer.php'; ?>
</body>
</html>