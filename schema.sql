-- ============================================================
--  HOTEL CONCIERGE & BILLING SYSTEM — DATABASE SCHEMA
-- ============================================================

CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_db;

-- ----------------------------
--  ROOMS
-- ----------------------------
CREATE TABLE IF NOT EXISTS rooms (
    room_id      INT AUTO_INCREMENT PRIMARY KEY,
    room_number  VARCHAR(10)  NOT NULL UNIQUE,
    room_type    ENUM('Standard','Deluxe','Suite','Presidential') NOT NULL DEFAULT 'Standard',
    floor        TINYINT      NOT NULL DEFAULT 1,
    capacity     TINYINT      NOT NULL DEFAULT 2,
    rate_per_night DECIMAL(10,2) NOT NULL,
    status       ENUM('Available','Occupied','Maintenance','Reserved') NOT NULL DEFAULT 'Available',
    amenities    TEXT,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
--  GUESTS
-- ----------------------------
CREATE TABLE IF NOT EXISTS guests (
    guest_id     INT AUTO_INCREMENT PRIMARY KEY,
    first_name   VARCHAR(60)  NOT NULL,
    last_name    VARCHAR(60)  NOT NULL,
    email        VARCHAR(120) UNIQUE,
    phone        VARCHAR(25),
    id_type      ENUM('Passport','Driver License','National ID','Other') DEFAULT 'Passport',
    id_number    VARCHAR(50),
    nationality  VARCHAR(60),
    address      TEXT,
    vip_status   BOOLEAN      DEFAULT FALSE,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
--  RESERVATIONS (CHECK-IN / CHECK-OUT)
-- ----------------------------
CREATE TABLE IF NOT EXISTS reservations (
    reservation_id   INT AUTO_INCREMENT PRIMARY KEY,
    guest_id         INT          NOT NULL,
    room_id          INT          NOT NULL,
    check_in_date    DATE         NOT NULL,
    check_out_date   DATE         NOT NULL,
    actual_check_in  DATETIME,
    actual_check_out DATETIME,
    adults           TINYINT      NOT NULL DEFAULT 1,
    children         TINYINT      NOT NULL DEFAULT 0,
    status           ENUM('Confirmed','Checked-In','Checked-Out','Cancelled','No-Show') NOT NULL DEFAULT 'Confirmed',
    special_requests TEXT,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(guest_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id)  REFERENCES rooms(room_id)   ON DELETE RESTRICT
);

-- ----------------------------
--  SERVICE CATEGORIES
-- ----------------------------
CREATE TABLE IF NOT EXISTS service_categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(80)  NOT NULL,
    description   TEXT
);

-- ----------------------------
--  SERVICES (Menu of chargeable items)
-- ----------------------------
CREATE TABLE IF NOT EXISTS services (
    service_id    INT AUTO_INCREMENT PRIMARY KEY,
    category_id   INT          NOT NULL,
    service_name  VARCHAR(120) NOT NULL,
    unit_price    DECIMAL(10,2) NOT NULL,
    unit_label    VARCHAR(30)  DEFAULT 'item',   -- e.g. 'night','hour','bottle'
    is_active     BOOLEAN      DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES service_categories(category_id)
);

-- ----------------------------
--  ROOM SERVICE ORDERS
-- ----------------------------
CREATE TABLE IF NOT EXISTS room_service_orders (
    order_id         INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id   INT          NOT NULL,
    service_id       INT          NOT NULL,
    quantity         DECIMAL(8,2) NOT NULL DEFAULT 1,
    unit_price       DECIMAL(10,2) NOT NULL,          -- snapshot at time of order
    discount_pct     DECIMAL(5,2) DEFAULT 0.00,
    notes            TEXT,
    ordered_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    status           ENUM('Pending','Delivered','Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id)     REFERENCES services(service_id)
);

-- ----------------------------
--  PAYMENTS
-- ----------------------------
CREATE TABLE IF NOT EXISTS payments (
    payment_id       INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id   INT          NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    payment_method   ENUM('Cash','Credit Card','Debit Card','GCash','Maya','Bank Transfer','Other') NOT NULL,
    reference_no     VARCHAR(80),
    paid_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    notes            TEXT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE
);

-- ----------------------------
--  TAX / CHARGE RATES
-- ----------------------------
CREATE TABLE IF NOT EXISTS charge_rates (
    rate_id      INT AUTO_INCREMENT PRIMARY KEY,
    rate_name    VARCHAR(60)  NOT NULL,
    rate_pct     DECIMAL(5,2) NOT NULL,
    applies_to   ENUM('Room','Services','All') DEFAULT 'All',
    is_active    BOOLEAN      DEFAULT TRUE
);

-- ============================================================
--  SEED DATA
--  Uses INSERT IGNORE so the script can be run multiple times
--  without throwing duplicate-key errors.
-- ============================================================

INSERT IGNORE INTO rooms (room_number, room_type, floor, capacity, rate_per_night, status, amenities) VALUES
('101','Standard',     1,2,  2500.00,'Available','WiFi, TV, AC'),
('102','Standard',     1,2,  2500.00,'Available','WiFi, TV, AC'),
('201','Deluxe',       2,2,  4200.00,'Available','WiFi, Smart TV, AC, Mini-bar, City View'),
('202','Deluxe',       2,3,  4500.00,'Available','WiFi, Smart TV, AC, Mini-bar, City View'),
('301','Suite',        3,4,  8800.00,'Available','WiFi, Smart TV, AC, Full Kitchenette, Living Room, Jacuzzi'),
('401','Presidential', 4,6, 18500.00,'Available','All Amenities, Private Butler, Panoramic View, Private Pool');

INSERT IGNORE INTO service_categories (category_id, category_name, description) VALUES
(1,'Room Service',  'Food and beverage delivered to guest room'),
(2,'Laundry',       'Laundry and dry-cleaning services'),
(3,'Spa & Wellness','Spa treatments and wellness services'),
(4,'Transport',     'Airport transfers and local transportation'),
(5,'Minibar',       'In-room minibar charges'),
(6,'Telephone',     'Local and international phone charges'),
(7,'Miscellaneous', 'Other hotel charges');

INSERT IGNORE INTO services (service_id, category_id, service_name, unit_price, unit_label) VALUES
( 1,1,'Continental Breakfast',    450.00,'meal'),
( 2,1,'Full Breakfast',           680.00,'meal'),
( 3,1,'Club Sandwich',            320.00,'plate'),
( 4,1,'Pasta Carbonara',          380.00,'plate'),
( 5,1,'Room Service Burger',      350.00,'plate'),
( 6,1,'Bottle of Water',           60.00,'bottle'),
( 7,1,'Fresh Juice',              150.00,'glass'),
( 8,2,'Regular Wash',             250.00,'kg'),
( 9,2,'Dry Cleaning (Suit)',      550.00,'piece'),
(10,2,'Express Laundry',          380.00,'kg'),
(11,3,'60-min Massage',          1200.00,'session'),
(12,3,'Full Body Scrub',         1500.00,'session'),
(13,4,'Airport Transfer (Sedan)',1800.00,'trip'),
(14,4,'Airport Transfer (Van)',  2500.00,'trip'),
(15,5,'Minibar Beer',             180.00,'bottle'),
(16,5,'Minibar Soda',              80.00,'can'),
(17,5,'Minibar Chips',             90.00,'pack'),
(18,6,'Local Call',                20.00,'minute'),
(19,6,'International Call',       150.00,'minute'),
(20,7,'Extra Bed',                800.00,'night'),
(21,7,'Late Check-out Fee',      1500.00,'hour');

INSERT IGNORE INTO charge_rates (rate_id, rate_name, rate_pct, applies_to) VALUES
(1,'VAT (12%)',            12.00,'All'),
(2,'Service Charge (10%)', 10.00,'All');

INSERT IGNORE INTO guests (guest_id, first_name, last_name, email, phone, id_type, id_number, nationality, vip_status) VALUES
(1,'Maria','Santos','maria.santos@email.com','+63912-345-6789','National ID','PH-1234567','Filipino',    FALSE),
(2,'James','Reyes', 'james.reyes@email.com', '+63917-654-3210','Passport',   'P1234567A', 'Filipino',    TRUE),
(3,'Emily','Chen',  'emily.chen@email.com',  '+65-9123-4567',  'Passport',   'S9876543B', 'Singaporean', FALSE);
