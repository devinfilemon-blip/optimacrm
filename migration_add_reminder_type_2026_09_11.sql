-- ============================================================
-- Interview Schedule on the Calendar — reuses the existing
-- Reminders table with a Type flag (Reminder vs Interview) and an
-- optional candidate link, instead of a whole new entity. The
-- calendar stops sourcing events from tblrequirement.dFollowupDate
-- ("Requirement Follow-up") and shows Interview-type reminders in
-- that slot instead; dFollowupDate itself is untouched and still
-- used on the Job Requirements list. Purely additive. Run this in
-- Hostinger phpMyAdmin before deploying the updated code. Safe to
-- run more than once (guarded for re-run).
-- ============================================================

ALTER TABLE `tblreminders` ADD COLUMN IF NOT EXISTS `sType` ENUM('Reminder','Interview') NOT NULL DEFAULT 'Reminder' AFTER `sDescription`;
ALTER TABLE `tblreminders` ADD COLUMN IF NOT EXISTS `iCandidateId` INT DEFAULT NULL AFTER `iReqId`;
