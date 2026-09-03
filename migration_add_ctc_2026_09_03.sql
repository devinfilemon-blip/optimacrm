-- Adds a direct Annual CTC field to placements, since deriving annual CTC
-- from monthly salary x 12 doesn't match real offer letters (CTC usually
-- includes components beyond 12x the monthly figure). Safe to run once;
-- guarded for re-run. The old dSalary column is left in place untouched.
SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblplacement' AND COLUMN_NAME = 'dCtc'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE tblplacement ADD COLUMN dCtc DECIMAL(12,2) DEFAULT 0 AFTER dSalary',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
