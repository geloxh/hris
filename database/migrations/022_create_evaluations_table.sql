CREATE TABLE IF NOT EXISTS evaluations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_cycle_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    evaluator_id INT UNSIGNED NOT NULL,
    type ENUM('self','manager','peer','360') NOT NULL,
    overall_rating DECIMAL(3,2) NULL,
    comments TEXT NULL,
    status ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evaluation (review_cycle_id, employee_id, evaluator_id, type),
    FOREIGN KEY (review_cycle_id) REFERENCES review_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_evaluations_employee (employee_id),
    INDEX idx_evaluations_cycle (review_cycle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
