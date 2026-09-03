-- ============================================================
-- Education Master feature — run this in Hostinger phpMyAdmin
-- BEFORE (or right alongside) deploying the updated code.
-- Safe to run more than once.
-- ============================================================

-- 1) Create the Education master table
CREATE TABLE IF NOT EXISTS `tbleducation` (
  `iEducationId` INT AUTO_INCREMENT PRIMARY KEY,
  `sEducation` VARCHAR(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Seed it with every education value already used in this
--    database's own Job Requirements, so nothing existing breaks
--    or goes missing from the new dropdown.
INSERT INTO `tbleducation` (`sEducation`)
SELECT DISTINCT sEducation FROM `tblrequirement`
WHERE sEducation IS NOT NULL AND sEducation <> ''
AND sEducation NOT IN (SELECT sEducation FROM `tbleducation`);
