<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$all_cities_query = "SELECT id, name FROM cities ORDER BY name ASC";
$stmt_all = $db->prepare($all_cities_query);
$stmt_all->execute();
$dropdown_cities = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT * FROM cities ORDER BY RAND() LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoStay - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/home.css">
</head>

<body>

    <nav class="main-nav">
        <div class="nav-icons">
            <a href="#" title="Loyalty"><i class="fa-solid fa-crown"></i></a>
            <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'admin/hub.php' : 'profile.php'; ?>" title="Profile">
                <i class="fa-solid fa-user"></i>
            </a>
            <a href="#" title="Trending"><i class="fa-solid fa-fire"></i></a>
            <a href="#" title="My Bookings"><i class="fa-solid fa-calendar-days"></i></a>
            <a href="#" title="Messages"><i class="fa-solid fa-comment"></i></a>
            <a href="auth/logout.php" class="logout-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </nav>

    <div class="content-wrapper">

        <header class="hero-section">

            <div class="hero-logo">GoStay</div>

            <h1 class="hero-headline pulse-effect">
                Discover exclusive<br>
                accommodations worldwide
            </h1>

            <div class="search-bar-outer">
                <form action="search_results.php" method="GET" class="search-form-layout">

                    <div class="search-part-left">
                        <i class="fa-solid fa-location-dot"></i>
                        <select name="city_id" required>
                            <option value="" disabled selected>Where are you going?</option>
                            <?php foreach ($dropdown_cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-part-right">

                        <div class="input-item date-wrapper" id="checkin-wrapper">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            <div class="text-col">
                                <span class="label" id="checkin-label">Check-in</span>
                                <input type="date" name="check_in" id="checkin-input" style="position: absolute; visibility: hidden;">
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="input-item date-wrapper" id="checkout-wrapper">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <div class="text-col">
                                <span class="label" id="checkout-label">Check-out</span>
                                <input type="date" name="check_out" id="checkout-input" style="position: absolute; visibility: hidden;">
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="input-item guests-wrapper">
                            <i class="fa-solid fa-user-group"></i>
                            <span class="label">Guests</span>
                            <input type="number" name="guests" min="1" value="2" class="guests-input">
                        </div>

                        <button type="submit" class="btn search-btn-custom">
                            Search <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
                        </button>
                    </div>
                </form>
            </div>
        </header>

        <section class="destinations-grid">
            <?php foreach ($featured_cities as $city): ?>

                <div class="city-card">
                    <?php
                    $db_image = $city['image_url'];

                    if (empty($db_image)) {
                        $img = 'assets/img/placeholder.jpg';
                    } elseif (strpos($db_image, 'http') === 0) {
                        $img = $db_image;
                    } else {
                        $filename = basename($db_image);
                        $img = '../assets/uploads/cities/' . $filename;
                    }
                    ?>

                    <div class="card-image" style="background-image: url('<?php echo $img; ?>');"></div>

                    <div class="card-info">
                        <span class="card-date">Available Now</span>
                        <span class="card-name"><?php echo htmlspecialchars($city['name']); ?></span>
                    </div>
                </div>

            <?php endforeach; ?>
        </section>

        <section class="contact-teaser">
            <div class="teaser-card">
                <div class="teaser-text">
                    <h3>Need help with your booking?</h3>
                    <p>Our concierge team is here for you.</p>
                </div>
                <a href="support/contact.php" class="btn teaser-btn">Contact Support</a>
            </div>
        </section>

    </div>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <span class="footer-logo">GoStay</span>
                <p class="footer-desc">Your premium partner for exclusive worldwide stays. Experience luxury like never before.</p>
                <span class="copyright">© <?php echo date('Y'); ?> GoStay Inc. All rights reserved.</span>
            </div>

            <div class="footer-column">
                <h4>Company</h4>
                <a href="#">About Us</a>
                <a href="#">Careers</a>
                <a href="#">Press</a>
                <a href="#">Blog</a>
            </div>

            <div class="footer-column">
                <h4>Support</h4>
                <a href="#">Help Center</a>
                <a href="#">Safety</a>
                <a href="#">Cancellation</a>
                <a href="#">Concierge</a>
            </div>

            <div class="footer-column">
                <h4>Legal</h4>
                <a href="#">Terms</a>
                <a href="#">Privacy</a>
                <a href="#">Cookies</a>
                <a href="#">Sitemap</a>
            </div>

            <div class="footer-social">
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-x"></i></a>
                <a href="#"><i class="fa-brands fa-facebook"></i></a>
            </div>
        </div>
    </footer>

    <div class="floating-logo"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function setupDatePicker(wrapperId, inputId, labelId, defaultText) {
                const wrapper = document.getElementById(wrapperId);
                const input = document.getElementById(inputId);
                const label = document.getElementById(labelId);

                wrapper.addEventListener('click', function() {
                    try {
                        input.showPicker();
                    } catch (error) {
                        input.click();
                        input.focus();
                    }
                });

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
            setupDatePicker('checkin-wrapper', 'checkin-input', 'checkin-label', 'Check-in');
            setupDatePicker('checkout-wrapper', 'checkout-input', 'checkout-label', 'Check-out');
        });
    </script>

</body>

</html>