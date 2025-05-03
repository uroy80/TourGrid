-- First, delete existing destinations
DELETE FROM destinations;

-- Reset auto-increment
ALTER TABLE destinations AUTO_INCREMENT = 1;

-- Insert Indian destinations by region
-- South India
INSERT INTO destinations (name, description, image, is_active) VALUES
('Kerala', 'Known as "God\'s Own Country", Kerala offers serene backwaters, lush hill stations, and pristine beaches.', 'kerala.jpg', TRUE),
('Tamil Nadu', 'Home to ancient Dravidian temples, vibrant culture, and beautiful hill stations like Ooty and Kodaikanal.', 'tamilnadu.jpg', TRUE),
('Karnataka', 'From the tech hub of Bangalore to the royal city of Mysore and the ruins of Hampi, Karnataka offers diverse experiences.', 'karnataka.jpg', TRUE),
('Andhra Pradesh', 'Famous for its spicy cuisine, ancient temples, and the beautiful beaches of Visakhapatnam.', 'andhra-pradesh.jpg', TRUE),
('Telangana', 'Home to the historic city of Hyderabad with its iconic Charminar and delicious Hyderabadi biryani.', 'telangana.jpg', TRUE);

-- North India
INSERT INTO destinations (name, description, image, is_active) VALUES
('Rajasthan', 'The land of kings featuring magnificent forts, palaces, and the Thar Desert with its camel safaris.', 'rajasthan.jpg', TRUE),
('Himachal Pradesh', 'A mountainous state with stunning hill stations like Shimla, Manali, and Dharamshala.', 'himachal-pradesh.jpg', TRUE),
('Uttarakhand', 'Known as "Devbhumi" or Land of Gods, featuring sacred rivers, yoga retreats, and the mighty Himalayas.', 'uttarakhand.jpg', TRUE),
('Uttar Pradesh', 'Home to the Taj Mahal, the holy city of Varanasi, and the spiritual centers of Mathura and Vrindavan.', 'uttar-pradesh.jpg', TRUE),
('Delhi', 'India\'s capital with a perfect blend of old and new, featuring historic monuments and modern infrastructure.', 'delhi.jpg', TRUE),
('Jammu & Kashmir', 'Known for its breathtaking landscapes, including the Kashmir Valley, Ladakh, and Gulmarg.', 'jammu-kashmir.jpg', TRUE),
('Punjab', 'The land of five rivers, known for its vibrant culture, delicious cuisine, and the Golden Temple.', 'punjab.jpg', TRUE);

-- East India
INSERT INTO destinations (name, description, image, is_active) VALUES
('West Bengal', 'Home to Kolkata, the Sundarbans mangrove forests, and the hill station of Darjeeling.', 'west-bengal.jpg', TRUE),
('Odisha', 'Known for its ancient temples, tribal cultures, and the beautiful beaches of Puri.', 'odisha.jpg', TRUE),
('Bihar', 'Rich in history with sites like Nalanda and Bodh Gaya, where Buddha attained enlightenment.', 'bihar.jpg', TRUE),
('Jharkhand', 'Known for its waterfalls, forests, and tribal culture.', 'jharkhand.jpg', TRUE);

-- West India
INSERT INTO destinations (name, description, image, is_active) VALUES
('Goa', 'India\'s beach paradise with a unique blend of Indian and Portuguese cultures.', 'goa.jpg', TRUE),
('Maharashtra', 'Home to Mumbai, the Ajanta and Ellora Caves, and beautiful Western Ghats.', 'maharashtra.jpg', TRUE),
('Gujarat', 'Known for its vibrant culture, the Great Rann of Kutch, and the Gir National Park.', 'gujarat.jpg', TRUE);

-- Northeast India
INSERT INTO destinations (name, description, image, is_active) VALUES
('Assam', 'Famous for its tea plantations, Kaziranga National Park, and the mighty Brahmaputra River.', 'assam.jpg', TRUE),
('Sikkim', 'A small Himalayan state with stunning mountain views, monasteries, and trekking routes.', 'sikkim.jpg', TRUE),
('Meghalaya', 'Known as the "Abode of Clouds" with living root bridges and the wettest place on Earth, Cherrapunji.', 'meghalaya.jpg', TRUE),
('Arunachal Pradesh', 'The "Land of the Dawn-Lit Mountains" with diverse tribal cultures and pristine landscapes.', 'arunachal-pradesh.jpg', TRUE),
('Nagaland', 'Known for its tribal heritage, the Hornbill Festival, and stunning hills.', 'nagaland.jpg', TRUE),
('Manipur', 'Home to Loktak Lake, the only floating lake in the world, and rich cultural traditions.', 'manipur.jpg', TRUE),
('Mizoram', 'Known for its bamboo forests, beautiful hills, and vibrant tribal culture.', 'mizoram.jpg', TRUE),
('Tripura', 'Famous for its rock-cut carvings, palaces, and temples.', 'tripura.jpg', TRUE);

-- Island Territories
INSERT INTO destinations (name, description, image, is_active) VALUES
('Andaman & Nicobar Islands', 'Tropical paradise with pristine beaches, coral reefs, and colonial history.', 'andaman-nicobar.jpg', TRUE),
('Lakshadweep', 'India\'s smallest union territory with beautiful coral atolls and lagoons.', 'lakshadweep.jpg', TRUE);
