CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
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

CREATE TABLE companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    siret CHAR(14) NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    naf_code VARCHAR(10) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    lat DECIMAL(10,7) DEFAULT NULL,
    lng DECIMAL(10,7) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_user_id (user_id),
    UNIQUE KEY uq_companies_siret (siret),
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
    PRIMARY KEY (id),
    KEY idx_internships_company_id (company_id),
    KEY idx_internships_status (status),
    KEY idx_internships_sector_tag (sector_tag),
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
    message TEXT NOT NULL,
    classe VARCHAR(100) NOT NULL,
    anonymized_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_applications_internship_id (internship_id),
    KEY idx_applications_student_id (student_id),
    KEY idx_applications_anonymized_at (anonymized_at),
    KEY idx_applications_created_at (created_at),
    CONSTRAINT fk_applications_internship
        FOREIGN KEY (internship_id) REFERENCES internships(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_applications_student
        FOREIGN KEY (student_id) REFERENCES users(id)
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
