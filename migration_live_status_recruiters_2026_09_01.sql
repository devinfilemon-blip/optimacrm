-- Run on LIVE only where tblstatus / tblrecruiter are empty.
-- Plain inserts (no dedupe checks) since the tables have no rows yet.

INSERT INTO tblstatus (sStatus) VALUES
 ('Searching'),
 ('Refine Search'),
 ('Profile Sent'),
 ('Int. Arranged'),
 ('Int Result ?'),
 ('Offer Sent'),
 ('Joined'),
 ('Not Joining'),
 ('Closed by Co.'),
 ('Job Left'),
 ('Hold by OJ'),
 ('Hold By Company');

INSERT INTO tblrecruiter (sRecruiter) VALUES
 ('AB'),
 ('PD'),
 ('Mrunal A'),
 ('Dhanshree'),
 ('Savita'),
 ('Rohit'),
 ('Suvarna'),
 ('Rushi Da'),
 ('Swati D'),
 ('Poonam K'),
 ('Sonal Khavat'),
 ('Saloni Udgire'),
 ('Rashmi Salunkhe'),
 ('Pooja A'),
 ('M Bhandare'),
 ('Anjali Hande'),
 ('Sanika Chatre'),
 ('Shruti Lole'),
 ('Sukanya Sonar'),
 ('Revati'),
 ('Aditi'),
 ('Rajlaxmi R'),
 ('Pranoti'),
 ('P Khamkar');
