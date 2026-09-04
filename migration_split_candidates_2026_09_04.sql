-- ============================================================
-- Split Candidates out of Placements — run this in Hostinger
-- phpMyAdmin AFTER migration_add_candidate_management_2026_09_04.sql
-- has already been applied. Safe to run more than once.
--
-- Before: tblplacement held both "who the candidate is" (name, mobile,
--         education, address, experience, current company, resume,
--         source, references) AND "this specific placement" (company,
--         requirement, joining status, invoicing).
-- After:  tblcandidate is the master person record (one row per
--         candidate, sMobile UNIQUE). tblplacement keeps only the
--         placement/invoice fields and links to a candidate via
--         iCandidateId, so a candidate can be placed more than once.
-- ============================================================

-- 1) Candidate master table.
CREATE TABLE IF NOT EXISTS `tblcandidate` (
  `iCandidateId` INT AUTO_INCREMENT PRIMARY KEY,
  `sCandidateName` VARCHAR(200) NOT NULL,
  `sMobile` VARCHAR(20) DEFAULT NULL,
  `sType` ENUM('T','NT') NOT NULL DEFAULT 'NT',
  `sEducation` VARCHAR(255) DEFAULT NULL,
  `sExperience` VARCHAR(100) DEFAULT NULL,
  `sCurrentCompany` VARCHAR(200) DEFAULT NULL,
  `sAddress` VARCHAR(500) DEFAULT NULL,
  `sSource` VARCHAR(100) DEFAULT NULL,
  `sResumePath` VARCHAR(255) DEFAULT NULL,
  `sRef1` VARCHAR(150) DEFAULT NULL,
  `sRef2` VARCHAR(150) DEFAULT NULL,
  `sRemark` TEXT DEFAULT NULL,
  `iCreatedBy` INT DEFAULT NULL,
  `dCreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `dUpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dDeletedAt` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Link column on placements (nullable — a placement always has exactly
--    one candidate once migrated, but stays nullable at the DB level so a
--    candidate can never take the whole placement row down with it).
ALTER TABLE `tblplacement` ADD COLUMN IF NOT EXISTS `iCandidateId` INT DEFAULT NULL AFTER `iPlacementId`;

-- 3) Backfill: create exactly one candidate per existing placement row that
--    isn't linked yet (idempotent — already-linked rows are skipped). A
--    temporary bridge column carries the placement id across the two
--    statements so each placement links back to *its own* new candidate,
--    not just any row with a matching name/mobile.
--
--    Guarded as dynamic SQL: step 7 (below) drops these very source columns
--    from tblplacement once they're copied, so a second run of this whole
--    file must skip the backfill entirely rather than reference columns
--    that no longer exist.
ALTER TABLE `tblcandidate` ADD COLUMN IF NOT EXISTS `iMigrationPlacementId` INT DEFAULT NULL;

SET @source_cols_exist = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblplacement' AND COLUMN_NAME = 'sCandidateName'
);

SET @sql = IF(@source_cols_exist > 0,
  'INSERT INTO tblcandidate
     (sCandidateName, sMobile, sType, sEducation, sExperience, sCurrentCompany, sAddress,
      sSource, sResumePath, sRef1, sRef2, iCreatedBy, dCreatedAt, dDeletedAt, iMigrationPlacementId)
   SELECT p.sCandidateName, p.sMobile, p.sType, p.sEducation, p.sExperience, p.sCurrentCompany, p.sAddress,
          p.sSource, p.sResumePath, p.sRef1, p.sRef2, p.iCreatedBy, p.dCreatedAt, p.dDeletedAt, p.iPlacementId
   FROM tblplacement p
   WHERE p.iCandidateId IS NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `tblplacement` p
JOIN `tblcandidate` c ON c.`iMigrationPlacementId` = p.`iPlacementId`
SET p.`iCandidateId` = c.`iCandidateId`
WHERE p.`iCandidateId` IS NULL;

ALTER TABLE `tblcandidate` DROP COLUMN IF EXISTS `iMigrationPlacementId`;

-- 4) Normalize + uniquify sMobile on the new candidate table (same reasoning
--    as the earlier placement migration: blank -> NULL so a UNIQUE index
--    doesn't choke on multiple ''s, only add the constraint if nothing
--    already collides).
UPDATE `tblcandidate` SET `sMobile` = NULL WHERE `sMobile` IS NOT NULL AND TRIM(`sMobile`) = '';
UPDATE `tblcandidate` SET `sMobile` = TRIM(`sMobile`) WHERE `sMobile` IS NOT NULL;

SET @dupe_count = (
  SELECT COUNT(*) FROM (
    SELECT sMobile FROM tblcandidate WHERE sMobile IS NOT NULL GROUP BY sMobile HAVING COUNT(*) > 1
  ) d
);
SET @index_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblcandidate' AND INDEX_NAME = 'uq_candidate_mobile'
);
SET @sql = IF(@dupe_count = 0 AND @index_exists = 0,
  'ALTER TABLE tblcandidate ADD UNIQUE KEY uq_candidate_mobile (sMobile)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5) Foreign key + index from placement to candidate. ON DELETE SET NULL —
--    a candidate should normally be soft-deleted (trashed), never hard
--    deleted while placements point at it, but this keeps a placement row
--    alive even if that ever happens.
SET @fk_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblplacement' AND CONSTRAINT_NAME = 'fk_place_candidate'
);
SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE tblplacement ADD CONSTRAINT fk_place_candidate FOREIGN KEY (iCandidateId) REFERENCES tblcandidate(iCandidateId) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblplacement' AND INDEX_NAME = 'iCandidateId'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE tblplacement ADD INDEX (iCandidateId)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6) Drop the old unique constraint on tblplacement.sMobile — uniqueness now
--    lives on tblcandidate.
SET @old_uniq_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblplacement' AND INDEX_NAME = 'uq_placement_mobile'
);
SET @sql = IF(@old_uniq_exists > 0, 'ALTER TABLE tblplacement DROP INDEX uq_placement_mobile', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7) Drop the now-redundant candidate-profile columns from tblplacement —
--    every value was already copied into tblcandidate in step 3.
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sCandidateName`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sMobile`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sType`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sEducation`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sExperience`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sCurrentCompany`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sAddress`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sSource`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sResumePath`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sRef1`;
ALTER TABLE `tblplacement` DROP COLUMN IF EXISTS `sRef2`;
