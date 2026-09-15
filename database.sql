-- =====================================================================
-- StaffHub: Workforce Time-Tracking and Salary Processing Portal
-- Database Schema
-- =====================================================================

DROP DATABASE IF EXISTS staffhub_db;
CREATE DATABASE staffhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staffhub_db;

-- ---------------------------------------------------------------------
-- Table: users
-- Stores login credentials and role for both admins and employees
-- ---------------------------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: departments
-- ---------------------------------------------------------------------
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: positions
-- ---------------------------------------------------------------------
CREATE TABLE positions (
    position_id INT AUTO_INCREMENT PRIMARY KEY,
    position_name VARCHAR(100) NOT NULL,
    department_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: employees
-- ---------------------------------------------------------------------
CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL UNIQUE,          -- human friendly Employee ID e.g. EMP-0001
    user_id INT NOT NULL,
    department_id INT NULL,
    position_id INT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    birthdate DATE NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    contact_number VARCHAR(20) NOT NULL,
    address VARCHAR(255) NULL,
    employment_status ENUM('Full-Time', 'Part-Time', 'Contractual', 'Probationary') NOT NULL DEFAULT 'Full-Time',
    basic_hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    date_hired DATE NOT NULL,
    profile_picture VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: attendance
-- ---------------------------------------------------------------------
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    time_in DATETIME NULL,
    time_out DATETIME NULL,
    total_hours DECIMAL(6,2) NULL DEFAULT 0.00,
    status ENUM('Incomplete', 'Completed', 'Verified') NOT NULL DEFAULT 'Incomplete',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_date (employee_id, attendance_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: payroll
-- ---------------------------------------------------------------------
CREATE TABLE payroll (
    payroll_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    payroll_period_start DATE NOT NULL,
    payroll_period_end DATE NOT NULL,
    verified_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    gross_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deductions DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    bonuses DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    processed_by INT NULL,
    processed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY unique_employee_period (employee_id, payroll_period_start, payroll_period_end)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: activity_logs
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    activity VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- Run seed_data.sql after this to populate demo data
-- =====================================================================
