-- Add admin_id column to tours table to track which admin created the tour
ALTER TABLE tours 
ADD COLUMN admin_id INT DEFAULT NULL,
ADD CONSTRAINT fk_tour_admin FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- Update existing tours to associate with the first admin (if any)
UPDATE tours SET admin_id = (SELECT user_id FROM users WHERE role = 'admin' LIMIT 1);
