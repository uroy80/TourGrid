-- First, delete existing tours
DELETE FROM tours;

-- Reset auto-increment
ALTER TABLE tours AUTO_INCREMENT = 1;

-- Insert new tours for Indian destinations
-- South India Tours
INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured) VALUES 
('Kerala Backwaters Cruise', 'Experience the serene backwaters of Kerala on a traditional houseboat, visit spice plantations, and relax on pristine beaches.', 1, 35000.00, 6, 12, 'Accommodation, Daily breakfast and dinner, Houseboat stay, Spice plantation tour, Airport transfers, Local guide', 'Flights, Lunch, Personal expenses, Travel insurance', 'kerala-backwaters.jpg', TRUE),
('Tamil Nadu Temple Trail', 'Explore the magnificent ancient temples of Tamil Nadu, including Meenakshi Temple, Brihadeeswarar Temple, and Shore Temple.', 2, 28000.00, 7, 15, 'Accommodation, Daily breakfast, Temple entrance fees, Air-conditioned transportation, Local guide', 'Flights, Lunch and dinner, Personal expenses, Camera fees', 'tamilnadu-temples.jpg', TRUE),
('Coorg Coffee Plantation Retreat', 'Discover the lush coffee plantations of Coorg, trek through misty hills, and experience the unique Kodava culture.', 3, 32000.00, 5, 10, 'Accommodation, Daily breakfast and dinner, Coffee plantation tour, Trekking guide, Transportation', 'Flights, Lunch, Personal expenses, Additional activities', 'coorg-coffee.jpg', FALSE),
('Hyderabad Heritage Tour', 'Explore the historic city of Hyderabad with its iconic Charminar, Golconda Fort, and indulge in delicious Hyderabadi cuisine.', 5, 25000.00, 4, 15, 'Accommodation, Daily breakfast, Monument entrance fees, City tour, Hyderabadi dinner experience', 'Flights, Lunch, Personal expenses, Optional activities', 'hyderabad-heritage.jpg', FALSE);

-- North India Tours
INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured) VALUES 
('Rajasthan Royal Retreat', 'Experience the royal heritage of Rajasthan with visits to magnificent forts and palaces in Jaipur, Udaipur, Jodhpur, and Jaisalmer.', 6, 45000.00, 10, 12, 'Accommodation, Daily breakfast, Monument entrance fees, Desert safari, Cultural performances, Transportation', 'Flights, Lunch and dinner, Personal expenses, Camera fees', 'rajasthan-royal.jpg', TRUE),
('Himalayan Escapade', 'Explore the beautiful hill stations of Himachal Pradesh including Shimla, Manali, and Dharamshala with stunning mountain views.', 7, 38000.00, 8, 10, 'Accommodation, Daily breakfast and dinner, Sightseeing tours, Mountain passes permits, Transportation', 'Flights, Lunch, Personal expenses, Adventure activities', 'himachal-escapade.jpg', TRUE),
('Spiritual Ganges Journey', 'Experience the spiritual essence of India with visits to Rishikesh, Haridwar, and Varanasi along the sacred Ganges River.', 8, 30000.00, 7, 15, 'Accommodation, Daily breakfast, Yoga sessions, Ganga Aarti ceremony, Boat ride, Spiritual guide', 'Flights, Lunch and dinner, Personal expenses, Temple donations', 'ganges-spiritual.jpg', FALSE),
('Golden Triangle Expedition', 'Discover India\'s famous Golden Triangle - Delhi, Agra, and Jaipur, featuring the iconic Taj Mahal and other UNESCO sites.', 10, 42000.00, 6, 20, 'Accommodation, Daily breakfast, Monument entrance fees, Air-conditioned transportation, Professional guide', 'Flights, Lunch and dinner, Personal expenses, Optional activities', 'golden-triangle.jpg', TRUE);

-- East India Tours
INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured) VALUES 
('Darjeeling Tea Estate Holiday', 'Experience the lush tea gardens of Darjeeling, ride the famous toy train, and enjoy breathtaking views of the Himalayas.', 13, 29000.00, 5, 12, 'Accommodation, Daily breakfast and dinner, Tea estate tour, Toy train ride, Sightseeing', 'Flights, Lunch, Personal expenses, Additional activities', 'darjeeling-tea.jpg', FALSE),
('Odisha Temple & Beach Tour', 'Explore the ancient temples of Bhubaneswar and Konark, and relax on the beautiful beaches of Puri.', 14, 27000.00, 6, 15, 'Accommodation, Daily breakfast, Temple entrance fees, Beach activities, Transportation', 'Flights, Lunch and dinner, Personal expenses, Camera fees', 'odisha-temple-beach.jpg', FALSE);

-- West India Tours
INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured) VALUES 
('Goa Beach Vacation', 'Relax on the beautiful beaches of Goa, explore Portuguese heritage sites, and enjoy the vibrant nightlife.', 18, 25000.00, 5, 15, 'Accommodation, Daily breakfast, Beach activities, Heritage tour, Sunset cruise', 'Flights, Lunch and dinner, Personal expenses, Water sports', 'goa-beaches.jpg', TRUE),
('Maharashtra Cave Exploration', 'Discover the ancient rock-cut caves of Ajanta and Ellora, and explore the vibrant city of Mumbai.', 19, 32000.00, 7, 12, 'Accommodation, Daily breakfast, Cave entrance fees, Mumbai city tour, Transportation', 'Flights, Lunch and dinner, Personal expenses, Optional activities', 'maharashtra-caves.jpg', FALSE);

-- Northeast India Tours
INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured) VALUES 
('Assam Wildlife Safari', 'Experience the rich biodiversity of Assam with safaris in Kaziranga National Park, home to the one-horned rhinoceros.', 21, 40000.00, 6, 10, 'Accommodation, Daily breakfast and dinner, Safari rides, Park entrance fees, Naturalist guide', 'Flights, Lunch, Personal expenses, Camera fees', 'assam-wildlife.jpg', FALSE),
('Sikkim Monastery Trek', 'Trek through the beautiful landscapes of Sikkim, visit ancient monasteries, and enjoy panoramic views of Kanchenjunga.', 22, 35000.00, 8, 8, 'Accommodation, All meals during trek, Monastery entrance fees, Trekking permits, Guide and porters', 'Flights, Personal expenses, Additional activities, Specialized equipment', 'sikkim-monastery.jpg', FALSE);

-- Island Tours
INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured) VALUES 
('Andaman Island Paradise', 'Explore the pristine beaches, coral reefs, and colonial history of the Andaman Islands with snorkeling and water activities.', 29, 48000.00, 7, 12, 'Accommodation, Daily breakfast, Island hopping, Snorkeling equipment, Ferry tickets, Some water activities', 'Flights, Lunch and dinner, Personal expenses, Scuba diving', 'andaman-paradise.jpg', TRUE),
('Lakshadweep Coral Reef Adventure', 'Discover the beautiful coral atolls and lagoons of Lakshadweep with water sports and beach relaxation.', 30, 52000.00, 6, 10, 'Accommodation, All meals, Island permits, Water sports, Boat transfers', 'Flights, Personal expenses, Premium water activities, Special permits', 'lakshadweep-coral.jpg', FALSE);
