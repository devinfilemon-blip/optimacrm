-- ============================================================
-- Optima CRM — incremental migration for Hostinger
-- Safe to run on a database that already has data:
--   * CREATE TABLE IF NOT EXISTS — won't touch existing tables
--   * ALTER TABLE ... ADD COLUMN IF NOT EXISTS — skips if already applied
--   * INSERT ... WHERE NOT EXISTS — won't create duplicate master rows
-- Run each numbered section in order. If a single line errors
-- because it was already applied before, skip just that line and continue.
-- ============================================================

-- 1) New tables

CREATE TABLE IF NOT EXISTS `tblpost` (
  `iPostId` int(11) NOT NULL AUTO_INCREMENT,
  `sPost` varchar(200) NOT NULL,
  PRIMARY KEY (`iPostId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblrecruiter` (
  `iRecruiterId` int(11) NOT NULL AUTO_INCREMENT,
  `sRecruiter` varchar(100) NOT NULL,
  PRIMARY KEY (`iRecruiterId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblstatussnapshot` (
  `dSnapshotDate` date NOT NULL,
  `sLabel` varchar(50) NOT NULL,
  `iCount` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`dSnapshotDate`,`sLabel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) New column on tblrequirement (vacancy count per opening)
-- If your host's MySQL/MariaDB doesn't support "IF NOT EXISTS" on ADD COLUMN,
-- and this errors saying the column already exists, that's fine — skip it.
ALTER TABLE `tblrequirement` ADD COLUMN IF NOT EXISTS `iNoOfVacancy` int(11) NOT NULL DEFAULT 1 AFTER `sPost`;

-- 3) Requirement status master — add any statuses not already there
INSERT INTO tblstatus (sStatus) SELECT 'Searching' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Searching');
INSERT INTO tblstatus (sStatus) SELECT 'Refine Search' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Refine Search');
INSERT INTO tblstatus (sStatus) SELECT 'Closed by Co.' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Closed by Co.');
INSERT INTO tblstatus (sStatus) SELECT 'Hold' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Hold');
INSERT INTO tblstatus (sStatus) SELECT 'Profiles not found' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Profiles not found');
INSERT INTO tblstatus (sStatus) SELECT 'Interview Scheduled' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Interview Scheduled');
INSERT INTO tblstatus (sStatus) SELECT 'Offer Made' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Offer Made');
INSERT INTO tblstatus (sStatus) SELECT 'Joined' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Joined');
INSERT INTO tblstatus (sStatus) SELECT 'Not Joining' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Not Joining');
INSERT INTO tblstatus (sStatus) SELECT 'Selected' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Selected');

-- 4) Recruiter master
INSERT INTO tblrecruiter (sRecruiter) SELECT 'AB' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'AB');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Savita' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Savita');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Dhanshree' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Dhanshree');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Swati D' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Swati D');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Rohit Desai' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Rohit Desai');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'M Bhandare' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'M Bhandare');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Sonal Khavat' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Sonal Khavat');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Mrunal A' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Mrunal A');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Rashmi Salunkhe' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Rashmi Salunkhe');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Pooja A' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Pooja A');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Rushi Da' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Rushi Da');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Poonam K' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Poonam K');

-- 5) Post / Designation master
INSERT INTO tblpost (sPost) SELECT 'Maintenance Engineer' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Maintenance Engineer');
INSERT INTO tblpost (sPost) SELECT 'Office Boy' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Office Boy');
INSERT INTO tblpost (sPost) SELECT 'Electrical Maint Engineer M Shop' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Electrical Maint Engineer M Shop');
INSERT INTO tblpost (sPost) SELECT 'In House Sales Male' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'In House Sales Male');
INSERT INTO tblpost (sPost) SELECT 'Export Executive' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Export Executive');
INSERT INTO tblpost (sPost) SELECT 'Tele Calling Female Married' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Tele Calling Female Married');
INSERT INTO tblpost (sPost) SELECT 'Manager Sales' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Manager Sales');
INSERT INTO tblpost (sPost) SELECT 'Quality Engineer' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Quality Engineer');
INSERT INTO tblpost (sPost) SELECT 'Manager Production' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Manager Production');
INSERT INTO tblpost (sPost) SELECT 'Production Supervisor' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Production Supervisor');
INSERT INTO tblpost (sPost) SELECT 'Executive Assistance' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Executive Assistance');
INSERT INTO tblpost (sPost) SELECT 'Institute Mgr' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Institute Mgr');
INSERT INTO tblpost (sPost) SELECT 'Accountant' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Accountant');
INSERT INTO tblpost (sPost) SELECT 'Development Supervisor' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Development Supervisor');
INSERT INTO tblpost (sPost) SELECT 'Customer Coordinator Sales' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Customer Coordinator Sales');
INSERT INTO tblpost (sPost) SELECT 'Design & Development Engineer' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Design & Development Engineer');
INSERT INTO tblpost (sPost) SELECT 'Dy Mgr Production' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Dy Mgr Production');
INSERT INTO tblpost (sPost) SELECT 'SCM Manager' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'SCM Manager');
INSERT INTO tblpost (sPost) SELECT 'HR Manager' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'HR Manager');
INSERT INTO tblpost (sPost) SELECT 'Production Engineer' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Production Engineer');
INSERT INTO tblpost (sPost) SELECT 'Account Executive' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Account Executive');
INSERT INTO tblpost (sPost) SELECT 'PPC Engineer Foundry' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'PPC Engineer Foundry');
INSERT INTO tblpost (sPost) SELECT 'Lab Assistant Female' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Lab Assistant Female');
INSERT INTO tblpost (sPost) SELECT 'senior interior designer' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'senior interior designer');
INSERT INTO tblpost (sPost) SELECT 'junior interior designer' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'junior interior designer');
INSERT INTO tblpost (sPost) SELECT 'HR Assistant' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'HR Assistant');
INSERT INTO tblpost (sPost) SELECT 'Vendor Development Manager' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Vendor Development Manager');
INSERT INTO tblpost (sPost) SELECT 'Admin/Reception' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Admin/Reception');
INSERT INTO tblpost (sPost) SELECT 'PPC Executive' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'PPC Executive');
INSERT INTO tblpost (sPost) SELECT 'Maintenance Supervisor' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Maintenance Supervisor');
INSERT INTO tblpost (sPost) SELECT 'HR Officer (Third Party Role) Male' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'HR Officer (Third Party Role) Male');
INSERT INTO tblpost (sPost) SELECT 'Method Engineer (Heavy Steel Foundry )' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Method Engineer (Heavy Steel Foundry )');
INSERT INTO tblpost (sPost) SELECT 'Head of Maintenance' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Head of Maintenance');
INSERT INTO tblpost (sPost) SELECT 'Method Head(Heavy Steel Foundry )' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Method Head(Heavy Steel Foundry )');
INSERT INTO tblpost (sPost) SELECT 'Trainee Engineer' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Trainee Engineer');
INSERT INTO tblpost (sPost) SELECT 'Quality Inspector (General Shift )' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Quality Inspector (General Shift )');
INSERT INTO tblpost (sPost) SELECT 'QA Head' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'QA Head');
INSERT INTO tblpost (sPost) SELECT 'Prod. Head' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Prod. Head');
INSERT INTO tblpost (sPost) SELECT 'Mechanical Draftsman' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Mechanical Draftsman');
INSERT INTO tblpost (sPost) SELECT 'Sales Asst. Female' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Sales Asst. Female');
INSERT INTO tblpost (sPost) SELECT 'Design Engg (Autocad , Solidwork , Creo)' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Design Engg (Autocad , Solidwork , Creo)');
INSERT INTO tblpost (sPost) SELECT 'Assistant manager R&D' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Assistant manager R&D');
INSERT INTO tblpost (sPost) SELECT 'Assistant Development ( Foundry Development )' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Assistant Development ( Foundry Development )');
INSERT INTO tblpost (sPost) SELECT 'Interior Designer Female' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Interior Designer Female');
INSERT INTO tblpost (sPost) SELECT 'Quality Head' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Quality Head');
INSERT INTO tblpost (sPost) SELECT 'Back Office Female' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Back Office Female');
INSERT INTO tblpost (sPost) SELECT 'Sales & Marketing Male' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Sales & Marketing Male');
INSERT INTO tblpost (sPost) SELECT 'Tele Callin (WFH)' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Tele Callin (WFH)');
INSERT INTO tblpost (sPost) SELECT 'Marketing Manager' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Marketing Manager');
INSERT INTO tblpost (sPost) SELECT 'Foundry Quality Head' WHERE NOT EXISTS (SELECT 1 FROM tblpost WHERE sPost = 'Foundry Quality Head');

-- 6) New recruiter login account (sPhone is unique, so this updates in place if it already exists)
INSERT INTO tbluser (sName, sEmail, sPhone, sRole, sPassword_hash, sIs_active) VALUES ('Vikas Sir', 'vikas@gmail.com', '8956440706', 'Recruiter', '$2y$10$xoNCew2SRXSsi3Vg5aCTreOEo0JZBIadQEEjw5sqcf2cWXGK6tWn.', 1)
ON DUPLICATE KEY UPDATE sName = VALUES(sName), sEmail = VALUES(sEmail), sRole = VALUES(sRole);
