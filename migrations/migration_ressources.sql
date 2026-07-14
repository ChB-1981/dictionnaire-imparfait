-- ============================================================
-- migration_ressources.sql
-- Ajoute la table des ressources éditoriales
-- ============================================================

CREATE TABLE `dictionnaire_ressources` (
  `id`          int(10) UNSIGNED  NOT NULL AUTO_INCREMENT,
  `titre`       varchar(255)      NOT NULL,
  `resume`      text              NOT NULL,
  `url`         varchar(500)      NOT NULL,
  `image_url`   varchar(500)      DEFAULT NULL COMMENT 'URL d une image de couverture (optionnel)',
  `source`      varchar(100)      DEFAULT NULL COMMENT 'Nom de la publication ou de l auteur',
  `created_at`  datetime          NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
