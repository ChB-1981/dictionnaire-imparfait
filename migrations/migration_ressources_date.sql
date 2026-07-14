-- ============================================================
-- migration_ressources_date.sql
-- Ajoute le champ date_publication à dictionnaire_ressources
-- ============================================================

ALTER TABLE `dictionnaire_ressources`
  ADD COLUMN `date_publication` DATE DEFAULT NULL
  AFTER `source`;
