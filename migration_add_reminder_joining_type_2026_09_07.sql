-- ============================================================
-- Adds a third Type option, "Candidate Joining", to the Calendar's
-- Add/Edit Schedule modal (tblreminders.sType), alongside the
-- existing Reminder and Interview types added in
-- migration_add_reminder_type_2026_09_11.sql. This lets a user
-- manually schedule/track an expected joining date — separate from
-- the automatic "Candidate Joining" events already sourced from
-- tblplacement.dJoiningDate on the calendar (those stay read-only
-- and untouched).
-- MODIFY COLUMN is naturally idempotent — safe to run more than once.
-- ============================================================

ALTER TABLE `tblreminders` MODIFY COLUMN `sType` ENUM('Reminder','Interview','Joining') NOT NULL DEFAULT 'Reminder';
