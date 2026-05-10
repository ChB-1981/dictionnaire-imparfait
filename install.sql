-- ============================================================
-- Dictionnaire imparfait — Script d'installation SQL
-- À exécuter une seule fois pour créer toutes les tables.
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ── Table principale des mots ──────────────────────────────
CREATE TABLE IF NOT EXISTS `dictionnaire_mots` (
    `id`                      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `mot_original`            VARCHAR(100)    NOT NULL,
    `type_original`           VARCHAR(50)     NOT NULL,
    `genre_mot`               VARCHAR(30)     DEFAULT NULL,
    `etymologie_originale`    TEXT            NOT NULL,
    `definition_1_originale`  TEXT            NOT NULL,
    `registre_definition_1`   VARCHAR(50)     NOT NULL,
    `definition_2_originale`  TEXT            DEFAULT NULL,
    `registre_definition_2`   VARCHAR(50)     DEFAULT NULL,
    `exemple_original`        TEXT            NOT NULL,
    `suggestions_ia`          LONGTEXT        DEFAULT NULL,
    `score_total`             TINYINT UNSIGNED DEFAULT NULL,
    `coeurs`                  INT UNSIGNED    NOT NULL DEFAULT 0,
    `statut`                  ENUM('brouillon','suggestion_creee','finalise') NOT NULL DEFAULT 'brouillon',
    `created_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_statut`    (`statut`),
    KEY `idx_score`     (`score_total`),
    KEY `idx_coeurs`    (`coeurs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registres d'expérience ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `dictionnaire_registres_experience` (
    `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom`  VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Liaison mots <-> registres ─────────────────────────────
CREATE TABLE IF NOT EXISTS `dictionnaire_mots_registres_experience` (
    `mot_id`     INT UNSIGNED NOT NULL,
    `registre_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`mot_id`, `registre_id`),
    CONSTRAINT `fk_mot`     FOREIGN KEY (`mot_id`)      REFERENCES `dictionnaire_mots` (`id`)                      ON DELETE CASCADE,
    CONSTRAINT `fk_registre` FOREIGN KEY (`registre_id`) REFERENCES `dictionnaire_registres_experience` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Votes (cœurs) ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `dictionnaire_votes` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mot_id`     INT UNSIGNED NOT NULL,
    `ip`         VARCHAR(45)  NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_mot_ip` (`mot_id`, `ip`),
    CONSTRAINT `fk_vote_mot` FOREIGN KEY (`mot_id`) REFERENCES `dictionnaire_mots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registres d'expérience — valeurs par défaut ────────────
INSERT IGNORE INTO `dictionnaire_registres_experience` (`nom`) VALUES
    ('Rapport à la beauté'),
    ('Rapport à l''absence'),
    ('Rapport à l''attention'),
    ('Rapport à l''invisible'),
    ('Rapport au corps'),
    ('Rapport au désir'),
    ('Rapport au lieu'),
    ('Rapport au manque'),
    ('Rapport au quotidien'),
    ('Rapport au temps'),
    ('Rapport au vivant'),
    ('Rapport aux autres'),
    ('Rapport aux émotions'),
    ('Rapport à la mémoire'),
    ('Rapport à la parole'),
    ('Rapport à soi');
