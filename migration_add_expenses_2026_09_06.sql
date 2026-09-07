-- ============================================================
-- Revenue & Financial module — expense tracking. Revenue itself
-- is already derived from tblplacement (dRecAmount); this table
-- only adds the missing half (Expenses), so Profit = Revenue -
-- Expenses can be computed from real data. Run this in Hostinger
-- phpMyAdmin before deploying the updated code. Safe to run more
-- than once (guarded for re-run).
-- ============================================================

CREATE TABLE IF NOT EXISTS `tblexpense` (
  `iExpenseId` INT AUTO_INCREMENT PRIMARY KEY,
  `sCategory` VARCHAR(100) NOT NULL,
  `sDescription` VARCHAR(255) DEFAULT NULL,
  `dAmount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `dExpenseDate` DATE NOT NULL,
  `sPaymentMode` VARCHAR(50) DEFAULT NULL,
  `sRemark` TEXT DEFAULT NULL,
  `iCreatedBy` INT DEFAULT NULL,
  `dCreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `dUpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dDeletedAt` DATETIME DEFAULT NULL,
  INDEX (`dExpenseDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
