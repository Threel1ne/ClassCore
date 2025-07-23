-- SQL commands to add columns for slip verification tracking
-- Run these commands in your database if the columns don't exist

-- Add slip verification columns to payments table
ALTER TABLE payments 
ADD COLUMN IF NOT EXISTS slip_verified TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS slip_receiver_name VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL;

-- Create payment_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
