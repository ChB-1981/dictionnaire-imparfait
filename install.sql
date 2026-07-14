-- ============================================================
-- schema.sql — Structure de la base de données
-- Dictionnaire imparfait
-- https://github.com/charleshenriboisseau/dictionnaire-imparfait
-- ============================================================
-- À importer pour initialiser une nouvelle instance du projet.
-- Ne contient aucune donnée, uniquement la structure des tables
-- et les valeurs de référence des registres d'expérience.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ── Table principale des mots ──────────────────────────────

CREATE TABLE `dictionnaire_mots` (
  `id`                     int(10) UNSIGNED  NOT NULL AUTO_INCREMENT,
  `mot_original`           varchar(100)      NOT NULL,
  `type_original`          varchar(50)       NOT NULL,
  `genre_mot`              varchar(30)       DEFAULT NULL,
  `type_entree`            enum('invente','reactive','importe','ressuscite')
                                             NOT NULL DEFAULT 'invente'
                                             COMMENT 'Nature du mot : inventé, réactivé, importé ou ressuscité',
  `etymologie_originale`   text              NOT NULL,
  `definition_1_originale` text              NOT NULL,
  `registre_definition_1`  varchar(50)       NOT NULL,
  `definition_2_originale` text              DEFAULT NULL,
  `registre_definition_2`  varchar(50)       DEFAULT NULL,
  `exemple_original`       text              NOT NULL,
  `suggestions_ia`         longtext          DEFAULT NULL
                                             COMMENT 'JSON retourné par le modèle d analyse (prompt v1.4+)',
  `score_total`            tinyint(3) UNSIGNED DEFAULT NULL
                                             COMMENT 'Score sur 20, calculé depuis suggestions_ia',
  `coeurs`                 int(10) UNSIGNED  NOT NULL DEFAULT 0,
  `statut`                 enum('brouillon','finalise')
                                             NOT NULL DEFAULT 'brouillon',
  `created_at`             datetime          NOT NULL DEFAULT current_timestamp(),
  `updated_at`             datetime          NOT NULL DEFAULT current_timestamp()
                                             ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_statut`  (`statut`),
  KEY `idx_score`   (`score_total`),
  KEY `idx_coeurs`  (`coeurs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Registres d expérience (référentiel) ───────────────────

CREATE TABLE `dictionnaire_registres_experience` (
  `id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100)     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valeurs de référence des 16 registres
INSERT INTO `dictionnaire_registres_experience` (`nom`) VALUES
  ('Rapport à la beauté'),
  ('Rapport à l\'absence'),
  ('Rapport à l\'attention'),
  ('Rapport à l\'invisible'),
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

-- ── Table de liaison mots / registres ──────────────────────

CREATE TABLE `dictionnaire_mots_registres_experience` (
  `mot_id`      int(10) UNSIGNED NOT NULL,
  `registre_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`mot_id`, `registre_id`),
  KEY `fk_registre` (`registre_id`),
  CONSTRAINT `fk_mot`      FOREIGN KEY (`mot_id`)      REFERENCES `dictionnaire_mots` (`id`)                 ON DELETE CASCADE,
  CONSTRAINT `fk_registre` FOREIGN KEY (`registre_id`) REFERENCES `dictionnaire_registres_experience` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Votes / cœurs (un vote par IP par mot) ─────────────────

CREATE TABLE `dictionnaire_votes` (
  `id`         int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mot_id`     int(10) UNSIGNED NOT NULL,
  `ip`         varchar(45)      NOT NULL,
  `created_at` datetime         NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vote` (`mot_id`, `ip`),
  CONSTRAINT `fk_vote_mot` FOREIGN KEY (`mot_id`) REFERENCES `dictionnaire_mots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
