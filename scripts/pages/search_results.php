<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/weather_logic.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Connection error");
}

// Fetch Cities for Dropdown
$cities_query = "SELECT id, name FROM cities ORDER BY name ASC";
$stmt_cities = $conn->prepare($cities_query);
$stmt_cities->execute();
$dropdown_cities = $stmt_cities->fetchAll(PDO::FETCH_ASSOC);

// Get Parameters
$city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : '';
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 2;

// Build Query
// We select room details, the primary photo, average rating, and review count.
$sql = "SELECT 
            r.*, 
            c.name as city_name,
            (SELECT photo_url 
             FROM room_photos 
             WHERE room_id = r.id 
             ORDER BY is_primary DESC, id ASC 
             LIMIT 1) as main_photo,
            (SELECT AVG(rating) FROM reviews WHERE room_id = r.id) as avg_rating,
            (SELECT COUNT(id) FROM reviews WHERE room_id = r.id) as review_count
        FROM rooms r
        JOIN cities c ON r.city_id = c.id
        WHERE 1=1";

$params = [];

// Filter by City
if (!empty($city_id)) {
    $sql .= " AND r.city_id = ?";
    $params[] = $city_id;
}

// Filter by Capacity
if ($guests > 0) {
    $sql .= " AND r.capacity >= ?";
    $params[] = $guests;
}

// Filter by Availability (Exclude rooms with overlapping reservations)
if (!empty($check_in) && !empty($check_out)) {
    $sql .= " AND r.id NOT IN (
                SELECT room_id 
                FROM reservations 
                WHERE status IN ('confirmed', 'pending')
                AND (
                    (check_in < ? AND check_out > ?) 
                )
              )";
    $params[] = $check_out;
    $params[] = $check_in;
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die($e->getMessage());
}

// 4. Weather Data Logic
$weather_city_name = "Bucharest"; // Default
if (!empty($city_id)) {
    foreach ($dropdown_cities as $dc) {
        if ($dc['id'] == $city_id) {
            $weather_city_name = $dc['name'];
            break;
        }
    }
}

$coords = getCityCoordinates($weather_city_name);
$forecast = null;
if ($coords) {
    $forecast = getWeatherData($coords['lat'], $coords['lon']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay - Search Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../styles/search_results.css">
    <link rel="stylesheet" href="../../styles/footer.css">
</head>

<body>

    <nav class="results-nav">
        <div class="nav-left">
            <a class="nav-logo"></a>

            <div class="search-bar-outer compact-search">
                <form action="search_results.php" method="GET" class="search-form-layout">

                    <div class="search-part-left">
                        <i class="fa-solid fa-location-dot"></i>
                        <select name="city_id">
                            <option value="" disabled <?php echo empty($city_id) ? 'selected' : ''; ?>>Where?</option>
                            <?php foreach ($dropdown_cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>" <?php echo ($city_id == $city['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-part-right">
                        <div class="input-item date-wrapper" id="checkin-wrapper">
                            <div class="text-col">
                                <span class="label" id="checkin-label"><?php echo $check_in ? $check_in : 'Check-in'; ?></span>
                                <input type="date" name="check_in" id="checkin-input" value="<?php echo $check_in; ?>" class="date-input-overlay">
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="input-item date-wrapper" id="checkout-wrapper">
                            <div class="text-col">
                                <span class="label" id="checkout-label"><?php echo $check_out ? $check_out : 'Check-out'; ?></span>
                                <input type="date" name="check_out" id="checkout-input" value="<?php echo $check_out; ?>" class="date-input-overlay">
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="input-item guests-wrapper">
                            <i class="fa-solid fa-user-group"></i>
                            <input type="number" name="guests" min="1" value="<?php echo $guests; ?>" class="guests-input">
                        </div>

                        <button type="submit" class="btn search-btn-custom">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="nav-icons">
            <a href="#" title="Loyalty"><i class="fa-solid fa-crown"></i></a>
            <a href="home.php" title="Home"><i class="fa-solid fa-house"></i></a>
            <a href="../auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <div class="content-wrapper results-wrapper">
        <div class="layout-grid">

            <main class="results-column">
                <div class="results-header-text">
                    <h2>Stays found: <?php echo count($rooms); ?></h2>
                    <?php if (!empty($check_in) && !empty($check_out)): ?>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Available from <?php echo htmlspecialchars($check_in); ?> to <?php echo htmlspecialchars($check_out); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (count($rooms) > 0): ?>
                    <div class="cards-grid-2col">
                        <?php foreach ($rooms as $room): ?>
                            <?php
                            // Image Path Logic
                            $image_to_show = '../../assets/img/placeholder.jpg';
                            $photo_db = $room['main_photo'];
                            if (!empty($photo_db)) {
                                if (strpos($photo_db, 'http') === 0) {
                                    $image_to_show = $photo_db;
                                } elseif (strpos($photo_db, 'uploads/') === 0) {
                                    $image_to_show = '../../assets/' . $photo_db;
                                } elseif (strpos($photo_db, 'assets/') === 0) {
                                    $image_to_show = '../../' . $photo_db;
                                } else {
                                    $image_to_show = '../../assets/uploads/rooms/' . $photo_db;
                                }
                            }

                            // Rating Logic
                            $avg_rating = $room['avg_rating'];
                            $review_count = $room['review_count'];
                            $rating_display = ($review_count > 0) ? number_format($avg_rating, 2) : 'New';
                            $count_display = ($review_count > 0) ? "($review_count reviews)" : '';
                            ?>

                            <div class="modern-card">
                                <div class="card-image-header" style="background-image: url('<?php echo htmlspecialchars($image_to_show); ?>');">
                                    <div class="top-badges">
                                        <button class="fav-icon"><i class="fa-regular fa-heart"></i></button>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="rating-pill">
                                        <i class="fa-solid fa-star"></i> <?php echo $rating_display; ?>
                                        <span class="reviews"><?php echo $count_display; ?></span>
                                    </div>

                                    <h3 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h3>
                                    <p class="card-location">
                                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($room['city_name']); ?>
                                    </p>

                                    <div class="card-meta">
                                        <span><i class="fa-solid fa-user-group"></i> <?php echo $room['capacity']; ?> guests</span>
                                        <span><i class="fa-solid fa-bed"></i> <?php echo $room['bedrooms']; ?> bedrooms</span>
                                        <span><i class="fa-solid fa-bath"></i> <?php echo $room['bathrooms']; ?> baths</span>
                                    </div>

                                    <div class="card-footer-row">
                                        <div class="price-box">
                                            <span class="price-val"><?php echo number_format($room['price']); ?> RON</span>
                                            <span class="price-unit">/ night</span>
                                        </div>
                                        <a href="room_details.php?id=<?php echo $room['id']; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>" class="btn card-btn-custom">Book Now</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="glass-card no-results">
                        <h3>No accommodations available for these dates.</h3>
                        <p>Try changing your check-in/check-out dates or location.</p>
                    </div>
                <?php endif; ?>
            </main>

            <aside class="sidebar-column">

                <?php
                require_once '../utils/weather_logic.php';

                $weather_city_name = "Bucharest"; 
                if (!empty($city_id)) {
                    foreach ($dropdown_cities as $dc) {
                        if ($dc['id'] == $city_id) {
                            $weather_city_name = $dc['name'];
                            break;
                        }
                    }
                }

                $coords = getCityCoordinates($weather_city_name);
                $forecast = null;
                if ($coords) {
                    $forecast = getWeatherData($coords['lat'], $coords['lon']);
                }
                ?>

                <div class="accu-weather-widget">
                    <div class="accu-header">
                        <div>
                            <h4><?php echo htmlspecialchars($weather_city_name); ?></h4>
                            <span><?php echo date('F Y'); ?> Forecast</span>
                        </div>
                        <img src="../../assets/img/logo.png" style="height:30px; opacity:0.8;">
                    </div>

                    <div class="accu-calendar">
                        <div class="weekdays-row">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>

                        <div class="days-grid">
                            <?php
                            if ($forecast && isset($forecast['daily']['time'])):
                                $total_days = count($forecast['daily']['time']);

                                // Calculate starting index based on the first day of the month
                                $first_day_timestamp = strtotime($forecast['daily']['time'][0]);
                                $start_day_index = date('w', $first_day_timestamp); // 0 (Sun) - 6 (Sat)

                                // Empty cells before the first day
                                for ($k = 0; $k < $start_day_index; $k++) {
                                    echo '<div class="day-cell empty"></div>';
                                }

                               
                                for ($i = 0; $i < $total_days; $i++):
                                    if (!isset($forecast['daily']['time'][$i])) continue;

                                    $date = $forecast['daily']['time'][$i];
                                    $max = round($forecast['daily']['temperature_2m_max'][$i]);
                                    $min = round($forecast['daily']['temperature_2m_min'][$i]);
                                    $code = $forecast['daily']['weathercode'][$i];

                                    $day_num = date('j', strtotime($date));
                                    $style = getWeatherIcon($code);

                                    // Highlight current day
                                    $is_today = ($date == date('Y-m-d')) ? 'today-active' : '';
                            ?>
                                    <div class="day-cell <?php echo $is_today; ?>">
                                        <span class="cell-date"><?php echo $day_num; ?></span>
                                        <i class="fa-solid <?php echo $style['icon']; ?>" style="color: <?php echo $style['color']; ?>;"></i>
                                        <div class="cell-temps">
                                            <span class="high"><?php echo $max; ?>°</span>
                                            <span class="low"><?php echo $min; ?>°</span>
                                        </div>
                                    </div>
                                <?php endfor;
                            else: ?>
                                <p style="grid-column: 1/-1; padding: 20px; text-align: center;">Weather data unavailable.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="accu-footer">
                        <small>Powered by Open-Meteo Parsed Data</small>
                    </div>
                </div>

            </aside>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupDateLabel(inputId, labelId, defaultText) {
                const input = document.getElementById(inputId);
                const label = document.getElementById(labelId);

                input.addEventListener('change', function() {
                    if (this.value) {
                        label.textContent = this.value;
                        label.style.color = '#7b2bd4';
                        label.style.fontWeight = 'bold';
                    } else {
                        label.textContent = defaultText;
                        label.style.color = '#333';
                    }
                });
            }
            setupDateLabel('checkin-input', 'checkin-label', 'Check-in');
            setupDateLabel('checkout-input', 'checkout-label', 'Check-out');
        });
    </script>

    <?php include '../utils/includes/footer.php'; ?>

</body>

</html>