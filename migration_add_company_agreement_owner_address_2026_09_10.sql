-- ============================================================
-- Adds Company Owner Name and Company Address to Company
-- Agreements, so the printed proposal (see
-- generate-company-agreement.php) always has these filled in even
-- when the underlying Company record doesn't — the agreement's
-- "Company Owner name" / "Company Address" lines are shown to a
-- specific signatory and can differ from the company's general
-- contact person / address.
-- Purely additive. Safe to run more than once.
-- ============================================================

ALTER TABLE `tblcompanyagreement` ADD COLUMN IF NOT EXISTS `sOwnerName` VARCHAR(150) DEFAULT NULL AFTER `iCompanyId`;
ALTER TABLE `tblcompanyagreement` ADD COLUMN IF NOT EXISTS `sAddress` TEXT DEFAULT NULL AFTER `sOwnerName`;
