-- ============================================================
-- Candidate Management upgrade — run this in Hostinger phpMyAdmin
-- (or any MySQL client) BEFORE / alongside deploying the updated code.
-- Safe to run more than once.
-- ============================================================

-- 1) New candidate profile fields on the Candidates & Placements table.
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `sEducation` VARCHAR(255) DEFAULT NULL AFTER `sPost`;
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `sExperience` VARCHAR(100) DEFAULT NULL AFTER `sEducation`;
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `sCurrentCompany` VARCHAR(200) DEFAULT NULL AFTER `sExperience`;
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `sAddress` VARCHAR(500) DEFAULT NULL AFTER `sCurrentCompany`;

-- 2) Rename the "Pending" joining status to "Offer Accepted" everywhere it's
--    used today, and make it the new default for the column.
UPDATE `tblplacement` SET `sJoiningStatus` = 'Offer Accepted' WHERE `sJoiningStatus` = 'Pending';
ALTER TABLE `tblplacement` MODIFY COLUMN `sJoiningStatus` VARCHAR(30) NOT NULL DEFAULT 'Offer Accepted';

-- 3) Normalize sMobile so blank values are NULL rather than empty strings —
--    a UNIQUE index treats every '' as a duplicate of every other '', while
--    it happily allows any number of NULLs.
UPDATE `tblplacement` SET `sMobile` = NULL WHERE `sMobile` IS NOT NULL AND TRIM(`sMobile`) = '';
UPDATE `tblplacement` SET `sMobile` = TRIM(`sMobile`) WHERE `sMobile` IS NOT NULL;

-- 4) Add a UNIQUE constraint on sMobile — but only if the data that's
--    already in this database doesn't have conflicting duplicates. If it
--    does, this step is skipped (existing records are left untouched) and
--    the app still enforces uniqueness at the API layer; re-run this file
--    after resolving the duplicates (see the SELECT below) to add the
--    database-level guarantee too.
--
-- Run this manually first if you want to see what's blocking it:
--   SELECT sMobile, COUNT(*) c FROM tblplacement
--   WHERE sMobile IS NOT NULL GROUP BY sMobile HAVING c > 1;

SET @dupe_count = (
  SELECT COUNT(*) FROM (
    SELECT sMobile FROM tblplacement WHERE sMobile IS NOT NULL GROUP BY sMobile HAVING COUNT(*) > 1
  ) d
);
SET @index_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblplacement' AND INDEX_NAME = 'uq_placement_mobile'
);
SET @sql = IF(@dupe_count = 0 AND @index_exists = 0,
  'ALTER TABLE tblplacement ADD UNIQUE KEY uq_placement_mobile (sMobile)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
