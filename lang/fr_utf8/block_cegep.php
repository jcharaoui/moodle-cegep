<?php

// Block menu
$string['admincegep'] = 'Admin CEGEP';
$string['enrolment'] = 'Inscriptions';
$tring['delete'] = 'Détruire';

// Tabs
$string['studentlist'] = 'Liste d\'étudiants';
$string['enrol'] = 'Inscrire groupe-cours';
$string['unenrol'] = 'Désinscrire groupe-cours';

// Common
$string['summer'] = 'Été';
$string['autumn'] = 'Automne';
$string['winter'] = 'Hiver';
$string['year'] = 'Année';
$string['semester'] = 'Session';
$string['coursegroup'] = 'Groupe-cours';

// Student list page
$string['studentlisttitle'] = 'Liste d\'étudiants inscrits dans ce cours';
$string['childcoursetitle'] = 'Titre du cours descendant';
$string['coursecode'] = 'Code du cours';
$string['coursegroupnumber'] = 'Numéro du groupe-cours';
$string['program'] = 'Programme';
$string['nocoursegroupsenrolled'] = 'Aucun groupe-cours n\'est inscrit dans ce cours';
$string['accessuserprofile'] = 'Accéder au profil de l\'utilisateur';

// Enrol form
$string['coursegroupenrolled'] = '<strong>Inscription complétée avec succès.</strong><br /><br />Voici les étudiants ajoutés au cours :<br /><br />$a[0]<br />Bonne session!<br /><br />';
$string['enrolanother'] = 'Inscrire un autre groupe-cours';

// Unenrol form
$string['students'] = 'étudiants';
$string['unenrolbutton'] = 'Désinscrire';
$string['coursegroupunenrolled'] = '<strong>Désinscription complétée avec succès.</strong><br /><br />$a[0] étudiants enlevés du cours.<br /><br />';

// Validation
$string['specifyyear'] = 'Veuillez spécifier l\'année.';
$string['specifysemester'] = 'Veuillez spécifier la session.';
$string['specifycoursegroup'] = 'Veuillez spécifier le groupe-cours.';
$string['semesterunavailable'] = 'La session spécifiée n\'est pas disponible dans le système.';
$string['coursegroupsixnumbersonly'] = 'Le numéro du groupe-cours doit comporter six chiffres.';
$string['coursegroupalreadyenrolled'] = 'Le groupe-cours spécifié est déjà inscrit à ce cours.';
$string['coursegroupnotenrolled'] = 'Le groupe-cours spécifié n\'est pas inscrit à ce cours.';
$string['coursegroupunavailable'] = 'Le groupe-cours spécifié n\'est pas disponible dans le système.';
$string['coursegroupinvalid'] = 'Le groupe-cours spécifié est invalide.';

// Settings
$string['studentrole'] = 'Rôle étudiant';
$string['studentrole_help'] = 'Rôle à assigner aux étudiants dans la base de données externe.';
$string['sisdb_heading'] = 'Base de données du SIS';
$string['sisdb_help'] = 'Informations d\'accès pour la base de données intermédiaire du système d\'information scolaire de l\'institution.';
$string['sisdb_type'] = 'Type de BD';
$string['sisdb_host'] = 'Hôte';
$string['sisdb_name'] = 'Nom de la BD';
$string['sisdb_user'] = 'Nom d\'utilisateur';
$string['sisdb_pass'] = 'Mot de passe';
$string['sisdb_sync_csv'] = 'Synchroniser la BD avec un fichier CSV';

// Errors
$string['errorenroldbnotavailable'] = 'Le module d\'inscription base de données externe doit être activé pour le fonctionnement de ce module.';
$string['erroractionnotavailable'] = 'Cette action n\'est pas disponible pour ce cours.';
$string['errormustbeteacher'] = 'Cette action est disponible uniquement pour les enseignants de ce cours.';
$string['errorimportingstudentlist'] = 'Une erreur s\'est produite lors de l\'importation de la liste d\'étudiants!';
$string['errordeletingenrolment'] = 'Une erreur s\'est produite lors de la suppression des inscriptions au cours!';

?>