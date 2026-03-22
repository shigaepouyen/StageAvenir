CREATE TABLE IF NOT EXISTS magic_link_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_magic_link_requests_email_requested_at (email, requested_at),
    KEY idx_magic_link_requests_ip_requested_at (ip_address, requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE applications
    ADD UNIQUE KEY uq_applications_internship_student (internship_id, student_id);
