-- LTTMS Database Schema
-- Local Tourism and Travel Management System

CREATE DATABASE IF NOT EXISTS lttms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lttms_db;

-- Users table: customers, agents, admins
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    kbz_phone VARCHAR(20),
    kbz_name VARCHAR(100),
    nrc_number VARCHAR(50),
    nrc_front_photo VARCHAR(255),
    nrc_back_photo VARCHAR(255),
    role ENUM('customer', 'agent', 'admin') NOT NULL DEFAULT 'customer',
    agent_location VARCHAR(100) DEFAULT NULL,
    status ENUM('pending', 'approved', 'suspended') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Hotels managed by agents
CREATE TABLE hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(200) NOT NULL,
    description TEXT,
    rating DECIMAL(2,1) DEFAULT 0.0,
    price_per_night DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Transportation options
CREATE TABLE transportation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    company_name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Travel packages
CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    destination VARCHAR(200) NOT NULL,
    description TEXT,
    duration_days INT NOT NULL,
    price_per_person DECIMAL(10,2) NOT NULL,
    max_slots INT NOT NULL DEFAULT 20,
    remaining_slots INT NOT NULL DEFAULT 20,
    hotel_id INT,
    transportation_id INT,
    image_url VARCHAR(255),
    rating_avg DECIMAL(2,1) DEFAULT 0.0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE SET NULL,
    FOREIGN KEY (transportation_id) REFERENCES transportation(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bookings
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    package_id INT NOT NULL,
    num_people INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    booking_date DATE NOT NULL,
    travel_date DATE NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'KBZ Pay',
    payment_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Feedback and ratings
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    package_id INT NOT NULL,
    booking_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    agent_reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Popular destinations (admin-managed)
CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    tagline VARCHAR(200),
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Indexes for common queries
CREATE INDEX idx_packages_destination ON packages(destination);
CREATE INDEX idx_packages_price ON packages(price_per_person);
CREATE INDEX idx_packages_status ON packages(status);
CREATE INDEX idx_bookings_customer ON bookings(customer_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_feedback_package ON feedback(package_id);

-- Seed admin account (password: admin123)
INSERT INTO users (username, email, password, full_name, role, status)
VALUES ('admin', 'admin@lttms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', 'approved');

-- Seed demo agent (password: agent123)
INSERT INTO users (username, email, password, full_name, phone, kbz_phone, kbz_name, role, agent_location, status)
VALUES ('agent1', 'agent@lttms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Agent', '09123456789', '09 987 654 321', 'Demo Agent KBZ', 'agent', 'Mandalay Region', 'approved');

-- Seed real Myanmar hotels with realistic MMK prices
INSERT INTO hotels (agent_id, name, location, description, rating, price_per_night, image_url) VALUES
(2, 'Inle Heritage Stilt Houses', 'Nyaung Shwe, Inle Lake, Shan State', 'Traditional stilt houses on Inle Lake with panoramic views of the water and Shan hills. Boat transfer included.', 4.5, 95000.00, 'hotel_inle_heritage.jpg'),
(2, 'Amazing Inlay Resort', 'Inle Lake, Shan State', 'Lakeside resort with floating garden views, traditional Shan architecture, and complimentary bicycle rental.', 4.2, 75000.00, 'hotel_amazing_inlay.jpg'),
(2, 'Bagan Thande Hotel', 'Old Bagan, Mandalay Region', 'Colonial-era heritage hotel on the banks of the Irrawaddy River, walking distance to major temples.', 4.3, 80000.00, 'hotel_bagan_thande.jpg'),
(2, 'Myanmar Han Hotel Bagan', 'New Bagan, Mandalay Region', 'Comfortable mid-range hotel with pool, near Dhammayangyi Temple. Free e-bike rental for temple exploration.', 4.0, 55000.00, 'hotel_myanmar_han.jpg'),
(2, 'Amata Resort Ngapali', 'Ngapali Beach, Thandwe, Rakhine State', 'Beachfront resort with private beach access, ocean-view bungalows, and fresh seafood restaurant.', 4.7, 160000.00, 'hotel_amata_ngapali.jpg'),
(2, 'Pleasant View Resort Ngapali', 'Ngapali Beach, Thandwe, Rakhine State', 'Mid-range beachside resort with garden and sea view rooms. Snorkeling equipment available.', 4.1, 100000.00, 'hotel_pleasant_view.jpg'),
(2, 'Sedona Hotel Mandalay', 'Mandalay, Mandalay Region', 'Premium hotel in the heart of Mandalay with rooftop pool and views of Mandalay Hill and the Royal Palace.', 4.4, 110000.00, 'hotel_sedona_mandalay.jpg'),
(2, 'Hotel Mandalay', '73rd Street, Mandalay, Mandalay Region', 'Budget-friendly hotel in central Mandalay. Close to Zegyo Market and Mahamuni Pagoda.', 3.8, 45000.00, 'hotel_mandalay.jpg'),
(2, 'Mountain Top Hotel', 'Kyaiktiyo, Mon State', 'Located near the Golden Rock summit. Best sunrise views of Kyaiktiyo Pagoda.', 3.9, 50000.00, 'hotel_mountain_top.jpg'),
(2, 'Hpa-An Lodge', 'Hpa-An, Kayin State', 'Riverside lodge with limestone karst mountain views. Guided cave and mountain tours available.', 4.3, 70000.00, 'hotel_hpaan_lodge.jpg');

-- Seed real Myanmar transportation with actual fares
INSERT INTO transportation (agent_id, type, company_name, description, price) VALUES
(2, 'VIP Bus', 'JJ Express', 'VIP 2+1 seating with reclining seats, onboard WiFi, toilet, snacks. Yangon-Bagan overnight ~9hrs.', 38000.00),
(2, 'Express Bus', 'JJ Express', 'Standard express bus with 2+2 seating, AC, Yangon-Mandalay overnight ~10hrs.', 27000.00),
(2, 'VIP Bus', 'Mandalar Minn', 'Premium bus service Yangon-Mandalay with first-class 2+1 seats.', 45000.00),
(2, 'Domestic Flight', 'Myanmar National Airlines', 'Yangon to Bagan direct flight, approximately 1 hour.', 185000.00),
(2, 'Domestic Flight', 'Air KBZ', 'Yangon to Mandalay direct flight, approximately 1.5 hours.', 155000.00),
(2, 'Domestic Flight', 'Myanmar National Airlines', 'Yangon to Thandwe (Ngapali) direct flight, approximately 50 minutes.', 210000.00),
(2, 'Private Car', 'LTTMS Transport', 'Air-conditioned private vehicle with driver for day trips and transfers.', 85000.00),
(2, 'Shared Minivan', 'Local Operator', 'Shared minivan service for shorter routes (Mandalay-Inle Lake ~6hrs).', 20000.00);

-- Seed real Myanmar travel packages with authentic prices
INSERT INTO packages (agent_id, title, destination, description, duration_days, price_per_person, max_slots, remaining_slots, hotel_id, transportation_id, image_url, rating_avg) VALUES
(2, 'Inle Lake Discovery',
 'Inle Lake, Shan State',
 'Experience the magic of Inle Lake — famous for its leg-rowing Intha fishermen, floating gardens, and silk-weaving villages. Includes full-day boat tour visiting Phaung Daw Oo Pagoda, Nga Hpe Kyaung monastery, local cheroot workshops, and the vibrant Nyaung Shwe morning market. Optional sunset boat ride.',
 3, 350000.00, 20, 15, 1, 2, 'pkg_inle.jpg', 4.3),

(2, 'Bagan Temples & Balloon Sunrise',
 'Bagan, Mandalay Region',
 'Explore over 2,000 ancient Buddhist temples and pagodas dating back to the 9th century. Highlights include Ananda Temple, Dhammayangyi Temple, Shwesandaw Pagoda sunset, and an optional hot-air balloon ride at sunrise (extra ~$350). Guided e-bike temple tours, traditional lacquerware workshop visit, and Irrawaddy riverboat sunset cruise included.',
 4, 520000.00, 15, 10, 3, 4, 'pkg_bagan.jpg', 4.6),

(2, 'Ngapali Beach Getaway',
 'Ngapali Beach, Rakhine State',
 'Relax on Myanmar''s most pristine beach — crystal-clear waters and white sand stretching for 7km. Includes snorkeling trips to Pearl Island, traditional fishing village visits, fresh lobster and crab seafood dinners, and optional scuba diving. Fly direct from Yangon.',
 5, 1250000.00, 10, 8, 5, 6, 'pkg_ngapali.jpg', 4.7),

(2, 'Mandalay & Amarapura Heritage Tour',
 'Mandalay, Mandalay Region',
 'Discover Myanmar''s cultural capital. Visit Mandalay Royal Palace, Kuthodaw Pagoda (the world''s largest book of 729 marble slabs), Shwenandaw Monastery''s exquisite teak carvings, and Sagaing Hill''s 600 monasteries. Walk the iconic 1.2km U Bein Bridge at sunset in Amarapura. Includes traditional marionette puppet show.',
 3, 300000.00, 25, 20, 8, 2, 'pkg_mandalay.jpg', 4.1),

(2, 'Golden Rock Pilgrimage',
 'Kyaiktiyo, Mon State',
 'Journey to the gravity-defying Golden Rock — a sacred boulder balanced on the edge of a cliff, covered in gold leaf. Ride the famous open-air mountain trucks from Kinpun base camp. Overnight stay at the summit for sunrise prayers. One of Myanmar''s most important Buddhist pilgrimage sites.',
 2, 180000.00, 30, 25, 9, 1, 'pkg_golden_rock.jpg', 4.4),

(2, 'Hpa-An Caves & Mountains',
 'Hpa-An, Kayin State',
 'Explore the stunning limestone karst landscape of Kayin State. Visit Saddan Cave (boat ride through underground river), Kawgun Cave (thousands of tiny Buddha images), climb Mount Zwegabin for panoramic views, and witness thousands of bats emerging from Bat Cave at dusk. Kayaking on the Thanlwin River included.',
 3, 280000.00, 15, 12, 10, 8, 'pkg_hpaan.jpg', 4.5),

(2, 'Budget Bagan Explorer',
 'Bagan, Mandalay Region',
 'Affordable Bagan experience by overnight bus from Yangon. Explore the temple plains by e-bike, visit local markets, watch sunset from Shwesandaw Pagoda, and enjoy traditional Burmese cuisine. Perfect for budget-conscious travelers who don''t want to miss Bagan''s magic.',
 3, 250000.00, 20, 18, 4, 1, 'pkg_bagan_budget.jpg', 4.2),

(2, 'Premium Ngapali & Bagan Combo',
 'Ngapali Beach + Bagan',
 'The ultimate Myanmar experience combining beach relaxation and ancient temple exploration. Fly to Ngapali for 3 days of beach bliss, then fly to Bagan for 3 days of temple discovery. All flights, premium hotels, guided tours, and meals included.',
 7, 2100000.00, 8, 6, 5, 4, 'pkg_combo_premium.jpg', 4.8);

-- Seed popular destinations
INSERT INTO destinations (name, tagline, sort_order) VALUES
('Bagan', '2,000+ Ancient Temples', 1),
('Inle Lake', 'Floating Gardens & Villages', 2),
('Ngapali Beach', 'Pristine White Sand', 3),
('Mandalay', 'Cultural Heart of Myanmar', 4),
('Golden Rock', 'Sacred Pilgrimage Site', 5),
('Hpa-An', 'Caves & Karst Mountains', 6);
