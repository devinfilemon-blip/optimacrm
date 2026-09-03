-- Adds the Team Leader role: a Team Leader user can add/manage Recruiters
-- that report to them. iManagerId links a Recruiter to their Team Leader.
-- Safe to run once; guarded for re-run.
SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbluser' AND COLUMN_NAME = 'iManagerId'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE tbluser ADD COLUMN iManagerId INT NULL AFTER sRole, ADD CONSTRAINT fk_user_manager FOREIGN KEY (iManagerId) REFERENCES tbluser(iUserid) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
