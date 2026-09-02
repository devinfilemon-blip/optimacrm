-- Adds resume file storage to placements. Safe to run once; guarded for re-run.
SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblplacement' AND COLUMN_NAME = 'sResumePath'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE tblplacement ADD COLUMN sResumePath VARCHAR(255) NULL AFTER sMobile',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
