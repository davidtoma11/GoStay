-- ==================================================
-- 1. TABLE: ROOMS (Role & Price Validation)
-- ==================================================
DELIMITER $$

-- Trigger: Ensure only 'manager' or 'admin' can list a room (INSERT)
CREATE TRIGGER validate_room_owner_role_insert
BEFORE INSERT ON rooms
FOR EACH ROW
BEGIN
    DECLARE owner_role VARCHAR(50);
    
    -- Get the role of the user trying to list the room
    SELECT role INTO owner_role 
    FROM users 
    WHERE id = NEW.user_id;
    
    -- Logic: Allow ONLY 'manager' or 'admin'. Block 'client' or 'guest'.
    IF owner_role NOT IN ('manager', 'admin') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Security Violation: Only users with Manager or Admin role can list properties.';
    END IF;
    
    -- Also validate price here to save code
    IF NEW.price <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Room price must be greater than 0.';
    END IF;
END$$

-- Trigger: Ensure only 'manager' or 'admin' can own a room (UPDATE)
-- This prevents changing ownership to a simple client later
CREATE TRIGGER validate_room_owner_role_update
BEFORE UPDATE ON rooms
FOR EACH ROW
BEGIN
    DECLARE owner_role VARCHAR(50);
    
    -- Check only if the user_id (owner) is changing
    IF NEW.user_id != OLD.user_id THEN
        SELECT role INTO owner_role 
        FROM users 
        WHERE id = NEW.user_id;
        
        IF owner_role NOT IN ('manager', 'admin') THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Security Violation: Only users with Manager or Admin role can list properties.';
        END IF;
    END IF;
    
    -- Validate price update
    IF NEW.price <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Room price must be greater than 0.';
    END IF;
END$$

DELIMITER ;

-- ==================================================
-- 2. TABLE: RESERVATIONS (Availability & Dates)
-- ==================================================
DELIMITER $$

-- Trigger: Prevent Double Booking (INSERT)
CREATE TRIGGER check_room_availability_insert
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    DECLARE overlapping_count INT;
    
    -- Check if dates overlap with any CONFIRMED or PENDING reservation
    -- Logic: (StartA < EndB) AND (EndA > StartB) detects any overlap
    SELECT COUNT(*) INTO overlapping_count
    FROM reservations
    WHERE room_id = NEW.room_id
    AND status IN ('confirmed', 'pending') 
    AND (
        (NEW.check_in < check_out AND NEW.check_out > check_in)
    );
    
    IF overlapping_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Room is already booked for the selected dates.';
    END IF;
    
    -- Date Logic Check
    IF NEW.check_out <= NEW.check_in THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Check-out date must be after check-in date.';
    END IF;
END$$

-- Trigger: Prevent Double Booking (UPDATE)
CREATE TRIGGER check_room_availability_update
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    DECLARE overlapping_count INT;
    
    -- Run check only if dates or room changed
    IF NEW.room_id != OLD.room_id OR NEW.check_in != OLD.check_in OR NEW.check_out != OLD.check_out THEN
        
        SELECT COUNT(*) INTO overlapping_count
        FROM reservations
        WHERE room_id = NEW.room_id
        AND status IN ('confirmed', 'pending')
        AND id != NEW.id -- Exclude current reservation from check
        AND (
            (NEW.check_in < check_out AND NEW.check_out > check_in)
        );
        
        IF overlapping_count > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Room is already booked for the selected dates.';
        END IF;
        
        -- Date Logic Check
        IF NEW.check_out <= NEW.check_in THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Check-out date must be after check-in date.';
        END IF;
    END IF;
END$$

DELIMITER ;

-- ==================================================
-- 3. TABLE: REVIEWS (Prevent Self-Reviews)
-- ==================================================
DELIMITER $$

-- Trigger: Owner cannot review their own property (INSERT)
CREATE TRIGGER prevent_self_review_insert
BEFORE INSERT ON reviews
FOR EACH ROW
BEGIN
    DECLARE room_owner_id INT;
    
    -- Find the owner of the room being reviewed
    SELECT user_id INTO room_owner_id 
    FROM rooms 
    WHERE id = NEW.room_id;
    
    IF room_owner_id = NEW.user_id THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Action denied: Property owners cannot review their own listings.';
    END IF;
END$$

-- Trigger: Owner cannot review their own property (UPDATE)
CREATE TRIGGER prevent_self_review_update
BEFORE UPDATE ON reviews
FOR EACH ROW
BEGIN
    DECLARE room_owner_id INT;
    
    IF NEW.user_id != OLD.user_id OR NEW.room_id != OLD.room_id THEN
        SELECT user_id INTO room_owner_id 
        FROM rooms 
        WHERE id = NEW.room_id;
        
        IF room_owner_id = NEW.user_id THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Action denied: Property owners cannot review their own listings.';
        END IF;
    END IF;
END$$

DELIMITER ;

-- ==================================================
-- 5. TABLE: ROOM_PHOTOS (Single Primary Photo)
-- ==================================================
DELIMITER $$

-- Trigger: Auto-fix primary photo on INSERT
-- If new photo is Primary, unset any existing Primary photo
CREATE TRIGGER ensure_single_primary_photo_insert
BEFORE INSERT ON room_photos
FOR EACH ROW
BEGIN
    DECLARE primary_exists INT;
    
    IF NEW.is_primary = 1 THEN
        SELECT COUNT(*) INTO primary_exists
        FROM room_photos 
        WHERE room_id = NEW.room_id AND is_primary = 1;
        
        -- Instead of error, we automatically downgrade the old primary photo
        IF primary_exists > 0 THEN
             UPDATE room_photos 
             SET is_primary = 0 
             WHERE room_id = NEW.room_id AND is_primary = 1;
        END IF;
    END IF;
END$$

-- Trigger: Auto-fix primary photo on UPDATE
CREATE TRIGGER ensure_single_primary_photo_update
BEFORE UPDATE ON room_photos
FOR EACH ROW
BEGIN
    -- If a photo is being promoted to Primary
    IF NEW.is_primary = 1 AND OLD.is_primary = 0 THEN
        -- Demote all other photos for this room
        UPDATE room_photos 
        SET is_primary = 0 
        WHERE room_id = NEW.room_id 
        AND is_primary = 1 
        AND id != NEW.id;
    END IF;
END$$

-- Trigger: Fallback if Primary is Deleted
-- If the primary photo is deleted, promote the oldest remaining photo to Primary
CREATE TRIGGER auto_set_primary_after_delete
AFTER DELETE ON room_photos
FOR EACH ROW
BEGIN
    DECLARE remaining_photos INT;
    
    IF OLD.is_primary = 1 THEN
        SELECT COUNT(*) INTO remaining_photos
        FROM room_photos 
        WHERE room_id = OLD.room_id;
        
        IF remaining_photos > 0 THEN
            UPDATE room_photos 
            SET is_primary = 1 
            WHERE room_id = OLD.room_id 
            ORDER BY id ASC 
            LIMIT 1;
        END IF;
    END IF;
END$$

DELIMITER ;