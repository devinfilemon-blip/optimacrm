-- Optima Recruitment CRM — schema
CREATE DATABASE IF NOT EXISTS dbcrm_optima CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dbcrm_optima;

-- ================= Users / Auth =================
CREATE TABLE tbluser (
  iUserid INT AUTO_INCREMENT PRIMARY KEY,
  sUserCode VARCHAR(50),
  sName VARCHAR(100) NOT NULL,
  sEmail VARCHAR(100),
  sPhone VARCHAR(15) NOT NULL UNIQUE,
  sRole VARCHAR(30) NOT NULL DEFAULT 'Recruiter',
  sPassword_hash VARCHAR(255) NOT NULL,
  sIs_active TINYINT(1) NOT NULL DEFAULT 1,
  sCreatedTimeStamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sModifiedTimeStamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tbltoken (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  sToken VARCHAR(64) NOT NULL,
  sExpire DATETIME NOT NULL,
  INDEX (sToken)
) ENGINE=InnoDB;

-- ================= Masters =================
CREATE TABLE tblstatus (
  iStatusId INT AUTO_INCREMENT PRIMARY KEY,
  sStatus VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tblsource (
  iSourceId INT AUTO_INCREMENT PRIMARY KEY,
  sSource VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tblrecruiter (
  iRecruiterId INT AUTO_INCREMENT PRIMARY KEY,
  sRecruiter VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tblpost (
  iPostId INT AUTO_INCREMENT PRIMARY KEY,
  sPost VARCHAR(200) NOT NULL
) ENGINE=InnoDB;

-- ================= Daily snapshot of the Requirement Status Breakdown widget =================
CREATE TABLE tblstatussnapshot (
  dSnapshotDate DATE NOT NULL,
  sLabel VARCHAR(50) NOT NULL,
  iCount INT NOT NULL DEFAULT 0,
  PRIMARY KEY (dSnapshotDate, sLabel)
) ENGINE=InnoDB;

-- ================= Companies (clients who hire) =================
CREATE TABLE tblcompany (
  iCompanyId INT AUTO_INCREMENT PRIMARY KEY,
  sCompanyName VARCHAR(200) NOT NULL,
  sContactPerson VARCHAR(150),
  sPhone VARCHAR(20),
  sEmail VARCHAR(150),
  sIndustry VARCHAR(150),
  sLocation VARCHAR(200),
  sAddress TEXT,
  sGstin VARCHAR(20),
  sStatus ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  sNotes TEXT,
  iCreatedBy INT,
  dCreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  dUpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================= Requirements (job openings from client companies) =================
CREATE TABLE tblrequirement (
  iReqId INT AUTO_INCREMENT PRIMARY KEY,
  sReqNo VARCHAR(30) UNIQUE,
  iCompanyId INT,
  sPost VARCHAR(200) NOT NULL,
  iNoOfVacancy INT NOT NULL DEFAULT 1,
  sType ENUM('T','NT') NOT NULL DEFAULT 'NT',
  sLocation VARCHAR(200),
  sEducation VARCHAR(255),
  sExperience VARCHAR(100),
  sSalary VARCHAR(100),
  dOpenDate DATE,
  dFollowupDate DATE,
  sRank VARCHAR(10),
  sStatus VARCHAR(100) NOT NULL DEFAULT 'Searching',
  sAssignedCode VARCHAR(20),
  sFollowupBy VARCHAR(100),
  sRecruiter VARCHAR(100),
  sRemark TEXT,
  sCompanyPhone VARCHAR(20),
  sCompanyEmail VARCHAR(150),
  iCreatedBy INT,
  dCreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  dUpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (iCompanyId),
  INDEX (sStatus),
  CONSTRAINT fk_req_company FOREIGN KEY (iCompanyId) REFERENCES tblcompany(iCompanyId) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ================= Placements (candidates placed, with invoicing) =================
CREATE TABLE tblplacement (
  iPlacementId INT AUTO_INCREMENT PRIMARY KEY,
  sSelectionNo VARCHAR(30) UNIQUE,
  iReqId INT,
  sExternalReqNo VARCHAR(30),
  sType ENUM('T','NT') NOT NULL DEFAULT 'NT',
  sCandidateName VARCHAR(200) NOT NULL,
  sMobile VARCHAR(20),
  sPost VARCHAR(200),
  iCompanyId INT,
  dSalary DECIMAL(12,2) DEFAULT 0,
  dJoiningDate DATE,
  sJoiningStatus VARCHAR(30) NOT NULL DEFAULT 'Pending',
  sWorkedBy VARCHAR(100),
  sSource VARCHAR(100),
  sRemark TEXT,
  dInvoiceDate DATE,
  sInvoiceNo VARCHAR(50),
  dCharges DECIMAL(12,2) DEFAULT 0,
  dCgst DECIMAL(12,2) DEFAULT 0,
  dSgst DECIMAL(12,2) DEFAULT 0,
  dTotalGst DECIMAL(12,2) DEFAULT 0,
  dAmount DECIMAL(12,2) DEFAULT 0,
  dRecAmount DECIMAL(12,2) DEFAULT 0,
  dPaymentRecDate DATE,
  sPaymentMode VARCHAR(50),
  dTds DECIMAL(12,2) DEFAULT 0,
  dIpcInvDate DATE,
  sIpcInvNo VARCHAR(50),
  dIpcInvAmt DECIMAL(12,2) DEFAULT 0,
  dPaymentDate DATE,
  sPaymentDetails VARCHAR(200),
  sRef1 VARCHAR(150),
  sRef2 VARCHAR(150),
  iCreatedBy INT,
  dCreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  dUpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (iCompanyId),
  INDEX (iReqId),
  CONSTRAINT fk_place_req FOREIGN KEY (iReqId) REFERENCES tblrequirement(iReqId) ON DELETE SET NULL,
  CONSTRAINT fk_place_company FOREIGN KEY (iCompanyId) REFERENCES tblcompany(iCompanyId) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ================= Reminders / Follow-ups =================
CREATE TABLE tblreminders (
  rrid INT AUTO_INCREMENT PRIMARY KEY,
  iUserid INT,
  sDescription VARCHAR(255) NOT NULL,
  sDate DATE NOT NULL,
  iReqId INT,
  sAssignedBy VARCHAR(100),
  sStatus ENUM('Pending','Done') NOT NULL DEFAULT 'Pending',
  dCreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================= Seed: masters =================
INSERT INTO tblstatus (sStatus) VALUES
 ('Searching'), ('Refine Search'), ('Closed by Co.'), ('Hold'),
 ('Profiles not found'), ('Interview Scheduled'), ('Offer Made'), ('Selected'),
 ('Joined'), ('Not Joining');

INSERT INTO tblsource (sSource) VALUES
 ('Social Media'), ('Naukri'), ('Referral'), ('WhatsApp'), ('Walk-in'), ('Website'), ('LinkedIn');

-- Default Admin login: phone 9999999999 / password Optima@123
INSERT INTO tbluser (sName, sEmail, sPhone, sRole, sPassword_hash, sIs_active) VALUES
 ('Admin', 'admin@optima.local', '9999999999', 'Admin', '$2y$10$Zbpzi/a5.b.EZ8uPqau0v.BuJ9vvq9P/JFpUXGQbfr2Iqb4/WBwd2', 1);
