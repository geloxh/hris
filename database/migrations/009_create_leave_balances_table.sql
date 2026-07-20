CREATE TABLE IF NOT EXISTS leave_balances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    leave_type_id INT UNSIGNED NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    allocated_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    used_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    carried_over_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
    UNIQUE KEY uq_leave_balance (employee_id, leave_type_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
