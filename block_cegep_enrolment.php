<?php

require_once('../../config.php');
require('lib.php');

$courseid = required_param('id', PARAM_INT);
$action   = optional_param('a', null, PARAM_ACTION);

global $CFG, $USER, $COURSE;

// Module unavailable for course id 0 or 1
if ($courseid == 1 || !$COURSE = get_record('course', 'id', $courseid))
    print_error('invalidcourse');

require_login($COURSE);

// Set up and display page header
$strtitle = get_string('enrolment','block_cegep');
$navlinks = array();
$navlinks[] = array('name' => $strtitle, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);
print_header("$COURSE->shortname: $strtitle", $COURSE->fullname, $navigation);

// Enrol and unenrol actions not applicable to metacourses
if ($COURSE->metacourse && !empty($action))
    redirect($CFG->wwwroot.'/course/view.php?id='.$COURSE->id, get_string('erroractionnotavailable','block_cegep').'<br /><br />',10);

// This module only available to teachers and admins
$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
if (isguest() or !has_capability('moodle/course:update', $context)) {
    print_error('mustbeteacher','block_cegep');
}

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

// Check how many coursegroups are enrolled at the moment
$select = "SELECT COUNT(DISTINCT `coursegroup_id`) AS num FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IS NOT NULL LIMIT 1";
$num_enrolments = $enroldb->Execute($select)->fields['num'];
    
// Main switch
switch ($action) {
    case 'enrol' :
        cegep_enrol();
        break;
    case 'unenrol' : 
        if ($num_enrolments > 0)
            cegep_unenrol();
        else
            notify(get_string('nocoursegroupsenrolled','block_cegep'));
        break;
    default : 
        cegep_studentlist();
        break;
}

// Enrol a coursegroup into a Moodle course
function cegep_enrol() {
    global $CFG, $COURSE, $enroldb, $sisdb;
    
    $currenttab = 'enrol';
    require('block_cegep_tabs.php');
    
    // Set up enrolment form
    require('block_cegep_enrol_form.php');
    $enrolform = new cegep_enrol_form('block_cegep_enrolment.php?a=enrol&id='.$COURSE->id.'');

    // Go back to course page if cancelled
    if ($enrolform->is_cancelled()) {
        redirect($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
    }
    // Process validated data
    elseif ($data = $enrolform->get_data()) {
   
        // Extract course code info
        $coursecode = substr($COURSE->idnumber, 0, strripos($COURSE->idnumber, '_'));

        // Extract semester info
        $semester = $data->year . $data->semester;

        // Fetch record of the coursegroup from SIS
        $select_coursegroup = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `coursecode` = '$coursecode' AND `semester` = '$semester' AND `group` = '$data->coursegroup'";
        $coursegroup = $sisdb->Execute($select_coursegroup)->fields;

        // Fetch records of students enrolled into this course from SIS
        $select_students = "SELECT * FROM `$CFG->sisdb_name`.`student_enrolment` WHERE `coursegroup_id` = '$coursegroup[id]'";
        $students_rs = $sisdb->Execute($select_students);
        
        if (empty($coursegroup) || !$students_rs) {
            print_error('errorimportingstudentlist','block_cegep');
        }

        // Go through each student and insert Moodle external enrolment database record
        $studentlist = '';
        while ($students_rs && !$students_rs->EOF) {
            $student = $students_rs->fields;
        	$insert = "INSERT INTO `$CFG->enrol_dbtable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield`, `$CFG->enrol_db_remoterolefield`, `coursegroup_id`) VALUES ('$COURSE->idnumber', '$student[username]', '$CFG->block_cegep_studentrole', '$coursegroup[id]');";
            $result = $enroldb->Execute($insert);
            if (!$result) {
                trigger_error(get_string('errorimportingstudentlist','block_cegep'), E_USER_ERROR);
                break;
            } else {
                $studentlist .= $student['username'].'<br />';
            }
            $students_rs->MoveNext();
        }
        
        // Display nice confirmation with student list and buttons
        notify(get_string('coursegroupenrolled','block_cegep',array($studentlist)),'notifysuccess');
        echo "<div class='continuebutton'>";
        print_single_button('block_cegep_enrolment.php', array('a' => 'enrol', 'id' => $COURSE->id), get_string('enrolanother','block_cegep'));
        echo "</div><br />";
        echo "<div class='continuebutton'>";
        print_single_button($CFG->wwwroot.'/course/view.php', array('id' => $COURSE->id), get_string('continuetocourse'));
        echo "</div>";
    }
    // Display the enrolment form
    else {
        print_heading(get_string('enrol','block_cegep'), 'center', 3);
        $enrolform->display();
    }
}

// Unenrol a coursegroup from a Moodle course
function cegep_unenrol() {
    global $CFG, $COURSE, $enroldb;
    
    $currenttab = 'unenrol';
    require('block_cegep_tabs.php');
    
    // Set up enrolment form
    require('block_cegep_unenrol_form.php');
    $unenrolform = new cegep_unenrol_form('block_cegep_enrolment.php?a=unenrol&id='.$COURSE->id.'');

    // Go back to course page if cancelled
    if ($unenrolform->is_cancelled()) {
        redirect($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
    }
    // Process validated data
    elseif ($data = $unenrolform->get_data()) {

        // Go through each coursegroup and remove Moodle external enrolment database record
        $coursegroup_list = implode(', ', $data->coursegroup);
        $delete = "DELETE FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IN ($coursegroup_list)";
        
        $result = $enroldb->Execute($delete);
        
        if (!$result) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
            print_error('errordeletingenrolment','block_cegep');
            break;
        } else {
            notify(get_string('coursegroupunenrolled','block_cegep',array($enroldb->Affected_Rows())),'notifysuccess');
	        echo "<div class='continuebutton'>";
            print_single_button($CFG->wwwroot.'/course/view.php', array('id' => $COURSE->id), get_string('continuetocourse'));
            echo "</div>";
        }       
    }
    // Display the enrolment form
    else {
        print_heading(get_string('unenrol','block_cegep'), 'center', 3);
        $unenrolform->display();
    }
}

// List all coursegroups and students enrolled in this Moodle course
function cegep_studentlist() {
    global $CFG, $COURSE, $enroldb, $sisdb;

    $currenttab = 'studentlist';
    require('block_cegep_tabs.php');
    
    $body = '';

    ($COURSE->metacourse) ? ($courses = get_courses_in_metacourse($COURSE->id)) : ($courses = array($COURSE));

    if ($courses) {
        
        foreach ($courses as $c) {
    
            if (empty($c->idnumber)) { $c = get_record('course', 'id', $c->id); }
            
            $select = "SELECT DISTINCT `coursegroup_id` FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$c->idnumber' AND `coursegroup_id` IS NOT NULL";
    
            $coursegroups = $enroldb->Execute($select);
            
            if (!$coursegroups) {
                trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select);
            }
            else {
    
                $coursegroup_id = '';
                
                while (!$coursegroups->EOF) {
                    
                    $coursegroup_id = $coursegroups->fields['coursegroup_id'];
                    
                    $semester = '';
                    $year = '';
            
                    $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `id` = '$coursegroup_id'";
                    $coursegroup = $sisdb->Execute($select)->fields;
            
                    switch (substr($coursegroup['semester'],0,1)) {
                        case 'e' : $semester = get_string('summer', 'block_cegep'); break;
                        case 'a' : $semester = get_string('autumn', 'block_cegep'); break;
                        case 'h' : $semester = get_string('winter', 'block_cegep'); break;
                    }
                    $year = substr($coursegroup['semester'],1,4);
            
                    $notice = '';
            
                    if ($COURSE->metacourse) { $notice .= '<strong>'.get_string('childcoursetitle','block_cegep').'</strong> : ' . $c->fullname . ' ('. $c->idnumber .')<br />'; }
                    $notice .= '<strong>'.get_string('semester','block_cegep').'</strong> : ' . $semester . '&nbsp;' . $year . '<br />';
                    $notice .= '<strong>'.get_string('coursecode','block_cegep').'</strong> : ' . $coursegroup['coursecode'] . '<br />';
                    $notice .= '<strong>'.get_string('coursegroupnumber','block_cegep').'</strong> : ' . $coursegroup['group'] . '<br /><br />';
            
                    $table = new stdClass;
                    $table->class = 'flexible';
                    $table->width = '100%';
                    $table->cellpadding = 4;
                    $table->cellspacing = 3;
                    $table->align = array('left','left','left');
                    $table->head = array(get_string('username'),get_string('firstname'),get_string('lastname'),get_string('program','block_cegep'));
                    $table->data = array();
            
                    // Obtenir la liste des étudiants
                    $select = "SELECT * FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$c->idnumber' AND `coursegroup_id` = '$coursegroup[id]' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' ORDER BY `$CFG->enrol_remoteuserfield` ASC";
                    $enrolments_rs = $enroldb->Execute($select);
            
                    // Inscription des étudiants
                    $lastnames = array();
                    while ($enrolments_rs and !$enrolments_rs->EOF) {
                        $select = "SELECT * FROM `$CFG->sisdb_name`.`student` WHERE `username` = '" . $enrolments_rs->fields[$CFG->enrol_remoteuserfield] . "'";
                        $student_rs = $sisdb->Execute($select);
                        if ($student_rs && $student_rs->RecordCount() == 1) {
                            $student_sisdb = $student_rs->fields;
                            $student_moodle = get_record('user', 'username', $student_sisdb['username']);
                            if ($student_moodle) {
                                $table->data[] = array('<a href="'.$CFG->wwwroot.'/user/view.php?id='.$student_moodle->id.'" title="'.get_string('accessuserprofile','block_cegep').'">'.$student_sisdb['username'].'</a>', $student_sisdb['firstname'], $student_sisdb['lastname'], $student_sisdb['program']);
                                $lastnames[] = $student_sisdb['lastname'];
                            } else {
                                $table->data[] = array($student_sisdb['username'], $student_sisdb['firstname'], $student_sisdb['lastname'], $student_sisdb['program']);
                                $lastnames[] = $student_sisdb['lastname'];
                            }
                        }
                        $enrolments_rs->MoveNext();
                    }
            
                    if (count($table->data) > 0) {
                        array_multisort($lastnames, SORT_ASC, $table->data);
                        $notice .= print_table($table,true);
                        $notice .= '<br /><strong>'.get_string('total').'</strong> : ' . count($table->data);
                    } else { ($notice .= "Il n'y a aucun étudiant inscrit à ce cours.<br /><br />"); }
            
                    $body .= print_simple_box($notice, 'center', '700px', '', 5, 'generalbox', '', true);
                    
                    $coursegroups->MoveNext();
                }
            }
        }
    }
    
    if (!empty($body)) {
        print_heading(get_string('studentlisttitle','block_cegep'), 'center', 3);
        print($body);
    } else {
        notify(get_string('nocoursegroupsenrolled', 'block_cegep'));
    }
}

print_footer();

$enroldb->Close();
$sisdb->Close();

?>
