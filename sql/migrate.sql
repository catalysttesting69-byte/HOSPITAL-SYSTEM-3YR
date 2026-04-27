-- Migration Script
USE rucu_db;

-- 1. Add missing columns to users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role ENUM('doctor', 'admin') NOT NULL DEFAULT 'doctor' AFTER hospital_id,
ADD COLUMN IF NOT EXISTS pin VARCHAR(255) DEFAULT NULL AFTER role,
ADD COLUMN IF NOT EXISTS mfa_code VARCHAR(10) DEFAULT NULL AFTER pin,
ADD COLUMN IF NOT EXISTS theme ENUM('light', 'dark') NOT NULL DEFAULT 'light' AFTER mfa_code;

-- 2. Create patients table
CREATE TABLE IF NOT EXISTS patients (
    id          INT          NOT NULL AUTO_INCREMENT,
    mrn         VARCHAR(50)  NOT NULL,
    full_name   VARCHAR(255) NOT NULL,
    dob         DATE         NOT NULL,
    gender      ENUM('Male', 'Female', 'Other') NOT NULL,
    contact     VARCHAR(100) DEFAULT NULL,
    hospital_id INT          NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_patients_mrn (mrn),
    CONSTRAINT fk_p_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id          INT          NOT NULL AUTO_INCREMENT,
    user_id     INT          NOT NULL,
    message     VARCHAR(500) NOT NULL,
    link        VARCHAR(255) DEFAULT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Update patient_records
-- We use a check to avoid errors if column already exists
SET @dropdown = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'patient_records' AND COLUMN_NAME = 'patient_id' AND TABLE_SCHEMA = 'rucu_db');
SET @sql = IF(@dropdown > 0, 'SELECT 1', 'ALTER TABLE patient_records ADD COLUMN patient_id INT DEFAULT NULL FIRST, ADD CONSTRAINT fk_pr_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
