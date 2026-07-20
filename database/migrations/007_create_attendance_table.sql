CREATE TABLE IF NOT EXISTS attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    time_in TIME NULL,
    time_out TIME NULL,
    status ENUM('present','late','absent','on-leave','half-day') NOT NULL DEFAULT 'present',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY uq_attendance_employee_date (employee_id, work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
