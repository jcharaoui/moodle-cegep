-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb5+lenny4
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Jeu 27 Mai 2010 à 10:49
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
  `service` tinyint(1) default NULL,
  PRIMARY KEY  (`coursecode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `coursegroup`
--

CREATE TABLE `coursegroup` (
  `id` bigint(10) unsigned NOT NULL auto_increment,
  `coursecode` varchar(8) NOT NULL,
  `group` varchar(6) NOT NULL,
  `semester` varchar(5) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `coursecode` (`coursecode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `program`
--

CREATE TABLE `program` (
  `id` varchar(5) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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

-- --------------------------------------------------------

--
-- Structure de la table `student_enrolment`
--

CREATE TABLE `student_enrolment` (
  `username` varchar(32) NOT NULL,
  `coursegroup_id` bigint(10) unsigned NOT NULL,
  KEY `code` (`username`,`coursegroup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_enrolment`
--

CREATE TABLE `teacher_enrolment` (
  `idnumber` varchar(255) NOT NULL,
  `coursegroup_id` bigint(10) unsigned NOT NULL,
  PRIMARY KEY  (`idnumber`,`coursegroup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

