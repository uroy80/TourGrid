-- Add company fields to users table
ALTER TABLE users 
ADD COLUMN company_name VARCHAR(100) DEFAULT NULL,
ADD COLUMN company_address VARCHAR(255) DEFAULT NULL;
