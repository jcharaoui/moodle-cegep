<?php

$start_time = (float) array_sum(explode(' ',microtime())); 

// as seen in /auth/ldap/auth_ldap_sync_users.php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot .'/blocks/cegep/lib.php');

$hash = optional_param('hash', null, PARAM_ALPHANUM);
$in_cron = false;

if ($hash == "Aith5xhOow5iuduaez3ahs5Eera") {
    $start_trimester = cegep_local_current_trimester();
    $in_cron = true;
} else {
    global $CFG, $USER;
    require_login();

    if (!is_siteadmin($USER->id)) {
        print_error("Désolé, cette page n'est accessible qu'aux administrateurs du système.");
    }

    $start_trimester  = optional_param('start_trimester', null, PARAM_ALPHANUM);
    echo "<!-- $start_trimester -->";
}

set_time_limit(600);

$strtitle = 'Synchronize from database';
if (!$in_cron) {
    print_header($strtitle,$strtitle,build_navigation(array(array('name'=>'Synchronization','link'=>'','type'=>'misc'))));
}

// Verify if external database enrolment is enabled
if (!in_array('database',explode(',',$CFG->enrol_plugins_enabled))) {
    print_error('errorenroldbnotavailable','block_cegep');
}

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

// Prepare external SIS source database connection
if ($sisdbsource = sisdbsource_connect()) {
    //$sisdbsource->Execute("SET NAMES 'utf8'");
}
else {
    error_log('[SIS_DB_SOURCE] Could not make a connection');
    print_error('dbconnectionfailed','error');
}

$msg = '';

if (empty($start_trimester)) {
    print_box('Please input the term at which you would like to start the synchronization');
    $form = '<center><form enctype="multipart/form-data" action="sync_db.php" method="post">';
    $form .= 'Term (eg. '. cegep_local_current_trimester() .'): <input name="start_trimester" type="text" size="5" maxlength="5" />';
    $form .= '<br /><br /><input type="submit" value="start synchronization" /></form></center>';
    print_box($form);
    print_footer();
}
else {

    $select = cegep_local_sisdbsource_select($start_trimester);

    $sessions = array();
    $codes_etudiants = array();
    $codes_programmes = array();
    $codes_cours = array();
    $groupecours = array();
    $inscriptions = array();
    $inscriptions_db = array();

    $ins_session = 0;
    $ins_etudiant = 0;
    $upd_etudiant = 0;
    $ins_cours = 0;
    $upd_cours = 0;
    $ins_program = 0;
    $upd_program = 0;
    $ins_groupecours = 0;
    $ins_inscription = 0;
    $del_inscription = 0;
    $ins_inscription_prog = 0;
    $del_inscription_prog = 0;
    $skipped = 0; // Inscriptions dont de groupecours n'est pas défini (encore)

    $student_role = get_record('role','shortname',$CFG->block_cegep_studentrole);

    //$sisdbsource->debug = true;
    $sisdbsource_rs = $sisdbsource->Execute($select); 

    if (!$sisdbsource_rs || $sisdbsource_rs->EOF || $sisdbsource_rs->RowCount() == 0) {
        die("Database query returned no results!");
    }

    while ($sisdbsource_rs && !$sisdbsource_rs->EOF) {

        // TODO: Any instances of 'session' or 'semester' should be renamed 'trimester'
        $session = array();
        $session = implode(cegep_local_sisdbsource_decode('coursetrimester',$sisdbsource_rs->fields['CourseTrimester']));
        if (!in_array($session,$sessions)) {
            $sessions[] = $session;
        }

        $code_etudiant = cegep_local_sisdbsource_decode('studentnumber',$sisdbsource_rs->fields['StudentNumber']);
        $code_cours = cegep_local_sisdbsource_decode('coursenumber',$sisdbsource_rs->fields['CourseNumber']);
        $titre_cours = cegep_local_sisdbsource_decode('coursetitle',$sisdbsource_rs->fields['CourseTitle']);
        $code_groupe = cegep_local_sisdbsource_decode('coursegroup',$sisdbsource_rs->fields['CourseGroup']);
        $first_name = cegep_local_sisdbsource_decode('studentfirstname',$sisdbsource_rs->fields['StudentFirstName']);
        $last_name = cegep_local_sisdbsource_decode('studentlastname',$sisdbsource_rs->fields['StudentLastName']);
        $service = cegep_local_sisdbsource_decode('coursecampus',$sisdbsource_rs->fields['CourseCampus']);
        $program = cegep_local_sisdbsource_decode('studentprogram',$sisdbsource_rs->fields['StudentProgram']);
        $programyear = cegep_local_sisdbsource_decode('studentprogramyear',$sisdbsource_rs->fields['StudentProgramYear']);
        $programtitle = cegep_local_sisdbsource_decode('studentprogramname',$sisdbsource_rs->fields['StudentProgramName']);
        $groupecoursid = '';

        /*
         print("$session,$code_etudiant,$code_cours,$titre_cours,$code_groupe,$first_name,$last_name,$service,$program,$programyear,$programtitle<br />\n");
        $sisdbsource_rs->MoveNext();
        continue;
         */

        if (empty($sisdbsource_rs->fields['CourseGroup'])) { $skipped++; continue; }

            // Mettre à jour les infos des étudiants
            if (!in_array($code_etudiant, $codes_etudiants)) {
                $select = "SELECT * FROM `$CFG->sisdb_name`.`student` WHERE `username` = '$code_etudiant'";
                $resultat = $sisdb->Execute($select);
                if ($resultat && $resultat->RecordCount() == 0) {
                    $insert = "INSERT INTO `$CFG->sisdb_name`.`student` (`username` , `lastname`, `firstname`, `program_id`, `program_year`) VALUES ('$code_etudiant', \"$last_name\", \"$first_name\", \"$program\", '$programyear'); ";
                    $resultat = $sisdb->Execute($insert);
                    if (!$resultat) {
                        trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                        if (!$in_cron) echo "Sync error : student process";
                        break;
                    } else { $ins_etudiant++; }                 
                }
                elseif ($resultat && ($resultat->fields['lastname'] != $last_name || $resultat->fields['firstname'] != $first_name || $resultat->fields['program_id'] != $program || $resultat->fields['program_year'] != $programyear)) {
                    $update = "UPDATE `$CFG->sisdb_name`.`student` SET `lastname` = \"$last_name\", `firstname` = \"$first_name\", `program_id` = \"$program\", `program_year` = \"$programyear\" WHERE `username` = '$code_etudiant'; ";
                    $resultat = $sisdb->Execute($update);
                    if (!$resultat) {
                        trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
                        if (!$in_cron) echo "Sync error : student process";
                        break;
                    } else { $upd_etudiant++; }
               }
                array_push($codes_etudiants, $code_etudiant);

                // Mettre à jour les inscriptions de programme
                $program_idyear = $program . '_' . $programyear;
                // Retraits
                $delete = "DELETE FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE `$CFG->enrol_remoteuserfield` = '$code_etudiant' AND `$CFG->enrol_db_remoterolefield` = '$student_role->shortname' AND `coursegroup_id` IS NULL AND program_idyear IS NOT NULL AND program_idyear != '$program_idyear'";
                if (!$resultat = $enroldb->Execute($delete)) {
                    trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
                    if (!$in_cron) echo "Erreur : inscription process";
                    break;
                } else {
                    $del_inscription_prog += $enroldb->Affected_Rows();
                }
                // Ajouts
                //$select = "SELECT DISTINCT(`$CFG->enrol_remotecoursefield`) FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` e WHERE '$code_etudiant' NOT IN (SELECT `$CFG->enrol_remoteuserfield` FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE program_idyear = '$program_idyear' AND e.`$CFG->enrol_remotecoursefield` = `$CFG->enrol_remotecoursefield` AND `$CFG->enrol_db_remoterolefield` = '$student_role->shortname') AND program_idyear = '$program_idyear' AND `$CFG->enrol_db_remoterolefield` = '$student_role->shortname'";
                $select = "SELECT $CFG->enrol_remotecoursefield, (SELECT count(*) FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE $CFG->enrol_remoteuserfield = '$code_etudiant' AND e1.$CFG->enrol_remotecoursefield = $CFG->enrol_remotecoursefield AND program_idyear IS NOT NULL AND `$CFG->enrol_db_remoterolefield` = '$student_role->shortname') AS c FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` e1 WHERE program_idyear = '$program_idyear' AND `$CFG->enrol_db_remoterolefield` = '$student_role->shortname' GROUP BY $CFG->enrol_remotecoursefield;";
                $progadd_rs = $enroldb->Execute($select);
                while ($progadd_rs && !$progadd_rs->EOF && $progadd_rs->fields['c'] == 0) {
                    $course = $progadd_rs->fields[$CFG->enrol_remotecoursefield];
                    $insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield`, `$CFG->enrol_db_remoterolefield`, `program_idyear`) VALUES ('$course', '$code_etudiant', '$student_role->shortname', '$program_idyear');";
                    if (!$resultat = $enroldb->Execute($insert)) {
                        trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $insert);
                        if (!$in_cron) echo "Erreur : inscription process";
                        break;
                    } else {
                        $ins_inscription_prog++;
                    }
                    $progadd_rs->MoveNext();
                }
            }

        // Mettre à jour les programmes
        if (!in_array($program, $codes_programmes)) {
            $select = "SELECT * FROM `$CFG->sisdb_name`.`program` WHERE `id` = '$program'";
            $resultat = $sisdb->Execute($select);
            if ($resultat && $resultat->RecordCount() == 0) {
                $insert = "INSERT INTO `$CFG->sisdb_name`.`program` (`id` , `title`) VALUES ('$program', \"$programtitle\"); ";
                $resultat = $sisdb->Execute($insert);
                if (!$resultat) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    if (!$in_cron) echo "Sync error : program process";
                    break;
                } else { $ins_program++; }
            }
            elseif ($resultat && ($resultat->fields['title'] != $programtitle) ) {
                $update = "UPDATE `$CFG->sisdb_name`.`program` SET `title` = \"$programtitle\" WHERE `id` = '$program'; ";
                $resultat = $sisdb->Execute($update);
                if (!$resultat) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
                    if (!$in_cron) echo "Sync error : program process";
                    break;
                } else { $upd_program++; }
            }
            array_push($codes_programmes, $program);
        }

        // Mettre à jour les informations de cours
        if (!in_array($code_cours, $codes_cours)) {
            $select = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = '$code_cours'";
            $resultat = $sisdb->Execute($select);
            if ($resultat && $resultat->RecordCount() == 0) {
                $insert = "INSERT INTO `$CFG->sisdb_name`.`course` (`coursecode` , `title`, `service`) VALUES ('$code_cours', \"$titre_cours\", '$service'); ";
                $resultat = $sisdb->Execute($insert);
                if (!$resultat) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    if (!$in_cron) echo "Sync error : course process";
                    break;
                } else { $ins_cours++; }
            }
            elseif ($resultat && ($resultat->fields['title'] != $titre_cours || $resultat->fields['service'] != $service) ) {
                $update = "UPDATE `$CFG->sisdb_name`.`course` SET `title` = \"$titre_cours\", `service` = \"$service\" WHERE `coursecode` = '$code_cours'; ";
                $resultat = $sisdb->Execute($update);
                if (!$resultat) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
                    if (!$in_cron) echo "Sync error : student process";
                    break;
                } else { $upd_cours++; }
            }
            array_push($codes_cours, $code_cours);
        }

        // Mettre à jour les infos de groupecours
        foreach ($groupecours as $gc) {
            if ($gc['coursecode'] == $code_cours && $gc['group'] == $code_groupe && $gc['semester'] == $session) {
                $groupecoursid = $gc['id'];
            }
        }
        if (empty($groupecoursid)) {
            $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `coursecode` = '$code_cours' AND `group` = '$code_groupe' AND `semester` = '$session'";
            $resultat = $sisdb->Execute($select);
            if ($resultat && $resultat->RecordCount() == 0) {
                $insert = "INSERT INTO `coursegroup` (`coursecode`, `group`, `semester`) VALUES ('$code_cours', '$code_groupe', '$session'); ";
                $resultat = $sisdb->Execute($insert);
                if (!$resultat) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    print($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    break;
                } else { $groupecoursid = $sisdb->Insert_ID(); $ins_groupecours++; }
            } else { $groupecoursid = $resultat->fields['id']; }
            array_push($groupecours, array('coursecode' => $code_cours, 'group' => $code_groupe, 'semester' => $session, 'id' => $groupecoursid));
        }

        array_push($inscriptions, serialize(array($groupecoursid, $code_etudiant)));

        $sisdbsource_rs->moveNext();
    }

    // Mettre à jour les inscriptions
    $inscriptions_db = array();
    $select = "SELECT * FROM `$CFG->sisdb_name`.`student_enrolment` WHERE `coursegroup_id` IN (SELECT id FROM `$CFG->sisdb_name`.`coursegroup` WHERE `semester` >= '$start_trimester')";

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
            if (!$in_cron) echo "Erreur : inscription process";
            break;
        }
        $enrolments_rs = get_recordset_sql("SELECT DISTINCT `$CFG->enrol_remotecoursefield` AS courseidnumber FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE `coursegroup_id` = '$inscription[0]'");
        while ($enrolment = rs_fetch_next_record($enrolments_rs)) {
            $insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield`, `$CFG->enrol_db_remoterolefield`, `coursegroup_id`) VALUES ('$enrolment->courseidnumber', '$inscription[1]', '$student_role->shortname', '$inscription[0]'); ";
            if (!$resultat = $enroldb->Execute($insert)) {
                trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $insert);
                if (!$in_cron) echo "Erreur : inscription process";
                break;
            }
            $course = get_record('course', 'idnumber', $enrolment->courseidnumber);
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            if ($student_user = get_record('user', 'username', $inscription[1])) {
                role_assign($student_role->id, $student_user->id, 0, $context->id);
            }
            $ins_inscription++;
        }
    }
    foreach ($retraits as $inscription) {
        $inscription = unserialize($inscription);
        $delete = "DELETE FROM `$CFG->sisdb_name`.`student_enrolment` WHERE (`coursegroup_id` = '$inscription[0]' AND `username` = '$inscription[1]'); ";
        if (!$resultat = $sisdb->Execute($delete)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
            if (!$in_cron) echo "Erreur : inscription process";
            break;
        }
        $enrolments_rs = get_recordset_sql("SELECT DISTINCT `$CFG->enrol_remotecoursefield` AS courseidnumber FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE `coursegroup_id` = '$inscription[0]'");
        while ($enrolment = rs_fetch_next_record($enrolments_rs)) {
            $delete = "DELETE FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$enrolment->courseidnumber' AND `$CFG->enrol_remoteuserfield` = '$inscription[1]' AND `$CFG->enrol_db_remoterolefield` = '$student_role->shortname'";
            if (!$resultat = $enroldb->Execute($delete)) {
                trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
                if (!$in_cron) echo "Erreur : inscription process";
                break;
            }
            $course = get_record('course', 'idnumber', $enrolment->courseidnumber);
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            if ($student_user = get_record('user', 'username', $inscription[1])) {
                role_unassign($student_role->id, $student_user->id, 0, $context->id);
            }
        }
        $del_inscription++;
    }

    // Nettoyage

    // À faire ...

    $enroldb->Close();
    $sisdb->Close();
    $sisdbsource->Close();
    
    $end_time = (float) array_sum(explode(' ',microtime())); 

    $msg .= "<strong><u>Opération complétée</u></strong><br /><br />";
    $msg .= "<strong>Sessions</strong> : ".implode($sessions,', ')."<br /><br />";
    $msg .= "<strong>Étudiants</strong> : $ins_etudiant ajouts; $upd_etudiant maj; " . count($codes_etudiants) . " total<br /><br />";
    $msg .= "<strong>Programmes</strong> : $ins_program ajouts; $upd_program maj; " . count($codes_programmes) . " total<br /><br />";
    $msg .= "<strong>Cours</strong> : $ins_cours ajouts; $upd_cours maj; " . count($codes_cours) . " total<br /><br />";
    $msg .= "<strong>Groupecours</strong> : $ins_groupecours ajouts; " . count($groupecours) . " total<br /><br />";
    $msg .= "<strong>Inscriptions groupecours</strong> : $ins_inscription ajouts; $del_inscription ret; $skipped skp " . count($inscriptions) . " total<br /><br />";
    $msg .= "<strong>Inscriptions programmes</strong> : $ins_inscription_prog ajouts; $del_inscription_prog ret;<br /><br />";
    $msg .= "Temps d'exécution : ". sprintf("%.4f", ($end_time-$start_time))." secondes"; 

    notice($msg,$CFG->wwwroot);
    print_footer();
}

exit(0);

?>
