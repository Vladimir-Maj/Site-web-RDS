-- ============================================================
-- USER MANAGEMENT
-- ============================================================

CREATE TABLE user (
    id BINARY(16) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pilot (
    user_id BINARY(16) PRIMARY KEY,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE student (
    user_id BINARY(16) PRIMARY KEY,
    status VARCHAR(50), -- e.g., 'Searching', 'Hired'
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE administrator (
    user_id BINARY(16) PRIMARY KEY,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ORGANIZATION
-- ============================================================

CREATE TABLE campus (
    id BINARY(16) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT
) ENGINE=InnoDB;

CREATE TABLE promotion (
    id BINARY(16) PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    academic_year VARCHAR(20),
    campus_id BINARY(16) NOT NULL,
    FOREIGN KEY (campus_id) REFERENCES campus(id)
) ENGINE=InnoDB;

-- ============================================================
-- BUSINESS & OFFERS
-- ============================================================

CREATE TABLE business_sector (
    id BINARY(16) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE company (
    id BINARY(16) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    email VARCHAR(255),
    phone VARCHAR(20),
    tax_id VARCHAR(14), -- SIRET
    is_active BOOLEAN DEFAULT 1,
    sector_id BINARY(16) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES business_sector(id)
) ENGINE=InnoDB;

CREATE TABLE company_site (
    id BINARY(16) PRIMARY KEY,
    address TEXT NOT NULL,
    city VARCHAR(100),
    company_id BINARY(16) NOT NULL,
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE skill (
    id BINARY(16) PRIMARY KEY,
    label VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE internship_offer (
    id BINARY(16) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    hourly_rate DECIMAL(10, 2),
    start_date DATE,
    duration_weeks INT,
    is_active BOOLEAN DEFAULT 1,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    site_id BINARY(16) NOT NULL,
    FOREIGN KEY (site_id) REFERENCES company_site(id)
) ENGINE=InnoDB;

-- ============================================================
-- ASSOCIATIVE TABLES (Relationships)
-- ============================================================

-- Pilot managing a Promotion
CREATE TABLE promotion_assignment (
    promotion_id BINARY(16),
    pilot_id BINARY(16),
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (promotion_id, pilot_id),
    FOREIGN KEY (promotion_id) REFERENCES promotion(id),
    FOREIGN KEY (pilot_id) REFERENCES pilot(user_id)
);

-- Student enrolled in a Promotion
CREATE TABLE student_enrollment (
    promotion_id BINARY(16),
    student_id BINARY(16),
    enrolled_at DATE,
    PRIMARY KEY (promotion_id, student_id),
    FOREIGN KEY (promotion_id) REFERENCES promotion(id),
    FOREIGN KEY (student_id) REFERENCES student(user_id)
);

-- Skills required for an Offer
CREATE TABLE offer_requirement (
    offer_id BINARY(16),
    skill_id BINARY(16),
    PRIMARY KEY (offer_id, skill_id),
    FOREIGN KEY (offer_id) REFERENCES internship_offer(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill(id)
);

-- Internship Application
CREATE TABLE application (
    id BINARY(16) PRIMARY KEY,
    student_id BINARY(16) NOT NULL,
    offer_id BINARY(16) NOT NULL,
    cv_path VARCHAR(255),
    cover_letter_path VARCHAR(255),
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(user_id),
    FOREIGN KEY (offer_id) REFERENCES internship_offer(id)
);

-- Pilot reviewing a Company
CREATE TABLE business_review (
    pilot_id BINARY(16),
    company_id BINARY(16),
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (pilot_id, company_id),
    FOREIGN KEY (pilot_id) REFERENCES pilot(user_id),
    FOREIGN KEY (company_id) REFERENCES company(id)
);

-- Student Wishlist (Favorites)
CREATE TABLE wishlist (
    student_id BINARY(16),
    offer_id BINARY(16),
    saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, offer_id),
    FOREIGN KEY (student_id) REFERENCES student(user_id),
    FOREIGN KEY (offer_id) REFERENCES internship_offer(id) ON DELETE CASCADE
);

DELIMITER //

CREATE FUNCTION generate_uuidv7() 
RETURNS BINARY(16)
DETERMINISTIC
BEGIN
    -- Get current timestamp in milliseconds
    SET @unix_ms = ROUND(UNIX_TIMESTAMP(CURTIME(4)) * 1000);
    -- Construct UUIDv7: 48 bits time | 4 bits version (7) | 12 bits sequence/rand | 2 bits variant (10) | 62 bits rand
    SET @uuid_hex = CONCAT(
        LPAD(HEX(@unix_ms), 12, '0'),
        '7',
        LPAD(HEX(FLOOR(RAND() * 0xFFF)), 3, '0'),
        HEX(0x8000 | FLOOR(RAND() * 0x3FFF)),
        LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFFFFFF)), 12, '0')
    );
    RETURN UNHEX(@uuid_hex);
END//

DELIMITER ;


DELIMITER //

-- Apply this logic to all main tables (user, campus, promotion, sector, company, site, skill, offer, application)
CREATE TRIGGER tg_user_insert BEFORE INSERT ON user FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_campus_insert BEFORE INSERT ON campus FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_promotion_insert BEFORE INSERT ON promotion FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_sector_insert BEFORE INSERT ON business_sector FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_company_insert BEFORE INSERT ON company FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_site_insert BEFORE INSERT ON company_site FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_skill_insert BEFORE INSERT ON skill FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_offer_insert BEFORE INSERT ON internship_offer FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

CREATE TRIGGER tg_application_insert BEFORE INSERT ON application FOR EACH ROW 
BEGIN IF NEW.id IS NULL THEN SET NEW.id = generate_uuidv7(); END IF; END//

DELIMITER ;
