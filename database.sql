-- Create database
CREATE DATABASE IF NOT EXISTS safari_minibus;
USE safari_minibus;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(13) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_phone CHECK (phone REGEXP '^\\+255[0-9]{9}$' AND LENGTH(phone) = 13)
);

-- Create drivers table
CREATE TABLE IF NOT EXISTS drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    phone VARCHAR(13) NOT NULL,
    status ENUM('available', 'assigned', 'off') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_phone CHECK (phone REGEXP '^\\+255[0-9]{9}$' AND LENGTH(phone) = 13)
);

-- Create minibuses table
CREATE TABLE IF NOT EXISTS minibuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    price_per_km DECIMAL(10,2) NOT NULL,
    features JSON DEFAULT NULL,
    driver_id INT,
    status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
);

-- Create minibus_images table
CREATE TABLE IF NOT EXISTS minibus_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    minibus_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (minibus_id) REFERENCES minibuses(id) ON DELETE CASCADE
);

-- Create bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    minibus_id INT NOT NULL,
    user_id INT NOT NULL,
    start_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    pickup_latitude DECIMAL(10,8) NOT NULL,
    pickup_longitude DECIMAL(11,8) NOT NULL,
    dropoff_location VARCHAR(255),
    dropoff_latitude DECIMAL(10,8),
    dropoff_longitude DECIMAL(11,8),
    route_distance DECIMAL(10,2) NOT NULL,
    route_duration INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    cancellation_reason TEXT,
    reschedule_request TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (minibus_id) REFERENCES minibuses(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create booking_logs table for tracking changes
CREATE TABLE IF NOT EXISTS booking_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_minibus_driver ON minibuses(driver_id);
CREATE INDEX idx_booking_user ON bookings(user_id);
CREATE INDEX idx_booking_minibus ON bookings(minibus_id);
CREATE INDEX idx_driver_status ON drivers(status);
CREATE INDEX idx_minibus_status ON minibuses(status);
CREATE INDEX idx_booking_status ON bookings(status);
CREATE INDEX idx_booking_date ON bookings(start_date);
CREATE INDEX idx_booking_datetime ON bookings(start_date, pickup_time);
CREATE INDEX idx_booking_reschedule ON bookings(reschedule_request);
CREATE INDEX idx_booking_logs_booking ON booking_logs(booking_id);

-- Create trigger to prevent assigning already assigned drivers
DELIMITER //
DROP TRIGGER IF EXISTS before_minibus_driver_update//
CREATE TRIGGER before_minibus_driver_update
    BEFORE UPDATE ON minibuses
    FOR EACH ROW
BEGIN
    DECLARE driver_status VARCHAR(20);
    
    -- Only check if we're assigning a new driver
    IF NEW.driver_id IS NOT NULL AND (NEW.driver_id != OLD.driver_id OR OLD.driver_id IS NULL) THEN
        SELECT status INTO driver_status FROM drivers WHERE id = NEW.driver_id;
        
        IF driver_status = 'assigned' THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Cannot assign this driver. They are already assigned to another minibus.';
        END IF;
    END IF;
END//
DELIMITER ;

-- Create trigger to update driver status when assigned to minibus
DELIMITER //
DROP TRIGGER IF EXISTS after_minibus_driver_update//
CREATE TRIGGER after_minibus_driver_update
    AFTER UPDATE ON minibuses
    FOR EACH ROW
BEGIN
    -- If driver was changed or removed
    IF NEW.driver_id != OLD.driver_id THEN
        -- Update old driver's status to available if they were assigned
        IF OLD.driver_id IS NOT NULL THEN
            UPDATE drivers 
            SET status = 'available' 
            WHERE id = OLD.driver_id;
        END IF;
        
        -- Update new driver's status to assigned if one was assigned
        IF NEW.driver_id IS NOT NULL THEN
            UPDATE drivers 
            SET status = 'assigned' 
            WHERE id = NEW.driver_id;
        END IF;
    END IF;
END//
DELIMITER ;

-- Create trigger to handle driver removal
DELIMITER //
DROP TRIGGER IF EXISTS before_minibus_driver_remove//
CREATE TRIGGER before_minibus_driver_remove
    BEFORE UPDATE ON minibuses
    FOR EACH ROW
BEGIN
    -- If driver is being removed (set to NULL)
    IF NEW.driver_id IS NULL AND OLD.driver_id IS NOT NULL THEN
        -- Update the driver's status to available
        UPDATE drivers 
        SET status = 'available' 
        WHERE id = OLD.driver_id;
    END IF;
END//
DELIMITER ;

-- Create trigger to update minibus status when booking is confirmed
DELIMITER //
DROP TRIGGER IF EXISTS after_booking_status_update//
CREATE TRIGGER after_booking_status_update
    AFTER UPDATE ON bookings
    FOR EACH ROW
BEGIN
    IF NEW.status = 'confirmed' AND OLD.status != 'confirmed' THEN
        UPDATE minibuses 
        SET status = 'booked' 
        WHERE id = NEW.minibus_id;
    ELSEIF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        UPDATE minibuses 
        SET status = 'available' 
        WHERE id = NEW.minibus_id;
    ELSEIF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE minibuses 
        SET status = 'available' 
        WHERE id = NEW.minibus_id;
    END IF;
END//
DELIMITER ;

-- Create trigger to update driver status when minibus is deleted
DELIMITER //
DROP TRIGGER IF EXISTS after_minibus_delete//
CREATE TRIGGER after_minibus_delete
    AFTER DELETE ON minibuses
    FOR EACH ROW
BEGIN
    IF OLD.driver_id IS NOT NULL THEN
        UPDATE drivers 
        SET status = 'available' 
        WHERE id = OLD.driver_id;
    END IF;
END//
DELIMITER ;

-- Insert default admin user
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin User', 'admin@example.com', '+255123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create required directories if they don't exist
-- Note: These commands need to be run in the shell, not in MySQL
-- mkdir -p assets/images/minibuses
-- mkdir -p assets/css
-- mkdir -p assets/pictures