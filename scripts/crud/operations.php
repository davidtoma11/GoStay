<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/index.php");
    exit;
}

require_once '../config/database.php';
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

$ADMIN_ID = $userId; 
$table = $_GET['table'] ?? 'users';
$allowed_tables = ['users', 'cities', 'rooms', 'facilities', 'reservations', 'reviews', 'messages', 'room_photos'];
if (!in_array($table, $allowed_tables)) { $table = 'users'; }

$room_id_filter = $_GET['room_id'] ?? '';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    if ($action === 'create') {
        createRecord($table, $_POST, $db);
    } elseif ($action === 'update') {
        updateRecord($table, $id, $_POST, $db, $ADMIN_ID);
    }
}

if ($action === 'delete' && $id) {
    deleteRecord($table, $id, $db, $ADMIN_ID);
}

function sendNotification($db, $sender_id, $receiver_id, $message) {
    $check = $db->prepare("SELECT id FROM users WHERE id = ?");
    $check->execute([$receiver_id]);
    if($check->rowCount() == 0) return; 
    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message_body) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $message]);
}

function createRecord($table, $data, $db) {
    unset($data['action'], $data['id']);
    if ($table === 'users' && !empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($query);
    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", ($value === '') ? null : htmlspecialchars(strip_tags($value)));
    }
    if ($stmt->execute()) {
        header("Location: operations.php?table=$table&success=created");
        exit;
    }
}

function updateRecord($table, $id, $data, $db, $admin_id) {
    unset($data['action'], $data['id']);
    if ($table === 'reservations') {
        $stmt = $db->prepare("SELECT res.user_id as client_id, r.user_id as host_id, r.name as room_name FROM reservations res JOIN rooms r ON res.room_id = r.id WHERE res.id = ?");
        $stmt->execute([$id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        if($info) {
            $notif = "Formal Notice: Administrative adjustments have been applied to reservation #$id regarding property '{$info['room_name']}'. Please review your dashboard for updated parameters.";
            sendNotification($db, $admin_id, $info['client_id'], $notif);
            sendNotification($db, $admin_id, $info['host_id'], $notif);
        }
    }
    if ($table === 'users') {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else { unset($data['password']); }
    }
    $setClause = [];
    foreach ($data as $key => $value) { $setClause[] = "$key = :$key"; }
    $query = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $id);
    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", ($value === '') ? null : htmlspecialchars(strip_tags($value)));
    }
    if ($stmt->execute()) {
        header("Location: operations.php?table=$table&success=updated");
        exit;
    }
}

function deleteRecord($table, $id, $db, $admin_id) {
    try {
        if ($table === 'rooms') {
            $stmt = $db->prepare("SELECT name, user_id FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            if($room) {
                sendNotification($db, $admin_id, $room['user_id'], "Property Rescinded: Listing '{$room['name']}' has been removed by administration.");
            }
        }
        $query = "DELETE FROM $table WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $id);
        if ($stmt->execute()) {
            header("Location: operations.php?table=$table&success=deleted");
            exit;
        }
    } catch (PDOException $e) { die("Delete Error: " . $e->getMessage()); }
}

function getRecords($table, $db, $room_id_filter = '') {
    if ($table === 'cities') { $query = "SELECT * FROM cities ORDER BY name ASC"; }
    elseif ($table === 'rooms') { $query = "SELECT r.*, c.name as city_name, u.email as owner_email FROM rooms r JOIN cities c ON r.city_id = c.id JOIN users u ON r.user_id = u.id ORDER BY r.id DESC"; }
    elseif ($table === 'facilities') { $query = "SELECT f.*, r.name as room_name FROM facilities f JOIN rooms r ON f.room_id = r.id ORDER BY f.id DESC"; }
    elseif ($table === 'reservations') { $query = "SELECT r.*, u.email as client_email, rm.name as room_name FROM reservations r JOIN users u ON r.user_id = u.id JOIN rooms rm ON r.room_id = rm.id ORDER BY r.id DESC"; }
    elseif ($table === 'reviews') { $query = "SELECT r.*, u.email as reviewer, rm.name as room_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN rooms rm ON r.room_id = rm.id ORDER BY r.id DESC"; }
    elseif ($table === 'messages') { $query = "SELECT m.*, u1.email as sender, u2.email as receiver FROM messages m JOIN users u1 ON m.sender_id = u1.id JOIN users u2 ON m.receiver_id = u2.id ORDER BY m.id DESC"; }
    elseif ($table === 'room_photos') { $query = "SELECT rp.*, r.name as room_name FROM room_photos rp JOIN rooms r ON rp.room_id = r.id ORDER BY rp.id DESC"; }
    else { $query = "SELECT * FROM $table ORDER BY id DESC"; }
    $stmt = $db->prepare($query); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$records = getRecords($table, $db, $room_id_filter);
$edit_record = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM $table WHERE id = :id");
    $stmt->bindValue(':id', $id); $stmt->execute(); $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage <?php echo ucfirst($table); ?></title>
    <link rel="stylesheet" href="../styles/crud.css">
    <style>
        .checkbox-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 15px; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid #eee; }
        .checkbox-item { font-size: 0.75rem; display: flex; align-items: center; gap: 8px; color: #444; }
    </style>
</head>
<body>
    <div class="operations-container">
        <div class="operations-header">
            <h1>Platform Management: <?php echo ucfirst($table); ?></h1>
            <a href="hub.php" class="back-link">← Hub</a>
        </div>
        <div class="operations-content">
            <?php if (isset($_GET['success'])): ?>
                <div style="background:#d4edda;color:#155724;padding:12px;margin-bottom:20px;border-radius:10px;">Operation successful. Relevant parties notified.</div>
            <?php endif; ?>
            <div class="crud-form">
                <h3><?php echo $edit_record ? 'Edit' : 'Create'; ?> Entry</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_record ? 'update' : 'create'; ?>">
                    <?php if ($edit_record): ?><input type="hidden" name="id" value="<?php echo $edit_record['id']; ?>"><?php endif; ?>
                    <div class="form-grid">
                        <?php if ($table === 'users'): ?>
                            <div class="form-group"><label>First Name</label><input type="text" name="first_name" class="form-control" value="<?= $edit_record['first_name'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" class="form-control" value="<?= $edit_record['last_name'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= $edit_record['email'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control"></div>
                            <div class="form-group"><label>Role</label><select name="role" class="form-control"><option value="client" <?= ($edit_record['role']??'')=='client'?'selected':'' ?>>Client</option><option value="manager" <?= ($edit_record['role']??'')=='manager'?'selected':'' ?>>Manager</option><option value="admin" <?= ($edit_record['role']??'')=='admin'?'selected':'' ?>>Admin</option></select></div>
                        <?php elseif ($table === 'cities'): ?>
                            <div class="form-group"><label>City Name</label><input type="text" name="name" class="form-control" value="<?= $edit_record['name'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Image Asset Path</label><input type="text" name="image_url" class="form-control" value="<?= $edit_record['image_url'] ?? '' ?>"></div>
                        <?php elseif ($table === 'rooms'): ?>
                            <div class="form-group"><label>Property Name</label><input type="text" name="name" class="form-control" value="<?= $edit_record['name'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Owner ID</label><input type="number" name="user_id" class="form-control" value="<?= $edit_record['user_id'] ?? '' ?>" required></div>
                            <div class="form-group"><label>City ID</label><input type="number" name="city_id" class="form-control" value="<?= $edit_record['city_id'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Price (RON)</label><input type="number" step="0.01" name="price" class="form-control" value="<?= $edit_record['price'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Capacity</label><input type="number" name="capacity" class="form-control" value="<?= $edit_record['capacity'] ?? '2' ?>"></div>
                        <?php elseif ($table === 'facilities'): ?>
                            <div class="form-group"><label>Room ID</label><input type="number" name="room_id" class="form-control" value="<?= $edit_record['room_id'] ?? '' ?>" required></div>
                            <div style="grid-column: 1/-1;"><label>Property Amenities Matrix</label><div class="checkbox-grid">
                                <?php $all_facs = ['has_wifi', 'has_workspace', 'has_ac', 'has_heating', 'has_parking', 'has_self_checkin', 'has_elevator', 'has_kitchen', 'has_fridge', 'has_microwave', 'has_cooking_basics', 'has_dishes', 'has_stove', 'has_coffee_maker', 'has_washing_machine', 'has_dryer', 'has_iron', 'has_hairdryer', 'has_hot_water', 'has_essentials', 'has_tv', 'has_balcony', 'has_pool', 'has_jacuzzi', 'has_smoke_alarm', 'has_first_aid', 'is_pet_friendly', 'is_smoking_allowed'];
                                foreach($all_facs as $f) { $ch = ($edit_record[$f] ?? 0) == 1 ? 'checked' : ''; echo "<div class='checkbox-item'><input type='hidden' name='$f' value='0'><input type='checkbox' name='$f' value='1' $ch> <span>".ucwords(str_replace(['has_', 'is_', '_'], ['', '', ' '], $f))."</span></div>"; } ?>
                            </div></div>
                        <?php elseif ($table === 'room_photos'): ?>
                            <div class="form-group"><label>Room ID</label><input type="number" name="room_id" class="form-control" value="<?= $edit_record['room_id'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Photo Path</label><input type="text" name="photo_url" class="form-control" value="<?= $edit_record['photo_url'] ?? '' ?>" required></div>
                            <div class="form-group"><label>Is Primary?</label><select name="is_primary" class="form-control"><option value="0">No</option><option value="1" <?= ($edit_record['is_primary']??0)==1?'selected':'' ?>>Yes</option></select></div>
                        <?php elseif ($table === 'reservations'): ?>
                            <div class="form-group"><label>User ID</label><input type="number" name="user_id" class="form-control" value="<?= $edit_record['user_id'] ?? '' ?>"></div>
                            <div class="form-group"><label>Check In</label><input type="date" name="check_in" class="form-control" value="<?= $edit_record['check_in'] ?? '' ?>"></div>
                            <div class="form-group"><label>Check Out</label><input type="date" name="check_out" class="form-control" value="<?= $edit_record['check_out'] ?? '' ?>"></div>
                            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="pending" <?= ($edit_record['status']??'')=='pending'?'selected':'' ?>>Pending</option><option value="confirmed" <?= ($edit_record['status']??'')=='confirmed'?'selected':'' ?>>Confirmed</option><option value="completed" <?= ($edit_record['status']??'')=='completed'?'selected':'' ?>>Completed</option></select></div>
                        <?php elseif ($table === 'reviews'): ?>
                            <div class="form-group"><label>Rating (1-5)</label><input type="number" name="rating" min="1" max="5" class="form-control" value="<?= $edit_record['rating'] ?? '' ?>"></div>
                            <div class="form-group" style="grid-column: 1/-1;"><label>Feedback</label><textarea name="comment" class="form-control"><?= $edit_record['comment'] ?? '' ?></textarea></div>
                        <?php elseif ($table === 'messages'): ?>
                            <div class="form-group"><label>Receiver ID</label><input type="number" name="receiver_id" class="form-control" value="<?= $edit_record['receiver_id'] ?? '' ?>"></div>
                            <div class="form-group" style="grid-column: 1/-1;"><label>Content</label><textarea name="message_body" class="form-control"><?= $edit_record['message_body'] ?? '' ?></textarea></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:20px; width: 100%;">Commit Entry</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><?php if(!empty($records)): foreach(array_keys($records[0]) as $k): ?><th><?= ucfirst(str_replace('_',' ',$k)) ?></th><?php endforeach; ?><th>Actions</th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php foreach($records as $row): ?>
                        <tr>
                            <?php foreach($row as $k => $v): ?><td><?= htmlspecialchars($v) ?></td><?php endforeach; ?>
                            <td>
                                <a href="operations.php?table=<?= $table ?>&action=edit&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="operations.php?table=<?= $table ?>&action=delete&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Purge record?');">Del</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>