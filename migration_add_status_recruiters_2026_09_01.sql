-- Adds the updated Requirement Status list and new Recruiters.
-- Idempotent: safe to run multiple times / on both local and live DB.

-- ================= Requirement statuses =================
INSERT INTO tblstatus (sStatus) SELECT 'Profile Sent' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Profile Sent');
INSERT INTO tblstatus (sStatus) SELECT 'Int. Arranged' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Int. Arranged');
INSERT INTO tblstatus (sStatus) SELECT 'Int Result ?' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Int Result ?');
INSERT INTO tblstatus (sStatus) SELECT 'Offer Sent' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Offer Sent');
INSERT INTO tblstatus (sStatus) SELECT 'Job Left' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Job Left');
INSERT INTO tblstatus (sStatus) SELECT 'Hold by OJ' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Hold by OJ');
INSERT INTO tblstatus (sStatus) SELECT 'Hold By Company' WHERE NOT EXISTS (SELECT 1 FROM tblstatus WHERE sStatus = 'Hold By Company');

-- ================= Recruiters =================
INSERT INTO tblrecruiter (sRecruiter) SELECT 'PD' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'PD');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Rohit' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Rohit');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Suvarna' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Suvarna');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Saloni Udgire' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Saloni Udgire');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Anjali Hande' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Anjali Hande');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Sanika Chatre' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Sanika Chatre');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Shruti Lole' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Shruti Lole');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Sukanya Sonar' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Sukanya Sonar');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Revati' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Revati');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Aditi' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Aditi');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Rajlaxmi R' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Rajlaxmi R');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'Pranoti' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'Pranoti');
INSERT INTO tblrecruiter (sRecruiter) SELECT 'P Khamkar' WHERE NOT EXISTS (SELECT 1 FROM tblrecruiter WHERE sRecruiter = 'P Khamkar');
