-- ============================================================
-- Recruiter/user document uploads — run this in Hostinger
-- phpMyAdmin before deploying the updated code. Safe to run
-- more than once (guarded for re-run).
-- ============================================================

CREATE TABLE IF NOT EXISTS `tbluserdocument` (
  `iDocId` INT AUTO_INCREMENT PRIMARY KEY,
  `iUserId` INT NOT NULL,
  `sFileName` VARCHAR(255) NOT NULL,
  `sStoredPath` VARCHAR(255) NOT NULL,
  `sFileType` VARCHAR(20) DEFAULT NULL,
  `iFileSize` INT DEFAULT NULL,
  `iUploadedBy` INT DEFAULT NULL,
  `dUploadedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`iUserId`),
  CONSTRAINT `fk_userdoc_user` FOREIGN KEY (`iUserId`) REFERENCES `tbluser` (`iUserid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
