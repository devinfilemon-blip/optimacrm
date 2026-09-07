-- ============================================================
-- Adds a Google location (Maps link/pin) field to Companies,
-- shown right below the existing Location field. Purely additive.
-- Run this in Hostinger phpMyAdmin before deploying the updated
-- code. Safe to run more than once (guarded for re-run).
-- ============================================================

ALTER TABLE `tblcompany` ADD COLUMN IF NOT EXISTS `sGoogleLocation` VARCHAR(500) DEFAULT NULL AFTER `sLocation`;
