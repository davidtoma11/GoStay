<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include_once '../utils/tracker.php';

// Security: Check session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$roomId = $_GET['id'] ?? null;
if (!$roomId) {
    header("Location: ../pages/manager_dashboard.php");
    exit;
}

// Fetch current data and verify ownership
$room = $db->prepare("SELECT * FROM rooms WHERE id = ? AND user_id = ?");
$room->execute([$roomId, $_SESSION['user_id']]);
$roomData = $room->fetch(PDO::FETCH_ASSOC);

if (!$roomData) {
    die("Access denied or property not found.");
}

// Delete single photo instantly
if (isset($_POST['delete_photo_id'])) {
    $photoId = $_POST['delete_photo_id'];

    // Verify photo belongs to this specific room
    $stmt_get_photo = $db->prepare("SELECT photo_url FROM room_photos WHERE id = ? AND room_id = ?");
    $stmt_get_photo->execute([$photoId, $roomId]);
    $photoPath = $stmt_get_photo->fetchColumn();

    if ($photoPath) {
        $fullPath = '../../assets/' . $photoPath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $stmt_del_photo = $db->prepare("DELETE FROM room_photos WHERE id = ?");
        $stmt_del_photo->execute([$photoId]);
    }
    header("Location: edit_property.php?id=$roomId&success=photo_deleted");
    exit;
}

// Delete entire property logic
if (isset($_POST['confirm_delete_property'])) {
    // Only delete if user owns it
    $stmt_del = $db->prepare("DELETE FROM rooms WHERE id = ? AND user_id = ?");
    if ($stmt_del->execute([$roomId, $_SESSION['user_id']])) {
        header("Location: ../pages/manager_dashboard.php?success=deleted");
        exit;
    }
}

// Fetch related data
$fac = $db->prepare("SELECT * FROM facilities WHERE room_id = ?");
$fac->execute([$roomId]);
$facData = $fac->fetch(PDO::FETCH_ASSOC);

$photos = $db->prepare("SELECT * FROM room_photos WHERE room_id = ?");
$photos->execute([$roomId]);
$roomPhotos = $photos->fetchAll(PDO::FETCH_ASSOC);

$cities = $db->query("SELECT id, name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Save changes logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_photo_id']) && !isset($_POST['confirm_delete_property'])) {
    try {
        $db->beginTransaction();

        // Update Rooms Table - Ownership already checked by $roomData check above
        $stmt_upd = $db->prepare("UPDATE rooms SET city_id=?, name=?, description=?, address=?, price=?, capacity=?, bedrooms=?, bathrooms=?, check_in_time=?, check_out_time=? WHERE id=? AND user_id=?");
        $stmt_upd->execute([
            $_POST['city_id'],
            $_POST['name'],
            $_POST['description'],
            $_POST['address'],
            $_POST['price'],
            $_POST['capacity'],
            $_POST['bedrooms'],
            $_POST['bathrooms'],
            $_POST['check_in_time'],
            $_POST['check_out_time'],
            $roomId,
            $_SESSION['user_id']
        ]);

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

        $set_parts = [];
        $upd_vals = [];
        foreach ($fac_fields as $f) {
            $set_parts[] = "$f = ?";
            $upd_vals[] = isset($_POST[$f]) ? 1 : 0;
        }
        $upd_vals[] = $roomId;
        $sql_fac_upd = "UPDATE facilities SET " . implode(', ', $set_parts) . " WHERE room_id = ?";
        $db->prepare($sql_fac_upd)->execute($upd_vals);

        if (!empty($_FILES['photos']['name'][0])) {
            $upload_dir = '../../assets/uploads/rooms/';
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                $file_name = time() . '_' . $_FILES['photos']['name'][$key];
                if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                    $db_path = 'uploads/rooms/' . $file_name;
                    $stmt_img = $db->prepare("INSERT INTO room_photos (room_id, photo_url, is_primary) VALUES (?, ?, 0)");
                    $stmt_img->execute([$roomId, $db_path]);
                }
            }
        }

        $db->commit();
        header("Location: edit_property.php?id=$roomId&success=updated");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Property - GoStay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/manager.css">
    <link rel="stylesheet" href="../styles/search_results.css">
    <link rel="stylesheet" href="../styles/footer.css">
</head>

<body style="background: #f4f7ff;">
    <nav class="results-nav">
        <div class="nav-left">
            <div class="nav-logo"></div>
            <h2 class="manager-header-text">Edit Property</h2>
        </div>
        <div class="nav-icons"><a href="../pages/manager_dashboard.php"><i class="fa-solid fa-arrow-left"></i></a></div>
    </nav>

    <div class="results-wrapper">
        <div class="property-form-container">
            <div class="manager-card-section">
                <?php if (isset($_GET['success'])): ?>
                    <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <?php if ($_GET['success'] == 'updated') echo "Changes saved successfully";
                        if ($_GET['success'] == 'photo_deleted') echo "Photo removed"; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-section-title">General information</div>
                    <div class="form-grid-layout">
                        <div class="input-group"><label>Property name</label><input type="text" name="name" value="<?= htmlspecialchars($roomData['name']) ?>" required></div>
                        <div class="input-group"><label>City</label><select name="city_id"><?php foreach ($cities as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $roomData['city_id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="input-group"><label>Price per night (RON)</label><input type="number" name="price" value="<?= $roomData['price'] ?>" required></div>
                        <div class="input-group"><label>Exact address</label><input type="text" name="address" value="<?= htmlspecialchars($roomData['address']) ?>"></div>
                        <div class="input-group"><label>Guest capacity</label><input type="number" name="capacity" value="<?= $roomData['capacity'] ?>"></div>
                        <div class="input-group"><label>Bedrooms</label><input type="number" name="bedrooms" value="<?= $roomData['bedrooms'] ?>"></div>
                        <div class="input-group"><label>Bathrooms</label><input type="number" name="bathrooms" value="<?= $roomData['bathrooms'] ?>"></div>
                        <div class="input-group"><label>Check-in time</label><input type="time" name="check_in_time" value="<?= $roomData['check_in_time'] ?>"></div>
                        <div class="input-group"><label>Check-out time</label><input type="time" name="check_out_time" value="<?= $roomData['check_out_time'] ?>"></div>
                    </div>

                    <div class="form-section-title">Description</div>
                    <textarea name="description" rows="4" style="width:100%; border-radius:15px; padding:15px; border:1px solid #eee;"><?= htmlspecialchars($roomData['description']) ?></textarea>

                    <div class="form-section-title">Gallery</div>
                    <div class="preview-container" style="margin-bottom: 20px; gap: 40px;">
                        <?php foreach ($roomPhotos as $p): ?>
                            <div style="position:relative; width: 120px; height: 120px;">
                                <img src="../../assets/<?= htmlspecialchars($p['photo_url']) ?>" class="preview-img" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">
                                <button type="submit" name="delete_photo_id" value="<?= $p['id'] ?>" style="position:absolute; top:-5px; right:-5px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-xmark" style="font-size: 10px;"></i></button>
                                <?php if ($p['is_primary']): ?><span style="position:absolute; bottom:5px; left:5px; background: #7b2bd4; color:white; font-size:9px; padding:2px 5px; border-radius:4px;">COVER</span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="upload-zone" id="dropZone"><i class="fa-solid fa-images"></i>
                        <p>Drag more photos here to add</p><input type="file" name="photos[]" id="fileInput" multiple hidden accept="image/*">
                    </div>

                    <div class="form-section-title">Facilities</div>
                    <div class="facilities-container">
                        <?php $fac_list = [
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
                            'has_dishes' => ['Dishes', 'fa-plate-wheat'],
                            'has_stove' => ['Stove', 'fa-fire-burner'],
                            'has_coffee_maker' => ['Coffee maker', 'fa-coffee'],
                            'has_washing_machine' => ['Laundry', 'fa-soap'],
                            'has_hot_water' => ['Hot water', 'fa-faucet-hot'],
                            'has_tv' => ['TV', 'fa-tv'],
                            'has_pool' => ['Pool', 'fa-person-swimming'],
                            'has_jacuzzi' => ['Jacuzzi', 'fa-hot-tub-person'],
                            'is_pet_friendly' => ['Pet friendly', 'fa-paw'],
                            'is_smoking_allowed' => ['Smoking allowed', 'fa-smoking']
                        ];
                        foreach ($fac_list as $key => $details): ?>
                            <label class="fac-checkbox-item"><input type="checkbox" name="<?= $key ?>" <?= $facData[$key] ? 'checked' : '' ?>><i class="fa-solid <?= $details[1] ?>" style="color: #7b2bd4; width: 20px; text-align: center;"></i> <?= $details[0] ?></label>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 40px; display: flex; gap: 20px;">
                        <button type="submit" class="btn" style="flex: 2; padding: 18px;">Save changes</button>
                        <button type="button" class="btn" onclick="openDeleteModal()" style="flex: 1; background: #fff5f5; color: #e74c3c; border: 1px solid #feb2b2;"><i class="fa-solid fa-trash-can"></i> Delete property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deletePropertyModal" class="res-overlay" style="display: none;">
        <div class="res-overlay-content modal-compact">
            <button class="close-modal" onclick="closeDeleteModal()"><i class="fa-solid fa-xmark"></i></button>
            <div class="modal-main-icon"><i class="fa-solid fa-triangle-exclamation" style="color: #e74c3c;"></i></div>
            <h2 id="modalTitle">Confirm deletion</h2>
            <p style="margin-bottom: 25px; color: #666; line-height: 1.5;">Are you sure you want to permanently delete <strong><?= htmlspecialchars($roomData['name']) ?></strong>? This action cannot be undone.</p>
            <form method="POST"><button type="submit" name="confirm_delete_property" class="btn" style="background: #e74c3c; width: 100%;">YES, DELETE PROPERTY</button></form>
        </div>
    </div>

    <?php include '../utils/includes/footer.php'; ?>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        dropZone.onclick = () => fileInput.click();
        dropZone.ondragover = (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        };
        dropZone.ondragleave = () => dropZone.classList.remove('dragover');
        dropZone.ondrop = (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
        };

        function openDeleteModal() {
            document.getElementById('deletePropertyModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deletePropertyModal').style.display = 'none';
        }
    </script>
</body>

</html>