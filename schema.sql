-- Barangay Records Management System
-- Import this file first (phpMyAdmin > Import), then optionally import sample_data.sql

CREATE DATABASE IF NOT EXISTS barangay_rms
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barangay_rms;

-- Staff accounts that can sign in
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Barangay details printed on certificates (name/value pairs)
CREATE TABLE IF NOT EXISTS settings (
  name  VARCHAR(50)  PRIMARY KEY,
  value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- The resident registry
CREATE TABLE IF NOT EXISTS residents (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  last_name    VARCHAR(60)  NOT NULL,
  first_name   VARCHAR(60)  NOT NULL,
  middle_name  VARCHAR(60)  NOT NULL DEFAULT '',
  suffix       VARCHAR(10)  NOT NULL DEFAULT '',
  birth_date   DATE         NOT NULL,
  sex          ENUM('Female','Male') NOT NULL,
  civil_status ENUM('Single','Married','Widowed','Separated') NOT NULL,
  purok        VARCHAR(20)  NOT NULL,
  street       VARCHAR(100) NOT NULL DEFAULT '',
  occupation   VARCHAR(100) NOT NULL DEFAULT '',
  contact_no   VARCHAR(20)  NOT NULL DEFAULT '',
  household_no VARCHAR(20)  NOT NULL DEFAULT '',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_resident (last_name, first_name, birth_date),
  INDEX idx_purok (purok)
) ENGINE=InnoDB;

-- Certificate and clearance requests. queue_no restarts every day.
CREATE TABLE IF NOT EXISTS requests (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ref_no       VARCHAR(20) NULL UNIQUE,
  queue_no     SMALLINT UNSIGNED NOT NULL,
  request_date DATE NOT NULL,
  resident_id  INT NOT NULL,
  type         ENUM('residency','indigency','clearance') NOT NULL,
  purpose      VARCHAR(150) NOT NULL,
  status       ENUM('pending','approved','released','rejected') NOT NULL DEFAULT 'pending',
  fee          DECIMAL(8,2) NOT NULL DEFAULT 0,
  or_no        VARCHAR(30) NULL,
  reason       VARCHAR(255) NULL,
  created_by   INT NULL,
  approved_by  INT NULL,
  approved_at  DATETIME NULL,
  released_at  DATETIME NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_queue (request_date, queue_no),
  INDEX idx_status (status),
  CONSTRAINT fk_req_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE RESTRICT,
  CONSTRAINT fk_req_created  FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_req_approved FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Default barangay details (change them in Settings after signing in)
INSERT IGNORE INTO settings (name, value) VALUES
  ('barangay',  'Maligaya'),
  ('locality',  'Municipality of Bayanihan'),
  ('province',  'Province of Sampaguita'),
  ('captain',   'Hon. Juan D. Bayani'),
  ('secretary', 'Ana L. Dimagiba');

-- Default accounts. Passwords: admin / admin123 and staff / staff123. Change them after the first login.
INSERT IGNORE INTO users (username, password_hash, full_name, role) VALUES
  ('admin', '$2y$10$tG3YORZAQ.nanBYrh59Ut.A7y9/4eZ6UE/.FP0GTXzFmn3yxmi4Km', 'Barangay Administrator', 'admin'),
  ('staff', '$2y$10$DXh4LxcJ.usBJaCRNPkBo.5qUE/zx9VIp2oKejjIOlZI0LArwDsKK', 'Barangay Staff', 'staff');
