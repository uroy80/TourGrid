-- First, delete existing hotels
DELETE FROM hotels;

-- Reset auto-increment
ALTER TABLE hotels AUTO_INCREMENT = 1;

-- Insert new hotels for Indian destinations
-- South India Hotels
INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image) VALUES
('Kumarakom Lake Resort', 'Luxury resort on the banks of Vembanad Lake with traditional Kerala architecture.', 'Kumarakom, Kerala, India', 1, 5, 15000.00, 'Swimming pool, Ayurvedic spa, Restaurant, Free Wi-Fi, Lake view rooms', 'kumarakom-resort.jpg'),
('Taj Coromandel', 'Elegant hotel in the heart of Chennai with world-class amenities.', 'MG Road, Chennai, Tamil Nadu, India', 2, 5, 12000.00, 'Multiple restaurants, Spa, Fitness center, Business center, Free Wi-Fi', 'taj-coromandel.jpg'),
('Taj West End', 'Historic luxury hotel set in a lush garden in Bangalore.', 'Race Course Road, Bangalore, Karnataka, India', 3, 5, 14000.00, 'Outdoor pool, Spa, Multiple restaurants, Tennis courts, Free Wi-Fi', 'taj-west-end.jpg'),
('Taj Krishna', 'Luxury hotel in the heart of Hyderabad with traditional charm.', 'Road No. 1, Banjara Hills, Hyderabad, Telangana, India', 5, 5, 11000.00, 'Swimming pool, Spa, Multiple restaurants, Fitness center, Free Wi-Fi', 'taj-krishna.jpg');

-- North India Hotels
INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image) VALUES
('Taj Lake Palace', 'Iconic luxury hotel floating on Lake Pichola in Udaipur.', 'Lake Pichola, Udaipur, Rajasthan, India', 6, 5, 30000.00, 'Lake view rooms, Spa, Multiple restaurants, Boat transfers, Cultural performances', 'taj-lake-palace.jpg'),
('Wildflower Hall', 'Luxury mountain retreat set in the Himalayas near Shimla.', 'Chharabra, Shimla, Himachal Pradesh, India', 7, 5, 25000.00, 'Mountain view rooms, Spa, Indoor pool, Restaurant, Adventure activities', 'wildflower-hall.jpg'),
('Ananda in the Himalayas', 'Luxury spa resort in the Himalayan foothills near Rishikesh.', 'The Palace Estate, Narendra Nagar, Uttarakhand, India', 8, 5, 28000.00, 'Spa treatments, Yoga classes, Meditation sessions, Organic cuisine, Mountain views', 'ananda-himalayas.jpg'),
('The Oberoi Amarvilas', 'Luxury hotel with views of the Taj Mahal from every room.', 'Taj East Gate Road, Agra, Uttar Pradesh, India', 9, 5, 26000.00, 'Taj Mahal views, Spa, Swimming pool, Fine dining, Butler service', 'oberoi-amarvilas.jpg'),
('The Imperial', 'Historic luxury hotel with colonial charm in central Delhi.', 'Janpath, New Delhi, Delhi, India', 10, 5, 18000.00, 'Multiple restaurants, Spa, Outdoor pool, Art collection, Business center', 'imperial-delhi.jpg');

-- East India Hotels
INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image) VALUES
('Glenburn Tea Estate', 'Boutique hotel on a working tea estate with stunning Himalayan views.', 'Darjeeling, West Bengal, India', 13, 4, 20000.00, 'Tea estate tours, Mountain views, Gourmet dining, Hiking trails, River activities', 'glenburn-tea-estate.jpg'),
('Mayfair Lagoon', 'Luxury resort with extensive gardens in Bhubaneswar.', 'Jaydev Vihar, Bhubaneswar, Odisha, India', 14, 5, 12000.00, 'Multiple restaurants, Spa, Swimming pool, Fitness center, Business facilities', 'mayfair-lagoon.jpg');

-- West India Hotels
INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image) VALUES
('Taj Fort Aguada', 'Luxury beachfront resort built on the ramparts of a 16th-century Portuguese fortress.', 'Sinquerim Beach, Candolim, Goa, India', 18, 5, 18000.00, 'Private beach, Swimming pools, Multiple restaurants, Spa, Water sports', 'taj-fort-aguada.jpg'),
('Taj Mahal Palace', 'Iconic luxury hotel overlooking the Gateway of India in Mumbai.', 'Apollo Bunder, Mumbai, Maharashtra, India', 19, 5, 25000.00, 'Sea views, Multiple restaurants, Spa, Outdoor pool, Historic architecture', 'taj-mahal-palace.jpg');

-- Northeast India Hotels
INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image) VALUES
('Diphlu River Lodge', 'Eco-friendly lodge at the edge of Kaziranga National Park.', 'Kaziranga National Park, Assam, India', 21, 4, 15000.00, 'Wildlife viewing, River views, Restaurant, Safari arrangements, Eco-friendly facilities', 'diphlu-river-lodge.jpg'),
('Mayfair Spa Resort', 'Luxury spa resort with mountain views in Gangtok.', 'Lower Samdur Block, Ranipool, Gangtok, Sikkim, India', 22, 5, 16000.00, 'Spa treatments, Mountain views, Multiple restaurants, Indoor pool, Casino', 'mayfair-spa-resort.jpg');

-- Island Hotels
INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image) VALUES
('Taj Exotica Resort & Spa', 'Luxury beachfront resort on Havelock Island with pristine beaches.', 'Radhanagar Beach, Havelock Island, Andaman & Nicobar Islands, India', 29, 5, 22000.00, 'Private beach, Spa, Water sports, Diving center, Beachfront dining', 'taj-exotica-andaman.jpg'),
('Bangaram Island Resort', 'Eco-friendly resort on an exclusive coral island in Lakshadweep.', 'Bangaram Island, Lakshadweep, India', 30, 4, 18000.00, 'Beach cottages, Water sports, Diving, Fishing trips, Eco-friendly facilities', 'bangaram-island-resort.jpg');

-- Update tour-hotel relationships
DELETE FROM tour_hotels;
INSERT INTO tour_hotels (tour_id, hotel_id, nights) VALUES
(1, 1, 5), -- Kerala Backwaters Cruise - Kumarakom Lake Resort
(2, 2, 6), -- Tamil Nadu Temple Trail - Taj Coromandel
(3, 3, 4), -- Coorg Coffee Plantation Retreat - Taj West End
(4, 4, 3), -- Hyderabad Heritage Tour - Taj Krishna
(5, 5, 9), -- Rajasthan Royal Retreat - Taj Lake Palace
(6, 6, 7), -- Himalayan Escapade - Wildflower Hall
(7, 7, 6), -- Spiritual Ganges Journey - Ananda in the Himalayas
(8, 10, 5), -- Golden Triangle Expedition - The Imperial
(9, 11, 4), -- Darjeeling Tea Estate Holiday - Glenburn Tea Estate
(10, 12, 5), -- Odisha Temple & Beach Tour - Mayfair Lagoon
(11, 13, 4), -- Goa Beach Vacation - Taj Fort Aguada
(12, 14, 6), -- Maharashtra Cave Exploration - Taj Mahal Palace
(13, 15, 5), -- Assam Wildlife Safari - Diphlu River Lodge
(14, 16, 7), -- Sikkim Monastery Trek - Mayfair Spa Resort
(15, 17, 6), -- Andaman Island Paradise - Taj Exotica Resort & Spa
(16, 18, 5); -- Lakshadweep Coral Reef Adventure - Bangaram Island Resort
