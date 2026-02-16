-- =====================================================
-- COMPLETE BLOOD BANK MANAGEMENT SYSTEM DATABASE
-- Modified for cPanel Import (NO CREATE DATABASE/USE statements)
-- =====================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    user_type ENUM('donor', 'receiver', 'admin') DEFAULT 'donor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Donor profiles table
CREATE TABLE IF NOT EXISTS donor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    last_donation_date DATE,
    medical_history TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    weight DECIMAL(5,2),
    age INT,
    gender ENUM('Male', 'Female', 'Other'),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Receiver profiles table
CREATE TABLE IF NOT EXISTS receiver_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    medical_condition VARCHAR(255),
    emergency_contact VARCHAR(15),
    emergency_contact_name VARCHAR(100),
    hospital_registered VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blood requests table
CREATE TABLE IF NOT EXISTS blood_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receiver_id INT,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    units_needed INT NOT NULL,
    hospital_name VARCHAR(255),
    hospital_address TEXT,
    request_date DATE,
    urgency ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    status ENUM('pending', 'matched', 'fulfilled', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Donations table
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT,
    request_id INT,
    donation_date DATE,
    units_donated INT DEFAULT 1,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES blood_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    subject VARCHAR(255),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blood inventory table
CREATE TABLE IF NOT EXISTS blood_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL UNIQUE,
    units_available INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hospitals table
CREATE TABLE IF NOT EXISTS hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(15),
    email VARCHAR(100),
    contact_person VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blood camps table
CREATE TABLE IF NOT EXISTS blood_camps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT,
    camp_name VARCHAR(255),
    camp_date DATE,
    start_time TIME,
    end_time TIME,
    address TEXT,
    contact_number VARCHAR(15),
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255),
    message TEXT,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT SAMPLE DATA
-- =====================================================

-- Insert sample admin user (password: admin123)
INSERT IGNORE INTO users (username, password, email, full_name, phone, address, user_type) 
VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@bloodbank.com', 'System Administrator', '+254700000000', 'Nairobi, Kenya', 'admin');

-- Insert sample donors (password: donor123)
INSERT IGNORE INTO users (username, password, email, full_name, phone, address, user_type) 
VALUES 
('john_donor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@email.com', 'John Doe', '+254711111111', 'Nairobi, Kenya', 'donor'),
('mary_donor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mary.smith@email.com', 'Mary Smith', '+254722222222', 'Mombasa, Kenya', 'donor'),
('peter_donor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'peter.jones@email.com', 'Peter Jones', '+254733333333', 'Kisumu, Kenya', 'donor'),
('sarah_donor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sarah.wilson@email.com', 'Sarah Wilson', '+254744444444', 'Nakuru, Kenya', 'donor');

-- Insert sample receivers (password: receiver123)
INSERT IGNORE INTO users (username, password, email, full_name, phone, address, user_type) 
VALUES 
('james_receiver', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'james.brown@email.com', 'James Brown', '+254755555555', 'Nairobi, Kenya', 'receiver'),
('lisa_receiver', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lisa.davis@email.com', 'Lisa Davis', '+254766666666', 'Mombasa, Kenya', 'receiver'),
('robert_receiver', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'robert.miller@email.com', 'Robert Miller', '+254777777777', 'Kisumu, Kenya', 'receiver');

-- Insert donor profiles (assuming user_ids 2-5)
INSERT IGNORE INTO donor_profiles (user_id, blood_group, last_donation_date, medical_history, is_available, weight, age, gender) 
VALUES 
(2, 'O+', '2024-01-15', 'No significant medical history', TRUE, 70.5, 28, 'Male'),
(3, 'A+', '2024-02-20', 'Mild asthma', TRUE, 65.0, 32, 'Female'),
(4, 'B+', '2023-12-10', 'Healthy', TRUE, 75.0, 35, 'Male'),
(5, 'AB+', '2024-01-30', 'No issues', TRUE, 68.5, 26, 'Female');

-- Insert receiver profiles (assuming user_ids 6-8)
INSERT IGNORE INTO receiver_profiles (user_id, medical_condition, emergency_contact, emergency_contact_name, hospital_registered) 
VALUES 
(6, 'Surgery required', '+254788888888', 'Mary Brown', 'Kenyatta National Hospital'),
(7, 'Anemia', '+254799999999', 'John Davis', 'Coast General Hospital'),
(8, 'Accident victim', '+254700000001', 'Sarah Miller', 'Kisumu Referral Hospital');

-- Insert blood inventory
INSERT IGNORE INTO blood_inventory (blood_group, units_available) 
VALUES 
('A+', 15),
('A-', 5),
('B+', 20),
('B-', 8),
('AB+', 10),
('AB-', 3),
('O+', 25),
('O-', 12);

-- Insert hospitals
INSERT IGNORE INTO hospitals (name, address, phone, email, contact_person) 
VALUES 
('Kenyatta National Hospital', 'Nairobi, Kenya', '+254200000000', 'info@knh.or.ke', 'Dr. Kamau'),
('Moi Teaching and Referral Hospital', 'Eldoret, Kenya', '+254200000001', 'info@mtrh.or.ke', 'Dr. Omondi'),
('Coast General Hospital', 'Mombasa, Kenya', '+254200000002', 'info@cgh.or.ke', 'Dr. Ali'),
('Kisumu Referral Hospital', 'Kisumu, Kenya', '+254200000003', 'info@krh.or.ke', 'Dr. Otieno');

-- Insert blood requests
INSERT IGNORE INTO blood_requests (receiver_id, blood_group, units_needed, hospital_name, hospital_address, request_date, urgency, status, notes) 
VALUES 
(6, 'O+', 2, 'Kenyatta National Hospital', 'Nairobi, Kenya', '2024-03-15', 'urgent', 'pending', 'Emergency surgery required'),
(7, 'A+', 1, 'Coast General Hospital', 'Mombasa, Kenya', '2024-03-20', 'normal', 'pending', 'Regular transfusion'),
(8, 'B+', 3, 'Kisumu Referral Hospital', 'Kisumu, Kenya', '2024-03-10', 'emergency', 'matched', 'Accident victim needs immediate transfusion');

-- Insert donations
INSERT IGNORE INTO donations (donor_id, request_id, donation_date, units_donated, status, notes) 
VALUES 
(2, 1, '2024-03-01', 1, 'completed', 'Successful donation'),
(3, 2, '2024-03-05', 1, 'scheduled', 'Appointment confirmed'),
(4, 3, '2024-02-28', 2, 'completed', 'Donated 2 units');

-- Insert messages
INSERT IGNORE INTO messages (sender_id, receiver_id, subject, message, is_read) 
VALUES 
(2, 6, 'Donation Confirmation', 'I would like to confirm my blood donation appointment.', TRUE),
(3, 7, 'Availability', 'I am available to donate blood next week.', FALSE),
(6, 2, 'Thank You', 'Thank you for your generous donation!', TRUE);

-- Insert blood camps
INSERT IGNORE INTO blood_camps (hospital_id, camp_name, camp_date, start_time, end_time, address, contact_number, status) 
VALUES 
(1, 'Annual Blood Donation Camp', '2024-04-10', '09:00:00', '17:00:00', 'Kenyatta National Hospital Grounds', '+254200000000', 'upcoming'),
(2, 'Community Blood Drive', '2024-04-15', '08:00:00', '16:00:00', 'Eldoret Town Hall', '+254200000001', 'upcoming'),
(3, 'Emergency Blood Camp', '2024-03-25', '10:00:00', '18:00:00', 'Mombasa Social Hall', '+254200000002', 'ongoing');

-- Insert notifications
INSERT IGNORE INTO notifications (user_id, title, message, type, is_read) 
VALUES 
(2, 'Welcome', 'Welcome to Blood Bank Management System!', 'success', TRUE),
(3, 'Donation Reminder', 'Your donation appointment is tomorrow.', 'info', FALSE),
(6, 'Request Update', 'Your blood request has been matched with a donor.', 'success', TRUE),
(7, 'Urgent Request', 'New matching donor found for your request.', 'warning', FALSE);

-- =====================================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_user_type ON users(user_type);
CREATE INDEX IF NOT EXISTS idx_blood_group ON donor_profiles(blood_group);
CREATE INDEX IF NOT EXISTS idx_availability ON donor_profiles(is_available);
CREATE INDEX IF NOT EXISTS idx_request_status ON blood_requests(status);
CREATE INDEX IF NOT EXISTS idx_request_urgency ON blood_requests(urgency);
CREATE INDEX IF NOT EXISTS idx_donation_status ON donations(status);
CREATE INDEX IF NOT EXISTS idx_message_read ON messages(is_read);
CREATE INDEX IF NOT EXISTS idx_notification_read ON notifications(is_read);

-- =====================================================
-- CREATE VIEWS FOR COMMON QUERIES
-- =====================================================

CREATE OR REPLACE VIEW available_donors AS
SELECT u.id, u.full_name, u.phone, u.address, dp.blood_group, dp.last_donation_date, dp.weight, dp.age, dp.gender
FROM users u
JOIN donor_profiles dp ON u.id = dp.user_id
WHERE u.user_type = 'donor' AND dp.is_available = TRUE;

CREATE OR REPLACE VIEW pending_requests AS
SELECT br.*, u.full_name as receiver_name, u.phone as receiver_phone, rp.emergency_contact, rp.hospital_registered
FROM blood_requests br
JOIN users u ON br.receiver_id = u.id
LEFT JOIN receiver_profiles rp ON u.id = rp.user_id
WHERE br.status = 'pending'
ORDER BY 
    CASE br.urgency 
        WHEN 'emergency' THEN 1
        WHEN 'urgent' THEN 2
        ELSE 3
    END,
    br.created_at ASC;

-- =====================================================
-- CREATE STORED PROCEDURES
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS FindMatchingDonors$$
CREATE PROCEDURE FindMatchingDonors(
    IN p_blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    IN p_location VARCHAR(255)
)
BEGIN
    SELECT u.*, dp.blood_group, dp.last_donation_date
    FROM users u
    JOIN donor_profiles dp ON u.id = dp.user_id
    WHERE u.user_type = 'donor' 
    AND dp.is_available = TRUE
    AND dp.blood_group = p_blood_group
    AND (p_location IS NULL OR p_location = '' OR u.address LIKE CONCAT('%', p_location, '%'))
    ORDER BY 
        CASE WHEN dp.last_donation_date IS NULL THEN 0 ELSE 1 END,
        dp.last_donation_date ASC;
END$$

DROP PROCEDURE IF EXISTS GetBloodStatistics$$
CREATE PROCEDURE GetBloodStatistics()
BEGIN
    SELECT 
        bi.blood_group,
        bi.units_available,
        COUNT(DISTINCT dp.user_id) as available_donors,
        COUNT(DISTINCT br.id) as pending_requests
    FROM blood_inventory bi
    LEFT JOIN donor_profiles dp ON bi.blood_group = dp.blood_group AND dp.is_available = TRUE
    LEFT JOIN blood_requests br ON bi.blood_group = br.blood_group AND br.status = 'pending'
    GROUP BY bi.blood_group, bi.units_available;
END$$

DELIMITER ;

-- =====================================================
-- CREATE TRIGGERS
-- =====================================================

DELIMITER $$

DROP TRIGGER IF EXISTS update_blood_inventory$$
CREATE TRIGGER update_blood_inventory
AFTER UPDATE ON donations
FOR EACH ROW
BEGIN
    DECLARE donor_blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');
    
    IF NEW.status = 'completed' AND (OLD.status != 'completed' OR OLD.status IS NULL) THEN
        -- Get donor's blood group
        SELECT blood_group INTO donor_blood_group 
        FROM donor_profiles 
        WHERE user_id = NEW.donor_id;
        
        -- Update inventory
        UPDATE blood_inventory 
        SET units_available = units_available + NEW.units_donated 
        WHERE blood_group = donor_blood_group;
    END IF;
END$$

DROP TRIGGER IF EXISTS notify_on_request_match$$
CREATE TRIGGER notify_on_request_match
AFTER UPDATE ON blood_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'matched' AND OLD.status != 'matched' THEN
        -- Notify receiver
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (
            NEW.receiver_id,
            'Request Matched',
            CONCAT('Your request for ', NEW.units_needed, ' unit(s) of ', NEW.blood_group, ' blood has been matched with a donor.'),
            'success'
        );
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

SELECT 'Blood Bank Database created successfully!' as 'Status';

SELECT 'Database Summary' as 'Section';
SELECT CONCAT('Total Users: ', COUNT(*)) as 'Summary' FROM users;
SELECT CONCAT('Total Donors: ', COUNT(*)) as 'Summary' FROM donor_profiles;
SELECT CONCAT('Total Receivers: ', COUNT(*)) as 'Summary' FROM receiver_profiles;
SELECT CONCAT('Total Blood Requests: ', COUNT(*)) as 'Summary' FROM blood_requests;
SELECT CONCAT('Total Donations: ', COUNT(*)) as 'Summary' FROM donations;

-- =====================================================
-- END OF SCRIPT
-- =====================================================