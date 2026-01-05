<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Room.php'; // New Requirement
require_once __DIR__ . '/../utils/weather_logic.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Connection error");
}

// Instantiate the Room model
$roomModel = new Room($conn);

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

// Fetch Cities for dropdown
$cities_query = "SELECT id, name FROM cities ORDER BY name ASC";
$stmt_cities = $conn->prepare($cities_query);
$stmt_cities->execute();
$dropdown_cities = $stmt_cities->fetchAll(PDO::FETCH_ASSOC);

// Get Parameters
$city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : '';
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 2;
$selected_facilities = isset($_GET['facilities']) ? $_GET['facilities'] : [];

// Use Room Model to fetch results (SQL Logic moved to Room.php)
$rooms = $roomModel->search($city_id, $guests, $check_in, $check_out, $selected_facilities);

// Weather Logic
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay - Search Results</title>
    <link rel="stylesheet" href="../styles/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/search_results.css">
</head>
<body>

    <nav class="results-nav">
        <div class="nav-left">
            <a href="home.php" class="nav-logo"></a>

            <div class="search-bar-outer compact-search">
                <form action="search_results.php" method="GET" class="search-form-layout" id="searchForm">
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
                        <div class="input-item date-wrapper">
                            <div class="text-col">
                                <span class="label" id="checkin-label"><?php echo $check_in ? $check_in : 'Check-in'; ?></span>
                                <input type="date" name="check_in" id="checkin-input" value="<?php echo $check_in; ?>" class="date-input-overlay">
                            </div>
                        </div>
                        <div class="divider"></div>
                        <div class="input-item date-wrapper">
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

                        <button type="submit" class="btn search-btn-custom" title="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>

                        <div class="filter-btn-wrapper">
                            <button type="button" class="btn search-btn-custom" id="toggleFilters" style="margin-left: 10px;" title="Filter Amenities">
                                <i class="fa-solid fa-sliders"></i>
                            </button>

                            <div class="filters-dropdown" id="filtersDropdown">
                                <h4>Amenities</h4>
                                <div class="filters-grid">
                                    <?php foreach ($facilities_map as $col_name => $details): ?>
                                        <label class="filter-option">
                                            <input type="checkbox" name="facilities[]" value="<?php echo $col_name; ?>"
                                                <?php echo in_array($col_name, $selected_facilities) ? 'checked' : ''; ?>>
                                            <i class="fa-solid <?php echo $details['icon']; ?>" style="width:20px; text-align:center;"></i>
                                            <?php echo $details['label']; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="filter-actions">
                                    <button type="button" id="resetFilters" style="background:none; border:none; color:#888; cursor:pointer; font-size:0.9rem; text-decoration:underline;">Reset</button>
                                    <button type="submit" class="btn-apply">Apply Filters</button>
                                </div>
                            </div>
                        </div>
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
                </div>

                <?php if (count($rooms) > 0): ?>
                    <div class="cards-grid-2col">
                        <?php foreach ($rooms as $room): ?>
                            <?php
                            $image_to_show = '../../assets/img/placeholder.jpg';
                            if (!empty($room['main_photo'])) {
                                $photo = $room['main_photo'];
                                if (strpos($photo, 'http') === 0) $image_to_show = $photo;
                                elseif (strpos($photo, 'uploads/') === 0) $image_to_show = '../../assets/' . $photo;
                                else $image_to_show = '../../assets/uploads/rooms/' . $photo;
                            }
                            $rating_display = ($room['review_count'] > 0) ? number_format($room['avg_rating'], 2) : 'New';
                            ?>
                            <div class="modern-card">
                                <div class="card-image-header" style="background-image: url('<?php echo htmlspecialchars($image_to_show); ?>');">
                                    <div class="top-badges"><button class="fav-icon"><i class="fa-regular fa-heart"></i></button></div>
                                </div>
                                <div class="card-body">
                                    <div class="rating-pill">
                                        <i class="fa-solid fa-star"></i> <?php echo $rating_display; ?>
                                        <span class="reviews"><?php echo ($room['review_count'] > 0) ? "({$room['review_count']} reviews)" : ''; ?></span>
                                    </div>
                                    <h3 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h3>
                                    <p class="card-location"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($room['city_name']); ?></p>
                                    
                                    <div class="card-meta">
                                        <span><i class="fa-solid fa-user-group"></i> <?php echo $room['capacity']; ?> guests</span>
                                        <span><i class="fa-solid fa-bed"></i> <?php echo $room['bedrooms'] . ' ' . ($room['bedrooms'] == 1 ? 'bedroom' : 'bedrooms'); ?></span>
                                        <span><i class="fa-solid fa-bath"></i> <?php echo $room['bathrooms'] . ' ' . ($room['bathrooms'] == 1 ? 'bath' : 'baths'); ?></span>
                                    </div>

                                    <div class="card-footer-row">
                                        <div class="price-box">
                                            <span class="price-val"><?php echo number_format($room['price']); ?> RON</span>
                                            <span class="price-unit">/ night</span>
                                        </div>
                                        <a href="room_details.php?id=<?php echo $room['id']; ?>" class="btn card-btn-custom">Book Now</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="glass-card no-results">
                        <h3>No matches found.</h3>
                        <a href="search_results.php?city_id=<?php echo $city_id; ?>" style="color: #7b2bd4; font-weight: bold;">Clear All Filters</a>
                    </div>
                <?php endif; ?>
            </main>

            <aside class="sidebar-column">
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
                                $start_day_index = date('w', strtotime($forecast['daily']['time'][0]));
                                for ($k = 0; $k < $start_day_index; $k++) echo '<div class="day-cell empty"></div>';
                                for ($i = 0; $i < $total_days; $i++):
                                    $style = getWeatherIcon($forecast['daily']['weathercode'][$i]);
                                    $is_today = ($forecast['daily']['time'][$i] == date('Y-m-d')) ? 'today-active' : '';
                            ?>
                                    <div class="day-cell <?php echo $is_today; ?>">
                                        <span class="cell-date"><?php echo date('j', strtotime($forecast['daily']['time'][$i])); ?></span>
                                        <i class="fa-solid <?php echo $style['icon']; ?>" style="color: <?php echo $style['color']; ?>;"></i>
                                        <div class="cell-temps">
                                            <span class="high"><?php echo round($forecast['daily']['temperature_2m_max'][$i]); ?>°</span>
                                            <span class="low"><?php echo round($forecast['daily']['temperature_2m_min'][$i]); ?>°</span>
                                        </div>
                                    </div>
                                <?php endfor;
                            endif; ?>
                        </div>
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

            const toggleBtn = document.getElementById('toggleFilters');
            const dropdown = document.getElementById('filtersDropdown');
            const resetBtn = document.getElementById('resetFilters');

            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
                toggleBtn.classList.toggle('active');
            });

            resetBtn.addEventListener('click', () => {
                dropdown.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            });

            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target) && e.target !== toggleBtn) {
                    dropdown.style.display = 'none';
                    toggleBtn.classList.remove('active');
                }
            });
            dropdown.addEventListener('click', (e) => e.stopPropagation());
        });
    </script>

    <?php include '../utils/includes/footer.php'; ?>
</body>
</html>