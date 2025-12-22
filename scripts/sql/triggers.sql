USE gostay;
DELIMITER $$

DROP TRIGGER IF EXISTS check_room_availability_insert$$

CREATE TRIGGER check_room_availability_insert
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    DECLARE overlapping_reservations INT;
    
    SELECT COUNT(*) INTO overlapping_reservations
    FROM reservations
    WHERE room_id = NEW.room_id
    AND status IN ('confirmed', 'pending')
    AND (
        (NEW.check_in BETWEEN check_in AND check_out) OR
        (NEW.check_out BETWEEN check_in AND check_out) OR
        (check_in BETWEEN NEW.check_in AND NEW.check_out) OR
        (NEW.check_in <= check_in AND NEW.check_out >= check_out)
    );
    
    IF overlapping_reservations > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Room is already booked for the selected dates. Please choose different dates.';
    END IF;
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS check_room_availability_update$$

CREATE TRIGGER check_room_availability_update
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    DECLARE overlapping_reservations INT;
    
    IF NEW.room_id != OLD.room_id OR NEW.check_in != OLD.check_in OR NEW.check_out != OLD.check_out THEN
        SELECT COUNT(*) INTO overlapping_reservations
        FROM reservations
        WHERE room_id = NEW.room_id
        AND status IN ('confirmed', 'pending')
        AND (
            (NEW.check_in BETWEEN check_in AND check_out) OR
            (NEW.check_out BETWEEN check_in AND check_out) OR
            (check_in BETWEEN NEW.check_in AND NEW.check_out) OR
            (NEW.check_in <= check_in AND NEW.check_out >= check_out)
        )
        AND id != NEW.id;
        
        IF overlapping_reservations > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Room is already booked for the selected dates. Please choose different dates.';
        END IF;
    END IF;
END$$

DELIMITER ;

-- Reservation Date Validation Triggers 

DELIMITER $$

DROP TRIGGER IF EXISTS validate_reservation_dates_insert$$

CREATE TRIGGER validate_reservation_dates_insert
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    -- Verify that check_out > check_in
    IF NEW.check_out <= NEW.check_in THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Check-out date must be after check-in date.';
    END IF;
    
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS validate_reservation_dates_update$$

CREATE TRIGGER validate_reservation_dates_update
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF NEW.check_in != OLD.check_in OR NEW.check_out != OLD.check_out THEN
        IF NEW.check_out <= NEW.check_in THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Check-out date must be after check-in date.';
        END IF;
        
    END IF;
END$$

DELIMITER ;

-- Review Validation Triggers

DELIMITER $$

DROP TRIGGER IF EXISTS prevent_self_review_insert$$

CREATE TRIGGER prevent_self_review_insert
BEFORE INSERT ON reviews
FOR EACH ROW
BEGIN
    DECLARE hotel_owner INT;
    DECLARE user_role VARCHAR(20);
    
    SELECT manager_id INTO hotel_owner 
    FROM hotels 
    WHERE id = NEW.hotel_id;
    
    SELECT role INTO user_role
    FROM users
    WHERE id = NEW.user_id;
    
    IF hotel_owner = NEW.user_id THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'You cannot review your own hotel.';
    END IF;
    
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS prevent_self_review_update$$

CREATE TRIGGER prevent_self_review_update
BEFORE UPDATE ON reviews
FOR EACH ROW
BEGIN
    DECLARE hotel_owner INT;
    DECLARE user_role VARCHAR(20);
    
    IF NEW.user_id != OLD.user_id OR NEW.hotel_id != OLD.hotel_id THEN
        SELECT manager_id INTO hotel_owner 
        FROM hotels 
        WHERE id = NEW.hotel_id;
        
        SELECT role INTO user_role
        FROM users
        WHERE id = NEW.user_id;
        
        IF hotel_owner = NEW.user_id THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'You cannot review your own hotel.';
        END IF;
        
    END IF;
END$$

DELIMITER ;

-- Hotel Rating Update Triggers

DELIMITER $$

DROP TRIGGER IF EXISTS update_hotel_rating_insert$$

CREATE TRIGGER update_hotel_rating_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    
    SELECT AVG(rating) INTO avg_rating
    FROM reviews 
    WHERE hotel_id = NEW.hotel_id;
    
    UPDATE hotels 
    SET rating = ROUND(COALESCE(avg_rating, 0), 2) 
    WHERE id = NEW.hotel_id;
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS update_hotel_rating_update$$

CREATE TRIGGER update_hotel_rating_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    
    IF NEW.rating != OLD.rating OR NEW.hotel_id != OLD.hotel_id THEN
        IF NEW.hotel_id != OLD.hotel_id THEN
            SELECT AVG(rating) INTO avg_rating
            FROM reviews 
            WHERE hotel_id = OLD.hotel_id;
            
            UPDATE hotels 
            SET rating = ROUND(COALESCE(avg_rating, 0), 2) 
            WHERE id = OLD.hotel_id;
        END IF;
        
        SELECT AVG(rating) INTO avg_rating
        FROM reviews 
        WHERE hotel_id = NEW.hotel_id;
        
        UPDATE hotels 
        SET rating = ROUND(COALESCE(avg_rating, 0), 2) 
        WHERE id = NEW.hotel_id;
    END IF;
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS update_hotel_rating_delete$$

CREATE TRIGGER update_hotel_rating_delete
AFTER DELETE ON reviews
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    
    SELECT AVG(rating) INTO avg_rating
    FROM reviews 
    WHERE hotel_id = OLD.hotel_id;
    
    UPDATE hotels 
    SET rating = ROUND(COALESCE(avg_rating, 0), 2) 
    WHERE id = OLD.hotel_id;
END$$

DELIMITER ;

-- Room Price Validation Triggers

DELIMITER $$

DROP TRIGGER IF EXISTS validate_room_price_insert$$

CREATE TRIGGER validate_room_price_insert
BEFORE INSERT ON rooms
FOR EACH ROW
BEGIN
    IF NEW.price <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Room price must be greater than 0.';
    END IF;
    
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS validate_room_price_update$$

CREATE TRIGGER validate_room_price_update
BEFORE UPDATE ON rooms
FOR EACH ROW
BEGIN
    IF NEW.price != OLD.price THEN
        IF NEW.price <= 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Room price must be greater than 0.';
        END IF;
        
    END IF;
END$$

DELIMITER ;

-- Room Photo Management Triggers

DELIMITER $$

DROP TRIGGER IF EXISTS ensure_single_primary_photo_insert$$

CREATE TRIGGER ensure_single_primary_photo_insert
BEFORE INSERT ON room_photos
FOR EACH ROW
BEGIN
    DECLARE primary_photo_count INT;
    
    IF NEW.is_primary = 1 THEN
        SELECT COUNT(*) INTO primary_photo_count
        FROM room_photos 
        WHERE room_id = NEW.room_id 
        AND is_primary = 1;
        
        IF primary_photo_count > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'This room already has a primary photo. Only one primary photo is allowed per room.';
        END IF;
    END IF;
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS ensure_single_primary_photo_update$$

CREATE TRIGGER ensure_single_primary_photo_update
BEFORE UPDATE ON room_photos
FOR EACH ROW
BEGIN
    DECLARE primary_photo_count INT;
    
    IF NEW.is_primary = 1 AND OLD.is_primary = 0 THEN
        SELECT COUNT(*) INTO primary_photo_count
        FROM room_photos 
        WHERE room_id = NEW.room_id 
        AND is_primary = 1
        AND id != NEW.id;
        
        IF primary_photo_count > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'This room already has a primary photo. Only one primary photo is allowed per room.';
        END IF;
    END IF;
END$$

DELIMITER ;

DELIMITER $$

DROP TRIGGER IF EXISTS auto_set_primary_after_delete$$

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

DELIMITER $$

DROP TRIGGER IF EXISTS ensure_primary_exists_on_update$$

CREATE TRIGGER ensure_primary_exists_on_update
BEFORE UPDATE ON room_photos
FOR EACH ROW
BEGIN
    DECLARE primary_photo_count INT;
    DECLARE total_photos INT;
    
    IF NEW.is_primary = 0 AND OLD.is_primary = 1 THEN
        SELECT COUNT(*) INTO primary_photo_count
        FROM room_photos 
        WHERE room_id = OLD.room_id 
        AND is_primary = 1
        AND id != OLD.id;
        
        SELECT COUNT(*) INTO total_photos
        FROM room_photos 
        WHERE room_id = OLD.room_id
        AND id != OLD.id;
        
        IF primary_photo_count = 0 AND total_photos > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Cannot remove primary status. This is the only primary photo for the room. Set another photo as primary first.';
        END IF;
    END IF;
END$$

DELIMITER ;