-- ============================================================
-- migration_type_entree.sql
-- Ajoute la colonne type_entree à dictionnaire_mots
-- et dictionnaire_mots_archive
-- ============================================================

ALTER TABLE `dictionnaire_mots`
  ADD COLUMN `type_entree`
    ENUM('invente','reactive','importe','ressuscite')
    NOT NULL DEFAULT 'invente'
  AFTER `type_original`;

ALTER TABLE `dictionnaire_mots_archive`
  ADD COLUMN `type_entree`
    ENUM('invente','reactive','importe','ressuscite')
    NOT NULL DEFAULT 'invente'
  AFTER `type_original`;
