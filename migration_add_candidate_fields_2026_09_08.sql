-- ============================================================
-- Extends tblcandidate to match the team's real candidate-intake
-- format (Name, Mobile, Email, Gender, Address, Education, Suitable
-- For, Current Company, Designation, Experience, Current CTC,
-- Expected CTC, Notice Period, Reference Number, Remark, Date
-- Added) — the format actually used across recruiters' working
-- sheets, so the bulk-upload template can match it exactly.
-- Purely additive; no existing column is touched. Run this in
-- Hostinger phpMyAdmin before deploying the updated code. Safe to
-- run more than once (guarded for re-run).
-- ============================================================

ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `sEmail` VARCHAR(150) DEFAULT NULL AFTER `sMobile`;
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `sGender` VARCHAR(10) DEFAULT NULL AFTER `sEmail`;
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `sAppliedFor` VARCHAR(200) DEFAULT NULL AFTER `sType`;
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `sCurrentDesignation` VARCHAR(200) DEFAULT NULL AFTER `sCurrentCompany`;
-- Free-text like tblrequirement.sSalary — real CTC values in the team's
-- sheets are things like "12-13k", "7.20LPA", "No" — not clean decimals.
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `sCurrentCtc` VARCHAR(100) DEFAULT NULL AFTER `sCurrentDesignation`;
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `sExpectedCtc` VARCHAR(100) DEFAULT NULL AFTER `sCurrentCtc`;
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `sNoticePeriod` VARCHAR(100) DEFAULT NULL AFTER `sExpectedCtc`;
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `dSourcedDate` DATE DEFAULT NULL AFTER `sNoticePeriod`;

-- ---- Email uniqueness, same safe pattern used for sMobile earlier:
--      blank -> NULL first (a UNIQUE index would otherwise choke on
--      multiple ''s), then only add the constraint if nothing collides.
UPDATE `tblcandidate` SET `sEmail` = NULL WHERE `sEmail` IS NOT NULL AND TRIM(`sEmail`) = '';
UPDATE `tblcandidate` SET `sEmail` = TRIM(LOWER(`sEmail`)) WHERE `sEmail` IS NOT NULL;

SET @dupe_count = (
  SELECT COUNT(*) FROM (
    SELECT sEmail FROM tblcandidate WHERE sEmail IS NOT NULL GROUP BY sEmail HAVING COUNT(*) > 1
  ) d
);
SET @index_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblcandidate' AND INDEX_NAME = 'uq_candidate_email'
);
SET @sql = IF(@dupe_count = 0 AND @index_exists = 0,
  'ALTER TABLE tblcandidate ADD UNIQUE KEY uq_candidate_email (sEmail)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
