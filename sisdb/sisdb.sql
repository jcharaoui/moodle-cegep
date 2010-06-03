-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb5+lenny4
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Mer 02 Juin 2010 à 15:38
-- Version du serveur: 5.0.51
-- Version de PHP: 5.2.6-1+lenny8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Base de données: `moodle-sis`
--

-- --------------------------------------------------------

--
-- Structure de la table `course`
--

CREATE TABLE `course` (
  `coursecode` varchar(32) NOT NULL,
  `title` varchar(254) NOT NULL,
  `unit` tinyint(1) default NULL,
  PRIMARY KEY  (`coursecode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déclencheurs `course`
--
DROP TRIGGER IF EXISTS `moodle-sis`.`ai_course`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ai_course` AFTER INSERT ON `moodle-sis`.`course`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO
        log_course (`timestamp`,`action`,`coursecode`,`title_old`,`unit_old`,`title_new`,`unit_new`)
    VALUES
        (@TIMESTAMP, 'insert', NEW.coursecode, NULL, NULL, NEW.title, NEW.unit);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`au_course`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`au_course` AFTER UPDATE ON `moodle-sis`.`course`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO
        log_course (`timestamp`,`action`,`coursecode`,`title_old`,`unit_old`,`title_new`,`unit_new`)
    VALUES
        (@TIMESTAMP, 'update', NEW.coursecode, OLD.title, OLD.unit, NEW.title, NEW.unit);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`ad_course`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ad_course` AFTER DELETE ON `moodle-sis`.`course`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO
        log_course (`timestamp`,`action`,`coursecode`,`title_old`,`unit_old`,`title_new`,`unit_new`)
    VALUES
        (@TIMESTAMP, 'delete', OLD.coursecode, OLD.title, OLD.unit, NULL, NULL);
END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `coursegroup`
--

CREATE TABLE `coursegroup` (
  `id` bigint(10) unsigned NOT NULL auto_increment,
  `coursecode` varchar(8) NOT NULL,
  `group` varchar(6) NOT NULL,
  `term` smallint(5) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `coursecode` (`coursecode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Déclencheurs `coursegroup`
--
DROP TRIGGER IF EXISTS `moodle-sis`.`ai_coursegroup`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ai_coursegroup` AFTER INSERT ON `moodle-sis`.`coursegroup`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_coursegroup
        (`timestamp`,`action`,`id`,`coursecode`,`group`,`term`)
    VALUES
        (@TIMESTAMP, 'insert', NEW.id, NEW.coursecode, NEW.group, NEW.term);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`ad_coursegroup`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ad_coursegroup` AFTER DELETE ON `moodle-sis`.`coursegroup`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_coursegroup
        (`timestamp`,`action`,`id`,`coursecode`,`group`,`term`)
    VALUES
        (@TIMESTAMP, 'delete', OLD.id, OLD.coursecode, OLD.group, OLD.term);
END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `log_course`
--

CREATE TABLE `log_course` (
  `timestamp` datetime NOT NULL,
  `action` enum('insert','update','delete') NOT NULL,
  `coursecode` varchar(32) NOT NULL,
  `title_old` varchar(254) default NULL,
  `unit_old` tinyint(1) unsigned default NULL,
  `title_new` varchar(254) default NULL,
  `unit_new` tinyint(1) unsigned default NULL,
  KEY `transactid` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `log_coursegroup`
--

CREATE TABLE `log_coursegroup` (
  `timestamp` datetime NOT NULL,
  `action` enum('insert','delete') NOT NULL,
  `id` bigint(10) unsigned NOT NULL,
  `coursecode` varchar(8) NOT NULL,
  `group` varchar(6) NOT NULL,
  `term` varchar(5) NOT NULL,
  KEY `transactid` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `log_program`
--

CREATE TABLE `log_program` (
  `timestamp` datetime NOT NULL,
  `action` enum('insert','update','delete') NOT NULL,
  `id` varchar(5) NOT NULL,
  `title_old` varchar(255) default NULL,
  `title_new` varchar(255) default NULL,
  KEY `transactid` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `log_student`
--

CREATE TABLE `log_student` (
  `timestamp` datetime NOT NULL,
  `action` enum('insert','update','delete') NOT NULL,
  `username` varchar(32) NOT NULL,
  `firstname_old` varchar(64) default NULL,
  `lastname_old` varchar(64) default NULL,
  `program_id_old` varchar(8) default NULL,
  `program_year_old` tinyint(1) unsigned default NULL,
  `firstname_new` varchar(64) default NULL,
  `lastname_new` varchar(64) default NULL,
  `program_id_new` varchar(8) default NULL,
  `program_year_new` tinyint(1) unsigned default NULL,
  KEY `transactid` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `log_student_enrolment`
--

CREATE TABLE `log_student_enrolment` (
  `timestamp` datetime NOT NULL,
  `action` enum('insert','delete') NOT NULL,
  `username` varchar(32) NOT NULL,
  `coursegroup_id` bigint(10) unsigned NOT NULL,
  KEY `transactid` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `log_teacher_enrolment`
--

CREATE TABLE `log_teacher_enrolment` (
  `timestamp` datetime NOT NULL,
  `action` enum('insert','delete') NOT NULL,
  `idnumber` varchar(255) NOT NULL,
  `coursegroup_id` bigint(10) unsigned NOT NULL,
  KEY `transactid` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `program`
--

CREATE TABLE `program` (
  `id` varchar(5) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déclencheurs `program`
--
DROP TRIGGER IF EXISTS `moodle-sis`.`ai_program`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ai_program` AFTER INSERT ON `moodle-sis`.`program`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_program
        (`timestamp`,`action`,`id`,`title_old`,`title_new`)
    VALUES
        (@TIMESTAMP, 'insert', NEW.id, NULL, NEW.title);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`au_program`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`au_program` AFTER UPDATE ON `moodle-sis`.`program`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_program
        (`timestamp`,`action`,`id`,`title_old`,`title_new`)
    VALUES
        (@TIMESTAMP, 'update', NEW.id, OLD.title, NEW.title);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`ad_program`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ad_program` AFTER DELETE ON `moodle-sis`.`program`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_program
        (`timestamp`,`action`,`id`,`title_oldf`,`title_new`)
    VALUES
        (@TIMESTAMP, 'delete', OLD.id, OLD.title, NULL);
END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `student`
--

CREATE TABLE `student` (
  `username` varchar(32) NOT NULL,
  `firstname` varchar(64) default NULL,
  `lastname` varchar(64) default NULL,
  `program_id` varchar(8) default NULL,
  `program_year` tinyint(1) unsigned default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déclencheurs `student`
--
DROP TRIGGER IF EXISTS `moodle-sis`.`ai_student`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ai_student` AFTER INSERT ON `moodle-sis`.`student`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_student
        (`timestamp`,`action`,`username`,`firstname_old`,`lastname_old`,`program_id_old`,`program_year_old`,`firstname_new`,`lastname_new`,`program_id_new`,`program_year_new`)
    VALUES
        (@TIMESTAMP, 'insert', NEW.username, NULL, NULL, NULL, NULL, NEW.firstname, NEW.lastname, NEW.program_id, NEW.program_year);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`au_student`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`au_student` AFTER UPDATE ON `moodle-sis`.`student`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_student
        (`timestamp`,`action`,`username`,`firstname_old`,`lastname_old`,`program_id_old`,`program_year_old`,`firstname_new`,`lastname_new`,`program_id_new`,`program_year_new`)
    VALUES
        (@TIMESTAMP, 'update', NEW.username, OLD.firstname, OLD.lastname, OLD.program_id, OLD.program_year, NEW.firstname, NEW.lastname, NEW.program_id, NEW.program_year);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`ad_student`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ad_student` AFTER DELETE ON `moodle-sis`.`student`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_student
        (`timestamp`,`action`,`username`,`firstname_old`,`lastname_old`,`program_id_old`,`program_year_old`,`firstname_new`,`lastname_new`,`program_id_new`,`program_year_new`)
    VALUES
        (@TIMESTAMP, 'delete', OLD.username, OLD.firstname, OLD.lastname, OLD.program_id, OLD.program_year, NULL, NULL, NULL, NULL);
END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `student_enrolment`
--

CREATE TABLE `student_enrolment` (
  `username` varchar(32) NOT NULL,
  `coursegroup_id` bigint(10) unsigned NOT NULL,
  KEY `code` (`username`,`coursegroup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déclencheurs `student_enrolment`
--
DROP TRIGGER IF EXISTS `moodle-sis`.`ai_student_enrolment`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ai_student_enrolment` AFTER INSERT ON `moodle-sis`.`student_enrolment`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_student_enrolment
        (`timestamp`,`action`,`username`,`coursegroup_id`)
    VALUES
        (@TIMESTAMP, 'insert', NEW.username, NEW.coursegroup_id);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`ad_student_enrolment`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ad_student_enrolment` AFTER DELETE ON `moodle-sis`.`student_enrolment`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_student_enrolment
        (`timestamp`,`action`,`username`,`coursegroup_id`)
    VALUES
        (@TIMESTAMP, 'delete', OLD.username, OLD.coursegroup_id);
END IF;
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_enrolment`
--

CREATE TABLE `teacher_enrolment` (
  `idnumber` varchar(255) NOT NULL,
  `coursegroup_id` bigint(10) unsigned NOT NULL,
  PRIMARY KEY  (`idnumber`,`coursegroup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déclencheurs `teacher_enrolment`
--
DROP TRIGGER IF EXISTS `moodle-sis`.`ai_teacher_enrolment`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ai_teacher_enrolment` AFTER INSERT ON `moodle-sis`.`teacher_enrolment`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_teacher_enrolment
        (`timestamp`,`action`,`idnumber`,`coursegroup_id`)
    VALUES
        (@TIMESTAMP, 'insert', NEW.idnumber, NEW.coursegroup_id);
END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `moodle-sis`.`ad_teacher_enrolment`;
DELIMITER //
CREATE TRIGGER `moodle-sis`.`ad_teacher_enrolment` AFTER DELETE ON `moodle-sis`.`teacher_enrolment`
 FOR EACH ROW BEGIN
IF (@LOGGING = 1) THEN
    INSERT INTO log_teacher_enrolment
        (`timestamp`,`action`,`idnumber`,`coursegroup_id`)
    VALUES
        (@TIMESTAMP, 'delete', OLD.idnumber, OLD.coursegroup_id);
END IF;
END
//
DELIMITER ;

