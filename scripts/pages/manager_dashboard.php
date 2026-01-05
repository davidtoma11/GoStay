<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$current_manager_id = $_SESSION['user_id'];

// Check Permissions
$stmt_user = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt_user->execute([$current_manager_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

$is_manager = ($user && ($user['role'] === 'manager' || $user['role'] === 'admin'));

if (!$is_manager): ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Access Denied - GoStay</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="../styles/search_results.css">
        <link rel="stylesheet" href="../styles/manager.css">
    </head>
    <body class="denied-body">
        <div class="denied-card">
            <div class="lock-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <h1>Restricted Area</h1>
            <p>You do not have the required permissions to view this management console.</p>
            <div class="denied-actions">
                <a href="home.php" class="btn">Return Home</a>
                <a href="../support/contact.php" class="btn support-btn">Support Forum</a>
            </div>
        </div>
    </body>
    </html>
<?php exit; endif;

// --- DATA FETCHING (FILTERED BY LOGGED IN MANAGER) ---

// 1. Stats - Only for rooms owned by this manager
$stmt_rev = $db->prepare("SELECT SUM(res.total_price) 
                          FROM reservations res 
                          JOIN rooms r ON res.room_id = r.id 
                          WHERE r.user_id = ? AND res.status IN ('confirmed', 'completed')");
$stmt_rev->execute([$current_manager_id]);
$total_revenue = $stmt_rev->fetchColumn() ?: 0;

// 2. Pending Reservations - Only for rooms owned by this manager
$stmt_pending = $db->prepare("
    SELECT res.*, r.name as room_name, u.first_name, u.last_name 
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    JOIN users u ON res.user_id = u.id
    WHERE r.user_id = ? AND res.status = 'pending'
    ORDER BY res.created_at DESC
");
$stmt_pending->execute([$current_manager_id]);
$pending_res = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

// 3. Upcoming Stays - Only for rooms owned by this manager
$stmt_upcoming = $db->prepare("
    SELECT res.*, r.name as room_name, u.first_name, u.last_name 
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    JOIN users u ON res.user_id = u.id
    WHERE r.user_id = ? AND res.status = 'confirmed'
    ORDER BY res.check_in ASC
");
$stmt_upcoming->execute([$current_manager_id]);
$upcoming_res = $stmt_upcoming->fetchAll(PDO::FETCH_ASSOC);

// 4. Properties - Only those owned by this manager
$stmt_rooms = $db->prepare("
    SELECT r.*, c.name as city_name, 
    (SELECT photo_url FROM room_photos rp WHERE rp.room_id = r.id ORDER BY is_primary DESC LIMIT 1) as main_photo
    FROM rooms r
    JOIN cities c ON r.city_id = c.id
    WHERE r.user_id = ?
    ORDER BY c.name ASC, r.name ASC
");
$stmt_rooms->execute([$current_manager_id]);
$rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

// 5. Latest Reviews - Only for rooms owned by this manager
$stmt_revs = $db->prepare("
    SELECT rev.*, u.first_name, u.last_name, r.name as room_name
    FROM reviews rev
    JOIN users u ON rev.user_id = u.id
    JOIN rooms r ON rev.room_id = r.id
    WHERE r.user_id = ?
    ORDER BY rev.created_at DESC LIMIT 5
");
$stmt_revs->execute([$current_manager_id]);
$reviews = $stmt_revs->fetchAll(PDO::FETCH_ASSOC);

// 6. Master Booked Dates - Only for rooms owned by this manager
$stmt_all_booked = $db->prepare("SELECT res.room_id, res.check_in, res.check_out 
                                 FROM reservations res 
                                 JOIN rooms r ON res.room_id = r.id 
                                 WHERE r.user_id = ? AND res.status IN ('confirmed', 'pending')");
$stmt_all_booked->execute([$current_manager_id]);
$all_booked_data = $stmt_all_booked->fetchAll(PDO::FETCH_ASSOC);

$booked_by_room = [];
foreach ($all_booked_data as $res) {
    $booked_by_room[$res['room_id']][] = ['from' => $res['check_in'], 'to' => $res['check_out']];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Console - GoStay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../styles/search_results.css">
    <link rel="stylesheet" href="../styles/manager.css">
    <link rel="stylesheet" href="../styles/footer.css">
    
    <script>
        const BOOKED_DATES_MASTER = <?php echo json_encode($booked_by_room); ?>;
    </script>
</head>

<body>
    <nav class="results-nav">
        <div class="nav-left">
            <div class="nav-logo"></div>
            <div class="manager-title-box">
                <span class="badge-premium">PREMIUM</span>
                <h2 class="manager-header-text">Manager Console</h2>
            </div>
        </div>
        <div class="nav-icons">
            <a href="home.php" title="Exit Dashboard"><i class="fa-solid fa-home"></i></a>
        </div>
    </nav>

    <div class="results-wrapper">
        <div class="stats-grid-dashboard">
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Revenue</span>
                    <span class="stat-value"><?php echo number_format($total_revenue); ?> RON</span>
                </div>
            </div>
            <div class="stat-item highlight">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Pending Approval</span>
                    <span class="stat-value"><?php echo count($pending_res); ?> Requests</span>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Upcoming Stays</span>
                    <span class="stat-value"><?php echo count($upcoming_res); ?> Active</span>
                </div>
            </div>
        </div>

        <div class="layout-grid">
            <div class="main-manager-content">
                <section class="manager-card-section">
                    <div class="section-header">
                        <h3><i class="fa-solid fa-bell"></i> Pending Approvals</h3>
                    </div>
                    <div class="table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Client / Property</th>
                                    <th>Dates</th>
                                    <th>Total</th>
                                    <th>Quick Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_res as $res): ?>
                                    <tr id="res-<?php echo $res['id']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($res['first_name'] . ' ' . $res['last_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($res['room_name']); ?></small>
                                        </td>
                                        <td class="td-dates"><?php echo $res['check_in']; ?> → <?php echo $res['check_out']; ?></td>
                                        <td class="td-price"><?php echo number_format($res['total_price']); ?> RON</td>
                                        <td class="td-actions" style="display: flex; gap: 30px;">
                                            <button class="action-btn approve" onclick="confirmStatus(<?php echo $res['id']; ?>, 'confirmed')" title="Approve"><i class="fa-solid fa-check"></i></button>
                                            <button class="action-btn reject" onclick="confirmStatus(<?php echo $res['id']; ?>, 'cancelled')" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="manager-card-section">
                    <div class="section-header">
                        <h3><i class="fa-solid fa-calendar-check"></i> Upcoming & Active Stays</h3>
                    </div>
                    <div class="table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Client / Property</th>
                                    <th>Dates</th>
                                    <th>Price Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_res as $res): ?>
                                    <tr id="res-<?php echo $res['id']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($res['first_name'] . ' ' . $res['last_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($res['room_name']); ?></small>
                                        </td>
                                        <td class="td-dates">
                                            <span class="date-badge">In: <?php echo $res['check_in']; ?></span><br>
                                            <span class="date-badge">Out: <?php echo $res['check_out']; ?></span>
                                        </td>
                                        <td class="td-price">
                                            <span class="current-total"><?php echo number_format($res['total_price']); ?> RON</span>
                                        </td>
                                        <td class="td-actions-grid">
                                            <button class="action-btn complete" onclick="confirmStatus(<?php echo $res['id']; ?>, 'completed')" title="Complete"><i class="fa-solid fa-check-double"></i></button>
                                            <button class="action-btn cancel" onclick="confirmStatus(<?php echo $res['id']; ?>, 'cancelled')" title="Cancel"><i class="fa-solid fa-ban"></i></button>
                                            <button class="action-btn edit" onclick="toggleEdit(<?php echo $res['id']; ?>)" title="Edit Dates"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <button class="action-btn discount" onclick="openModal('discount', <?php echo $res['id']; ?>)" title="Apply Discount"><i class="fa-solid fa-tags"></i></button>
                                            <button class="action-btn penalty" onclick="openModal('penalty', <?php echo $res['id']; ?>)" title="Apply Penalty"><i class="fa-solid fa-gavel"></i></button>
                                        </td>
                                    </tr>
                                    <tr id="edit-row-<?php echo $res['id']; ?>" class="edit-row" style="display:none;">
                                        <td colspan="4">
                                            <div class="edit-inline-box">
                                                <input type="date" id="in-<?php echo $res['id']; ?>" value="<?php echo $res['check_in']; ?>">
                                                <input type="date" id="out-<?php echo $res['id']; ?>" value="<?php echo $res['check_out']; ?>">
                                                <button class="btn-save-inline" onclick="saveDates(<?php echo $res['id']; ?>)">Update Dates</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="manager-card-section">
                    <div class="section-header">
                        <h3><i class="fa-solid fa-hotel"></i> Managed Properties</h3>
                        <a href="../utils/add_property.php" class="btn-add-new-link" style="text-decoration: none;">
                            <button class="btn">+ Add New</button>
                        </a>
                    </div>
                    <div class="property-grid-manager">
                        <?php
                        $current_city = "";
                        foreach ($rooms as $r):
                            if ($current_city !== $r['city_name']):
                                $current_city = $r['city_name'];
                        ?>
                                <div class="city-separator"><span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($current_city); ?></span></div>
                            <?php endif; ?>

                            <div class="mini-property-card">
                                <div class="mini-img" style="background-image: url('../../assets/<?php echo htmlspecialchars($r['main_photo']); ?>');"></div>
                                <div class="mini-details">
                                    <h4><?php echo htmlspecialchars($r['name']); ?></h4>
                                    <span><?php echo number_format($r['price']); ?> RON / night</span>
                                </div>
                                <div class="mini-actions">
                                    <button class="calendar-view-btn" onclick="toggleRoomCalendar(<?php echo $r['id']; ?>)" title="View Availability">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </button>
                                    <a href="../utils/edit_property.php?id=<?php echo $r['id']; ?>" class="mini-edit"><i class="fa-solid fa-pen"></i></a>
                                </div>
                            </div>
                            <div id="calendar-container-<?php echo $r['id']; ?>" class="room-calendar-wrapper" style="display:none;">
                                <div class="calendar-inline-admin" id="calendar-<?php echo $r['id']; ?>"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <aside class="sidebar-column">
                <section class="manager-card-section reviews-sidebar">
                    <div class="section-header">
                        <h3><i class="fa-solid fa-star"></i> Latest Feedback</h3>
                    </div>
                    <div class="reviews-stack">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="mini-review-item">
                                <div class="rev-top">
                                    <strong><?php echo htmlspecialchars($rev['first_name']); ?></strong>
                                    <div class="rev-stars">
                                        <?php for ($i = 0; $i < $rev['rating']; $i++) echo '<i class="fa-solid fa-star"></i>'; ?>
                                    </div>
                                </div>
                                <span class="rev-room">on <?php echo htmlspecialchars($rev['room_name']); ?></span>
                                <p>"<?php echo htmlspecialchars(substr($rev['comment'], 0, 80)) . '...'; ?>"</p>
                                <div class="rev-date-footer">
                                    <i class="fa-regular fa-calendar-days"></i>
                                    <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <div id="managerActionModal" class="res-overlay" style="display: none;">
        <div class="res-overlay-content modal-compact">
            <button class="close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            <div id="modalIcon" class="modal-main-icon"></div>
            <h2 id="modalTitle">Confirm Action</h2>
            <p id="modalDescription" style="margin-bottom: 20px; color: #666;"></p>
            <div id="inputSection" style="display: none;">
                <div class="input-group">
                    <label id="inputLabel">Value</label>
                    <input type="number" id="modalValue" placeholder="0">
                </div>
                <div id="reasonGroup" class="input-group" style="display:none;">
                    <label>Reason</label>
                    <textarea id="modalReason"></textarea>
                </div>
            </div>
            <button id="modalSubmitBtn" class="btn">YES, PROCEED</button>
        </div>
    </div>

    <?php include '../utils/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/manager_dashboard.js"></script>
</body>
</html>