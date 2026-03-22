-- ------------------------------------------------------------
-- Avenir Pro - jeu de donnees de demonstration
-- A importer apres schema.sql sur une base vide.
-- Les identifiants sont fixes pour rendre les relations lisibles.
-- Comptes de demo utiles :
-- - admin@avenirpro.test
-- - marine.duval@avenirpro.test
-- - thomas.leroy@avenirpro.test
-- - zoe.martin@avenirpro.test
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Utilisateurs
-- ------------------------------------------------------------
INSERT INTO users (id, email, role, created_at) VALUES
    (1, 'admin@avenirpro.test', 'admin', '2025-09-01 08:00:00'),
    (2, 'marine.duval@avenirpro.test', 'parent', '2025-09-01 08:10:00'),
    (3, 'thomas.leroy@avenirpro.test', 'company', '2025-09-01 08:15:00'),
    (4, 'nina.caron@avenirpro.test', 'company', '2025-09-01 08:20:00'),
    (5, 'lucie.bernard@avenirpro.test', 'parent', '2025-09-01 08:25:00'),
    (6, 'camille.robert@avenirpro.test', 'company', '2025-09-01 08:30:00'),
    (7, 'hugo.morel@avenirpro.test', 'company', '2025-09-01 08:35:00'),
    (8, 'zoe.martin@avenirpro.test', 'student', '2025-09-05 09:00:00'),
    (9, 'yanis.benali@avenirpro.test', 'student', '2025-09-05 09:02:00'),
    (10, 'lina.petit@avenirpro.test', 'student', '2025-09-05 09:04:00'),
    (11, 'amine.kaci@avenirpro.test', 'student', '2025-09-05 09:06:00'),
    (12, 'juliette.dubois@avenirpro.test', 'student', '2025-09-05 09:08:00'),
    (13, 'sami.elmansouri@avenirpro.test', 'student', '2025-09-05 09:10:00'),
    (14, 'lea.brunet@avenirpro.test', 'student', '2025-09-05 09:12:00'),
    (15, 'noah.leruste@avenirpro.test', 'student', '2025-09-05 09:14:00'),
    (16, 'ines.leclercq@avenirpro.test', 'student', '2025-09-05 09:16:00');

-- ------------------------------------------------------------
-- Tags eleves -> prefixes NAF
-- ------------------------------------------------------------
INSERT INTO tags_mapping (id, tag_name, naf_prefix) VALUES
    (1, 'Sante', '86'),
    (2, 'Tech', '62'),
    (3, 'Animaux', '75'),
    (4, 'Animaux', '01'),
    (5, 'Commerce', '47'),
    (6, 'Education', '85'),
    (7, 'Sport', '93'),
    (8, 'Culture', '90'),
    (9, 'Administration', '84'),
    (10, 'Communication', '73'),
    (11, 'Industrie', '25'),
    (12, 'Social', '88');

-- ------------------------------------------------------------
-- Entreprises
-- ------------------------------------------------------------
INSERT INTO companies (id, user_id, siret, name, naf_code, address, lat, lng) VALUES
    (1, 2, '34987654321011', 'Clinique du Parc', '8610Z', '12 avenue du Parc, 59100 Roubaix', 50.6942050, 3.1745590),
    (2, 3, '51234567890123', 'Studio Pixel Nord', '6201Z', '45 rue de Lille, 59100 Roubaix', 50.6924400, 3.1742800),
    (3, 4, '42345678901234', 'Ferme des Trois Tilleuls', '0149Z', '88 chemin des Tilleuls, 59910 Bondues', 50.7009100, 3.0938200),
    (4, 5, '63456789012345', 'Librairie des Arcades', '4761Z', '19 place de la Republique, 59000 Lille', NULL, NULL),
    (5, 6, '74567890123456', 'Centre Aquatique Blue Wave', '9311Z', '7 rue des Sports, 59200 Tourcoing', 50.7244200, 3.1611000),
    (6, 7, '85678901234567', 'Mairie de Wattrelos - Communication', '8411Z', 'Place Jean Delvainquiere, 59150 Wattrelos', 50.7019500, 3.2188300);

-- ------------------------------------------------------------
-- Referentiel ONISEP minimal de demo
-- ------------------------------------------------------------
INSERT INTO ref_jobs (id_onisep, libelle, domaine) VALUES
    ('ONI-M1805', 'Developpeur web', 'Numerique'),
    ('ONI-J1501', 'Secretaire medicale', 'Sante'),
    ('ONI-A1407', 'Soigneur animalier', 'Animaux'),
    ('ONI-D1211', 'Libraire', 'Commerce et culture'),
    ('ONI-G1204', 'Educateur sportif', 'Sport'),
    ('ONI-K1404', 'Agent d accueil en administration', 'Service public');

-- ------------------------------------------------------------
-- Offres de stage
-- ------------------------------------------------------------
INSERT INTO internships (id, company_id, title, description, sector_tag, places_count, status, academic_year) VALUES
    (
        1,
        1,
        'Observer le secretariat medical et l accueil des patients',
        'Tu observes la prise de rendez vous, le circuit des patients et la coordination entre accueil, secretariat et soignants. Stage adapte a un eleve ponctuel, curieux et a l aise a l oral.',
        'Sante',
        2,
        'active',
        '2025-2026'
    ),
    (
        2,
        1,
        'Decouvrir un laboratoire d analyses et sa logistique',
        'Tu suis une journee type entre reception des dossiers, organisation des echantillons et explications sur les regles d hygiene. Le stage montre aussi les metiers autour du laboratoire hors gestes techniques.',
        'Sante',
        1,
        'active',
        '2025-2026'
    ),
    (
        3,
        2,
        'Stage decouverte developpement web et vie d agence',
        'Tu assistes aux points equipe, tu vois comment une maquette devient une page web et tu decouvres le travail de developpeurs, designers et chefs de projet sur de vrais dossiers clients.',
        'Tech',
        2,
        'active',
        '2025-2026'
    ),
    (
        4,
        2,
        'Creer des contenus pour les reseaux sociaux',
        'Tu observes la preparation d un calendrier editorial, la prise de vues, la retouche et la mise en ligne de publications. Stage ideal si tu aimes la communication et les outils numeriques.',
        'Culture',
        1,
        'active',
        '2025-2026'
    ),
    (
        5,
        3,
        'Soins aux animaux et organisation de la ferme',
        'Tu aides a preparer les tours de soin, tu observes la gestion des boxes et tu decouvres le lien entre bien etre animal, nettoyage, stocks et accueil du public.',
        'Animaux',
        2,
        'active',
        '2025-2026'
    ),
    (
        6,
        4,
        'Vie d une librairie de quartier',
        'Tu observes la reception des cartons, la mise en rayon, le conseil aux clients et l organisation d une petite vitrine thematique. Offre actuellement masquee cote eleve pour gestion interne.',
        'Commerce',
        1,
        'sleeping',
        '2025-2026'
    ),
    (
        7,
        5,
        'Accueil et coulisses d un centre aquatique',
        'Tu suis les equipes d accueil et d entretien pour comprendre comment un equipement sportif ouvre au public chaque jour. Tu decouvres aussi la securite, les plannings et les checks avant ouverture.',
        'Sport',
        2,
        'active',
        '2025-2026'
    ),
    (
        8,
        6,
        'Communication locale a la mairie',
        'Tu observes la preparation de contenus pour les habitants, l affichage, le site web municipal et l organisation d un petit evenement local.',
        'Administration',
        1,
        'archived',
        '2024-2025'
    ),
    (
        9,
        6,
        'Observer l accueil au service etat civil',
        'Tu decouvres comment les demandes sont recues, triees puis orientees vers les bons services. Stage interessant si tu veux voir le fonctionnement concret d une mairie.',
        'Administration',
        1,
        'active',
        '2025-2026'
    ),
    (
        10,
        4,
        'Mettre en rayon et conseiller en librairie jeunesse',
        'Tu observes le rangement, l etiquetage et la preparation d une selection thematique autour des romans jeunesse. Offre conservee pour reference de campagne precedente.',
        'Culture',
        2,
        'sleeping',
        '2024-2025'
    ),
    (
        11,
        5,
        'Technique et securite autour des bassins',
        'Tu suis les equipes qui controlent les espaces, verifient le materiel et preparent les installations pour accueillir les scolaires et les familles.',
        'Sport',
        1,
        'active',
        '2025-2026'
    ),
    (
        12,
        3,
        'Organisation administrative dans une ferme pedagogique',
        'Tu observes les appels, les reservations des groupes, la preparation des supports de visite et la coordination entre accueil, animaux et activites scolaires.',
        'Animaux',
        1,
        'active',
        '2025-2026'
    );

-- ------------------------------------------------------------
-- Candidatures
-- ------------------------------------------------------------
INSERT INTO applications (id, internship_id, student_id, student_pseudonym, status, message, classe, anonymized_at, created_at) VALUES
    (
        1,
        1,
        8,
        NULL,
        'new',
        'Je suis serieuse et j aime comprendre comment fonctionne un lieu d accueil. Je voudrais observer le contact avec les patients et mieux connaitre les metiers autour de la sante.',
        '3e A',
        NULL,
        '2026-02-03 18:12:00'
    ),
    (
        2,
        1,
        9,
        NULL,
        'contacted',
        'Je suis tres motive pour ce stage car je pense travailler plus tard dans un metier utile aux autres. Je suis ponctuel et je parle facilement avec les adultes.',
        '3e B',
        NULL,
        '2026-02-05 17:40:00'
    ),
    (
        3,
        3,
        10,
        NULL,
        'accepted',
        'Je passe du temps a apprendre le code et je veux voir comment une equipe construit un site pour de vrais clients. J aime aussi comprendre comment on travaille en groupe.',
        '3e A',
        NULL,
        '2026-01-28 19:05:00'
    ),
    (
        4,
        3,
        11,
        NULL,
        'new',
        'Le numerique me plait beaucoup et je voudrais decouvrir la difference entre developpement, design et gestion de projet. Je suis curieux et autonome.',
        '3e C',
        NULL,
        '2026-02-10 08:55:00'
    ),
    (
        5,
        5,
        12,
        NULL,
        'contacted',
        'J aime les animaux et je voudrais comprendre tout le travail invisible autour de leur bien etre. Je suis volontaire pour aider et apprendre.',
        '3e B',
        NULL,
        '2026-02-11 16:20:00'
    ),
    (
        6,
        7,
        13,
        NULL,
        'accepted',
        'Je pratique la natation et je trouve interessant de voir ce qui se passe avant l ouverture au public. J aimerais mieux connaitre les metiers du sport et de l accueil.',
        '3e B',
        NULL,
        '2026-01-30 13:35:00'
    ),
    (
        7,
        9,
        14,
        NULL,
        'rejected',
        'Je souhaite comprendre comment une mairie aide les habitants au quotidien. Je suis organisee et j aime les activites de service public.',
        '3e A',
        NULL,
        '2026-02-08 11:10:00'
    ),
    (
        8,
        6,
        15,
        NULL,
        'accepted',
        'Je lis beaucoup et je voudrais voir comment on choisit les livres, comment on range les nouveautes et comment on conseille les clients.',
        '3e C',
        NULL,
        '2026-01-18 15:45:00'
    ),
    (
        9,
        6,
        9,
        NULL,
        'rejected',
        'Je souhaite comprendre comment fonctionne une librairie independante et comment on prepare une vitrine attirante. Je suis motive et soigneux.',
        '3e B',
        NULL,
        '2026-01-19 09:25:00'
    ),
    (
        10,
        11,
        8,
        NULL,
        'contacted',
        'Je veux mieux connaitre les aspects techniques et les regles de securite dans un equipement sportif. Je suis attentif et j aime voir comment une equipe s organise.',
        '3e A',
        NULL,
        '2026-02-14 18:05:00'
    ),
    (
        11,
        12,
        10,
        NULL,
        'new',
        'Je suis interessee par la vie dune structure qui accueille des groupes et des scolaires. J aimerais voir a la fois le contact avec le public et l organisation en coulisses.',
        '3e A',
        NULL,
        '2026-02-16 10:45:00'
    ),
    (
        12,
        4,
        13,
        NULL,
        'accepted',
        'J aime les reseaux sociaux, la photo et les petites videos. Je voudrais decouvrir comment on prepare un contenu avant de le publier.',
        '3e B',
        NULL,
        '2026-02-01 12:20:00'
    ),
    (
        13,
        8,
        NULL,
        'eleve-000013',
        'accepted',
        '[message anonymise automatiquement apres la campagne]',
        '[classe anonymisee]',
        '2025-07-15 03:10:00',
        '2025-04-09 14:30:00'
    ),
    (
        14,
        7,
        11,
        NULL,
        'new',
        'Je cherche un stage concret avec du mouvement et du contact avec le public. Je veux observer le travail d equipe dans un lieu sportif.',
        '3e C',
        NULL,
        '2026-02-17 09:05:00'
    );

-- ------------------------------------------------------------
-- Campagne de reveil de demo
-- ------------------------------------------------------------
INSERT INTO internship_revival_requests (
    id,
    internship_id,
    target_academic_year,
    selector,
    hashed_validator,
    emails_sent,
    last_sent_at,
    confirmed_at,
    archived_at,
    created_at
) VALUES
    (
        1,
        10,
        '2025-2026',
        'revivaldemo000000000001',
        SHA2('demo-validator-1', 256),
        2,
        '2025-09-04 07:30:00',
        NULL,
        NULL,
        '2025-09-01 07:30:00'
    );
