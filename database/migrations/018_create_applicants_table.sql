CREATE TABLE IF NOT EXISTS applicants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_posting_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    resume_path VARCHAR(255) NULL,
    stage ENUM('applied','screening','interview','offer','hired','rejected') NOT NULL DEFAULT 'applied',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_posting_id) REFERENCES job_postings(id) ON DELETE CASCADE,
    INDEX idx_applicants_stage (stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
