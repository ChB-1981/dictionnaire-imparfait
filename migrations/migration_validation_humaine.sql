-- ============================================================
-- migration_validation_humaine.sql
-- Ajoute le statut en_attente, l'email contributeur et le motif
-- ============================================================

-- Nouveau statut intermédiaire
ALTER TABLE `dictionnaire_mots`
  MODIFY COLUMN `statut`
    ENUM('brouillon','en_attente','finalise')
    NOT NULL DEFAULT 'brouillon';

-- Email optionnel du contributeur (supprimé après notification)
ALTER TABLE `dictionnaire_mots`
  ADD COLUMN `email_contributeur` VARCHAR(255) DEFAULT NULL
  AFTER `statut`;

-- Motif de refus (rédigé par l'éditeur, envoyé par email)
ALTER TABLE `dictionnaire_mots`
  ADD COLUMN `motif_refus` TEXT DEFAULT NULL
  AFTER `email_contributeur`;
