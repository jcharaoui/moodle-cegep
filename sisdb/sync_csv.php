<?php

global $CFG, $USER;

require_once('../../../config.php');
require('../lib.php');

require_login();

if (!is_siteadmin($USER->id)) {
	print_error("Désolé, cette page n'est accessible qu'aux administrateurs du système.");
}

$trimestre  = optional_param('trimestre', null, PARAM_ALPHA);
$annee      = optional_param('annee', null, PARAM_INT);
$go         = optional_param('confirmation', null, PARAM_BOOL);

$strtitle = 'Synchroniser avec un fichier CSV';
print_header($strtitle,$strtitle,build_navigation(array(array('name'=>'Synchronisation','link'=>'','type'=>'misc'))));

// Verify if external database enrolment is enabled
if (!in_array('database',explode(',',$CFG->enrol_plugins_enabled)))
print_error('errorenroldbnotavailable','block_cegep');

// Prepare external enrolment database connection
if ($enroldb = enroldb_connect()) {
	$enroldb->Execute("SET NAMES 'utf8'");
}
else {
	error_log('[ENROL_DB] Could not make a connection');
	print_error('dbconnectionfailed','error');
}

// Prepare external SIS database connection
if ($sisdb = sisdb_connect()) {
	$sisdb->Execute("SET NAMES 'utf8'");
}
else {
	error_log('[SIS_DB] Could not make a connection');
	print_error('dbconnectionfailed','error');
}

$msg = '';

if (empty($_FILES) || empty($trimestre) || empty($annee)) {
	print_box('Mettre à jour la base de données intermédiaire du SIS avec un fichier CSV. Le fichier doit comporter les champs suivants :<br /><br />Service<br />Session (ex. Été)<br />Code du cours, format MEQ (ex. 101-801-MA)<br />Titre du cours<br />Numéro du groupe-cours (six chiffres)<br />Numéro de DA<br />Nom, Prénom<br />Programme', 'generalbox boxwidthnarrow boxaligncenter');
	$form = '<center><form enctype="multipart/form-data" action="sync_csv.php" method="post">Fichier : <input name="uploadedfile" type="file" /><br /><br />';
	$form .= 'Session : <select name="trimestre"><option value=""></option><option value="automne">Automne</option><option value="hiver">Hiver</option><option value="ete">Été</option> <input name="annee" type="text" size="4" maxlength="4" />';
	$form .= '<br /><br /><input type="submit" value="Envoyer" /></form></center>';
	print_box($form);
	print_footer();
}
else {

	// TODO: Backup de la base de données avant opérations
	// TODO: Upload en fichier .gz

	$file = file($_FILES['uploadedfile']['tmp_name']);

	$codes_etudiants = array();
	$codes_cours = array();
	$groupecours = array();
	$inscriptions = array();
	$inscriptions_db = array();

	$ins_session = 0;
	$ins_etudiant = 0;
	$upd_etudiant = 0;
	$ins_cours = 0;
	$upd_cours = 0;
	$ins_groupecours = 0;
	$ins_inscription = 0;
	$del_inscription = 0;
	$skipped = 0;

	$session = substr($trimestre,0,1) . $annee;

	foreach ($file as $line) {

		$record = explode(';',utf8_encode($line));
		$code_etudiant = 'e' . substr($record[6], 2, strlen($record[6]) - 2);
		$code_cours = substr(str_replace('-','',$record[3]), 0, 6);
		$groupecoursid = '';

		if (empty($record[5])) { $skipped++; continue; }

		// Mettre à jour les infos de session
		$record[1] = strtolower($record[1]);
		if ($record[1] != 'hiver' && $record[1] != 'automne') { $record[1] = 'ete'; }
		if ($trimestre != $record[1] || $annee != $record[2]) {
			echo "Erreur, une session trouvée dans le fichier ne correspond pas! ($record[1] $record[2])";
			break;
		}

		// Mettre à jour les infos des étudiants
		if (!in_array($code_etudiant, $codes_etudiants)) {
			$nom_etudiant = explode(',', $record[7]);
			$select = "SELECT * FROM `$CFG->sisdb_name`.`student` WHERE `username` = '$code_etudiant'";
			$resultat = $sisdb->Execute($select);
			if ($resultat && $resultat->RecordCount() == 0) {
				$insert = "INSERT INTO `$CFG->sisdb_name`.`student` (`username` , `lastname`, `firstname`, `program`) VALUES ('$code_etudiant', \"$nom_etudiant[0]\", \"$nom_etudiant[1]\", \"$record[8]\"); ";
				$resultat = $sisdb->Execute($insert);
				if (!$resultat) {
					trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
					echo "Sync error : student process";
					break;
				} else { $ins_etudiant++; }
			}
			elseif ($resultat && ($resultat->fields['lastname'] != $nom_etudiant[0] || $resultat->fields['firstname'] != $nom_etudiant[1] || $resultat->fields['program'] != $record[8])) {
				$update = "UPDATE `$CFG->sisdb_name`.`student` SET `lastname` = \"$nom_etudiant[0]\", `firstname` = \"$nom_etudiant[1]\", `program` = \"$record[8]\" WHERE `username` = '$code_etudiant'; ";
				$resultat = $sisdb->Execute($update);
				if (!$resultat) {
					trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
					echo "Sync error : student process";
					break;
				} else { $upd_etudiant++; }
			}
			array_push($codes_etudiants, $code_etudiant);
		}

		// Mettre à jour les informations de cours
		if (!in_array($code_cours, $codes_cours)) {
			$select = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = '$code_cours'";
			$resultat = $sisdb->Execute($select);
			if ($resultat && $resultat->RecordCount() == 0) {
				$insert = "INSERT INTO `$CFG->sisdb_name`.`course` (`coursecode` , `title`, `service`) VALUES ('$code_cours', \"$record[4]\", '$record[0]'); ";
				$resultat = $sisdb->Execute($insert);
				if (!$resultat) {
					trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
					echo "Sync error : course process";
					break;
				} else { $ins_cours++; }
			}
			elseif ($resultat && ($resultat->fields['title'] != $record[4] || $resultat->fields['service'] != $record[0]) ) {
				$update = "UPDATE `$CFG->sisdb_name`.`course` SET `title` = \"$record[4]\", `service` = \"$record[0]\" WHERE `coursecode` = '$code_cours'; ";
				$resultat = $sisdb->Execute($update);
				if (!$resultat) {
					trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
					echo "Sync error : student process";
					break;
				} else { $upd_cours++; }
			}
			array_push($codes_cours, $code_cours);
		}

		// Mettre à jour les infos de groupecours
		foreach ($groupecours as $gc) {
			if ($gc['coursecode'] == $code_cours && $gc['group'] == $record[5] && $gc['semester'] == $session) {
				$groupecoursid = $gc['id'];
			}
		}
		if (empty($groupecoursid)) {
			$select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `coursecode` = '$code_cours' AND `group` = '$record[5]' AND `semester` = '$session'";
			$resultat = $sisdb->Execute($select);
			if ($resultat && $resultat->RecordCount() == 0) {
				$insert = "INSERT INTO `coursegroup` (`coursecode`, `group`, `semester`) VALUES ('$code_cours', '$record[5]', '$session'); ";
				$resultat = $sisdb->Execute($insert);
				if (!$resultat) {
					trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
					print($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
					break;
				} else { $groupecoursid = $sisdb->Insert_ID(); $ins_groupecours++; }
			} else { $groupecoursid = $resultat->fields['id']; }
			array_push($groupecours, array('coursecode' => $code_cours, 'group' => $record[5], 'semester' => $session, 'id' => $groupecoursid));
		}

		array_push($inscriptions, serialize(array($groupecoursid, $code_etudiant)));
	}

	// Mettre à jour les inscriptions
	$inscriptions_db = array();
	$select = "SELECT * FROM `$CFG->sisdb_name`.`student_enrolment` WHERE `coursegroup_id` IN (SELECT id FROM `$CFG->sisdb_name`.`coursegroup` WHERE `semester` = '$session')";

	$resultat = $sisdb->Execute($select);
	while ($resultat && !$resultat->EOF) {
		array_push($inscriptions_db, serialize(array($resultat->fields['coursegroup_id'], $resultat->fields['username'])));
		$resultat->MoveNext();
	}

	$ajouts = array_diff($inscriptions, $inscriptions_db);
	$retraits = array_diff($inscriptions_db, $inscriptions);

	foreach ($ajouts as $inscription) {
		$inscription = unserialize($inscription);
		$insert = "INSERT INTO `$CFG->sisdb_name`.`student_enrolment` (`coursegroup_id` , `username`) VALUES ('$inscription[0]', '$inscription[1]'); ";
		if (!$resultat = $sisdb->Execute($insert)) {
			trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
			echo "Erreur : inscription process";
			break;
		}
		$enrolments_rs = get_recordset_sql("SELECT DISTINCT `courseidnumber` FROM `$CFG->enrol_dbname`.`enrolments` WHERE `coursegroup_id` = '$inscription[0]'");
		while ($enrolment = rs_fetch_next_record($enrolments_rs)) {
			$insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` (`$CFG->enrol_remotecoursefield` , `username`) VALUES ('$enrolment->idnumber', '$inscription[1]'); ";
			if (!$resultat = $enroldb->Execute($insert)) {
				trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $insert);
				echo "Erreur : inscription process";
				break;
			}
			$ins_inscription++;
		}
	}
	foreach ($retraits as $inscription) {
		$inscription = unserialize($inscription);
		$delete = "DELETE FROM `$CFG->sisdb_name`.`student_enrolment` WHERE (`coursegroup_id` = '$inscription[0]' AND `username` = '$inscription[1]'); ";
		if (!$resultat = $sisdb->Execute($delete)) {
			trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
			echo "Erreur : inscription process";
			break;
		}
		$enrolments_rs = get_recordset_sql("SELECT DISTINCT `courseidnumber` FROM `$CFG->enrol_dbname`.`enrolments` WHERE `coursegroup_id` = '$inscription[0]'");
		while ($enrolment = rs_fetch_next_record($enrolments_rs)) {
			$delete = "DELETE FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$enrolment->idnumber' AND `username` = '$inscription[1]'";
			if (!$resultat = $enroldb->Execute($delete)) {
				trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
				echo "Erreur : inscription process";
				break;
			}
		}
		$del_inscription++;
	}

	$enroldb->Close();
	$sisdb->Close();

	$msg .= "<strong><u>Opération complétée</u></strong><br /><br />";
	$msg .= "<strong>Session</strong> : $session<br /><br />";
	$msg .= "<strong>Étudiants</strong> : $ins_etudiant ajouts; $upd_etudiant maj; " . count($codes_etudiants) . " total<br /><br />";
	$msg .= "<strong>Cours</strong> : $ins_cours ajouts; $upd_cours maj; " . count($codes_cours) . " total<br /><br />";
	$msg .= "<strong>Groupecours</strong> : $ins_groupecours ajouts; " . count($groupecours) . " total<br /><br />";
	$msg .= "<strong>Inscriptions</strong> : $ins_inscription ajouts; $del_inscription ret; $skipped skp " . count($inscriptions) . " total<br /><br />";

	notice($msg,$CFG->wwwroot);
	print_footer();
}

exit(0);

?>