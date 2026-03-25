CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    role VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) DEFAULT NULL,
    school_class VARCHAR(100) DEFAULT NULL,
    managed_class VARCHAR(100) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_users_last_name_first_name (last_name, first_name),
    KEY idx_users_role_school_class (role, school_class),
    KEY idx_users_role_managed_class (role, managed_class),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    selector CHAR(24) NOT NULL,
    hashed_validator CHAR(64) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_auth_tokens_selector (selector),
    KEY idx_auth_tokens_user_id (user_id),
    KEY idx_auth_tokens_expires_at (expires_at),
    CONSTRAINT fk_auth_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE magic_link_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_magic_link_requests_email_requested_at (email, requested_at),
    KEY idx_magic_link_requests_ip_requested_at (ip_address, requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    siret CHAR(14) NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    naf_code VARCHAR(10) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    lat DECIMAL(10,7) DEFAULT NULL,
    lng DECIMAL(10,7) DEFAULT NULL,
    validation_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    validation_checked_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_user_id (user_id),
    UNIQUE KEY uq_companies_siret (siret),
    KEY idx_companies_validation_status (validation_status),
    CONSTRAINT fk_companies_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE internships (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    sector_tag VARCHAR(50) DEFAULT NULL,
    places_count INT UNSIGNED NOT NULL,
    status ENUM('active', 'archived', 'sleeping') NOT NULL DEFAULT 'active',
    academic_year VARCHAR(9) NOT NULL,
    validation_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    validation_checked_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_internships_company_id (company_id),
    KEY idx_internships_status (status),
    KEY idx_internships_sector_tag (sector_tag),
    KEY idx_internships_validation_status (validation_status),
    CONSTRAINT fk_internships_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags_mapping (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tag_name VARCHAR(100) NOT NULL,
    naf_prefix VARCHAR(10) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_tags_mapping_tag_name (tag_name),
    KEY idx_tags_mapping_naf_prefix (naf_prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ref_jobs (
    id_onisep VARCHAR(100) NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    domaine VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id_onisep),
    KEY idx_ref_jobs_libelle (libelle),
    KEY idx_ref_jobs_domaine (domaine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE applications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    internship_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED DEFAULT NULL,
    student_pseudonym VARCHAR(64) DEFAULT NULL,
    status ENUM('new', 'contacted', 'accepted', 'rejected') NOT NULL DEFAULT 'new',
    message TEXT NOT NULL,
    classe VARCHAR(100) NOT NULL,
    anonymized_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_applications_internship_id (internship_id),
    KEY idx_applications_student_id (student_id),
    KEY idx_applications_status (status),
    UNIQUE KEY uq_applications_internship_student (internship_id, student_id),
    KEY idx_applications_anonymized_at (anonymized_at),
    KEY idx_applications_created_at (created_at),
    CONSTRAINT fk_applications_internship
        FOREIGN KEY (internship_id) REFERENCES internships(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_applications_student
        FOREIGN KEY (student_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient_user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    link_path VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_recipient_read_created (recipient_user_id, is_read, created_at),
    CONSTRAINT fk_notifications_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE internship_revival_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    internship_id BIGINT UNSIGNED NOT NULL,
    target_academic_year VARCHAR(9) NOT NULL,
    selector CHAR(24) NOT NULL,
    hashed_validator CHAR(64) NOT NULL,
    emails_sent INT UNSIGNED NOT NULL DEFAULT 0,
    last_sent_at DATETIME DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    archived_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_revival_selector (selector),
    UNIQUE KEY uq_revival_internship_target_year (internship_id, target_academic_year),
    KEY idx_revival_pending (confirmed_at, archived_at, last_sent_at),
    CONSTRAINT fk_revival_internship
        FOREIGN KEY (internship_id) REFERENCES internships(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
