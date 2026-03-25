ALTER TABLE users
    ADD COLUMN first_name VARCHAR(100) DEFAULT NULL AFTER role,
    ADD COLUMN last_name VARCHAR(100) DEFAULT NULL AFTER first_name,
    ADD COLUMN school_class VARCHAR(100) DEFAULT NULL AFTER last_name,
    ADD COLUMN managed_class VARCHAR(100) DEFAULT NULL AFTER school_class,
    ADD KEY idx_users_last_name_first_name (last_name, first_name),
    ADD KEY idx_users_role_school_class (role, school_class),
    ADD KEY idx_users_role_managed_class (role, managed_class);

UPDATE users
SET first_name = CONCAT(UPPER(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(email, '@', 1), '.', 1), 1, 1)), LOWER(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(email, '@', 1), '.', 1), 2))),
    last_name = CONCAT(UPPER(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(email, '@', 1), '.', -1), 1, 1)), LOWER(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(email, '@', 1), '.', -1), 2)))
WHERE role = 'student'
  AND (first_name IS NULL OR first_name = '')
  AND (last_name IS NULL OR last_name = '')
  AND SUBSTRING_INDEX(email, '@', 1) LIKE '%.%';

UPDATE users u
INNER JOIN (
    SELECT student_id, MAX(classe) AS classe
    FROM applications
    WHERE student_id IS NOT NULL
      AND classe <> ''
    GROUP BY student_id
) a ON a.student_id = u.id
SET u.school_class = a.classe
WHERE u.role = 'student'
  AND (u.school_class IS NULL OR u.school_class = '');

ALTER TABLE companies
    ADD COLUMN validation_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER lng,
    ADD COLUMN validation_checked_at DATETIME DEFAULT NULL AFTER validation_status,
    ADD KEY idx_companies_validation_status (validation_status);

UPDATE companies
SET validation_status = 'approved',
    validation_checked_at = NOW()
WHERE validation_status = 'pending';

ALTER TABLE internships
    ADD COLUMN validation_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER academic_year,
    ADD COLUMN validation_checked_at DATETIME DEFAULT NULL AFTER validation_status,
    ADD KEY idx_internships_validation_status (validation_status);

UPDATE internships
SET validation_status = 'approved',
    validation_checked_at = NOW()
WHERE validation_status = 'pending';

CREATE TABLE application_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id BIGINT UNSIGNED NOT NULL,
    sender_user_id BIGINT UNSIGNED DEFAULT NULL,
    sender_role VARCHAR(50) NOT NULL,
    sender_label VARCHAR(120) DEFAULT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_application_messages_application_id (application_id, created_at),
    KEY idx_application_messages_sender_user_id (sender_user_id),
    CONSTRAINT fk_application_messages_application
        FOREIGN KEY (application_id) REFERENCES applications(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_application_messages_sender
        FOREIGN KEY (sender_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
