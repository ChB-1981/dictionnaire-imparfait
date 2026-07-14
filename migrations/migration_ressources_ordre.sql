-- ============================================================
-- migration_ressources_ordre.sql
-- Ajoute le champ ordre pour hiérarchiser les ressources
-- ============================================================

ALTER TABLE `dictionnaire_ressources`
  ADD COLUMN `ordre` TINYINT UNSIGNED NOT NULL DEFAULT 0
  AFTER `date_publication`;
