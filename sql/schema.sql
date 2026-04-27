-- ============================================================
-- RUCU: Secure Digital Exchange System for Inter-Hospital
--       Patient Transfer
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS rucu_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rucu_db;

-- --------------------------------------------------------
-- Table: hospitals
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS hospitals (
    id       INT          NOT NULL AUTO_INCREMENT,
    name     VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT          NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    hospital_id INT          NOT NULL,
    role        ENUM('doctor', 'admin') NOT NULL DEFAULT 'doctor',
    pin         VARCHAR(255) DEFAULT NULL,
    mfa_code    VARCHAR(10)  DEFAULT NULL,
    theme       ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT fk_users_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: patients
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id          INT          NOT NULL AUTO_INCREMENT,
    mrn         VARCHAR(50)  NOT NULL, -- Medical Record Number
    full_name   VARCHAR(255) NOT NULL,
    dob         DATE         NOT NULL,
    gender      ENUM('Male', 'Female', 'Other') NOT NULL,
    contact     VARCHAR(100) DEFAULT NULL,
    hospital_id INT          NOT NULL, -- Patient's home/origin hospital
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_patients_mrn (mrn),
    CONSTRAINT fk_p_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: patient_records
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS patient_records (
    id                  INT          NOT NULL AUTO_INCREMENT,
    patient_id          INT          DEFAULT NULL, -- Link to patients table
    patient_name        VARCHAR(255) NOT NULL,     -- Legacy/Fallback
    diagnosis           TEXT         NOT NULL,
    file_path           VARCHAR(500) DEFAULT NULL,
    sender_id           INT          NOT NULL,
    receiver_hospital_id INT         NOT NULL,
    is_encrypted        TINYINT(1)   NOT NULL DEFAULT 0,
    status              ENUM('pending','received','rejected') NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_pr_patient  FOREIGN KEY (patient_id)          REFERENCES patients(id)  ON DELETE SET NULL,
    CONSTRAINT fk_pr_sender   FOREIGN KEY (sender_id)            REFERENCES users(id)     ON DELETE CASCADE,
    CONSTRAINT fk_pr_receiver FOREIGN KEY (receiver_hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table: audit_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id           INT          NOT NULL AUTO_INCREMENT,
    action       VARCHAR(100) NOT NULL,
    performed_by INT          NOT NULL,
    record_id    INT          DEFAULT NULL,
    timestamp    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    previous_hash TEXT        NOT NULL,
    current_hash  TEXT        NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_al_user   FOREIGN KEY (performed_by) REFERENCES users(id)           ON DELETE CASCADE,
    CONSTRAINT fk_al_record FOREIGN KEY (record_id)    REFERENCES patient_records(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Seed Data: Sample hospitals
-- --------------------------------------------------------
INSERT INTO hospitals (name, location) VALUES
('Muhimbili National Hospital',   'Dar es Salaam'),
('Kilimanjaro Christian Medical Centre', 'Moshi'),
('Bugando Medical Centre',        'Mwanza'),
('Mbeya Referral Hospital',       'Mbeya');
