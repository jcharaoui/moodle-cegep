<?php

// as seen in /auth/ldap/auth_ldap_sync_users.php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot .'/blocks/cegep/lib.php');

global $CFG, $DB, $USER;

// Get parameters
$op = optional_param('op', null, PARAM_ACTION);
$start_term  = optional_param('start_term', null, PARAM_INT);
$keep_terms  = optional_param('keep_terms', null, PARAM_INT);

set_time_limit(600);

// Check access permissions
if (!empty($CFG->block_cegep_cron_password) && $password == $CFG->block_cegep_cron_password) {
    $start_term = cegep_local_current_term();
    $in_cron = true;
} else {
    require_login();
    if (!is_siteadmin($USER)) {
        print_error("Désolé, cette page n'est accessible qu'aux administrateurs du système.");
    }
    $strtitle = 'SIS DB maintenance';
    print_header($strtitle,$strtitle,build_navigation(array(array('name'=>get_string('admincegep','block_cegep'),'link'=>'','type'=>'misc'),array('name'=>get_string('sisdb_maintenance','block_cegep'),'link'=>'','type'=>'misc'))));
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

// Main switch
switch ($op) {
    case 'prune' :
        (empty($keep_terms)) ? (cegep_sisdb_prune_form()) : (cegep_sisdb_prune($keep_terms));
        break;
    case 'sync' :
        (empty($start_term)) ? (cegep_sisdb_sync_form()) : (cegep_sisdb_sync($start_term));
        break;
    default :
        cegep_sisdb_sync_form();
        break;
}

function cegep_sisdb_sync_form() {

    $currenttab = 'sync';
    require('sisdb_tabs.php');

    print_box('Please input the term at which you would like to start the synchronization.');
    $form = '<center><form enctype="multipart/form-data" action="sisdb.php" method="post">';
    $form .= 'Term (eg. '. cegep_local_current_term() .'): <input name="start_term" type="text" size="5" maxlength="5" />';
    $form .= '<input type="hidden" name="op" value="sync" />';
    $form .= '<br /><br /><input type="submit" value="Start" /></form></center>';
    print_box($form);
    print_footer();

}

function cegep_sisdb_sync($start_term) {
    global $CFG, $DB, $enroldb, $sisdb, $sisdbsource;

    // Keep track of time
    $start_time = (float) array_sum(explode(' ',microtime()));
    $timestamp = time();
    $mysqltimestamp = date('Y-m-d H:i:s', $timestamp);

    // Set up SQL variables for triggers
    if ($CFG->sisdb_logging) {
        $sisdb->Execute('SET @LOGGING = 1');
        $sisdb->Execute("SET @TIMESTAMP = '$mysqltimestamp'");
    } else {
        $sisdb->Execute('SET @LOGGING = 0');
    }

    // Get data from external SIS database (ie, Clara)
    $select = cegep_local_sisdbsource_select_students($start_term);
    $sisdbsource_rs = $sisdbsource->Execute($select); 

    // Don't proceed any further if no data is returned
    if (!$sisdbsource_rs || $sisdbsource_rs->EOF || $sisdbsource_rs->RowCount() == 0) {
        die("Database query returned no results!");
    }

    // Initialize variables
    
    // Keep track of processed objects
    $terms = array();
    $students = array();
    $programs = array();
    $courses = array();
    $coursegroups = array();

    // These arrays are used for comparison (determine additions and removals)
    $student_enrol_localdb = array();  // Student enrolments in moodle-sis
    $student_enrol_remotedb = array(); // Student enrolments in sisdbsource (ex. Clara)
    $teacher_enrol_localdb = array();  // Teacher enrolments in moodle-sis
    $teacher_enrol_remotedb = array(); // Teacher enrolments in sisdbsource (ex. Clara)

    // Counters for report
    $count = array();
    $count['records_skipped'] = 0;
    $count['students_added'] = 0;
    $count['students_updated'] = 0;
    $count['courses_added'] = 0;
    $count['courses_updated'] = 0;
    $count['programs_added'] = 0;
    $count['programs_updated'] = 0;
    $count['coursegroups_added'] = 0;
    $count['student_enrolments_added'] = 0;
    $count['student_enrolments_removed'] = 0;
    $count['student_program_enrolments_added'] = 0;
    $count['student_program_enrolments_removed'] = 0;
    $count['teacher_enrolments_added'] = 0;
    $count['teacher_enrolments_removed'] = 0;

    $student_role = $DB->get_record('role', array('shortname' => $CFG->block_cegep_studentrole));

    while ($sisdbsource_rs && !$sisdbsource_rs->EOF) {

        $term = array();
        $term = implode(cegep_local_sisdbsource_decode('courseterm',$sisdbsource_rs->fields['CourseTerm']));
        if (!in_array($term,$terms)) {
            $terms[] = $term;
        }

        $student = cegep_local_sisdbsource_decode('studentnumber',$sisdbsource_rs->fields['StudentNumber']);
        $course = cegep_local_sisdbsource_decode('coursenumber',$sisdbsource_rs->fields['CourseNumber']);
        $course_title = cegep_local_sisdbsource_decode('coursetitle',$sisdbsource_rs->fields['CourseTitle']);
        $course_unit = cegep_local_sisdbsource_decode('courseunit',$sisdbsource_rs->fields['CourseUnit']);
        $coursegroup = cegep_local_sisdbsource_decode('coursegroup',$sisdbsource_rs->fields['CourseGroup']);
        $student_firstname = cegep_local_sisdbsource_decode('studentfirstname',$sisdbsource_rs->fields['StudentFirstName']);
        $student_lastname = cegep_local_sisdbsource_decode('studentlastname',$sisdbsource_rs->fields['StudentLastName']);
        $program = cegep_local_sisdbsource_decode('studentprogram',$sisdbsource_rs->fields['StudentProgram']);
        $programyear = cegep_local_sisdbsource_decode('studentprogramyear',$sisdbsource_rs->fields['StudentProgramYear']);
        $programtitle = cegep_local_sisdbsource_decode('studentprogramname',$sisdbsource_rs->fields['StudentProgramName']);
        $coursegroup_id = '';

        // We ignore enrolments that don't have a coursegroup (section) number
        if (empty($sisdbsource_rs->fields['CourseGroup'])) {
            $count['records_skipped']++;
            $sisdbsource_rs->moveNext();
            continue;
        }

        // Update student data
        if (!in_array($student, $students)) {

            $select = "SELECT * FROM `$CFG->sisdb_name`.`student` WHERE `username` = ?";
            $result = $sisdb->Execute($select, array($student));

            if ($result && $result->RecordCount() == 0) {
                $insert = "INSERT INTO `$CFG->sisdb_name`.`student` (`username` , `lastname`, `firstname`, `program_id`, `program_year`) VALUES (?, ?, ?, ?, ?); ";
                $result = $sisdb->Execute($insert, array($student, $student_lastname, $student_firstname, $program, $programyear));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    if (!$in_cron) echo "Sync error : student process";
                    break;
                } else { $count['students_added']++; }
            }
            elseif ($result && ($result->fields['lastname'] != $student_lastname || $result->fields['firstname'] != $student_firstname || $result->fields['program_id'] != $program || $result->fields['program_year'] != $programyear)) {
                $update = "UPDATE `$CFG->sisdb_name`.`student` SET `lastname` = ?, `firstname` = ?, `program_id` = ?, `program_year` = ? WHERE `username` = ?; ";
                $result = $sisdb->Execute($update, array($student_lastname, $student_firstname, $program, $programyear, $student));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
                    if (!$in_cron) echo "Sync error : student process";
                    break;
                } else { $count['students_updated']++; }
            }

            array_push($students, $student);

            // Update program enrolments for student
            $program_idyear = $program . '_' . $programyear;

            // Removals
            $delete = "DELETE FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remoteuserfield` = '$student' AND `$CFG->enrol_remoterolefield` = '$student_role->shortname' AND `coursegroup_id` IS NULL AND program_idyear IS NOT NULL AND program_idyear != '$program_idyear'";
            if (!$result = $enroldb->Execute($delete)) {
                trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
                if (!$in_cron) echo "Erreur : inscription process";
                break;
            } else {
                $count['student_program_enrolments_removed'] += $enroldb->Affected_Rows();
            }

            // Additions
            $select = "SELECT $CFG->enrol_remotecoursefield, (SELECT count(*) FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE $CFG->enrol_remoteuserfield = '$student' AND e1.$CFG->enrol_remotecoursefield = $CFG->enrol_remotecoursefield AND program_idyear IS NOT NULL AND `$CFG->enrol_remoterolefield` = '$student_role->shortname') AS c FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` e1 WHERE program_idyear = '$program_idyear' AND `$CFG->enrol_remoterolefield` = '$student_role->shortname' GROUP BY $CFG->enrol_remotecoursefield;";
            $progadd_rs = $enroldb->Execute($select);
            while ($progadd_rs && !$progadd_rs->EOF && $progadd_rs->fields['c'] == 0) {
                $course = $progadd_rs->fields[$CFG->enrol_remotecoursefield];
                $insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield`, `$CFG->enrol_remoterolefield`, `program_idyear`) VALUES (?, ?, ?, ?);";
                if (!$result = $enroldb->Execute($insert, array($course, $student, $student_role->shortname, $program_idyear))) {
                    trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $insert);
                    if (!$in_cron) echo "Erreur : inscription process";
                    break;
                } else {
                    $count['student_program_enrolments_added']++;
                }
                $progadd_rs->MoveNext();
            }
        }

        // Update programs data
        if (!in_array($program, $programs)) {

            $select = "SELECT * FROM `$CFG->sisdb_name`.`program` WHERE `id` = ?";
            $result = $sisdb->Execute($select, array($program));
            if ($result && $result->RecordCount() == 0) {
                $insert = "INSERT INTO `$CFG->sisdb_name`.`program` (`id` , `title`) VALUES (?, ?); ";
                $result = $sisdb->Execute($insert, array($program, $programtitle));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    if (!$in_cron) echo "Sync error : program process";
                    break;
                } else { $count['programs_added']++; }
            }
            elseif ($result && ($result->fields['title'] != $programtitle) ) {
                $update = "UPDATE `$CFG->sisdb_name`.`program` SET `title` = ? WHERE `id` = ?; ";
                $result = $sisdb->Execute($update, array($programtitle, $program));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
                    if (!$in_cron) echo "Sync error : program process";
                    break;
                } else { $count['programs_updated']++; }
            }

            array_push($programs, $program);
        }

        // Update courses data
        if (!in_array($course, $courses)) {

            $select = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = ?";
            $result = $sisdb->Execute($select, array($course));
            if ($result && $result->RecordCount() == 0) {
                $insert = "INSERT INTO `$CFG->sisdb_name`.`course` (`coursecode` , `title`, `unit`) VALUES (?, ?, ?); ";
                $result = $sisdb->Execute($insert, array($course, $course_title, $course_unit));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    if (!$in_cron) echo "Sync error : course process";
                    break;
                } else { $count['courses_added']++; }
            }
            elseif ($result && ($result->fields['title'] != $course_title || $result->fields['unit'] != $course_unit)) {
                $update = "UPDATE `$CFG->sisdb_name`.`course` SET `title` = ?, `unit` = ? WHERE `coursecode` = ?;";
                $result = $sisdb->Execute($update, array($course_title, $course_unit, $course));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
                    if (!$in_cron) echo "Sync error : student process";
                    break;
                } else { $count['courses_updated']++; }
            }

            array_push($courses, $course);
        }

        // Update coursegroups data
        foreach ($coursegroups as $cg) {
            if ($cg['coursecode'] == $course && $cg['group'] == $coursegroup && $cg['term'] == $term) {
                $coursegroup_id = $cg['id'];
                break;
            }
        }

        if (empty($coursegroup_id)) {
            $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `coursecode` = ? AND `group` = ? AND `term` = ?";
            $result = $sisdb->Execute($select, array($course, $coursegroup, $term));
            if ($result && $result->RecordCount() == 0) {
                $insert = "INSERT INTO `coursegroup` (`coursecode`, `group`, `term`) VALUES (?, ?, ?); ";
                $result = $sisdb->Execute($insert, array($course, $coursegroup, $term));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    print($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    break;
                } else { $coursegroup_id = $sisdb->Insert_ID(); $count['coursegroups_added']++; }
            } else { $coursegroup_id = $result->fields['id']; }

            array_push($coursegroups, array('coursecode' => $course, 'group' => $coursegroup, 'term' => $term, 'id' => $coursegroup_id));
        }

        array_push($student_enrol_remotedb, serialize(array($coursegroup_id, $student)));

        $sisdbsource_rs->moveNext();
    }

    // Update student enrolments

    // Get enrolments from local database

    $student_enrol_localdb = array();
    $select = "SELECT * FROM `$CFG->sisdb_name`.`student_enrolment` WHERE `coursegroup_id` IN (SELECT id FROM `$CFG->sisdb_name`.`coursegroup` WHERE `term` >= $start_term)";

    $result = $sisdb->Execute($select);
    while ($result && !$result->EOF) {
        array_push($student_enrol_localdb, serialize(array($result->fields['coursegroup_id'], $result->fields['username'])));
        $result->MoveNext();
    }

    // Compute differences between local and remote datasets

    $student_enrolments_add = array_diff($student_enrol_remotedb, $student_enrol_localdb);
    $student_enrolments_del = array_diff($student_enrol_localdb, $student_enrol_remotedb);

    // Add and remove enrolments as required

    foreach ($student_enrolments_add as $enrolment) {
        $enrolment = unserialize($enrolment);
        $insert = "INSERT INTO `$CFG->sisdb_name`.`student_enrolment` (`coursegroup_id` , `username`) VALUES ('$enrolment[0]', '$enrolment[1]'); ";
        if (!$result = $sisdb->Execute($insert)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
            if (!$in_cron) echo "Erreur : inscription process";
            break;
        }
        // Add student to courses to which this coursegroup is assigned
        $coursegroup_enrolments_rs = $DB->get_recordset_sql("SELECT DISTINCT `$CFG->enrol_remotecoursefield` AS courseidnumber FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE `coursegroup_id` = '$enrolment[0]'");
        foreach ($coursegroup_enrolments_rs as $coursegroup_enrolment) {
            // Do external enrolments DB
            $insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield`, `$CFG->enrol_remoterolefield`, `coursegroup_id`) VALUES ('$coursegroup_enrolment->courseidnumber', '$enrolment[1]', '$student_role->shortname', '$enrolment[0]'); ";
            if (!$result = $enroldb->Execute($insert)) {
                trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $insert);
                if (!$in_cron) echo "Erreur : inscription process";
                break;
            }
            // Do internal enrolments DB
            $course = $DB->get_record('course', array('idnumber' =>  $coursegroup_enrolment->courseidnumber));
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            if ($student_user = $DB->get_record('user', array($CFG->enrol_localuserfield => $enrolment[1]))) {
                role_assign($student_role->id, $student_user->id, $context->id, 'enrol_database');
            }
            $count['student_enrolments_added']++;
        }
    }

    foreach ($student_enrolments_del as $enrolment) {
        $enrolment = unserialize($enrolment);
        $delete = "DELETE FROM `$CFG->sisdb_name`.`student_enrolment` WHERE (`coursegroup_id` = '$enrolment[0]' AND `username` = '$enrolment[1]'); ";
        if (!$result = $sisdb->Execute($delete)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
            if (!$in_cron) echo "Erreur : inscription process";
            break;
        }
        // Remove student from courses to which this coursegroup is assigned
        $coursegroup_enrolments_rs = $DB->get_recordset_sql("SELECT DISTINCT `$CFG->enrol_remotecoursefield` AS courseidnumber FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE `coursegroup_id` = '$enrolment[0]'");
        foreach($coursegroup_enrolments_rs as $coursegroup_enrolment) {
            // Do external enrolments DB
            $delete = "DELETE FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$coursegroup_enrolment->courseidnumber' AND `$CFG->enrol_remoteuserfield` = '$enrolment[1]' AND `$CFG->enrol_remoterolefield` = '$student_role->shortname'";
            if (!$result = $enroldb->Execute($delete)) {
                trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
                if (!$in_cron) echo "Erreur : inscription process";
                break;
            }
            // Do internal enrolments DB
            $course = $DB->get_record('course', array('idnumber' => $coursegroup_enrolment->courseidnumber));
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            if ($student_user = $DB->get_record('user', array($CFG->enrol_localuserfield => $enrolment[1]))) {
                role_unassign($student_role->id, $student_user->id, $context->id, 'enrol_database');
            }
        }
        $count['student_enrolments_removed']++;
    }
 
    // Update teacher enrolments

    // Get enrolments from remote database (ie, Clara)

    $select = cegep_local_sisdbsource_select_teachers($start_term);
    $sisdbsource_rs = $sisdbsource->Execute($select);

    while ($sisdbsource_rs && !$sisdbsource_rs->EOF) {

        if (!in_array($term,$terms)) {
            $terms[] = $term;
        }

        $term = implode(cegep_local_sisdbsource_decode('courseterm',$sisdbsource_rs->fields['CourseTerm']));
        $teacher = cegep_local_sisdbsource_decode('teachernumber',$sisdbsource_rs->fields['TeacherNumber']);
        $course = cegep_local_sisdbsource_decode('coursenumber',$sisdbsource_rs->fields['CourseNumber']);
        $coursegroup = cegep_local_sisdbsource_decode('coursegroup',$sisdbsource_rs->fields['CourseGroup']);
        $course_title = cegep_local_sisdbsource_decode('coursetitle',$sisdbsource_rs->fields['CourseTitle']);
        $coursegroup_id = '';
        $course_unit = 0;

        if (empty($teacher)) {
            $sisdbsource_rs->MoveNext();
            continue;
        }

        foreach ($coursegroups as $cg) {
          if ($cg['coursecode'] == $course && $cg['group'] == $coursegroup && $cg['term'] == $term) {
            $coursegroup_id = $cg['id'];
            break;
          }
        }

        // Add coursegroup if not found (no students enrolled yet)
        if (empty($coursegroup_id)) {
            $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `coursecode` = ? AND `group` = ? AND `term` = ?";
            $result = $sisdb->Execute($select, array($course, $coursegroup, $term));
            if ($result && $result->RecordCount() == 0) {
                $insert = "INSERT INTO `coursegroup` (`coursecode`, `group`, `term`) VALUES (?, ?, ?);";
                $result = $sisdb->Execute($insert, array($course, $coursegroup, $term));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    print($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    break;
                } else { $coursegroup_id = $sisdb->Insert_ID(); $count['coursegroups_added']++; }
            } else { $coursegroup_id = $result->fields['id']; }
            array_push($coursegroups, array('coursecode' => $course, 'group' => $coursegroup, 'term' => $term, 'id' => $coursegroup_id));
        }

        // Update courses data
        if (!in_array($course, $courses)) {

            $select = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = ?";
            $result = $sisdb->Execute($select, array($course));
            if ($result && $result->RecordCount() == 0) {
                $insert = "INSERT INTO `$CFG->sisdb_name`.`course` (`coursecode` , `title`, `unit`) VALUES (?, ?, ?); ";
                $result = $sisdb->Execute($insert, array($course, $course_title, $course_unit));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
                    if (!$in_cron) echo "Sync error : course process";
                    break;
                } else { $count['courses_added']++; }
            }
            elseif ($result && $result->fields['title'] != $course_title) {
                $update = "UPDATE `$CFG->sisdb_name`.`course` SET `title` = ? WHERE `coursecode` = ?; ";
                $result = $sisdb->Execute($update, array($course_title, $course));
                if (!$result) {
                    trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $update);
                    if (!$in_cron) echo "Sync error : course process";
                    break;
                } else { $count['courses_updated']++; }
            }

            array_push($courses, $course);
        }


        array_push($teacher_enrol_remotedb, serialize(array($coursegroup_id, $teacher)));

        $sisdbsource_rs->MoveNext();
    }

    // Get enrolments from local database

    $select = "SELECT * FROM `$CFG->sisdb_name`.`teacher_enrolment` WHERE `coursegroup_id` IN (SELECT id FROM `$CFG->sisdb_name`.`coursegroup` WHERE `term` >= '$start_term')";
    $result = $sisdb->Execute($select);

    while ($result && !$result->EOF) {
        array_push($teacher_enrol_localdb, serialize(array($result->fields['coursegroup_id'], $result->fields['idnumber'])));
        $result->MoveNext();
    }

    // Compute differences between local and remote datasets

    $teacher_enrolments_add = array_diff($teacher_enrol_remotedb, $teacher_enrol_localdb);
    $teacher_enrolments_del = array_diff($teacher_enrol_localdb, $teacher_enrol_remotedb);

    // Add and remove enrolments as required

    foreach ($teacher_enrolments_add as $enrolment) {
        $enrolment = unserialize($enrolment);
        $insert = "INSERT INTO `$CFG->sisdb_name`.`teacher_enrolment` (`coursegroup_id` , `idnumber`) VALUES ('$enrolment[0]', '$enrolment[1]'); ";
        if (!$result = $sisdb->Execute($insert)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $insert);
            if (!$in_cron) echo "Erreur : inscription process";
            break;
        }
        $count['teacher_enrolments_added']++;
    }

    foreach ($teacher_enrolments_del as $enrolment) {
        $enrolment = unserialize($enrolment);
        $delete = "DELETE FROM `$CFG->sisdb_name`.`teacher_enrolment` WHERE `coursegroup_id` = '$enrolment[0]' AND `idnumber` = '$enrolment[1]';";
        if (!$result = $sisdb->Execute($delete)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
            if (!$in_cron) echo "Erreur : inscription process";
            break;
        }
        $count['teacher_enrolments_removed']++;
    }
    
    // Stop counting time
    $end_time = (float) array_sum(explode(' ',microtime()));

    // Display report
    $msg = '';
    $msg .= "<strong><u>Synchronization completed</u></strong><br /><br />";
    $msg .= sprintf("<strong>Terms</strong> : %s<br /><br />", implode(', ', $terms));
    $msg .= sprintf("<strong>Students</strong> : %d added; %d updated; %d processed<br /><br />", $count['students_added'], $count['students_updated'], count($students));
    $msg .= sprintf("<strong>Programs</strong> : %d added; %d updated; %d processed<br /><br />", $count['programs_added'], $count['programs_updated'], count($programs));
    $msg .= sprintf("<strong>Courses</strong> : %d added; %d updated; %d processed<br /><br />", $count['courses_added'], $count['courses_updated'], count($courses));
    $msg .= sprintf("<strong>Coursegroups</strong> : %d added; %d processed<br /><br />", $count['coursegroups_added'], count($coursegroups));
    $msg .= sprintf("<strong>Student course enrolments</strong> : %d added; %d removed; %d skipped; %d processed<br /><br />", $count['student_enrolments_added'], $count['student_enrolments_removed'], $count['records_skipped'], count($student_enrol_remotedb));
    $msg .= sprintf("<strong>Student program enrolments</strong> : %d added; %d removed<br /><br />", $count['student_program_enrolments_added'], $count['student_program_enrolments_removed']);
    $msg .= sprintf("<strong>Teacher course enrolments</strong> : %d added; %d removed; %d processed<br /><br />", $count['teacher_enrolments_added'], $count['teacher_enrolments_removed'], count($teacher_enrol_remotedb));
    
    $msg .= "Started at : $mysqltimestamp<br />";
    $msg .= "Execution time : ". sprintf("%.4f", ($end_time-$start_time))." seconds";

    $currenttab = 'sync';
    require('sisdb_tabs.php');

    notice($msg,$CFG->wwwroot);
    print_footer();
}

function cegep_sisdb_prune_form() {

    $currenttab = 'prune';
    require('sisdb_tabs.php');

    print_box('Please indicate the number of back terms (other than the most current term) to keep in the database.<br /><br />For example, if the latest term in the database is 20102 and you indicate 3, then all terms older that 20102, 20101, 20093 and 20092 will be pruned from the database.<br /><br />Older coursegroups that have active enrolments in Moodle courses will not be affected.');
    $form = '<center><form enctype="multipart/form-data" action="sisdb.php" method="post">';
    $form .= 'Number of terms to keep : <input name="keep_terms" type="text" size="5" maxlength="1" value="3" />';
    $form .= '<input type="hidden" name="op" value="prune" />';
    $form .= '<br /><br /><input type="submit" value="Start" /></form></center>';
    print_box($form);
    print_footer();

}

function cegep_sisdb_prune($keep_terms) {
    global $CFG, $enroldb, $sisdb;

    // Keep track of time
    $start_time = (float) array_sum(explode(' ',microtime()));
    $timestamp = time();
    $mysqltimestamp = date('Y-m-d H:i:s', $timestamp);

    // Set up SQL variables for triggers
    if ($CFG->sisdb_logging) {
        $sisdb->Execute('SET @LOGGING = 1');
        $sisdb->Execute("SET @TIMESTAMP = '$mysqltimestamp'");
    } else {
        $sisdb->Execute('SET @LOGGING = 0');
    }

    // Set up counters
    $pruned_terms = array();
    $count['courses_pruned'] = 0;
    $count['students_pruned'] = 0;
    $count['programs_pruned'] = 0;
    $count['coursegroups_pruned'] = 0;
    $count['student_enrolments_pruned'] = 0;
    $count['teacher_enrolments_pruned'] = 0;

    // Get most recent term
    $select = "SELECT MAX(term) as latest_term FROM `$CFG->sisdb_name`.`coursegroup`;";
    $result = $sisdb->Execute($select);
    $latest_term = $result->fields['latest_term'];

    if (!empty($latest_term)) {

        // Get old coursegroups
        $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `term` < " . cegep_local_decrement_term($latest_term, $keep_terms) . ";";
        $coursegroups_rs = $sisdb->Execute($select);

        while ($coursegroups_rs && !$coursegroups_rs->EOF) {

            $coursegroup_id = $coursegroups_rs->fields['id'];

            // Check if coursegroup has any active enrolments
            $select = "SELECT COUNT(*) as count FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE coursegroup_id = $coursegroup_id";
            $result = $sisdb->Execute($select);
            if ($result && $result->fields['count'] > 0) {
                // Don't remove it, skip to next coursegroup
                $coursegroups_rs->MoveNext();
                continue;
            }

            // Keep track of pruned terms
            if (!in_array($coursegroups_rs->fields['term'], $pruned_terms)) {
                array_push($pruned_terms, $coursegroups_rs->fields['term']);
            }

            // Prune old and inactive student and enrolment records
            $delete = "DELETE FROM `$CFG->sisdb_name`.`student_enrolment` WHERE `coursegroup_id` = $coursegroup_id;";
            if (!$result = $sisdb->Execute($delete)) {
                trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
                if (!$in_cron) echo "Error pruning old student enrolment records.";
            } else {
                $count['student_enrolments_pruned'] += $sisdb->Affected_Rows();
            }

            // Prune old and inactive teacher enrolment records
            $delete = "DELETE FROM `$CFG->sisdb_name`.`teacher_enrolment` WHERE `coursegroup_id` = $coursegroup_id;";
            if (!$result = $sisdb->Execute($delete)) {
                trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
                if (!$in_cron) echo "Error pruning old teacher enrolment records.";
            } else {
                $count['teacher_enrolments_pruned'] += $sisdb->Affected_Rows();
            }

            // Prune coursegroup record
            $delete = "DELETE FROM `$CFG->sisdb_name`.`coursegroup` WHERE `id` = $coursegroup_id;";
            if (!$result = $sisdb->Execute($delete)) {
                trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
                if (!$in_cron) echo "Error pruning old coursegroup records.";
            } else {
                $count['coursegroups_pruned'] += $sisdb->Affected_Rows();
            }

            $coursegroups_rs->MoveNext();
        }

        // Prune courses with no coursegroups
        $delete = "DELETE FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` NOT IN (SELECT DISTINCT `coursecode` FROM `$CFG->sisdb_name`.`coursegroup`);";
        if (!$result = $sisdb->Execute($delete)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
            if (!$in_cron) echo "Error pruning old course records.";
        } else {
            $count['courses_pruned'] += $sisdb->Affected_Rows();
        }

        // Prune students with no enrolments
        $delete = "DELETE FROM `$CFG->sisdb_name`.`student` WHERE `username` NOT IN (SELECT DISTINCT `username` FROM `$CFG->sisdb_name`.`student_enrolment`);";
        if (!$result = $sisdb->Execute($delete)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
            if (!$in_cron) echo "Error pruning old student records.";$coursegroup_id = $coursegroups->fields['coursegroup_id'];
        } else {
            $count['students_pruned'] += $sisdb->Affected_Rows();
        }

        // Prune programs with no students
        $delete = "DELETE FROM `$CFG->sisdb_name`.`program` WHERE `id` NOT IN (SELECT DISTINCT `program_id` FROM `$CFG->sisdb_name`.`student`);";
        if (!$result = $sisdb->Execute($delete)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $delete);
            if (!$in_cron) echo "Error pruning old program records.";
        } else {
            $count['programs_pruned'] += $sisdb->Affected_Rows();
        }

        // Optimize tables
        $optimize = 'OPTIMIZE TABLE `course` , `coursegroup` , `program` , `student` , `student_enrolment` , `teacher_enrolment`;';
        if (!$result = $sisdb->Execute($optimize)) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $optimize);
            if (!$in_cron) echo "Error optimizing tables.";
        }
    }

    // Stop counting time
    $end_time = (float) array_sum(explode(' ',microtime()));

    // Display report
    $msg = '';
    $msg .= "<strong><u>Pruning completed</u></strong><br /><br />";
    $msg .= sprintf("<strong>Terms</strong> : %s<br /><br />", implode(', ', $pruned_terms));
    $msg .= sprintf("<strong>Students</strong> : %d removed<br /><br />", $count['students_pruned']);
    $msg .= sprintf("<strong>Programs</strong> : %d removed<br /><br />", $count['programs_pruned']);
    $msg .= sprintf("<strong>Courses</strong> : %d removed<br /><br />", $count['courses_pruned']);
    $msg .= sprintf("<strong>Coursegroups</strong> : %d removed<br /><br />", $count['coursegroups_pruned']);
    $msg .= sprintf("<strong>Student enrolments</strong> : %d removed<br /><br />", $count['student_enrolments_pruned']);
    $msg .= sprintf("<strong>Teacher course enrolments</strong> : %d removed<br /><br />", $count['teacher_enrolments_pruned']);
    
    $msg .= "Started at : $mysqltimestamp<br />";
    $msg .= "Execution time : ". sprintf("%.4f", ($end_time-$start_time))." seconds";

    $currenttab = 'prune';
    require('sisdb_tabs.php');

    notice($msg,$CFG->wwwroot);
    print_footer();
}

$enroldb->Close();
$sisdb->Close();
$sisdbsource->Close();

exit(0);

?>
