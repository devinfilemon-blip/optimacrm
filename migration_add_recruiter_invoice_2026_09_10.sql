-- ============================================================
-- Recruiter Tax Invoice — invoice metadata for a Commission expense
-- row (invoice no/date, GST, total invoice amount, and how much of
-- it has actually been paid to the recruiter). Company invoices
-- need nothing new — they already live entirely on tblplacement
-- (sInvoiceNo, dInvoiceDate, dCharges, dCgst/dSgst/dTotalGst,
-- dAmount, dRecAmount, dPaymentRecDate). Run this in Hostinger
-- phpMyAdmin before deploying the updated code. Safe to run more
-- than once (guarded for re-run).
-- ============================================================

ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `sInvoiceNo` VARCHAR(50) DEFAULT NULL AFTER `dAmount`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dInvoiceDate` DATE DEFAULT NULL AFTER `sInvoiceNo`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dGstPercent` DECIMAL(5,2) DEFAULT 0 AFTER `dInvoiceDate`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dCgst` DECIMAL(12,2) DEFAULT 0 AFTER `dGstPercent`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dSgst` DECIMAL(12,2) DEFAULT 0 AFTER `dCgst`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dTotalGst` DECIMAL(12,2) DEFAULT 0 AFTER `dSgst`;
-- dAmount stays the base commission (20% of CTC); dInvoiceAmount = dAmount + dTotalGst.
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dInvoiceAmount` DECIMAL(12,2) DEFAULT 0 AFTER `dTotalGst`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dPaidAmount` DECIMAL(12,2) DEFAULT 0 AFTER `dInvoiceAmount`;
ALTER TABLE `tblexpense` ADD COLUMN IF NOT EXISTS `dPaymentDate` DATE DEFAULT NULL AFTER `dPaidAmount`;

SET @idx_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpense' AND INDEX_NAME = 'uq_expense_invoiceno'
);
SET @sql = IF(@idx_exists = 0, 'ALTER TABLE tblexpense ADD UNIQUE KEY uq_expense_invoiceno (sInvoiceNo)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
