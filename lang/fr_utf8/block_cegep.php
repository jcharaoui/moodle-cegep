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
$string['number'] = 'Nombre';
$string['comments'] = "Commentaires";

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

// Enrolment validation
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

// Course request
$string['courserequest'] = 'Demande de cours';
$string['courserequest_instructions'] = "Utilisez ce formulaire pour effectuer une demande de création de cours dans Moodle.<br /><br />Pour le code de cours, entrez seulement le code programme-cours de trois chiffres er trois lettres, sans le groupe.<br />Exemple : ABC123<br /><br />Ensuite choisissez le nombre de cours Moodle voulus pour le même code de cours. Un cours dans Moodle peut être associé à plusieurs groupe-cours.<br /><br />Enfin, vous pouvez utiliser la boîte de commentaires afin de communiquer avec l'administrateur de Moodle.";
$string['courserequest_username'] = 'Demandeur';
$string['courserequest_success'] = 'La demande de cours a été enregistrée avec succès.';
$string['courserequest_failed'] = 'Erreur : La demande de cours n\'a pas été enregistrée. Veuillez contacter l\'administrateur.';
$string['courserequest_nothing'] = 'Aucune demande à afficher.';
$string['courserequest_nonew'] = 'Il n\'y a aucune nouvelle demande.';
$string['courserequest_nowaiting'] = 'Il n\'y a aucune demande en attente.';
$string['courserequest_by'] = 'Demandé par';
$string['courserequest_since'] = 'Depuis';
$string['courserequest_courses'] = 'Cours demandés';
$string['courserequest_comments'] = 'Commentaires';
$string['courserequest_state'] = 'État / Action';
$string['courserequest_new'] = 'Nouvelles demandes';
$string['courserequest_waiting'] = 'Demandes en attente';
$string['courserequest_statenew'] = 'nouveau';
$string['courserequest_statewaiting'] = 'en attente';
$string['courserequest_stateaccepted'] = 'accepté';
$string['courserequest_statedenied'] = 'refusé';
$string['courserequest_statedelete'] = 'supprimer';
$string['courserequest_statemodify'] = 'modifier';
$string['courserequest_modsuccess'] = 'La demande a été modifiée avec succès.';
$string['courserequest_delsuccess'] = 'La demande a été supprimée avec succès.';
$string['courserequest_modfailed'] = 'Erreur : La demande n\'a pu être modifiée!';
$string['courserequest_createsuccess'] = 'Les cours spécifiés dans la demande ont été créés avec succès.';
$string['courserequest_createfailed'] = 'Erreur : Les cours spécifiés dans la demande n\'ont pas pu être créés!';
$string['courserequest_exists'] = "Une demande à votre nom pour ce code de cours a déjà été reçue.";
$string['courserequest_duplicate'] = "Chaque code de cours doit être unique.";
$string['invalidcoursecode'] = 'Code de cours invalide.';
$string['atleastonecoursecode'] = "Au moins un code de cours doit être entré.";
$string['specifycoursenumber'] = "Veuillez spécifier le code du cours.";
$string['specifycoursenumber'] = "Veuillez spécifier le nombre de cours.";

// Errors
$string['errorenroldbnotavailable'] = 'Le module d\'inscription base de données externe doit être activé pour le fonctionnement de ce module.';
$string['erroractionnotavailable'] = 'Cette action n\'est pas disponible pour ce cours.';
$string['errormustbeteacher'] = 'Cette page est disponible uniquement pour les enseignants.';
$string['errormustbeadmin'] = 'Cette page est disponible uniquement pour les administraeurs du système.';
$string['errorimportingstudentlist'] = 'Une erreur s\'est produite lors de l\'importation de la liste d\'étudiants!';
$string['errordeletingenrolment'] = 'Une erreur s\'est produite lors de la suppression des inscriptions au cours!';

?>