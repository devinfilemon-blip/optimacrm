-- ============================================================
-- URGENT — fixes the live "Unknown column 'r.dDeletedAt'" error
-- Run this now in Hostinger phpMyAdmin > your database > SQL tab.
-- Safe to run on a database that already has data. If a line
-- errors saying something "already exists", just skip that one
-- line and continue — it means it was already applied.
-- ============================================================

-- 1) PRIORITY — the Trash / soft-delete feature added this column
--    to 4 tables locally, but it was never pushed to this database.
--    This alone fixes the live crash.
ALTER TABLE `tblcompany` ADD COLUMN IF NOT EXISTS `dDeletedAt` DATETIME DEFAULT NULL;
ALTER TABLE `tblrequirement` ADD COLUMN IF NOT EXISTS `dDeletedAt` DATETIME DEFAULT NULL;
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `dDeletedAt` DATETIME DEFAULT NULL;
ALTER TABLE `tbluser` ADD COLUMN IF NOT EXISTS `dDeletedAt` DATETIME DEFAULT NULL;

-- 2) Team Leader hierarchy (Recruiters & Users "Reports To") — also
--    missing from this database.
ALTER TABLE `tbluser` ADD COLUMN IF NOT EXISTS `iManagerId` INT(11) DEFAULT NULL;

-- This adds the relationship link. If it errors because it already
-- exists, that's fine — skip it.
ALTER TABLE `tbluser`
  ADD CONSTRAINT `fk_user_manager` FOREIGN KEY (`iManagerId`) REFERENCES `tbluser` (`iUserid`) ON DELETE SET NULL;

-- 3) Placement resume upload + CTC fields
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `sResumePath` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `dCtc` DECIMAL(12,2) DEFAULT 0.00;
