-- ============================================================
-- Recruiter Commission as an auto-calculated expense type, linked
-- back to the placement it came from (20% of that placement's CTC).
-- Everything else about the placement — recruiter, candidate,
-- company, CTC, invoice — is already on tblplacement, so this only
-- adds the link (iPlacementId) and a type flag; nothing is
-- duplicated. Run this in Hostinger phpMyAdmin before deploying the
-- updated code. Safe to run more than once (guarded for re-run).
-- ============================================================

ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `sExpenseType` ENUM('Commission','Other') NOT NULL DEFAULT 'Other' AFTER `sCategory`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `iPlacementId` INT DEFAULT NULL AFTER `sExpenseType`;

SET @idx_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpense' AND INDEX_NAME = 'uq_expense_placement'
);
-- UNIQUE allows unlimited NULLs (every "Other" expense), but at most one
-- Commission row per placement.
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE tblexpense ADD UNIQUE KEY uq_expense_placement (iPlacementId)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpense' AND CONSTRAINT_NAME = 'fk_expense_placement'
);
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE tblexpense ADD CONSTRAINT fk_expense_placement FOREIGN KEY (iPlacementId) REFERENCES tblplacement(iPlacementId) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
