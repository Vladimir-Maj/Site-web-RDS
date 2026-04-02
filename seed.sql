USE sql_db;

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM wishlist;
DELETE FROM business_review;
DELETE FROM application;
DELETE FROM offer_requirement;
DELETE FROM student_enrollment;
DELETE FROM promotion_assignment;
DELETE FROM internship_offer;
DELETE FROM skill;
DELETE FROM company_site;
DELETE FROM company;
DELETE FROM business_sector;
DELETE FROM promotion;
DELETE FROM campus;
DELETE FROM administrator;
DELETE FROM student;
DELETE FROM pilot;
DELETE FROM user;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- USER MANAGEMENT
-- ============================================================

-- Mots de passe :
-- admin@example.com    -> Admin1234!
-- pilot@example.com    -> Pilote1234!
-- pilot2@example.com   -> Pilote1234!
-- student1@example.com -> Etudiant1234!
-- student2@example.com -> EtudiantBis1234!
-- test@example.com     -> Test1234!

INSERT INTO user (
    id_user,
    email_user,
    password,
    first_name_user,
    last_name_user,
    is_active_user,
    created_at_user
) VALUES
      (1, 'admin@example.com',    '$2y$12$DR5WynRNPxfvnadXL0U9peZ33QH9F7moUbYsSA.jc82pkrRVJy9.e', 'Alice',  'Admin',   1, '2026-04-01 09:00:00'),
      (2, 'pilot@example.com',    '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Paul',   'Pilote',  1, '2026-04-01 09:05:00'),
      (3, 'student1@example.com', '$2y$12$/UP4TFAcbUQs/dCNRrmksu.C/5zI.nTNIzwKCF/sMgsvSMs1KARRe', 'Sonia',  'Martin',  1, '2026-04-01 09:10:00'),
      (4, 'student2@example.com', '$2y$12$cCi0YPr56ohxVUwoc3zmCOgxzdkEltmIlpuLpvPgWdK1NnCn0aiqi', 'Karim',  'Benali',  1, '2026-04-01 09:15:00'),
      (5, 'pilot2@example.com',   '$2y$12$qdiIoK3NJTJcXGBs0OomBe/WGTT4TCAsG7FwXIPE8bIqXi/6sjUKa', 'Claire', 'Durand',  1, '2026-04-01 09:20:00'),
      (6, 'test@example.com',     '$2y$12$3jbLcI1FHArYwkjCTBFwvuIwstxRulE62PhLwCZuxWpKpw40l95UC', 'Test',   'User',    1, '2026-04-01 09:25:00');

INSERT INTO administrator (id_administrator) VALUES
    (1);

INSERT INTO pilot (id_pilot) VALUES
                                 (2),
                                 (5);

INSERT INTO student (id_student, status_student) VALUES
                                                     (3, 'searching'),
                                                     (4, 'hired'),
                                                     (6, 'searching');

-- ============================================================
-- ORGANIZATION
-- ============================================================

INSERT INTO campus (id_campus, name_campus, address_campus) VALUES
                                                                (1, 'CESI Nancy',      '8 rue de la Grande Oye, 54500 Vandœuvre-lès-Nancy'),
                                                                (2, 'CESI Strasbourg', '2 allée des Foulons, 67380 Lingolsheim'),
                                                                (3, 'CESI Reims',      '7 bis avenue Robert Schuman, 51100 Reims');

INSERT INTO promotion (id_promotion, label_promotion, academic_year_promotion, campus_id_promotion) VALUES
                                                                                                        (1, 'CPI A2 Info', '2025-2026', 1),
                                                                                                        (2, 'CPI A1 Info', '2025-2026', 1),
                                                                                                        (3, 'B3 Info',     '2025-2026', 2),
                                                                                                        (4, 'MSI',         '2025-2026', 3);

-- ============================================================
-- BUSINESS & OFFERS
-- ============================================================

INSERT INTO business_sector (id_business_sector, name_business_sector, description_business_sector) VALUES
                                                                                                        (1, 'Informatique',   'Développement logiciel, web, applicatif et outils métiers'),
                                                                                                        (2, 'Industrie',      'Production, systèmes industriels et informatique interne'),
                                                                                                        (3, 'Réseaux & Cyber','Infrastructure, réseau, sécurité et administration systèmes'),
                                                                                                        (4, 'Cloud & SaaS',   'Services cloud, DevOps et applications SaaS');

INSERT INTO company (
    id_company,
    name_company,
    description_company,
    email_company,
    phone_company,
    tax_id_company,
    is_active_company,
    sector_id_company,
    created_at_company
) VALUES
      (1, 'TechNova',      'Entreprise spécialisée en développement web et outils métiers.',      'contact@technova.fr',    '0383000001', '54200000000001', 1, 1, '2026-04-01 10:00:00'),
      (2, 'Rhein Systems', 'Entreprise industrielle avec une équipe IT interne.',                 'hr@rheinsystems.fr',    '0383000002', '54200000000002', 1, 2, '2026-04-01 10:05:00'),
      (3, 'CloudPulse',    'Société orientée SaaS, DevOps et cloud.',                             'jobs@cloudpulse.fr',    '0383000003', '54200000000003', 1, 4, '2026-04-01 10:10:00'),
      (4, 'NetSecure',     'Prestataire réseau et cybersécurité pour PME et collectivités.',      'contact@netsecure.fr',  '0383000004', '54200000000004', 1, 3, '2026-04-01 10:15:00'),
      (5, 'DataForge',     'Cabinet orienté data engineering, reporting et automatisation.',      'careers@dataforge.fr',  '0383000005', '54200000000005', 1, 1, '2026-04-01 10:20:00');

INSERT INTO company_site (
    id_company_site,
    address_company_site,
    city_company_site,
    company_id_company_site
) VALUES
      (1, '10 rue Saint-Georges',       'Nancy',      1),
      (2, '5 avenue Foch',              'Metz',       1),
      (3, '22 route de Bischwiller',    'Haguenau',   2),
      (4, '7 quai Kléber',              'Strasbourg', 3),
      (5, '14 boulevard Lobau',         'Nancy',      4),
      (6, '3 rue Gambetta',             'Reims',      5);

INSERT INTO skill (id_skill, label_skill) VALUES
                                              (1, 'PHP'),
                                              (2, 'MySQL'),
                                              (3, 'Docker'),
                                              (4, 'Twig'),
                                              (5, 'JavaScript'),
                                              (6, 'Linux'),
                                              (7, 'Réseau'),
                                              (8, 'Cybersécurité'),
                                              (9, 'Git'),
                                              (10, 'API REST');

INSERT INTO internship_offer (
    id_internship_offer,
    title_internship_offer,
    description_internship_offer,
    hourly_rate_internship_offer,
    start_date_internship_offer,
    duration_weeks_internship_offer,
    is_active_internship_offer,
    published_at_internship_offer,
    company_site_id_internship_offer
) VALUES
      (1, 'Stage Développeur PHP/Twig',        'Participation au développement d’une plateforme interne MVC.',      5.50, '2026-05-02', 10, 1, '2026-04-01 11:00:00', 1),
      (2, 'Stage Admin Systèmes Linux',        'Administration Linux, scripts bash, supervision et déploiement.',   5.80, '2026-05-15', 12, 1, '2026-04-01 11:05:00', 5),
      (3, 'Stage Développeur Full Stack',      'Développement front/back sur une application SaaS.',                 6.00, '2026-06-01', 16, 1, '2026-04-01 11:10:00', 4),
      (4, 'Stage Support IT Industriel',       'Support utilisateurs et maintenance du parc informatique.',          5.20, '2026-05-10',  8, 1, '2026-04-01 11:15:00', 3),
      (5, 'Stage Data / Reporting',            'Création de tableaux de bord, automatisation de flux et reporting.', 5.70, '2026-05-20', 14, 1, '2026-04-01 11:20:00', 6),
      (6, 'Stage Réseau & Cybersécurité',      'Audit réseau, durcissement de postes et supervision sécurité.',      5.90, '2026-05-25', 10, 1, '2026-04-01 11:25:00', 5);

-- ============================================================
-- ASSOCIATIVE TABLES
-- ============================================================

INSERT INTO promotion_assignment (
    promotion_assignment_id,
    pilot_assignment_id,
    assigned_at
) VALUES
      (1, 2, '2026-03-01 09:00:00'),
      (2, 5, '2026-03-02 09:00:00'),
      (3, 5, '2026-03-03 09:00:00');

INSERT INTO student_enrollment (
    promotion_id_student_enrollment,
    student_id_student_enrollment,
    enrolled_at
) VALUES
      (1, 3, '2025-09-01'),
      (1, 4, '2025-09-01'),
      (2, 6, '2025-09-01');

INSERT INTO offer_requirement (
    offer_requirement_id,
    skill_requirement_id
) VALUES
      (1, 1),
      (1, 2),
      (1, 3),
      (1, 4),
      (1, 9),

      (2, 3),
      (2, 6),
      (2, 9),

      (3, 1),
      (3, 2),
      (3, 5),
      (3, 10),

      (4, 6),
      (4, 7),

      (5, 2),
      (5, 9),
      (5, 10),

      (6, 6),
      (6, 7),
      (6, 8);

INSERT INTO application (
    id_application,
    student_id_application,
    offer_id_application,
    cv_path_application,
    cover_letter_path_application,
    status_application,
    applied_at_application
) VALUES
      (1, 3, 1, '/uploads/cv/student1_cv.pdf', '/uploads/letters/student1_lp.pdf',  'pending',  '2026-04-01 14:00:00'),
      (2, 3, 2, '/uploads/cv/student1_cv.pdf', '/uploads/letters/student1_lp2.pdf', 'accepted', '2026-04-02 09:30:00'),
      (3, 4, 3, '/uploads/cv/student2_cv.pdf', '/uploads/letters/student2_lp.pdf',  'rejected', '2026-04-02 10:15:00'),
      (4, 6, 4, '/uploads/cv/test_cv.pdf',     '/uploads/letters/test_lp.pdf',      'pending',  '2026-04-03 11:45:00'),
      (5, 6, 5, '/uploads/cv/test_cv.pdf',     '/uploads/letters/test_lp2.pdf',     'pending',  '2026-04-04 16:20:00');

INSERT INTO business_review (
    pilot_id_business_review,
    company_id_business_review,
    rating_business_review,
    comment_business_review,
    reviewed_at_business_review
) VALUES
      (2, 1, 5, 'Très bon partenaire, suivi sérieux des stagiaires.',        '2026-03-20 10:00:00'),
      (2, 2, 4, 'Bonne entreprise, missions intéressantes.',                 '2026-03-21 11:00:00'),
      (5, 3, 5, 'Excellent environnement technique.',                        '2026-03-22 14:00:00'),
      (5, 4, 4, 'Bonne structure pour profils systèmes et réseau.',          '2026-03-23 15:30:00'),
      (2, 5, 4, 'Entreprise utile pour les profils orientés data.',          '2026-03-24 09:45:00');

INSERT INTO wishlist (
    student_id_wishlist,
    offer_id_wishlist,
    saved_at_wishlist
) VALUES
      (3, 3, '2026-04-01 18:00:00'),
      (3, 5, '2026-04-02 18:05:00'),
      (4, 1, '2026-04-02 18:10:00'),
      (6, 2, '2026-04-03 18:15:00'),
      (6, 6, '2026-04-03 18:20:00');