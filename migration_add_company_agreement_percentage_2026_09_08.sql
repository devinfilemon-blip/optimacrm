-- ============================================================
-- Adds an "Agreement Percentage" to Companies — the % of a
-- candidate's Annual CTC this company has agreed to pay as the
-- placement fee. Shown in the company list, and used on Add
-- Placement to auto-calculate the invoice "Charges (base)" amount
-- from CTC x this percentage, instead of the user computing it by
-- hand every time.
-- Purely additive. Safe to run more than once.
-- ============================================================

ALTER TABLE `tblcompany` ADD COLUMN IF NOT EXISTS `dAgreementPercentage` DECIMAL(5,2) DEFAULT NULL AFTER `sGstin`;
