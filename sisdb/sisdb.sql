-- phpMyAdmin SQL Dump
-- version 3.1.2deb1
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Ven 29 Mai 2009 à 18:04
-- Version du serveur: 5.0.75
-- Version de PHP: 5.2.6-3ubuntu4.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Structure de la table `course`
--

CREATE TABLE IF NOT EXISTS `course` (
  `coursecode` varchar(32) collate utf8_unicode_ci NOT NULL,
  `title` varchar(254) collate utf8_unicode_ci NOT NULL,
  `service` tinyint(1) default NULL,
  PRIMARY KEY  (`coursecode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `coursegroup`
--

CREATE TABLE IF NOT EXISTS `coursegroup` (
  `id` bigint(10) unsigned NOT NULL auto_increment,
  `coursecode` varchar(8) collate utf8_unicode_ci NOT NULL,
  `group` varchar(6) collate utf8_unicode_ci NOT NULL,
  `semester` varchar(5) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `code` (`coursecode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student`
--

CREATE TABLE IF NOT EXISTS `student` (
  `username` varchar(32) collate utf8_unicode_ci NOT NULL,
  `firstname` varchar(64) collate utf8_unicode_ci default NULL,
  `lastname` varchar(64) collate utf8_unicode_ci default NULL,
  `program` varchar(8) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `student_enrolment`
--

CREATE TABLE IF NOT EXISTS `student_enrolment` (
  `username` varchar(32) collate utf8_unicode_ci NOT NULL,
  `coursegroup_id` bigint(10) unsigned NOT NULL,
  KEY `code` (`username`,`coursegroup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

