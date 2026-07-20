CREATE TABLE IF NOT EXISTS goals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    review_cycle_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    target_date DATE NULL,
    status ENUM('draft','active','achieved','missed') NOT NULL DEFAULT 'active',
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (review_cycle_id) REFERENCES review_cycles(id) ON DELETE SET NULL,
    INDEX idx_goals_employee (employee_id),
    INDEX idx_goals_cycle (review_cycle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
