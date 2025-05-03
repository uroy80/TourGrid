-- First, let's modify the destinations table to support hierarchical structure
ALTER TABLE destinations ADD COLUMN parent_id INT DEFAULT NULL;
ALTER TABLE destinations ADD COLUMN type ENUM('region', 'state', 'city') DEFAULT 'city';
ALTER TABLE destinations ADD FOREIGN KEY (parent_id) REFERENCES destinations(destination_id) ON DELETE CASCADE;

-- Clear existing destinations
DELETE FROM destinations;
ALTER TABLE destinations AUTO_INCREMENT = 1;

-- Insert regions (top level)
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('South India', 'The southern part of India comprising Tamil Nadu, Kerala, Karnataka, Andhra Pradesh, and Telangana.', 'south-india.jpg', 'region', NULL),
('North India', 'The northern part of India including Jammu & Kashmir, Himachal Pradesh, Uttarakhand, Rajasthan, Delhi, Punjab, and Uttar Pradesh.', 'north-india.jpg', 'region', NULL),
('East India', 'The eastern part of India including West Bengal, Odisha, Bihar, and Jharkhand.', 'east-india.jpg', 'region', NULL),
('West India', 'The western part of India including Maharashtra, Gujarat, and Goa.', 'west-india.jpg', 'region', NULL),
('Northeast India', 'The northeastern part of India comprising seven sister states.', 'northeast-india.jpg', 'region', NULL),
('Islands', 'The island territories of India including Andaman & Nicobar and Lakshadweep.', 'islands.jpg', 'region', NULL);

-- Insert states (second level) - South India
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Kerala', 'Known as "God\'s Own Country", Kerala offers serene backwaters, lush hill stations, and pristine beaches.', 'kerala.jpg', 'state', 1),
('Tamil Nadu', 'Home to ancient Dravidian temples, vibrant culture, and beautiful hill stations.', 'tamilnadu.jpg', 'state', 1),
('Karnataka', 'From the tech hub of Bangalore to the royal city of Mysore and the ruins of Hampi.', 'karnataka.jpg', 'state', 1),
('Andhra Pradesh', 'Famous for its spicy cuisine, ancient temples, and beautiful beaches.', 'andhra-pradesh.jpg', 'state', 1),
('Telangana', 'Home to the historic city of Hyderabad with its iconic Charminar.', 'telangana.jpg', 'state', 1),
('Andaman and Nicobar Islands', 'Tropical paradise with pristine beaches, coral reefs, and colonial history.', 'andaman-nicobar.jpg', 'state', 6);

-- Insert states (second level) - North India
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Jammu and Kashmir', 'Known for its breathtaking landscapes, including the Kashmir Valley, Ladakh, and Gulmarg.', 'jammu-kashmir.jpg', 'state', 2),
('Himachal Pradesh', 'A mountainous state with stunning hill stations like Shimla, Manali, and Dharamshala.', 'himachal-pradesh.jpg', 'state', 2),
('Uttarakhand', 'Known as "Devbhumi" or Land of Gods, featuring sacred rivers and the mighty Himalayas.', 'uttarakhand.jpg', 'state', 2),
('Rajasthan', 'The land of kings featuring magnificent forts, palaces, and the Thar Desert.', 'rajasthan.jpg', 'state', 2),
('Delhi', 'India\'s capital with a perfect blend of old and new.', 'delhi.jpg', 'state', 2),
('Punjab', 'The land of five rivers, known for its vibrant culture and the Golden Temple.', 'punjab.jpg', 'state', 2),
('Uttar Pradesh', 'Home to the Taj Mahal, the holy city of Varanasi, and spiritual centers.', 'uttar-pradesh.jpg', 'state', 2);

-- Insert states (second level) - East India
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('West Bengal', 'Home to Kolkata, the Sundarbans mangrove forests, and Darjeeling.', 'west-bengal.jpg', 'state', 3),
('Odisha', 'Known for its ancient temples, tribal cultures, and beautiful beaches.', 'odisha.jpg', 'state', 3),
('Bihar', 'Rich in history with sites like Nalanda and Bodh Gaya.', 'bihar.jpg', 'state', 3),
('Assam', 'Famous for its tea plantations and Kaziranga National Park.', 'assam.jpg', 'state', 5),
('Meghalaya', 'Known as the "Abode of Clouds" with living root bridges.', 'meghalaya.jpg', 'state', 5);

-- Insert states (second level) - West India
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Maharashtra', 'Home to Mumbai, the Ajanta and Ellora Caves, and Western Ghats.', 'maharashtra.jpg', 'state', 4),
('Gujarat', 'Known for its vibrant culture, the Great Rann of Kutch, and the Gir National Park.', 'gujarat.jpg', 'state', 4),
('Madhya Pradesh', 'The heart of India with historical sites and natural beauty.', 'madhya-pradesh.jpg', 'state', 4),
('Goa', 'India\'s beach paradise with a unique blend of Indian and Portuguese cultures.', 'goa.jpg', 'state', 4);

-- Insert states (second level) - Northeast India
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Sikkim', 'A small Himalayan state with stunning mountain views and monasteries.', 'sikkim.jpg', 'state', 5),
('Arunachal Pradesh', 'The "Land of the Dawn-Lit Mountains" with diverse tribal cultures.', 'arunachal-pradesh.jpg', 'state', 5),
('Nagaland', 'Known for its tribal heritage and the Hornbill Festival.', 'nagaland.jpg', 'state', 5),
('Mizoram', 'Known for its bamboo forests, beautiful hills, and tribal culture.', 'mizoram.jpg', 'state', 5),
('Tripura', 'Famous for its rock-cut carvings, palaces, and temples.', 'tripura.jpg', 'state', 5),
('Manipur', 'Home to Loktak Lake, the only floating lake in the world.', 'manipur.jpg', 'state', 5);

-- Insert states (second level) - Islands
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Lakshadweep', 'India\'s smallest union territory with beautiful coral atolls and lagoons.', 'lakshadweep.jpg', 'state', 6);

-- Insert cities (third level) - Kerala
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Munnar', 'Hill station known for its tea plantations and cool climate.', 'munnar.jpg', 'city', 7),
('Wayanad', 'Green paradise with forests, spice plantations, and wildlife.', 'wayanad.jpg', 'city', 7),
('Alleppey', 'Famous for its backwaters, houseboats, and serene canals.', 'alleppey.jpg', 'city', 7),
('Cochin', 'Historic port city with colonial architecture and fishing nets.', 'cochin.jpg', 'city', 7),
('Kovalam', 'Beach destination with lighthouse and crescent-shaped beaches.', 'kovalam.jpg', 'city', 7),
('Trivandrum', 'Capital city with historic temples and museums.', 'trivandrum.jpg', 'city', 7),
('Kumily', 'Gateway to Periyar Wildlife Sanctuary and spice gardens.', 'kumily.jpg', 'city', 7),
('Gavi', 'Eco-tourism destination with diverse wildlife and forests.', 'gavi.jpg', 'city', 7);

-- Insert cities (third level) - Tamil Nadu
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Ooty', 'Queen of hill stations with botanical gardens and toy train.', 'ooty.jpg', 'city', 8),
('Kodaikanal', 'Princess of hill stations with lakes and pine forests.', 'kodaikanal.jpg', 'city', 8),
('Yercaud', 'Serene hill station known as the jewel of the south.', 'yercaud.jpg', 'city', 8),
('Coonoor', 'Tea garden hill station near Ooty with scenic beauty.', 'coonoor.jpg', 'city', 8),
('Chennai', 'Capital city with beaches, temples, and colonial heritage.', 'chennai.jpg', 'city', 8),
('Mahabalipuram', 'UNESCO site with ancient rock-cut temples and shore temple.', 'mahabalipuram.jpg', 'city', 8),
('Pondicherry', 'Former French colony with unique architecture and beaches.', 'pondicherry.jpg', 'city', 8);

-- Insert cities (third level) - Karnataka
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Coorg', 'Coffee country with misty hills and waterfalls.', 'coorg.jpg', 'city', 9),
('Chikmagalur', 'Coffee land with hills, streams, and wildlife.', 'chikmagalur.jpg', 'city', 9),
('Sakleshpur', 'Hill station with coffee estates and trekking trails.', 'sakleshpur.jpg', 'city', 9),
('Bangalore', 'Silicon Valley of India with gardens and pleasant climate.', 'bangalore.jpg', 'city', 9),
('Mysore', 'City of palaces with royal heritage and Dasara celebrations.', 'mysore.jpg', 'city', 9),
('Hampi', 'UNESCO World Heritage Site with ancient ruins and boulders.', 'hampi.jpg', 'city', 9),
('Gokarna', 'Temple town with pristine beaches and laid-back atmosphere.', 'gokarna.jpg', 'city', 9);

-- Insert cities (third level) - Andhra Pradesh
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Tirupati', 'Temple city with the famous Venkateshwara Temple.', 'tirupati.jpg', 'city', 10),
('Visakhapatnam', 'Port city with beaches and hills.', 'visakhapatnam.jpg', 'city', 10),
('Warangal', 'Historic city with Kakatiya era monuments.', 'warangal.jpg', 'city', 10);

-- Insert cities (third level) - Telangana
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Hyderabad', 'City of pearls with Charminar and biryani.', 'hyderabad.jpg', 'city', 11);

-- Insert cities (third level) - Andaman and Nicobar Islands
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Port Blair', 'Capital city with Cellular Jail and water sports.', 'port-blair.jpg', 'city', 12),
('Havelock Island', 'Island with Radhanagar Beach and diving spots.', 'havelock.jpg', 'city', 12);

-- Insert cities (third level) - Jammu and Kashmir
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Srinagar', 'Summer capital with Dal Lake and houseboats.', 'srinagar.jpg', 'city', 13),
('Gulmarg', 'Ski resort with meadows and Gondola ride.', 'gulmarg.jpg', 'city', 13),
('Sonmarg', 'Meadow of gold with glaciers and lakes.', 'sonmarg.jpg', 'city', 13),
('Leh', 'High-altitude desert with monasteries and landscapes.', 'leh.jpg', 'city', 13);

-- Insert cities (third level) - Himachal Pradesh
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Shimla', 'Former summer capital with colonial architecture.', 'shimla.jpg', 'city', 14),
('Manali', 'Valley destination with adventure sports and hot springs.', 'manali.jpg', 'city', 14),
('Dharamshala', 'Home to Dalai Lama with Tibetan culture and cricket stadium.', 'dharamshala.jpg', 'city', 14),
('Kullu', 'Valley of gods with Dussehra celebrations and rafting.', 'kullu.jpg', 'city', 14);

-- Insert cities (third level) - Uttarakhand
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Nainital', 'Lake district with boating and viewpoints.', 'nainital.jpg', 'city', 15),
('Mussoorie', 'Queen of hills with mall road and waterfalls.', 'mussoorie.jpg', 'city', 15),
('Rishikesh', 'Yoga capital with rafting and Beatles Ashram.', 'rishikesh.jpg', 'city', 15),
('Haridwar', 'Holy city with Ganga Aarti and Kumbh Mela.', 'haridwar.jpg', 'city', 15);

-- Insert cities (third level) - Rajasthan
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Jaipur', 'Pink city with Amber Fort and City Palace.', 'jaipur.jpg', 'city', 16),
('Udaipur', 'City of lakes with Lake Palace and romantic setting.', 'udaipur.jpg', 'city', 16),
('Jodhpur', 'Blue city with Mehrangarh Fort and desert culture.', 'jodhpur.jpg', 'city', 16),
('Jaisalmer', 'Golden city with sand dunes and havelis.', 'jaisalmer.jpg', 'city', 16);

-- Insert cities (third level) - Delhi
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Delhi', 'National capital with Red Fort, Qutub Minar, and India Gate.', 'delhi-city.jpg', 'city', 17);

-- Insert cities (third level) - Punjab
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Amritsar', 'Holy city with Golden Temple and Wagah Border.', 'amritsar.jpg', 'city', 18);

-- Insert cities (third level) - Uttar Pradesh
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Agra', 'City of Taj Mahal and Agra Fort.', 'agra.jpg', 'city', 19),
('Varanasi', 'Spiritual capital with ghats and Ganga Aarti.', 'varanasi.jpg', 'city', 19);

-- Insert cities (third level) - West Bengal
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Darjeeling', 'Tea garden hill station with views of Kanchenjunga.', 'darjeeling.jpg', 'city', 20),
('Kolkata', 'Cultural capital with colonial architecture and Durga Puja.', 'kolkata.jpg', 'city', 20);

-- Insert cities (third level) - Odisha
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Puri', 'Temple town with Jagannath Temple and beaches.', 'puri.jpg', 'city', 21),
('Bhubaneswar', 'Temple city with ancient architecture.', 'bhubaneswar.jpg', 'city', 21);

-- Insert cities (third level) - Bihar
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Patna', 'Capital city with historical sites.', 'patna.jpg', 'city', 22),
('Gaya', 'Pilgrimage site with Mahabodhi Temple.', 'gaya.jpg', 'city', 22);

-- Insert cities (third level) - Assam
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Kaziranga', 'National park with one-horned rhinoceros.', 'kaziranga.jpg', 'city', 23),
('Guwahati', 'Gateway to Northeast with Kamakhya Temple.', 'guwahati.jpg', 'city', 23);

-- Insert cities (third level) - Meghalaya
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Shillong', 'Scotland of the East with waterfalls and music.', 'shillong.jpg', 'city', 24);

-- Insert cities (third level) - Maharashtra
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Mumbai', 'Financial capital with Gateway of India and Bollywood.', 'mumbai.jpg', 'city', 25),
('Pune', 'Cultural capital with historical sites and education.', 'pune.jpg', 'city', 25);

-- Insert cities (third level) - Gujarat
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Ahmedabad', 'Largest city with heritage sites and textile industry.', 'ahmedabad.jpg', 'city', 26),
('Vadodara', 'Cultural city with palaces and museums.', 'vadodara.jpg', 'city', 26),
('Diu', 'Former Portuguese colony with beaches and forts.', 'diu.jpg', 'city', 26);

-- Insert cities (third level) - Madhya Pradesh
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Khajuraho', 'Temple town with UNESCO heritage sculptures.', 'khajuraho.jpg', 'city', 27),
('Bhopal', 'City of lakes with museums and mosques.', 'bhopal.jpg', 'city', 27);

-- Insert cities (third level) - Sikkim
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Gangtok', 'Capital city with monasteries and mountain views.', 'gangtok.jpg', 'city', 30);

-- Insert cities (third level) - Arunachal Pradesh
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Tawang', 'Monastery town with snow-capped mountains.', 'tawang.jpg', 'city', 31);

-- Insert cities (third level) - Nagaland
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Kohima', 'Capital city with war cemetery and tribal culture.', 'kohima.jpg', 'city', 32);

-- Insert cities (third level) - Mizoram
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Aizawl', 'Capital city with scenic beauty and tribal culture.', 'aizawl.jpg', 'city', 33);

-- Insert cities (third level) - Tripura
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Agartala', 'Capital city with palaces and temples.', 'agartala.jpg', 'city', 34);

-- Insert cities (third level) - Manipur
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Imphal', 'Capital city with Loktak Lake and war memorials.', 'imphal.jpg', 'city', 35);

-- Insert cities (third level) - Lakshadweep
INSERT INTO destinations (name, description, image, type, parent_id) VALUES
('Agatti Island', 'Island with coral reefs and water sports.', 'agatti.jpg', 'city', 36);
