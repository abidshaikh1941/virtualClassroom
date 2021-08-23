/*SQL  (Mysql | Mariab syntax)*/

Create database vclassroom;
use vclassroom;


CREATE TABLE student (
  susername varchar(32) NOT NULL,
  password varchar(32) NOT NULL,
PRIMARY KEY(susername)
) ;


CREATE TABLE tutor(
  tusername varchar(32) NOT NULL,
  password varchar(32) NOT NULL,
PRIMARY KEY(tusername)
) ;


CREATE TABLE assignment (
  assignmentid int AUTO_INCREMENT,
  tusername varchar(32) NOT NULL,
  description text NOT NULL,
  publishedat DATE NOT NULL,
  deadline DATE NOT NULL,
  PRIMARY KEY(assignmentid)

) ;



ALTER TABLE assignment
ADD FOREIGN KEY (tusername) REFERENCES tutor(tusername) ON UPDATE CASCADE ON DELETE CASCADE;


CREATE TABLE tasks( assignmentid int NOT NULL, `susername` varchar(32) NOT NULL, remarks text , status char(10) NOT NULL DEFAULT 'PENDING', PRIMARY KEY(assignmentid,susername) );

ALTER TABLE tasks
ADD FOREIGN KEY (assignmentid) REFERENCES assignment(assignmentid) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE tasks
ADD FOREIGN KEY (susername) REFERENCES student(susername) ON UPDATE CASCADE ON DELETE CASCADE;
