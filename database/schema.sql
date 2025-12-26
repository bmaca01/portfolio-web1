-- Guestbook Database Schema
-- Web 1.0 Portfolio Site

CREATE DATABASE IF NOT EXISTS guestbook
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE guestbook;

CREATE TABLE IF NOT EXISTS entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    homepage VARCHAR(255) DEFAULT NULL,
    location VARCHAR(100) DEFAULT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved TINYINT(1) DEFAULT 1,
    INDEX idx_created (created_at DESC),
    INDEX idx_approved (approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog tables are defined in blog-schema.sql and loaded separately

-- Contact Messages Table
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
