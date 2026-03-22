ALTER TABLE applications
    ADD COLUMN status ENUM('new', 'contacted', 'accepted', 'rejected') NOT NULL DEFAULT 'new' AFTER student_pseudonym,
    ADD KEY idx_applications_status (status);
