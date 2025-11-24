<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get current table from URL
$table = $_GET['table'] ?? 'users';
$allowed_tables = ['users', 'hotels', 'rooms', 'reservations', 'reviews', 'messages', 'room_photos'];
if (!in_array($table, $allowed_tables)) {
    $table = 'users';
}

// Handle room_id filter for room_photos
$room_id_filter = $_GET['room_id'] ?? '';

// Handle CRUD operations
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';

    if ($action === 'create') {
        createRecord($table, $_POST, $db);
    } elseif ($action === 'update') {
        updateRecord($table, $id, $_POST, $db);
    }
}

// Handle delete actions
if ($action === 'delete' && $id) {
    deleteRecord($table, $id, $db);
}

// CRUD Functions
function createRecord($table, $data, $db)
{
    unset($data['action'], $data['id']);

    // Handle password hashing for users 
    if ($table === 'users' && isset($data['password']) && !empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        error_log("DEBUG: Password hashed for new user");
    }

    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));

    $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($query);

    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", htmlspecialchars(strip_tags($value)));
    }

    if ($stmt->execute()) {
        header("Location: operations.php?table=$table&success=created");
        exit;
    } else {
        error_log("CREATE Error: " . implode(", ", $stmt->errorInfo()));
    }
}

function updateRecord($table, $id, $data, $db)
{
    unset($data['action'], $data['id']);

    // Handle password hashing for users -
    if ($table === 'users' && isset($data['password']) && !empty(trim($data['password']))) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        error_log("DEBUG: Password hashed for user update");
    } elseif ($table === 'users' && isset($data['password']) && empty(trim($data['password']))) {
        unset($data['password']); // Remove empty password field to keep current one
    }

    // Remove empty fields to avoid overwriting with empty values
    $data = array_filter($data, function ($value) {
        return $value !== '' && $value !== null;
    });

    // If no fields to update, redirect back
    if (empty($data)) {
        header("Location: operations.php?table=$table");
        exit;
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
        $stmt->bindValue(":$key", htmlspecialchars(strip_tags($value)));
    }

    if ($stmt->execute()) {
        header("Location: operations.php?table=$table&success=updated");
        exit;
    } else {
        error_log("UPDATE Error: " . implode(", ", $stmt->errorInfo()));
    }
}

function deleteRecord($table, $id, $db)
{
    $query = "DELETE FROM $table WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $id);

    if ($stmt->execute()) {
        header("Location: operations.php?table=$table&success=deleted");
        exit;
    }
}

// Get records for current table
function getRecords($table, $db, $room_id_filter = '')
{

    // Add joins for better data display
    if ($table === 'hotels') {
        $query = "SELECT h.*, u.first_name, u.last_name 
                  FROM hotels h 
                  LEFT JOIN users u ON h.manager_id = u.id 
                  ORDER BY h.id DESC";
    } elseif ($table === 'reservations') {
        $query = "SELECT r.*, u.first_name, u.last_name, h.name as hotel_name, rm.room_type 
                  FROM reservations r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN rooms rm ON r.room_id = rm.id 
                  JOIN hotels h ON rm.hotel_id = h.id 
                  ORDER BY r.id DESC";
    } elseif ($table === 'reviews') {
        $query = "SELECT r.*, u.first_name, u.last_name, h.name as hotel_name, res.id as reservation_number
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN hotels h ON r.hotel_id = h.id 
                  JOIN reservations res ON r.reservation_id = res.id
                  ORDER BY r.id DESC";
    } elseif ($table === 'messages') {
        $query = "SELECT m.*, 
                         u1.first_name as sender_first, u1.last_name as sender_last,
                         u2.first_name as receiver_first, u2.last_name as receiver_last,
                         res.id as reservation_number
                  FROM messages m 
                  JOIN users u1 ON m.sender_id = u1.id 
                  JOIN users u2 ON m.receiver_id = u2.id 
                  JOIN reservations res ON m.reservation_id = res.id
                  ORDER BY m.id DESC";
    } elseif ($table === 'rooms') {
        $query = "SELECT rm.*, h.name as hotel_name 
                  FROM rooms rm 
                  JOIN hotels h ON rm.hotel_id = h.id 
                  ORDER BY rm.id DESC";
    } elseif ($table === 'room_photos') {
        $query = "SELECT rp.*, r.room_type, h.name as hotel_name 
                  FROM room_photos rp 
                  JOIN rooms r ON rp.room_id = r.id 
                  JOIN hotels h ON r.hotel_id = h.id";

        // Add room filter if specified
        if (!empty($room_id_filter)) {
            $query .= " WHERE rp.room_id = " . intval($room_id_filter);
        }

        $query .= " ORDER BY rp.is_primary DESC, rp.id DESC";
    } else {
        // Default query for users
        $query = "SELECT * FROM $table ORDER BY id DESC";
    }

    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$records = getRecords($table, $db, $room_id_filter);
$edit_record = null;

if ($action === 'edit' && $id) {
    $query = "SELECT * FROM $table WHERE id = :id";
    $stmt = $db->prepare($query);
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
    <title>Manage <?php echo ucfirst($table); ?> - GoStay CRUD</title>
    <link rel="stylesheet" href="../../styles/crud.css">
</head>

<body>
    <div class="operations-container">
        <!-- Header -->
        <div class="operations-header">
            <h1>
                Manage <?php echo ucfirst($table); ?>
                <?php if (!empty($room_id_filter) && $table === 'room_photos'): ?>
                    <small style="font-size: 0.6em; color: #666;">(Filtered by Room ID: <?php echo $room_id_filter; ?>)</small>
                <?php endif; ?>
            </h1>
            <a href="hub.php" class="back-link">← Back to CRUD Panel</a>
        </div>

        <div class="operations-content">
            <!-- Success Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #d5f4e6; color: #27ae60; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    ✅ Record <?php echo $_GET['success']; ?> successfully!
                </div>
            <?php endif; ?>

            <!-- Add/Edit Form -->
            <div class="crud-form">
                <h3><?php echo $edit_record ? 'Edit' : 'Add New'; ?> <?php echo ucfirst($table); ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_record ? 'update' : 'create'; ?>">
                    <?php if ($edit_record): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_record['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <?php if ($table === 'users'): ?>
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control"
                                    value="<?php echo $edit_record['first_name'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control"
                                    value="<?php echo $edit_record['last_name'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?php echo $edit_record['email'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password <?php echo $edit_record ? '(leave empty to keep current)' : ''; ?></label>
                                <input type="password" name="password" class="form-control"
                                    <?php echo $edit_record ? '' : 'required'; ?>>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="client" <?php echo ($edit_record['role'] ?? '') === 'client' ? 'selected' : ''; ?>>Client</option>
                                    <option value="manager" <?php echo ($edit_record['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo ($edit_record['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>

                        <?php elseif ($table === 'hotels'): ?>
                            <div class="form-group">
                                <label class="form-label">Hotel Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?php echo $edit_record['name'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control"
                                    value="<?php echo $edit_record['location'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo $edit_record['description'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Manager ID</label>
                                <select name="manager_id" class="form-control" required>
                                    <option value="">Select Manager</option>
                                    <?php
                                    $managers = $db->query("SELECT id, first_name, last_name FROM users WHERE role = 'manager'")->fetchAll();
                                    foreach ($managers as $manager): ?>
                                        <option value="<?php echo $manager['id']; ?>"
                                            <?php echo ($edit_record['manager_id'] ?? '') == $manager['id'] ? 'selected' : ''; ?>>
                                            <?php echo $manager['first_name'] . ' ' . $manager['last_name'] . ' (ID: ' . $manager['id'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending_approval" <?php echo ($edit_record['status'] ?? '') === 'pending_approval' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="approved" <?php echo ($edit_record['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo ($edit_record['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>

                        <?php elseif ($table === 'rooms'): ?>
                            <div class="form-group">
                                <label class="form-label">Hotel</label>
                                <select name="hotel_id" class="form-control" required>
                                    <option value="">Select Hotel</option>
                                    <?php
                                    $hotels = $db->query("SELECT id, name FROM hotels WHERE status = 'approved'")->fetchAll();
                                    foreach ($hotels as $hotel): ?>
                                        <option value="<?php echo $hotel['id']; ?>"
                                            <?php echo ($edit_record['hotel_id'] ?? '') == $hotel['id'] ? 'selected' : ''; ?>>
                                            <?php echo $hotel['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room Type</label>
                                <select name="room_type" class="form-control" required>
                                    <option value="single" <?php echo ($edit_record['room_type'] ?? '') === 'single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="double" <?php echo ($edit_record['room_type'] ?? '') === 'double' ? 'selected' : ''; ?>>Double</option>
                                    <option value="suite" <?php echo ($edit_record['room_type'] ?? '') === 'suite' ? 'selected' : ''; ?>>Suite</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Price</label>
                                <input type="number" step="0.01" name="price" class="form-control"
                                    value="<?php echo $edit_record['price'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo $edit_record['description'] ?? ''; ?></textarea>
                            </div>

                        <?php elseif ($table === 'reservations'): ?>
                            <div class="form-group">
                                <label class="form-label">User</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">Select User</option>
                                    <?php
                                    $users = $db->query("SELECT id, first_name, last_name FROM users WHERE role = 'client'")->fetchAll();
                                    foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($edit_record['user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-control" required>
                                    <option value="">Select Room</option>
                                    <?php
                                    $rooms = $db->query("SELECT r.id, r.room_type, h.name as hotel_name 
                                                       FROM rooms r JOIN hotels h ON r.hotel_id = h.id")->fetchAll();
                                    foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>"
                                            <?php echo ($edit_record['room_id'] ?? '') == $room['id'] ? 'selected' : ''; ?>>
                                            <?php echo $room['hotel_name'] . ' - ' . ucfirst($room['room_type']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Check-in Date</label>
                                <input type="date" name="check_in" class="form-control"
                                    value="<?php echo $edit_record['check_in'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Check-out Date</label>
                                <input type="date" name="check_out" class="form-control"
                                    value="<?php echo $edit_record['check_out'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?php echo ($edit_record['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($edit_record['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo ($edit_record['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo ($edit_record['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                        <?php elseif ($table === 'reviews'): ?>
                            <div class="form-group">
                                <label class="form-label">Reservation</label>
                                <select name="reservation_id" class="form-control" required>
                                    <option value="">Select Reservation</option>
                                    <?php
                                    $reservations = $db->query("
                                        SELECT r.id, u.first_name, u.last_name, h.name as hotel_name 
                                        FROM reservations r 
                                        JOIN users u ON r.user_id = u.id 
                                        JOIN rooms rm ON r.room_id = rm.id 
                                        JOIN hotels h ON rm.hotel_id = h.id 
                                        WHERE r.status = 'completed'
                                        ORDER BY r.id DESC
                                    ")->fetchAll();
                                    foreach ($reservations as $res): ?>
                                        <option value="<?php echo $res['id']; ?>"
                                            <?php echo ($edit_record['reservation_id'] ?? '') == $res['id'] ? 'selected' : ''; ?>>
                                            <?php echo 'Res #' . $res['id'] . ' - ' . $res['first_name'] . ' ' . $res['last_name'] . ' - ' . $res['hotel_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">User</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">Select User</option>
                                    <?php
                                    $users = $db->query("SELECT id, first_name, last_name FROM users WHERE role = 'client'")->fetchAll();
                                    foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($edit_record['user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Hotel</label>
                                <select name="hotel_id" class="form-control" required>
                                    <option value="">Select Hotel</option>
                                    <?php
                                    $hotels = $db->query("SELECT id, name FROM hotels WHERE status = 'approved'")->fetchAll();
                                    foreach ($hotels as $hotel): ?>
                                        <option value="<?php echo $hotel['id']; ?>"
                                            <?php echo ($edit_record['hotel_id'] ?? '') == $hotel['id'] ? 'selected' : ''; ?>>
                                            <?php echo $hotel['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rating (1-5)</label>
                                <select name="rating" class="form-control" required>
                                    <option value="1" <?php echo ($edit_record['rating'] ?? '') == '1' ? 'selected' : ''; ?>>1 ⭐</option>
                                    <option value="2" <?php echo ($edit_record['rating'] ?? '') == '2' ? 'selected' : ''; ?>>2 ⭐⭐</option>
                                    <option value="3" <?php echo ($edit_record['rating'] ?? '') == '3' ? 'selected' : ''; ?>>3 ⭐⭐⭐</option>
                                    <option value="4" <?php echo ($edit_record['rating'] ?? '') == '4' ? 'selected' : ''; ?>>4 ⭐⭐⭐⭐</option>
                                    <option value="5" <?php echo ($edit_record['rating'] ?? '') == '5' ? 'selected' : ''; ?>>5 ⭐⭐⭐⭐⭐</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Comment</label>
                                <textarea name="comment" class="form-control" rows="3" required><?php echo $edit_record['comment'] ?? ''; ?></textarea>
                            </div>

                        <?php elseif ($table === 'messages'): ?>
                            <div class="form-group">
                                <label class="form-label">Reservation</label>
                                <select name="reservation_id" class="form-control" required>
                                    <option value="">Select Reservation</option>
                                    <?php
                                    $reservations = $db->query("
                                        SELECT r.id, u.first_name, u.last_name, h.name as hotel_name 
                                        FROM reservations r 
                                        JOIN users u ON r.user_id = u.id 
                                        JOIN rooms rm ON r.room_id = rm.id 
                                        JOIN hotels h ON rm.hotel_id = h.id 
                                        ORDER BY r.id DESC
                                    ")->fetchAll();
                                    foreach ($reservations as $res): ?>
                                        <option value="<?php echo $res['id']; ?>"
                                            <?php echo ($edit_record['reservation_id'] ?? '') == $res['id'] ? 'selected' : ''; ?>>
                                            <?php echo 'Res #' . $res['id'] . ' - ' . $res['first_name'] . ' ' . $res['last_name'] . ' - ' . $res['hotel_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sender</label>
                                <select name="sender_id" class="form-control" required>
                                    <option value="">Select Sender</option>
                                    <?php
                                    $users = $db->query("SELECT id, first_name, last_name, role FROM users")->fetchAll();
                                    foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($edit_record['sender_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo $user['first_name'] . ' ' . $user['last_name'] . ' (' . ucfirst($user['role']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Receiver</label>
                                <select name="receiver_id" class="form-control" required>
                                    <option value="">Select Receiver</option>
                                    <?php
                                    $users = $db->query("SELECT id, first_name, last_name, role FROM users")->fetchAll();
                                    foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($edit_record['receiver_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo $user['first_name'] . ' ' . $user['last_name'] . ' (' . ucfirst($user['role']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Message</label>
                                <textarea name="message_body" class="form-control" rows="3" required><?php echo $edit_record['message_body'] ?? ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Is Read</label>
                                <select name="is_read" class="form-control" required>
                                    <option value="0" <?php echo ($edit_record['is_read'] ?? '') == '0' ? 'selected' : ''; ?>>Unread</option>
                                    <option value="1" <?php echo ($edit_record['is_read'] ?? '') == '1' ? 'selected' : ''; ?>>Read</option>
                                </select>
                            </div>

                        <?php elseif ($table === 'room_photos'): ?>
                            <div class="form-group">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-control" required>
                                    <option value="">Select Room</option>
                                    <?php
                                    $rooms = $db->query("SELECT r.id, r.room_type, h.name as hotel_name 
                                                       FROM rooms r JOIN hotels h ON r.hotel_id = h.id 
                                                       ORDER BY h.name, r.room_type")->fetchAll();
                                    foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>"
                                            <?php echo ($edit_record['room_id'] ?? '') == $room['id'] ? 'selected' : ''; ?>>
                                            <?php echo $room['hotel_name'] . ' - ' . ucfirst($room['room_type']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Image URL</label>
                                <input type="text" name="image_url" class="form-control"
                                    value="<?php echo $edit_record['image_url'] ?? ''; ?>"
                                    placeholder="path/to/image.jpg" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Is Primary Photo</label>
                                <select name="is_primary" class="form-control" required>
                                    <option value="0" <?php echo ($edit_record['is_primary'] ?? '') == '0' ? 'selected' : ''; ?>>No (Gallery Photo)</option>
                                    <option value="1" <?php echo ($edit_record['is_primary'] ?? '') == '1' ? 'selected' : ''; ?>>Yes (Cover Photo)</option>
                                </select>
                            </div>

                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <?php echo $edit_record ? 'Update' : 'Create'; ?> <?php echo ucfirst($table); ?>
                    </button>
                    <?php if ($edit_record): ?>
                        <a href="operations.php?table=<?php echo $table; ?>" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Records Table -->
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search <?php echo $table; ?>..."
                    onkeyup="searchTable('<?php echo $table; ?>Table', this.value)">
            </div>

            <div class="table-responsive">
                <table class="data-table" id="<?php echo $table; ?>Table">
                    <thead>
                        <tr>
                            <?php if (!empty($records)): ?>
                                <?php foreach (array_keys($records[0]) as $column): ?>
                                    <th><?php echo ucfirst(str_replace('_', ' ', $column)); ?></th>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <?php foreach ($record as $key => $value): ?>
                                    <td>
                                        <?php if ($key === 'password'): ?>
                                            <span class="badge badge-warning">Encrypted</span>
                                        <?php elseif ($key === 'role' || $key === 'status' || $key === 'room_type'): ?>
                                            <span class="badge badge-<?php
                                                                        echo ($value === 'admin' || $value === 'approved' || $value === 'suite') ? 'danger' : (($value === 'manager' || $value === 'pending_approval' || $value === 'double') ? 'warning' : 'info');
                                                                        ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $value)); ?>
                                            </span>
                                        <?php elseif ($key === 'rating'): ?>
                                            <span class="badge badge-<?php echo $value >= 4 ? 'success' : ($value >= 3 ? 'warning' : 'danger'); ?>">
                                                ⭐ <?php echo $value; ?>
                                            </span>
                                        <?php elseif ($key === 'is_primary'): ?>
                                            <span class="badge badge-<?php echo $value ? 'success' : 'info'; ?>">
                                                <?php echo $value ? 'Cover Photo' : 'Gallery'; ?>
                                            </span>
                                        <?php elseif ($key === 'is_read'): ?>
                                            <span class="badge badge-<?php echo $value ? 'success' : 'warning'; ?>">
                                                <?php echo $value ? 'Read' : 'Unread'; ?>
                                            </span>
                                        <?php elseif ($key === 'image_url'): ?>
                                            <a href="<?php echo $value; ?>" target="_blank" class="image-preview">
                                                📷 View Image
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '')); ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="action-buttons">
                                    <?php if ($table === 'rooms'): ?>
                                        <!-- Buton special pentru Photos în tabelul Rooms -->
                                        <a href="operations.php?table=room_photos&room_id=<?php echo $record['id']; ?>"
                                            class="btn btn-sm btn-info">Photos</a>
                                    <?php endif; ?>

                                    <a href="operations.php?table=<?php echo $table; ?>&action=edit&id=<?php echo $record['id']; ?>"
                                        class="btn btn-sm btn-primary">Edit</a>
                                    <a href="operations.php?table=<?php echo $table; ?>&action=delete&id=<?php echo $record['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function searchTable(tableId, searchText) {
            const table = document.getElementById(tableId);
            const tr = table.getElementsByTagName('tr');
            const filter = searchText.toLowerCase();

            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const td = tr[i].getElementsByTagName('td');
                for (let j = 0; j < td.length - 1; j++) { // Exclude actions column
                    if (td[j]) {
                        if (td[j].textContent.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? '' : 'none';
            }
        }

        function confirm(message) {
            return window.confirm(message);
        }
    </script>
</body>

</html>