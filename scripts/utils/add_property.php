<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include_once '../utils/tracker.php';

// Security: Check if manager is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Fetch Cities for dropdown
$cities = $db->query("SELECT id, name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Insert room data - automatically linked to logged in user
        $stmt = $db->prepare("INSERT INTO rooms (user_id, city_id, name, description, address, price, capacity, bedrooms, bathrooms, check_in_time, check_out_time) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['city_id'],
            $_POST['name'],
            $_POST['description'],
            $_POST['address'],
            $_POST['price'],
            $_POST['capacity'],
            $_POST['bedrooms'],
            $_POST['bathrooms'],
            $_POST['check_in_time'],
            $_POST['check_out_time']
        ]);
        $roomId = $db->lastInsertId();

        // Insert facilities data
        $fac_fields = [
            'has_wifi',
            'has_workspace',
            'has_ac',
            'has_heating',
            'has_parking',
            'has_self_checkin',
            'has_elevator',
            'has_kitchen',
            'has_fridge',
            'has_microwave',
            'has_cooking_basics',
            'has_dishes',
            'has_stove',
            'has_coffee_maker',
            'has_washing_machine',
            'has_dryer',
            'has_iron',
            'has_hairdryer',
            'has_hot_water',
            'has_essentials',
            'has_tv',
            'has_balcony',
            'has_pool',
            'has_jacuzzi',
            'has_smoke_alarm',
            'has_first_aid',
            'is_pet_friendly',
            'is_smoking_allowed'
        ];

        $placeholders = str_repeat('?,', count($fac_fields)) . '?';
        $sql_fac = "INSERT INTO facilities (room_id, " . implode(',', $fac_fields) . ") VALUES ($placeholders)";

        $fac_values = [$roomId];
        foreach ($fac_fields as $f) {
            $fac_values[] = isset($_POST[$f]) ? 1 : 0;
        }
        $db->prepare($sql_fac)->execute($fac_values);

        // Process photo uploads
        if (!empty($_FILES['photos']['name'][0])) {
            $upload_dir = '../../assets/uploads/rooms/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                $file_name = time() . '_' . $_FILES['photos']['name'][$key];
                $target_file = $upload_dir . $file_name;
                $db_path = 'uploads/rooms/' . $file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $is_primary = ($key === 0) ? 1 : 0;
                    $stmt_img = $db->prepare("INSERT INTO room_photos (room_id, photo_url, is_primary) VALUES (?, ?, ?)");
                    $stmt_img->execute([$roomId, $db_path, $is_primary]);
                }
            }
        }

        $db->commit();
        header("Location: ../pages/manager_dashboard.php?success=added");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Property - GoStay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/manager.css">
    <link rel="stylesheet" href="../styles/search_results.css">
    <link rel="stylesheet" href="../styles/footer.css">
</head>

<body style="background: #f4f7ff;">
    <nav class="results-nav">
        <div class="nav-left">
            <div class="nav-logo"></div>
            <h2 class="manager-header-text">Add New Property</h2>
        </div>
        <div class="nav-icons">
            <a href="../pages/manager_dashboard.php"><i class="fa-solid fa-xmark"></i></a>
        </div>
    </nav>

    <div class="results-wrapper">
        <div class="property-form-container">
            <div class="manager-card-section">
                <form method="POST" enctype="multipart/form-data" id="propertyForm">
                    <h2 class="form-section-title">General information</h2>
                    <div class="form-grid-layout">
                        <div class="input-group">
                            <label>Property name</label>
                            <input type="text" name="name" required placeholder="e.g. Modern Apartment">
                        </div>
                        <div class="input-group">
                            <label>City</label>
                            <select name="city_id" required>
                                <?php foreach ($cities as $c): echo "<option value='{$c['id']}'>{$c['name']}</option>";
                                endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Price per night (RON)</label>
                            <input type="number" name="price" required>
                        </div>
                        <div class="input-group"><label>Address</label><input type="text" name="address" required></div>
                        <div class="input-group"><label>Guests</label><input type="number" name="capacity" value="2"></div>
                        <div class="input-group"><label>Bedrooms</label><input type="number" name="bedrooms" value="1"></div>
                        <div class="input-group"><label>Bathrooms</label><input type="number" name="bathrooms" value="1"></div>
                        <div class="input-group"><label>Check-in</label><input type="time" name="check_in_time" value="14:00"></div>
                        <div class="input-group"><label>Check-out</label><input type="time" name="check_out_time" value="11:00"></div>
                    </div>

                    <h2 class="form-section-title">Description</h2>
                    <textarea name="description" rows="4" style="width:100%; border-radius:15px; padding:15px; border:1px solid #ddd;" placeholder="Describe your property..."></textarea>

                    <h2 class="form-section-title">Photos (Drag and drop)</h2>
                    <div class="upload-zone" id="dropZone">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Click or drag photos here (First photo will be the cover)</p>
                        <input type="file" name="photos[]" id="fileInput" multiple hidden accept="image/*">
                    </div>
                    <div id="previewContainer" class="preview-container"></div>

                    <h2 class="form-section-title">Facilities</h2>
                    <div class="facilities-container">
                        <?php
                        $fac_list = [
                            'has_wifi' => ['WiFi', 'fa-wifi'],
                            'has_workspace' => ['Workspace', 'fa-laptop'],
                            'has_ac' => ['AC', 'fa-snowflake'],
                            'has_heating' => ['Heating', 'fa-fire-alt'],
                            'has_parking' => ['Parking', 'fa-car'],
                            'has_self_checkin' => ['Self check-in', 'fa-key'],
                            'has_elevator' => ['Elevator', 'fa-arrow-up-wide-short'],
                            'has_kitchen' => ['Kitchen', 'fa-kitchen-set'],
                            'has_fridge' => ['Fridge', 'fa-refrigerator'],
                            'has_microwave' => ['Microwave', 'fa-microchip'],
                            'has_cooking_basics' => ['Cooking basics', 'fa-utensils'],
                            'has_dishes' => ['Dishes', 'fa-plate-wheat'],
                            'has_stove' => ['Stove', 'fa-fire-burner'],
                            'has_coffee_maker' => ['Coffee maker', 'fa-coffee'],
                            'has_washing_machine' => ['Laundry', 'fa-soap'],
                            'has_dryer' => ['Dryer', 'fa-wind'],
                            'has_iron' => ['Iron', 'fa-shirt'],
                            'has_hairdryer' => ['Hairdryer', 'fa-scissors'],
                            'has_hot_water' => ['Hot water', 'fa-faucet-hot'],
                            'has_essentials' => ['Essentials', 'fa-box-open'],
                            'has_tv' => ['TV', 'fa-tv'],
                            'has_balcony' => ['Balcony', 'fa-sun'],
                            'has_pool' => ['Pool', 'fa-person-swimming'],
                            'has_jacuzzi' => ['Jacuzzi', 'fa-hot-tub-person'],
                            'has_smoke_alarm' => ['Smoke alarm', 'fa-bell'],
                            'has_first_aid' => ['First aid', 'fa-kit-medical'],
                            'is_pet_friendly' => ['Pet friendly', 'fa-paw'],
                            'is_smoking_allowed' => ['Smoking allowed', 'fa-smoking']
                        ];
                        foreach ($fac_list as $key => $details): ?>
                            <label class="fac-checkbox-item">
                                <input type="checkbox" name="<?= $key ?>">
                                <i class="fa-solid <?= $details[1] ?>" style="color: #7b2bd4; width: 20px; text-align: center;"></i>
                                <?= $details[0] ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn" style="width:100%; margin-top:30px; padding:20px;">Publish property</button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../utils/includes/footer.php'; ?>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        dropZone.onclick = () => fileInput.click();
        fileInput.onchange = (e) => handleFiles(e.target.files);
        dropZone.ondragover = (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        };
        dropZone.ondragleave = () => dropZone.classList.remove('dragover');
        dropZone.ondrop = (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            handleFiles(e.dataTransfer.files);
        };

        function handleFiles(files) {
            previewContainer.innerHTML = '';
            [...files].forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-img';
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>

</html>