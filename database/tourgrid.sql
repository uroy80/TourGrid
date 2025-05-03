-- Create database
CREATE DATABASE IF NOT EXISTS tourgrid;
USE tourgrid;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profile_image VARCHAR(255) DEFAULT 'default-user.jpg',
    company_name VARCHAR(100) DEFAULT NULL,
    company_address VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Destinations table
CREATE TABLE IF NOT EXISTS destinations (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tours table
CREATE TABLE IF NOT EXISTS tours (
    tour_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    destination_id INT,
    price DECIMAL(10, 2) NOT NULL,
    duration INT NOT NULL, -- in days
    max_people INT NOT NULL,
    inclusions TEXT,
    exclusions TEXT,
    image VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    admin_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Hotels table
CREATE TABLE IF NOT EXISTS hotels (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    address VARCHAR(255) NOT NULL,
    destination_id INT,
    star_rating INT NOT NULL CHECK (star_rating BETWEEN 1 AND 5),
    price_per_night DECIMAL(10, 2) NOT NULL,
    amenities TEXT,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    admin_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Tour Schedules table
CREATE TABLE IF NOT EXISTS tour_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    available_seats INT NOT NULL,
    guide_name VARCHAR(100),
    price_adjustment DECIMAL(10, 2) DEFAULT 0.00, -- For seasonal pricing adjustments
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE
);

-- Tour-Hotel Relationship table
CREATE TABLE IF NOT EXISTS tour_hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    hotel_id INT NOT NULL,
    nights INT NOT NULL,
    room_type VARCHAR(50) DEFAULT 'Standard',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tour_id INT NOT NULL,
    schedule_id INT NOT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    num_people INT NOT NULL,
    base_amount DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    cancellation_reason TEXT,
    special_requests TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES tour_schedules(schedule_id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('credit_card', 'paypal', 'bank_transfer', 'cash') NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Feedback table (renamed from reviews)
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tour_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    admin_response TEXT,
    is_published BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL
);

-- Wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tour_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, tour_id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking', 'payment', 'tour', 'system') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Discount Codes table
CREATE TABLE IF NOT EXISTS discount_codes (
    code_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    min_purchase DECIMAL(10, 2) DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tour Itinerary table
CREATE TABLE IF NOT EXISTS tour_itinerary (
    itinerary_id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    day_number INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id) ON DELETE CASCADE
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', '$2y$10$8WxhJz0q.Y9BsEUwI1KJXOXCKLQgJab7jgMQkEG/oyMQ9FCNx9Wn2', 'admin@tourgrid.com', 'Admin User', 'admin');

-- Insert sample destinations
INSERT INTO destinations (name, description, image) VALUES 
('Bali', 'Beautiful island in Indonesia with stunning beaches and rich culture.', 'bali.jpg'),
('Paris', 'The city of love with iconic landmarks like the Eiffel Tower.', 'paris.jpg'),
('Tokyo', 'Modern metropolis with a perfect blend of traditional and futuristic elements.', 'tokyo.jpg'),
('New York', 'The city that never sleeps with iconic skyscrapers and diverse culture.', 'newyork.jpg');

-- Insert sample tours
INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured) VALUES 
('Bali Paradise Tour', 'Experience the beauty of Bali with this comprehensive tour package.', 1, 1200.00, 7, 15, 'Hotel, Breakfast, Tour guide, Transportation', 'Flights, Personal expenses, Travel insurance', 'bali-tour.jpg', TRUE),
('Paris Explorer', 'Discover the magic of Paris with guided tours to all major attractions.', 2, 1500.00, 5, 10, 'Hotel, Breakfast, Tour guide, Museum passes', 'Flights, Lunch and Dinner, Personal expenses', 'paris-tour.jpg', TRUE),
('Tokyo Adventure', 'Immerse yourself in Japanese culture and explore Tokyo\'s wonders.', 3, 1800.00, 6, 12, 'Hotel, Transportation, Tour guide, Welcome dinner', 'Flights, Most meals, Personal expenses', 'tokyo-tour.jpg', FALSE),
('New York City Break', 'Experience the energy of New York with this exciting city tour.', 4, 1300.00, 4, 15, 'Hotel, City pass, Guided tours', 'Flights, Meals, Personal expenses', 'newyork-tour.jpg', TRUE);

-- Insert sample hotels
INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image) VALUES
('Bali Beach Resort', 'Luxury beachfront resort with stunning ocean views.', 'Jl. Pantai Kuta No. 123, Bali, Indonesia', 1, 5, 250.00, 'Swimming pool, Spa, Restaurant, Free Wi-Fi, Beach access', 'bali-resort.jpg'),
('Paris Luxury Hotel', 'Elegant hotel in the heart of Paris with Eiffel Tower views.', '123 Avenue des Champs-Élysées, Paris, France', 2, 4, 300.00, 'Restaurant, Bar, Fitness center, Free Wi-Fi, Room service', 'paris-hotel.jpg'),
('Tokyo Skyline Hotel', 'Modern hotel with panoramic city views in central Tokyo.', '1-2-3 Shinjuku, Tokyo, Japan', 3, 5, 280.00, 'Restaurant, Bar, Spa, Fitness center, Free Wi-Fi', 'tokyo-hotel.jpg'),
('Manhattan Suites', 'Contemporary hotel in the heart of Manhattan.', '123 Broadway, New York, NY, USA', 4, 4, 320.00, 'Restaurant, Bar, Fitness center, Business center, Free Wi-Fi', 'newyork-hotel.jpg');

-- Insert sample tour schedules
INSERT INTO tour_schedules (tour_id, start_date, end_date, available_seats, guide_name, status) VALUES
(1, '2023-07-15', '2023-07-22', 10, 'John Smith', 'upcoming'),
(1, '2023-08-10', '2023-08-17', 15, 'Sarah Johnson', 'upcoming'),
(2, '2023-07-20', '2023-07-25', 8, 'Pierre Dubois', 'upcoming'),
(2, '2023-08-15', '2023-08-20', 10, 'Marie Laurent', 'upcoming'),
(3, '2023-09-05', '2023-09-11', 12, 'Takashi Yamamoto', 'upcoming'),
(4, '2023-08-01', '2023-08-05', 15, 'Michael Brown', 'upcoming');

-- Insert sample tour-hotel relationships
INSERT INTO tour_hotels (tour_id, hotel_id, nights) VALUES
(1, 1, 6),
(2, 2, 4),
(3, 3, 5),
(4, 4, 3);

-- Insert sample tour itineraries
INSERT INTO tour_itinerary (tour_id, day_number, title, description) VALUES
(1, 1, 'Arrival in Bali', 'Arrive at Denpasar International Airport. Transfer to your hotel in Kuta. Welcome dinner at a local restaurant.'),
(1, 2, 'Ubud Cultural Tour', 'Visit Ubud Monkey Forest, Ubud Palace, and local art galleries. Enjoy a traditional Balinese lunch.'),
(1, 3, 'Temples and Rice Terraces', 'Explore Tanah Lot Temple, Ulun Danu Temple, and the beautiful Tegalalang Rice Terraces.'),
(2, 1, 'Arrival in Paris', 'Arrive at Charles de Gaulle Airport. Transfer to your hotel. Evening Seine River cruise.'),
(2, 2, 'Eiffel Tower and Louvre', 'Morning visit to the Eiffel Tower. Afternoon exploring the Louvre Museum.'),
(2, 3, 'Montmartre and Notre Dame', 'Explore the artistic neighborhood of Montmartre and visit Notre Dame Cathedral.');

-- Insert sample discount codes
INSERT INTO discount_codes (code, description, discount_type, discount_value, min_purchase, start_date, end_date, usage_limit) VALUES
('SUMMER2023', 'Summer season discount', 'percentage', 10.00, 1000.00, '2023-06-01', '2023-08-31', 100),
('WELCOME50', 'New user discount', 'fixed', 50.00, 500.00, '2023-01-01', '2023-12-31', 200);

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(1, 'Welcome to TourGrid', 'Thank you for joining TourGrid. Start exploring our amazing tour packages!', 'system', FALSE),
(1, 'New Tour Available', 'Check out our new Paris Explorer tour package with special discounts!', 'tour', FALSE),
(1, 'Booking Confirmation', 'Your booking for Bali Paradise Tour has been confirmed.', 'booking', TRUE);
