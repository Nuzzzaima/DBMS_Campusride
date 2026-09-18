-- =========================================================
-- CAMPUSRIDE - COMPLETE DATABASE SQL
-- Compatible with the provided CampusRide ER diagram
-- and the Ride Management PHP files.
--
-- Database: campusride
-- No DROP DATABASE is used.
-- Passwords are stored using SHA2-256.
-- =========================================================

CREATE DATABASE IF NOT EXISTS campusride;
USE campusride;

-- =========================================================
-- 1. USER TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS `USER` (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    university_email VARCHAR(150) NOT NULL UNIQUE,
    profile_picture VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    department VARCHAR(100) NOT NULL,
    CONSTRAINT chk_user_email
        CHECK (university_email REGEXP '^[A-Za-z0-9._%+-]+@uiu\.ac\.bd$')
) ENGINE=InnoDB;


-- =========================================================
-- 2. VEHICLE TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS vehicle (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_type VARCHAR(30) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    seat_capacity INT NOT NULL,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    CONSTRAINT chk_vehicle_type
        CHECK (vehicle_type IN ('Car', 'Bike')),
    CONSTRAINT chk_vehicle_seat_capacity
        CHECK (seat_capacity > 0),
    CONSTRAINT fk_vehicle_user
        FOREIGN KEY (user_id) REFERENCES `USER`(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =========================================================
-- 3. RIDE TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS ride (
    ride_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT DEFAULT NULL,
    ride_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    start_location VARCHAR(150) NOT NULL,
    destination VARCHAR(150) NOT NULL,
    ride_type VARCHAR(50) NOT NULL,
    available_seats INT NOT NULL,
    fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    women_only VARCHAR(3) NOT NULL DEFAULT 'No',
    description TEXT,
    ride_status VARCHAR(20) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_ride_seats
        CHECK (available_seats >= 0),
    CONSTRAINT chk_ride_fare
        CHECK (fare >= 0),
    CONSTRAINT chk_ride_women_only
        CHECK (women_only IN ('Yes', 'No')),
    CONSTRAINT chk_ride_status
        CHECK (ride_status IN ('Active', 'Completed', 'Cancelled')),
    CONSTRAINT fk_ride_user
        FOREIGN KEY (user_id) REFERENCES `USER`(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ride_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES vehicle(vehicle_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =========================================================
-- 4. RIDE_REQUEST TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS ride_request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ride_id INT NOT NULL,
    request_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    request_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    CONSTRAINT chk_request_status
        CHECK (request_status IN ('Pending', 'Accepted', 'Rejected')),
    CONSTRAINT fk_request_user
        FOREIGN KEY (user_id) REFERENCES `USER`(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_request_ride
        FOREIGN KEY (ride_id) REFERENCES ride(ride_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =========================================================
-- 5. BOOKING TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS booking (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL UNIQUE,
    booking_date DATE NOT NULL,
    booking_status VARCHAR(20) NOT NULL DEFAULT 'Confirmed',
    CONSTRAINT chk_booking_status
        CHECK (booking_status IN ('Confirmed', 'Cancelled', 'Completed')),
    CONSTRAINT fk_booking_request
        FOREIGN KEY (request_id) REFERENCES ride_request(request_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =========================================================
-- 6. SOS_ALERT TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS sos_alert (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    alert_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    alert_status VARCHAR(20) NOT NULL DEFAULT 'Active',
    CONSTRAINT chk_alert_status
        CHECK (alert_status IN ('Active', 'Resolved')),
    CONSTRAINT fk_sos_user
        FOREIGN KEY (user_id) REFERENCES `USER`(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sos_booking
        FOREIGN KEY (booking_id) REFERENCES booking(booking_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =========================================================
-- 7. RATING TABLE
-- =========================================================

CREATE TABLE IF NOT EXISTS rating (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    stars INT NOT NULL,
    review TEXT,
    rating_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_rating_stars
        CHECK (stars BETWEEN 1 AND 5),
    CONSTRAINT chk_rating_users
        CHECK (reviewer_id <> reviewee_id),
    CONSTRAINT fk_rating_booking
        FOREIGN KEY (booking_id) REFERENCES booking(booking_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rating_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES `USER`(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rating_reviewee
        FOREIGN KEY (reviewee_id) REFERENCES `USER`(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =========================================================
-- DUMMY DATA
-- =========================================================

-- =========================================================
-- USER DATA
-- =========================================================

INSERT INTO `USER`
(user_id, student_id, full_name, created_at, university_email,
 profile_picture, password, gender, phone, department)
VALUES
(1, '011231001', 'Arafat Rahman', '2026-01-10 10:00:00',
 'arafat@uiu.ac.bd', 'profile1.jpg', SHA2('Arafat123', 256),
 'Male', '01711000001', 'CSE'),

(2, '011231002', 'Nusrat Jahan', '2026-01-11 11:00:00',
 'nusrat@uiu.ac.bd', 'profile2.jpg', SHA2('Nusrat123', 256),
 'Female', '01711000002', 'CSE'),

(3, '011231003', 'Tanvir Ahmed', '2026-01-12 12:00:00',
 'tanvir@uiu.ac.bd', 'profile3.jpg', SHA2('Tanvir123', 256),
 'Male', '01711000003', 'BBA'),

(4, '011231004', 'Sadia Islam', '2026-01-13 09:30:00',
 'sadia@uiu.ac.bd', 'profile4.jpg', SHA2('Sadia123', 256),
 'Female', '01711000004', 'CSE'),

(5, '011231005', 'Rakib Hasan', '2026-01-15 14:00:00',
 'rakib@uiu.ac.bd', 'profile5.jpg', SHA2('Rakib123', 256),
 'Male', '01711000005', 'EEE'),

(6, '011231006', 'Mahi Rahman', '2026-01-17 15:30:00',
 'mahi@uiu.ac.bd', 'profile6.jpg', SHA2('Mahi123', 256),
 'Female', '01711000006', 'CSE'),

(7, '011231007', 'Fahim Chowdhury', '2026-01-18 16:00:00',
 'fahim@uiu.ac.bd', 'profile7.jpg', SHA2('Fahim123', 256),
 'Male', '01711000007', 'CSE'),

(8, '011231008', 'Jannatul Ferdous', '2026-01-20 10:30:00',
 'jannatul@uiu.ac.bd', 'profile8.jpg', SHA2('Jannatul123', 256),
 'Female', '01711000008', 'BBA')
ON DUPLICATE KEY UPDATE user_id = user_id;


-- =========================================================
-- VEHICLE DATA
-- =========================================================

INSERT INTO vehicle
(vehicle_id, user_id, vehicle_type, created_at, seat_capacity,
 registration_number)
VALUES
(1, 1, 'Car', '2026-02-01 10:00:00', 4, 'DHAKA-METRO-GA-1101'),
(2, 3, 'Bike', '2026-02-02 11:00:00', 1, 'DHAKA-METRO-LA-2202'),
(3, 5, 'Car', '2026-02-03 12:00:00', 4, 'DHAKA-METRO-GHA-3303'),
(4, 7, 'Car', '2026-02-05 13:00:00', 5, 'DHAKA-METRO-KHA-4404'),
(5, 2, 'Bike', '2026-02-06 14:00:00', 1, 'DHAKA-METRO-LA-5505')
ON DUPLICATE KEY UPDATE vehicle_id = vehicle_id;


-- =========================================================
-- RIDE DATA
-- =========================================================

INSERT INTO ride
(ride_id, user_id, vehicle_id, ride_date, departure_time,
 start_location, destination, ride_type, available_seats,
 fare, women_only, description, ride_status, created_at)
VALUES

(1, 1, 1, '2026-09-18', '08:30:00',
 'Khilgaon Railgate', 'United International University',
 'Rickshaw Share', 3, 50.00, 'No',
 'Morning ride to UIU. Seats available.',
 'Active', '2026-09-16 08:00:00'),

(2, 2, 5, '2026-09-18', '08:00:00',
 'Badda', 'United International University',
 'Bike Share', 1, 40.00, 'Yes',
 'Morning ride for female students.',
 'Active', '2026-09-16 09:00:00'),

(3, 3, 2, '2026-09-19', '09:00:00',
 'Mirpur 10', 'United International University',
 'Bike Share', 1, 60.00, 'No',
 'Direct route to UIU.',
 'Active', '2026-09-16 10:00:00'),

(4, 5, 3, '2026-09-19', '08:15:00',
 'Jatrabari', 'United International University',
 'Car Share', 3, 70.00, 'No',
 'Carpool ride with limited seats.',
 'Active', '2026-09-16 10:30:00'),

(5, 7, 4, '2026-09-20', '08:45:00',
 'Uttara', 'United International University',
 'Car Share', 4, 80.00, 'No',
 'Comfortable morning carpool.',
 'Active', '2026-09-16 11:00:00'),

(6, 1, 1, '2026-09-10', '08:30:00',
 'Khilgaon Railgate', 'United International University',
 'Car Share', 0, 50.00, 'No',
 'Completed morning ride.',
 'Completed', '2026-09-09 12:00:00'),

(7, 5, 3, '2026-09-12', '08:00:00',
 'Jatrabari', 'United International University',
 'Car Share', 3, 70.00, 'No',
 'Ride cancelled by driver.',
 'Cancelled', '2026-09-11 15:00:00'),

(8, 3, 2, '2026-09-11', '09:00:00',
 'Mirpur 10', 'United International University',
 'Bike Share', 0, 60.00, 'No',
 'Completed ride.',
 'Completed', '2026-09-10 14:00:00')
ON DUPLICATE KEY UPDATE ride_id = ride_id;


-- =========================================================
-- RIDE REQUEST DATA
-- =========================================================

INSERT INTO ride_request
(request_id, user_id, ride_id, request_time, request_status)
VALUES
(1, 2, 1, '2026-09-16 12:00:00', 'Accepted'),
(2, 4, 1, '2026-09-16 12:30:00', 'Pending'),
(3, 6, 1, '2026-09-16 13:00:00', 'Rejected'),
(4, 4, 2, '2026-09-16 13:30:00', 'Accepted'),
(5, 8, 2, '2026-09-16 14:00:00', 'Pending'),
(6, 2, 4, '2026-09-16 14:30:00', 'Accepted'),
(7, 7, 4, '2026-09-16 15:00:00', 'Pending'),
(8, 2, 6, '2026-09-09 16:00:00', 'Accepted'),
(9, 4, 6, '2026-09-09 17:00:00', 'Accepted'),
(10, 8, 3, '2026-09-16 16:00:00', 'Rejected')
ON DUPLICATE KEY UPDATE request_id = request_id;


-- =========================================================
-- BOOKING DATA
-- =========================================================

INSERT INTO booking
(booking_id, request_id, booking_date, booking_status)
VALUES
(1, 8, '2026-09-10', 'Completed'),
(2, 9, '2026-09-10', 'Completed'),
(3, 1, '2026-09-16', 'Confirmed'),
(4, 4, '2026-09-16', 'Confirmed'),
(5, 6, '2026-09-16', 'Confirmed'),
(6, 3, '2026-09-16', 'Cancelled')
ON DUPLICATE KEY UPDATE booking_id = booking_id;


-- =========================================================
-- SOS ALERT DATA
-- =========================================================

INSERT INTO sos_alert
(alert_id, user_id, booking_id, alert_time, alert_status)
VALUES
(1, 2, 3, '2026-09-18 08:45:00', 'Active'),
(2, 2, 1, '2026-09-10 08:50:00', 'Resolved'),
(3, 4, 2, '2026-09-10 09:10:00', 'Resolved')
ON DUPLICATE KEY UPDATE alert_id = alert_id;


-- =========================================================
-- RATING DATA
-- =========================================================

INSERT INTO rating
(rating_id, booking_id, reviewer_id, reviewee_id,
 stars, review, rating_date)
VALUES
(1, 1, 2, 1, 5,
 'Very safe and comfortable ride.',
 '2026-09-10 12:00:00'),

(2, 2, 4, 1, 4,
 'Good ride and friendly driver.',
 '2026-09-10 13:00:00'),

(3, 1, 1, 2, 5,
 'Great passenger and arrived on time.',
 '2026-09-10 13:30:00'),

(4, 2, 1, 4, 4,
 'Pleasant ride experience.',
 '2026-09-10 14:00:00')
ON DUPLICATE KEY UPDATE rating_id = rating_id;


-- =========================================================
-- OPTIONAL VERIFICATION QUERIES
-- Run these after importing to check the data.
-- =========================================================

SELECT * FROM `USER`;
SELECT * FROM vehicle;
SELECT * FROM ride;
SELECT * FROM ride_request;
SELECT * FROM booking;
SELECT * FROM sos_alert;
SELECT * FROM rating;

-- Useful joined view for checking the complete ride/request/booking flow:
SELECT
    rr.request_id,
    u.full_name AS requester,
    r.ride_id,
    r.start_location,
    r.destination,
    r.ride_date,
    r.departure_time,
    rr.request_status,
    b.booking_id,
    b.booking_status
FROM ride_request rr
JOIN `USER` u ON rr.user_id = u.user_id
JOIN ride r ON rr.ride_id = r.ride_id
LEFT JOIN booking b ON rr.request_id = b.request_id
ORDER BY rr.request_id;
