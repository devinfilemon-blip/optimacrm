-- ============================================================
-- New "Company Agreement" module — tracks the commercial terms
-- Optima has agreed with each client company (see Recruitment
-- Agreement Proposal template), separate from the day-to-day
-- Company record. One agreement row per company; its status moves
-- from Pending to Completed once terms are finalized and signed.
--
-- dCtcPercentage mirrors tblcompany.dAgreementPercentage (added in an
-- earlier migration to auto-fill placement invoice charges) — the API
-- keeps the two in sync whenever a Completed agreement's % is saved,
-- so the existing Add Placement auto-calc keeps working unchanged.
--
-- Purely additive. Safe to run more than once.
-- ============================================================

CREATE TABLE IF NOT EXISTS `tblcompanyagreement` (
    `iAgreementId` INT NOT NULL AUTO_INCREMENT,
    `iCompanyId` INT NOT NULL,
    `sStatus` ENUM('Pending','Completed') NOT NULL DEFAULT 'Pending',
    `dCtcPercentage` DECIMAL(5,2) DEFAULT NULL,
    `dGovernmentTax` DECIMAL(5,2) DEFAULT 18.00,
    `iReplacementMonths` INT DEFAULT 3,
    `iPaymentWithinDays` INT DEFAULT 15,
    `iBillingWithinDays` INT DEFAULT 8,
    `dEffectiveDate` DATE DEFAULT NULL,
    `sRemark` TEXT DEFAULT NULL,
    `iCreatedBy` INT DEFAULT NULL,
    `dCreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `dUpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`iAgreementId`),
    UNIQUE KEY `uq_company_agreement` (`iCompanyId`),
    CONSTRAINT `fk_companyagreement_company` FOREIGN KEY (`iCompanyId`) REFERENCES `tblcompany` (`iCompanyId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
