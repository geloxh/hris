CREATE TABLE IF NOT EXISTS payslips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_run_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    basic_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
    gross_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
    working_days INT NOT NULL DEFAULT 0,
    absent_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payslip (payroll_run_id, employee_id),
    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
