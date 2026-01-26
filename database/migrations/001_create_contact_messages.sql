-- Migration: 001_create_contact_messages
-- Date: 2025-12-25
-- Description: Add contact_messages table for contact form submissions

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    referral_source VARCHAR(50) DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at DESC),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
