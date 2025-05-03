-- Add admin_id column to hotels table to track which admin created the hotel
ALTER TABLE hotels 
ADD COLUMN admin_id INT DEFAULT NULL,
ADD CONSTRAINT fk_hotel_admin FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- Update existing hotels to associate with the first admin (if any)
UPDATE hotels SET admin_id = (SELECT user_id FROM users WHERE role = 'admin' LIMIT 1);
