-- ============================================================
-- Adds a "Vacancy Owner" field to Job Requirements — the person
-- accountable for that vacancy (any active user, not restricted to
-- the Recruiter role), separate from the existing Recruiter and
-- Follow-up By fields. Follows the same name-matching convention as
-- sRecruiter/sFollowupBy (no real FK — accepted app-wide pattern).
-- Purely additive. Safe to run more than once.
-- ============================================================

ALTER TABLE `tblrequirement` ADD COLUMN IF NOT EXISTS `sVacancyOwner` VARCHAR(100) DEFAULT NULL AFTER `sRecruiter`;
