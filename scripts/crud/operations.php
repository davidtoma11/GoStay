<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Allowed Tables
$table = $_GET['table'] ?? 'users';
$allowed_tables = ['users', 'cities', 'rooms', 'facilities', 'reservations', 'reviews', 'messages', 'room_photos'];
if (!in_array($table, $allowed_tables)) {
    $table = 'users';
}

// Filters
$room_id_filter = $_GET['room_id'] ?? '';

// CRUD Actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($action === 'create') {
        createRecord($table, $_POST, $db);
    } elseif ($action === 'update') {
        updateRecord($table, $id, $_POST, $db);
    }
}

if ($action === 'delete' && $id) {
    deleteRecord($table, $id, $db);
}

// --- CRUD FUNCTIONS ---

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
        // Handle NULL for optional fields
        if ($value === '') {
            $value = null;
            $stmt->bindValue(":$key", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":$key", htmlspecialchars(strip_tags($value)));
        }
    }

    if ($stmt->execute()) {
        header("Location: operations.php?table=$table&success=created");
        exit;
    } else {
        die("Create Error: " . implode(" ", $stmt->errorInfo()));
    }
}

function updateRecord($table, $id, $data, $db) {
    unset($data['action'], $data['id']);

    if ($table === 'users') {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
    }

    $setClause = [];
    foreach ($data as $key => $value) {
        $setClause[] = "$key = :$key";
    }
    $setClause = implode(', ', $setClause);

    $query = "UPDATE $table SET $setClause WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $id);

    foreach ($data as $key => $value) {
        if ($value === '') {
            $stmt->bindValue(":$key", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":$key", htmlspecialchars(strip_tags($value)));
        }
    }

    if ($stmt->execute()) {
        header("Location: operations.php?table=$table&success=updated");
        exit;
    } else {
        die("Update Error: " . implode(" ", $stmt->errorInfo()));
    }
}

function deleteRecord($table, $id, $db) {
    try {
        $query = "DELETE FROM $table WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $id);
        if ($stmt->execute()) {
            header("Location: operations.php?table=$table&success=deleted");
            exit;
        }
    } catch (PDOException $e) {
        die("Delete Error (Constraint violation?): " . $e->getMessage());
    }
}

// --- DATA FETCHING ---

function getRecords($table, $db, $room_id_filter = '') {
    if ($table === 'cities') {
        $query = "SELECT * FROM cities ORDER BY name ASC";
    } elseif ($table === 'rooms') {
        $query = "SELECT r.*, c.name as city_name, u.first_name, u.last_name 
                  FROM rooms r 
                  JOIN cities c ON r.city_id = c.id 
                  JOIN users u ON r.user_id = u.id 
                  ORDER BY r.id DESC";
    } elseif ($table === 'facilities') {
        $query = "SELECT f.*, r.name as room_name, c.name as city_name 
                  FROM facilities f 
                  JOIN rooms r ON f.room_id = r.id 
                  JOIN cities c ON r.city_id = c.id
                  ORDER BY f.id DESC";
    } elseif ($table === 'reservations') {
        $query = "SELECT r.*, u.first_name, u.last_name, rm.name as room_name, c.name as city_name
                  FROM reservations r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN rooms rm ON r.room_id = rm.id 
                  JOIN cities c ON rm.city_id = c.id
                  ORDER BY r.id DESC";
    } elseif ($table === 'reviews') {
        // Fixed Query for Reviews (Linked to Room and User)
        $query = "SELECT r.*, u.first_name, u.last_name, rm.name as room_name
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN rooms rm ON r.room_id = rm.id
                  ORDER BY r.id DESC";
    } elseif ($table === 'messages') {
        // Fixed Query for Messages (Linked to Sender/Receiver)
        $query = "SELECT m.*, u1.email as sender, u2.email as receiver, r.name as room_name
                  FROM messages m 
                  JOIN users u1 ON m.sender_id = u1.id 
                  JOIN users u2 ON m.receiver_id = u2.id
                  LEFT JOIN rooms r ON m.room_id = r.id
                  ORDER BY m.id DESC";
    } elseif ($table === 'room_photos') {
        $query = "SELECT rp.*, r.name as room_name 
                  FROM room_photos rp 
                  JOIN rooms r ON rp.room_id = r.id";
        if (!empty($room_id_filter)) {
            $query .= " WHERE rp.room_id = " . intval($room_id_filter);
        }
        $query .= " ORDER BY rp.room_id DESC, rp.is_primary DESC";
    } else {
        $query = "SELECT * FROM $table ORDER BY id DESC";
    }

    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$records = getRecords($table, $db, $room_id_filter);
$edit_record = null;

if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM $table WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $edit_record = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage <?php echo ucfirst($table); ?> - GoStay</title>
    <link rel="stylesheet" href="../../styles/crud.css">
    <style>
        .checkbox-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .checkbox-item { display: flex; align-items: center; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; }
        .checkbox-item input { margin-right: 8px; width: auto; }
        .checkbox-item label { margin: 0; font-size: 0.9em; cursor: pointer; }
    </style>
</head>
<body>
    <div class="operations-container">
        <div class="operations-header">
            <h1>Manage <?php echo ucfirst($table); ?></h1>
            <a href="hub.php" class="back-link">← Back to Hub</a>
        </div>

        <div class="operations-content">
            <?php if (isset($_GET['success'])): ?>
                <div style="background:#d4edda;color:#155724;padding:10px;margin-bottom:15px;border-radius:4px;">
                    Action successful!
                </div>
            <?php endif; ?>

            <div class="crud-form">
                <h3><?php echo $edit_record ? 'Edit' : 'Add New'; ?> Record</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_record ? 'update' : 'create'; ?>">
                    <?php if ($edit_record): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_record['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        
                        <?php if ($table === 'users'): ?>
                            <div class="form-group"><label>First Name</label><input type="text" name="first_name" class="form-control" value="<?php echo $edit_record['first_name'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" class="form-control" value="<?php echo $edit_record['last_name'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?php echo $edit_record['email'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" placeholder="<?php echo $edit_record ? '(Keep empty to maintain)' : ''; ?>"></div>
                            <div class="form-group"><label>Role</label>
                                <select name="role" class="form-control">
                                    <option value="client" <?php echo ($edit_record['role']??'')=='client'?'selected':''; ?>>Client</option>
                                    <option value="manager" <?php echo ($edit_record['role']??'')=='manager'?'selected':''; ?>>Manager</option>
                                    <option value="admin" <?php echo ($edit_record['role']??'')=='admin'?'selected':''; ?>>Admin</option>
                                </select>
                            </div>

                        <?php elseif ($table === 'cities'): ?>
                            <div class="form-group"><label>City Name (e.g. Paris, FRA)</label><input type="text" name="name" class="form-control" value="<?php echo $edit_record['name'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Image URL</label><input type="text" name="image_url" class="form-control" value="<?php echo $edit_record['image_url'] ?? ''; ?>"></div>

                        <?php elseif ($table === 'rooms'): ?>
                            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?php echo $edit_record['name'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Owner</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">Select Owner</option>
                                    <?php
                                    $mgrs = $db->query("SELECT id, first_name, last_name FROM users WHERE role IN ('manager','admin')")->fetchAll();
                                    foreach ($mgrs as $m) {
                                        $sel = ($edit_record['user_id']??'') == $m['id'] ? 'selected' : '';
                                        echo "<option value='{$m['id']}' $sel>{$m['first_name']} {$m['last_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group"><label>City</label>
                                <select name="city_id" class="form-control" required>
                                    <option value="">Select City</option>
                                    <?php
                                    $cities = $db->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();
                                    foreach ($cities as $c) {
                                        $sel = ($edit_record['city_id']??'') == $c['id'] ? 'selected' : '';
                                        echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Price ($)</label><input type="number" step="0.01" name="price" class="form-control" value="<?php echo $edit_record['price'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Address</label><input type="text" name="address" class="form-control" value="<?php echo $edit_record['address'] ?? ''; ?>" required></div>
                            
                            <div class="form-group"><label>Capacity</label><input type="number" name="capacity" class="form-control" value="<?php echo $edit_record['capacity'] ?? '2'; ?>"></div>
                            <div class="form-group"><label>Bedrooms</label><input type="number" name="bedrooms" class="form-control" value="<?php echo $edit_record['bedrooms'] ?? '1'; ?>"></div>
                            <div class="form-group"><label>Bathrooms</label><input type="number" name="bathrooms" class="form-control" value="<?php echo $edit_record['bathrooms'] ?? '1'; ?>"></div>
                            
                            <div class="form-group"><label>Check-in</label><input type="time" name="check_in_time" class="form-control" value="<?php echo $edit_record['check_in_time'] ?? '14:00'; ?>"></div>
                            <div class="form-group"><label>Check-out</label><input type="time" name="check_out_time" class="form-control" value="<?php echo $edit_record['check_out_time'] ?? '11:00'; ?>"></div>
                            <div class="form-group" style="grid-column: 1/-1;"><label>Description</label><textarea name="description" class="form-control" rows="3"><?php echo $edit_record['description'] ?? ''; ?></textarea></div>

                        <?php elseif ($table === 'facilities'): ?>
                            <div class="form-group"><label>Room ID</label>
                                <input type="number" name="room_id" class="form-control" value="<?php echo $edit_record['room_id'] ?? ''; ?>" required>
                            </div>
                            <div style="grid-column: 1/-1;">
                                <label>Amenities</label>
                                <div class="checkbox-grid">
                                    <?php
                                    $facilities_list = [
                                        'has_wifi', 'has_workspace', 'has_ac', 'has_heating', 'has_parking', 
                                        'has_self_checkin', 'has_elevator', 'has_kitchen', 'has_fridge', 
                                        'has_microwave', 'has_cooking_basics', 'has_dishes', 'has_stove', 
                                        'has_coffee_maker', 'has_washing_machine', 'has_dryer', 'has_iron', 
                                        'has_hairdryer', 'has_hot_water', 'has_essentials', 'has_tv', 
                                        'has_balcony', 'has_pool', 'has_jacuzzi', 'has_bbq', 'has_gym', 
                                        'has_smoke_alarm', 'has_first_aid', 'is_pet_friendly', 'is_smoking_allowed'
                                    ];
                                    foreach ($facilities_list as $fac) {
                                        $checked = ($edit_record[$fac] ?? 0) == 1 ? 'checked' : '';
                                        $label = ucfirst(str_replace(['has_', 'is_', '_'], ['', '', ' '], $fac));
                                        echo "
                                        <div class='checkbox-item'>
                                            <input type='hidden' name='$fac' value='0'>
                                            <input type='checkbox' id='$fac' name='$fac' value='1' $checked>
                                            <label for='$fac'>$label</label>
                                        </div>";
                                    }
                                    ?>
                                </div>
                            </div>

                        <?php elseif ($table === 'reservations'): ?>
                            <div class="form-group"><label>User ID</label><input type="number" name="user_id" class="form-control" value="<?php echo $edit_record['user_id'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Room ID</label><input type="number" name="room_id" class="form-control" value="<?php echo $edit_record['room_id'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Check In</label><input type="date" name="check_in" class="form-control" value="<?php echo $edit_record['check_in'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Check Out</label><input type="date" name="check_out" class="form-control" value="<?php echo $edit_record['check_out'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Total Price</label><input type="number" step="0.01" name="total_price" class="form-control" value="<?php echo $edit_record['total_price'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="pending" <?php echo ($edit_record['status']??'')=='pending'?'selected':''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($edit_record['status']??'')=='confirmed'?'selected':''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo ($edit_record['status']??'')=='cancelled'?'selected':''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo ($edit_record['status']??'')=='completed'?'selected':''; ?>>Completed</option>
                                </select>
                            </div>

                        <?php elseif ($table === 'reviews'): ?>
                            <div class="form-group"><label>User ID</label><input type="number" name="user_id" class="form-control" value="<?php echo $edit_record['user_id'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Room ID</label><input type="number" name="room_id" class="form-control" value="<?php echo $edit_record['room_id'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Rating (1-5)</label>
                                <select name="rating" class="form-control" required>
                                    <option value="1" <?php echo ($edit_record['rating']??'')==1?'selected':''; ?>>1 ⭐</option>
                                    <option value="2" <?php echo ($edit_record['rating']??'')==2?'selected':''; ?>>2 ⭐⭐</option>
                                    <option value="3" <?php echo ($edit_record['rating']??'')==3?'selected':''; ?>>3 ⭐⭐⭐</option>
                                    <option value="4" <?php echo ($edit_record['rating']??'')==4?'selected':''; ?>>4 ⭐⭐⭐⭐</option>
                                    <option value="5" <?php echo ($edit_record['rating']??'')==5?'selected':''; ?>>5 ⭐⭐⭐⭐⭐</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column:1/-1"><label>Comment</label><textarea name="comment" class="form-control" rows="2"><?php echo $edit_record['comment'] ?? ''; ?></textarea></div>

                        <?php elseif ($table === 'messages'): ?>
                            <div class="form-group"><label>Sender ID</label><input type="number" name="sender_id" class="form-control" value="<?php echo $edit_record['sender_id'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Receiver ID</label><input type="number" name="receiver_id" class="form-control" value="<?php echo $edit_record['receiver_id'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Room ID (Opt)</label><input type="number" name="room_id" class="form-control" value="<?php echo $edit_record['room_id'] ?? ''; ?>"></div>
                            <div class="form-group"><label>Is Read?</label>
                                <select name="is_read" class="form-control">
                                    <option value="0" <?php echo ($edit_record['is_read']??'')==0?'selected':''; ?>>Unread</option>
                                    <option value="1" <?php echo ($edit_record['is_read']??'')==1?'selected':''; ?>>Read</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column:1/-1"><label>Message Body</label><textarea name="message_body" class="form-control" rows="3" required><?php echo $edit_record['message_body'] ?? ''; ?></textarea></div>

                        <?php elseif ($table === 'room_photos'): ?>
                            <div class="form-group"><label>Room ID</label><input type="number" name="room_id" class="form-control" value="<?php echo $edit_record['room_id'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Photo URL</label><input type="text" name="photo_url" class="form-control" value="<?php echo $edit_record['photo_url'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Is Primary?</label>
                                <select name="is_primary" class="form-control">
                                    <option value="0" <?php echo ($edit_record['is_primary']??'')==0?'selected':''; ?>>No</option>
                                    <option value="1" <?php echo ($edit_record['is_primary']??'')==1?'selected':''; ?>>Yes (Cover)</option>
                                </select>
                            </div>
                        
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                    <?php if($edit_record): ?>
                        <a href="operations.php?table=<?php echo $table; ?>" class="btn btn-danger">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if(!empty($records)): foreach(array_keys($records[0]) as $key): ?>
                                <th><?php echo ucfirst(str_replace('_', ' ', $key)); ?></th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($records as $row): ?>
                        <tr>
                            <?php foreach($row as $key => $val): ?>
                                <td>
                                    <?php 
                                    if(strpos($key, 'image') !== false || strpos($key, 'photo') !== false) {
                                        $imgLink = $val;
                                        // Fix for Local vs External images
                                        if (strpos($val, 'http') === false) {
                                            $imgLink = "../../assets/" . $val; 
                                        }
                                        echo "<a href='$imgLink' target='_blank'>📷 View</a>";
                                    } elseif(strlen($val) > 50) {
                                        echo substr(htmlspecialchars($val), 0, 50) . '...';
                                    } else {
                                        echo htmlspecialchars($val);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <a href="operations.php?table=<?php echo $table; ?>&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="operations.php?table=<?php echo $table; ?>&action=delete&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?');">Delete</a>
                                
                                <?php if($table === 'rooms'): ?>
                                    <br><a href="operations.php?table=room_photos&room_id=<?php echo $row['id']; ?>" style="font-size:10px; color:blue;">Photos</a>
                                    | <a href="operations.php?table=facilities&action=edit&id=<?php 
                                        $fid = $db->query("SELECT id FROM facilities WHERE room_id=".$row['id'])->fetchColumn();
                                        echo $fid ? $fid : ''; 
                                    ?>" style="font-size:10px; color:green;">Facilities</a>
                                <?php endif; ?>
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