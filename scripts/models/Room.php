<?php
/**
 * Room Model
 * Handles all database operations for the rooms, photos, and facilities tables.
 */
class Room {
    private $conn;
    private $table = "rooms";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Executes the complex search logic including city, guests, availability, and facilities.
    public function search($city_id, $guests, $check_in, $check_out, $selected_facilities) {
        $sql = "SELECT 
                    r.*, 
                    c.name as city_name,
                    (SELECT photo_url FROM room_photos WHERE room_id = r.id ORDER BY is_primary DESC, id ASC LIMIT 1) as main_photo,
                    (SELECT AVG(rating) FROM reviews WHERE room_id = r.id) as avg_rating,
                    (SELECT COUNT(id) FROM reviews WHERE room_id = r.id) as review_count
                FROM " . $this->table . " r
                JOIN cities c ON r.city_id = c.id
                LEFT JOIN facilities f ON r.id = f.room_id
                WHERE 1=1";

        $params = [];

        // Filter by City
        if (!empty($city_id)) {
            $sql .= " AND r.city_id = ?";
            $params[] = $city_id;
        }

        // Filter by Guest Capacity
        if ($guests > 0) {
            $sql .= " AND r.capacity >= ?";
            $params[] = $guests;
        }

        // Filter by Date Availability (Excludes overlapping confirmed/pending reservations)
        if (!empty($check_in) && !empty($check_out)) {
            $sql .= " AND r.id NOT IN (
                        SELECT room_id FROM reservations 
                        WHERE status IN ('confirmed', 'pending')
                        AND (check_in < ? AND check_out > ?)
                      )";
            $params[] = $check_out;
            $params[] = $check_in;
        }

        // Dynamic Filtering for Facilities (Amenities)
        if (!empty($selected_facilities)) {
            foreach ($selected_facilities as $fac) {
                // Column names are safe-checked in the controller/page before calling this
                $sql .= " AND f.$fac = 1";
            }
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetches detailed information for a single room.
    public function getDetails($id) {
        $query = "SELECT r.*, c.name as city_name 
                  FROM " . $this->table . " r
                  JOIN cities c ON r.city_id = c.id
                  WHERE r.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // Retrieves all photos associated with a room.
    public function getPhotos($id) {
        $query = "SELECT photo_url FROM room_photos WHERE room_id = ? ORDER BY is_primary DESC, id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
     // Retrieves the boolean flags for all facilities of a specific room.
    public function getFacilities($id) {
        $query = "SELECT * FROM facilities WHERE room_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}