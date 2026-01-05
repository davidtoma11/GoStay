<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Room.php';

$database = new Database();
$db = $database->getConnection();
$roomModel = new Room($db);

// Get Room ID and Context
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';

// Fetch Data via Model
$room = $roomModel->getDetails($room_id);
if (!$room) { header("Location: home.php"); exit; }

$photos = $roomModel->getPhotos($room_id);
$facilities = $roomModel->getFacilities($room_id);

// Facility UI Map
$fac_map = [
    'has_wifi' => ['label' => 'Wi-Fi', 'icon' => 'fa-wifi'],
    'has_ac' => ['label' => 'Air Conditioning', 'icon' => 'fa-snowflake'],
    'has_kitchen' => ['label' => 'Full Kitchen', 'icon' => 'fa-kitchen-set'],
    'has_tv' => ['label' => 'Smart TV', 'icon' => 'fa-tv'],
    'has_pool' => ['label' => 'Private Pool', 'icon' => 'fa-person-swimming'],
    'is_pet_friendly' => ['label' => 'Pets Welcome', 'icon' => 'fa-paw'],
    'has_parking' => ['label' => 'Free Parking', 'icon' => 'fa-square-parking'],
    'has_workspace' => ['label' => 'Dedicated Workspace', 'icon' => 'fa-laptop']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> - GoStay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/room_details.css"> 
    <link rel="stylesheet" href="../styles/footer.css"> 
</head>
<body>
    <nav class="details-nav">
        <a href="javascript:history.back()" class="back-link"><i class="fa-solid fa-chevron-left"></i> Back</a>
        <div class="logo">GoStay</div>
    </nav>

    <main class="room-container">
        <section class="room-gallery">
            <?php if (!empty($photos)): ?>
                <div class="main-photo" style="background-image: url('../../assets/uploads/rooms/<?php echo $photos[0]['photo_url']; ?>');"></div>
                <div class="side-photos">
                    <?php for($i=1; $i<min(3, count($photos)); $i++): ?>
                        <div class="small-photo" style="background-image: url('../../assets/uploads/rooms/<?php echo $photos[$i]['photo_url']; ?>');"></div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="room-content">
            <div class="room-main-info">
                <h1><?php echo htmlspecialchars($room['name']); ?></h1>
                <p class="location-tag"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($room['city_name']); ?></p>
                
                <div class="capacity-pills">
                    <span><i class="fa-solid fa-user"></i> <?php echo $room['capacity']; ?> guests</span>
                    <span><i class="fa-solid fa-bed"></i> <?php echo $room['bedrooms']; ?> Bedrooms</span>
                    <span><i class="fa-solid fa-bath"></i> <?php echo $room['bathrooms']; ?> Baths</span>
                </div>

                <hr>

                <section class="amenities-section">
                    <h3>What this place offers</h3>
                    <div class="amenities-grid">
                        <?php foreach($fac_map as $key => $meta): ?>
                            <?php if(!empty($facilities[$key])): ?>
                                <div class="amenity-card">
                                    <i class="fa-solid <?php echo $meta['icon']; ?>"></i>
                                    <span><?php echo $meta['label']; ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <aside class="booking-sidebar">
                <div class="price-header">
                    <span class="price"><?php echo number_format($room['price']); ?> RON</span> <small>/ night</small>
                </div>
                <form action="process_booking.php" method="POST" class="booking-form">
                    <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                    <div class="date-inputs">
                        <div class="input-group">
                            <label>CHECK-IN</label>
                            <input type="date" name="check_in" value="<?php echo $check_in; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>CHECK-OUT</label>
                            <input type="date" name="check_out" value="<?php echo $check_out; ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-reserve">Confirm Reservation</button>
                </form>
            </aside>
        </div>
    </main>

    <?php include '../utils/includes/footer.php'; ?>
</body>
</html>