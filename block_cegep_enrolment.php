<?php

require_once('../../config.php');
require('lib.php');

$courseid = required_param('id', PARAM_INT);
$action   = optional_param('a', null, PARAM_ACTION);

global $CFG, $USER, $COURSE, $DB, $OUTPUT;

// Module unavailable for course id 0 or 1
if ($courseid == 1 || !$COURSE = $DB->get_record('course', array('id' => $courseid)))
    print_error('invalidcourse');

require_login($COURSE);

// Set up and display page header
$strtitle = get_string('enrolment','block_cegep');
$navlinks = array();
$navlinks[] = array('name' => $strtitle, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);
print_header("$COURSE->shortname: $strtitle", $COURSE->fullname, $navigation);

// This module only available to teachers and admins
$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
require_capability('moodle/course:update', $context, null, true, 'errormustbeteacher', 'block_cegep');

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

// Get system context for global Admin CEGEP permissions
$syscontext = get_context_instance(CONTEXT_SYSTEM, 1);

// Main switch
switch ($action) {
    case 'enrol' :
        if (is_siteadmin() || has_capability('block/cegep:enroladmin_course', $syscontext))
            cegep_enrol_admin();
        else
            cegep_enrol();
        break;
   case 'unenrol' : 
            cegep_unenrol();
        break;
     case 'enrolprogram' :
        require_capability('block/cegep:enroladmin_program', $syscontext);
        cegep_enrolprogram();
        break;
    case 'unenrolprogram' : 
        require_capability('block/cegep:enroladmin_program', $syscontext);
        if ($num_programenrolments > 0)
            cegep_unenrolprogram();
        else
            notify(get_string('noprogramsenrolled','block_cegep'));
        break;
    default : 
        cegep_studentlist();
        break;
}

// Enrol a coursegroup into a Moodle course (admin function)
function cegep_enrol_admin() {
    global $CFG, $COURSE, $DB, $USER, $OUTPUT, $enroldb, $sisdb;

    $currenttab = 'enrol';
    require('block_cegep_tabs.php');
    
    // Set up enrolment form
    require('block_cegep_enrol_admin_form.php');
    $enrolform = new cegep_enrol_admin_form('block_cegep_enrolment.php?a=enrol&id='.$COURSE->id.'');

    // Go back to course page if cancelled
    if ($enrolform->is_cancelled()) {
        redirect($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
    }
    // Process validated data
    elseif ($data = $enrolform->get_data()) {
   
        // Extract course code info
        if (empty($data->coursecode)) { 
            $coursecode = substr($COURSE->idnumber, 0, strripos($COURSE->idnumber, '_'));
        } else {
            $coursecode = $data->coursecode;
        }

        // Extract term info
        $term = $data->year . $data->semester;

        if (strstr($data->coursegroup, ',')) {
            $coursegroups = explode(',', $data->coursegroup);
        } else {
            $coursegroups = array($data->coursegroup);
        }

        $students_enrolled = array();
        foreach ($coursegroups as $coursegroup) {
            // Enrol coursegroup
            if (!$s = cegep_local_enrol_coursegroup($coursecode, $coursegroup, $term)) {
                print_error('errorimportingstudentlist','block_cegep');
            } else {
                $students_enrolled = array_merge($students_enrolled, $s);
            }

            // Create Moodle group
            if (isset($data->creategroups) && $data->creategroups) {
                $groupname = sprintf('%s gr.%d %s', $coursecode, $coursegroup, cegep_local_term_to_string($term));
                $group = $DB->get_record('groups', array('name' => $groupname), '*');
                if (is_object($group) && isset($group->id)) {
                    $groupid = $group->id;
                }
                else {
                    $group = new stdClass();
                    $group->courseid = $COURSE->id;
                    $group->name = $groupname;
                    $groupid = groups_create_group($group);
                }
                foreach ($s as $username) {
                    $u = $DB->get_record('user', array('username' => $username), 'id', MUST_EXIST);
                    groups_add_member($groupid, $u->id);
                }
            }
        }

        if (!$COURSE->visible && isset($data->makevisible) && $data->makevisible) {
            $COURSE->visible = 1;
            $DB->update_record('course', $COURSE);
        }
        
        // Display nice confirmation with student list and buttons
        echo $OUTPUT->notification(get_string('coursegroupenrolled','block_cegep',implode($students_enrolled,'<br />')),'notifysuccess');
        $linkyes = new moodle_url('block_cegep_enrolment.php', array('a' => 'enrol', 'id' => $COURSE->id));
        $linkno = new moodle_url($CFG->wwwroot.'/course/view.php', array('id' => $COURSE->id));
        echo $OUTPUT->confirm(get_string('enrolanother','block_cegep'), $linkyes, $linkno);
    }
    // Display the enrolment form
    else {
        echo $OUTPUT->heading(get_string('enrol','block_cegep'), 3);
        $enrolform->display();
    }
}

/* 'cegep_enrol'
 *
 * This function is aimed at teachers enrolling a section into
 * a course. It restricts them to enrolling sections
 * that the DB says they are teaching.
 *
 */
function cegep_enrol() {
    global $CFG, $DB, $USER, $COURSE, $OUTPUT, $enroldb, $sisdb;

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

        $students_enrolled = array();

        foreach ($data->coursegroup as $coursegroup_id) {
            // Enrol selected coursegroup(s)
            if (!$se = cegep_local_enrol_coursegroup($coursegroup_id)) {
                print_error('errorimportingstudentlist','block_cegep');
            }

            // Create Moodle group
            if (isset($data->creategroups) && $data->creategroups) {
                $coursegroup = cegep_local_get_coursegroup($coursegroup_id);
                $groupname = sprintf('%s gr.%d %s', $coursegroup['coursecode'], $coursegroup['group'], cegep_local_term_to_string($coursegroup['term']));
                $group = $DB->get_record('groups', array('name' => $groupname), '*');
                if (is_object($group) && isset($group->id)) {
                    $groupid = $group->id;
                }
                else {
                    $group = new stdClass();
                    $group->courseid = $COURSE->id;
                    $group->name = $groupname;
                    $groupid = groups_create_group($group);
                }
                foreach ($se as $username) {
                    $u = $DB->get_record('user', array('username' => $username), 'id', MUST_EXIST);
                    groups_add_member($groupid, $u->id);
                }
            }

            $students_enrolled += $se;
        }

        // Display nice confirmation with student list and buttons
        echo $OUTPUT->notification(get_string('coursegroupenrolled','block_cegep',implode($students_enrolled,'<br />')),'notifysuccess');
        $linkyes = new moodle_url('block_cegep_enrolment.php', array('a' => 'enrol', 'id' => $COURSE->id));
        $linkno = new moodle_url($CFG->wwwroot.'/course/view.php', array('id' => $COURSE->id));
        echo $OUTPUT->confirm(get_string('enrolanother','block_cegep'), $linkyes, $linkno);
    }
    // Display the enrolment form
    else {
        echo $OUTPUT->heading(get_string('enrol','block_cegep'), 3);
        $enrolform->display();
    }

}

// Unenrol a coursegroup from a Moodle course
function cegep_unenrol() {
    global $CFG, $COURSE, $DB, $OUTPUT, $enroldb;
    
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

        $coursegroup_list = implode(', ', $data->coursegroup);

        // Get usernames before removing
        $select = "SELECT `$CFG->enrol_remoteuserfield` FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IN ($coursegroup_list);";

        $usernames = $enroldb->Execute($select)->getRows();

        // If user exists, unassign role right away
        if ($usernames) {
            $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
            $student_role = $DB->get_record('role', array($CFG->enrol_localrolefield => $CFG->block_cegep_studentrole));
            $enrol = enrol_get_plugin('database');
            if ($instance = $DB->get_record('enrol', array('courseid' => $COURSE->id, 'enrol' => 'database'), '*', IGNORE_MULTIPLE)) {
                foreach ($usernames as $username) {
                    if ($student_user = $DB->get_record('user', array($CFG->enrol_localuserfield => $username[$CFG->enrol_remoteuserfield]))) {
                        $enrol->unenrol_user($instance, $student_user->id);
                    }
                }
            }
        }

        // Go through each coursegroup and remove Moodle external enrolment database record
        $delete = "DELETE FROM `$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IN ($coursegroup_list);";
        
        $result = $enroldb->Execute($delete);
        
        if (!$result) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
            print_error('errordeletingenrolment','block_cegep');
            break;
        } else {
            echo $OUTPUT->notification(get_string('coursegroupunenrolled','block_cegep',$enroldb->Affected_Rows()),'notifysuccess');
            echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
        }       
    }
    // Display the enrolment form
    else {
        echo $OUTPUT->heading(get_string('unenrol','block_cegep'), 3);
        $unenrolform->display();
    }
}

// Enrol a program into a Moodle course
function cegep_enrolprogram() {
    global $CFG, $COURSE, $OUTPUT, $enroldb, $sisdb;

    $currenttab = 'enrolprogram';
    require('block_cegep_tabs.php');
    
    // Set up enrolment form
    require('block_cegep_enrolprogram_form.php');
    $enrolprogramform = new cegep_enrolprogram_form('block_cegep_enrolment.php?a=enrolprogram&id='.$COURSE->id.'');

    // Go back to course page if cancelled
    if ($enrolprogramform->is_cancelled()) {
        redirect($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
    }
    // Process validated data
    elseif ($data = $enrolprogramform->get_data()) {

        $program_idyear = $data->program_id . '_' . $data->program_year;
   
        // Fetch records of students enrolled into this program/year from SISDB 
        $select_students = "SELECT * FROM `$CFG->sisdb_name`.`student` WHERE `program_id` = '".$data->program_id."' AND program_year = '".$data->program_year."';";
        $students_rs = $sisdb->Execute($select_students);
        
        if (!$students_rs) {
            print_error('errorimportingstudentlist','block_cegep');
        }

        // Go through each student and insert Moodle external enrolment database record
        $studentlist = '';
        while ($students_rs && !$students_rs->EOF) {
            $student = $students_rs->fields;
            $program_idyear = $data->program_id . "_" . $data->program_year;
            if (!cegep_local_enrol_user($COURSE->idnumber, $student['username'], $CFG->block_cegep_studentrole, NULL, $program_idyear)) {
                trigger_error(get_string('errorimportingstudentlist','block_cegep'), E_USER_ERROR);
                break;
            } else {
                $studentlist .= $student['username'].'<br />';
            }
            $students_rs->MoveNext();
        }
        
        // Display nice confirmation with student list and buttons
        echo $OUTPUT->notification(get_string('programenrolled','block_cegep'),'notifysuccess');
        echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
    }
    // Display the enrolment form
    else {
        echo $OUTPUT->heading(get_string('enrolprogram','block_cegep'), 3);
        $enrolprogramform->display();
    }
}

// Unenrol a program from a Moodle course
function cegep_unenrolprogram() {
    global $CFG, $COURSE, $DB, $OUTPUT, $enroldb;
    
    $currenttab = 'unenrolprogram';
    require('block_cegep_tabs.php');
    
    // Set up enrolment form
    require('block_cegep_unenrolprogram_form.php');
    $unenrolprogramform = new cegep_unenrolprogram_form('block_cegep_enrolment.php?a=unenrolprogram&id='.$COURSE->id.'');

    // Go back to course page if cancelled
    if ($unenrolprogramform->is_cancelled()) {
        redirect($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
    }
    // Process validated data
    elseif ($data = $unenrolprogramform->get_data()) {

        foreach ($data->program as $p) {
            $program_list = "'$p',";
        }
        $program_list = rtrim($program_list, ',');

        // Get usernames before removing
        $select = "SELECT `$CFG->enrol_remoteuserfield` FROM `$CFG->enrol_dbname`.`$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_remoterolefield` = '$CFG->block_cegep_studentrole' AND `program_idyear` IN ($program_list);";

        $usernames = recordset_to_array($enroldb->Execute($select));

        // If user exists, unassign role right away
        if ($usernames) {
            $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
            $student_role = $DB->get_record('role', array($CFG->enrol_localrolefield => $CFG->block_cegep_studentrole));
            foreach ($usernames as $username) {
                if ($student_user = $DB->get_record('user', array($CFG->enrol_localuserfield => $username->username))) {
                    role_unassign($student_role->id, $student_user->id, $context->id, 'enrol_database');
                }
            }
        }

        // Go through each program/year and remove Moodle external enrolment database record
        $delete = "DELETE FROM `$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_remoterolefield` = '$CFG->block_cegep_studentrole' AND `program_idyear` IN ($program_list);";
        
        $result = $enroldb->Execute($delete);
        
        if (!$result) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $delete);
            print_error('errordeletingenrolment','block_cegep');
            break;
        } else {
            echo $OUTPUT->notification(get_string('programunenrolled','block_cegep',$enroldb->Affected_Rows()),'notifysuccess');
            echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$COURSE->id);
        }       
    }
    // Display the unenrolment form
    else {
        echo $OUTPUT->heading(get_string('unenrolprogram','block_cegep'), 3);
        $unenrolprogramform->display();
    }
}

// List all coursegroups and students enrolled in this Moodle course
function cegep_studentlist() {
    global $CFG, $COURSE, $DB, $OUTPUT, $enroldb, $sisdb;

    $currenttab = 'studentlist';
    require('block_cegep_tabs.php');
    
    $body = '';

    $courses = array($COURSE);

    if ($courses) {
        
        foreach ($courses as $c) {
    
            if (empty($c->idnumber)) {
                $c = $DB->get_record('course', array('id'=> $c->id));
            }
    
            $select = "SELECT DISTINCT `program_idyear` FROM `$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$c->idnumber' AND `program_idyear` IS NOT NULL";

            $program_idyears = $enroldb->Execute($select);
           
            $select = "SELECT DISTINCT `coursegroup_id` FROM `$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$c->idnumber' AND `coursegroup_id` IS NOT NULL";
    
            $coursegroups = $enroldb->Execute($select);
            
            if (!$coursegroups && !$program_idyears) {
                trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select);
            }
            else {

                $program_idyear = '';
                $coursegroup_id = '';

                while (!$program_idyears->EOF) {

                    $program_idyear = $program_idyears->fields['program_idyear'];
                    $program_idyear = explode('_', $program_idyear);

                    $program_id = $program_idyear[0];
                    $program_year = $program_idyear[1];

                    // Get title
                    $select = "SELECT `title` FROM `$CFG->sisdb_name`.`program` WHERE `id` = '$program_id'";
                    $program = $sisdb->Execute($select)->fields;

                    $notice = '';

                    $notice .= '<strong>'.get_string('program','block_cegep').'</strong> : ' . $program_id . ' - ' . $program['title'] . '<br />';
                    $notice .= '<strong>'.get_string('programyear','block_cegep').'</strong> : ' . get_string('programyear'.$program_year,'block_cegep');

                    $select = "SELECT * FROM `$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$c->idnumber' AND `program_idyear` = '".$program_id."_".$program_year."' AND `$CFG->enrol_remoterolefield` = '$CFG->block_cegep_studentrole' ORDER BY `$CFG->enrol_remoteuserfield` ASC";
                   
                    $table = cegep_studentlist_enrolmenttable($select);

                    if (count($table->data) > 0) {
                        $notice .= html_writer::table($table);
                        $notice .= '<br /><strong>'.get_string('total').'</strong> : ' . count($table->data);
                    } else { ($notice .= get_string('nostudentsenrolled', 'block_cegep') . "<br /><br />"); }
 
                    $body .= $OUTPUT->box_start();
                    $body .= $OUTPUT->box($notice, 'coursebox');
                    $body .= $OUTPUT->box_end(); 

                    $program_idyears->MoveNext();
                }
                
                while (!$coursegroups->EOF) {
                    
                    $coursegroup_id = $coursegroups->fields['coursegroup_id'];
                    
                    $term = '';
                    $year = '';
            
                    $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `id` = '$coursegroup_id'";
                    $coursegroup = $sisdb->Execute($select)->fields;
            
                    switch (substr($coursegroup['term'],-1)) {
                        case '1' : $term = get_string('winter', 'block_cegep'); break;
                        case '2' : $term = get_string('summer', 'block_cegep'); break;
                        case '3' : $term = get_string('autumn', 'block_cegep'); break;
                    }
                    $year = substr($coursegroup['term'],0,4);
            
                    $notice = '';
            
                    $notice .= '<strong>'.get_string('semester','block_cegep').'</strong> : ' . $term . '&nbsp;' . $year . '<br />';
                    $notice .= '<strong>'.get_string('coursecode','block_cegep').'</strong> : ' . $coursegroup['coursecode'] . '<br />';
                    $notice .= '<strong>'.get_string('coursegroupnumber','block_cegep').'</strong> : ' . $coursegroup['group'] . '<br /><br />';

                    $select = "SELECT * FROM `$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$c->idnumber' AND `coursegroup_id` = '$coursegroup[id]' AND `$CFG->enrol_remoterolefield` = '$CFG->block_cegep_studentrole' ORDER BY `$CFG->enrol_remoteuserfield` ASC";
                   
                    $table = cegep_studentlist_enrolmenttable($select);
           
                    if (count($table->data) > 0) {
                        $notice .= html_writer::table($table);
                        $notice .= '<br /><strong>'.get_string('total').'</strong> : ' . count($table->data);
                    } 
                    else if (is_null($coursegroup['id'])) {
                        $coursegroups->MoveNext();
                        continue;
                    }
                    else { 
                        $notice .= get_string('nocoursegroupsenrolled','block_cegep');
                    }

                    $body .= $OUTPUT->box_start();
                    $body .= $OUTPUT->box($notice, 'coursebox');
                    $body .= $OUTPUT->box_end(); 
                    
                    $coursegroups->MoveNext();
                }
            }
        }
    }
    
    if (!empty($body)) {
        echo $OUTPUT->heading(get_string('studentlisttitle','block_cegep'), 3);
        print($body);
    } else {
        notify(get_string('nocoursegroupsenrolled', 'block_cegep'));
    }
}

function cegep_studentlist_enrolmenttable($select) {
    global $CFG, $COURSE, $DB, $enroldb, $sisdb;

    $table = new html_table();
    $table->class = 'flexible';
    $table->width = '100%';
    $table->cellpadding = 4;
    $table->cellspacing = 3;
    $table->align = array('left','left','left');
    $table->head = array(get_string('username'),get_string('lastname'),get_string('firstname'),get_string('program','block_cegep'));
    $table->data = array();

    // Obtenir la liste des étudiants
    $enrolments_rs = $enroldb->Execute($select);

    // Inscription des étudiants
    $lastnames = array();
    while ($enrolments_rs and !$enrolments_rs->EOF) {
        $select = "SELECT * FROM `$CFG->sisdb_name`.`student` WHERE `username` = '" . $enrolments_rs->fields[$CFG->enrol_remoteuserfield] . "'";
        $student_rs = $sisdb->Execute($select);
        if ($student_rs && $student_rs->RecordCount() == 1) {
            $student_sisdb = $student_rs->fields;
            $student_moodle = $DB->get_record('user', array($CFG->enrol_localuserfield => $student_sisdb['username']));
            if ($student_moodle) {

                $select = "SELECT `title` FROM `$CFG->sisdb_name`.`program` WHERE `id` = '" . $student_sisdb['program_id'] . "'";
                $program = $sisdb->Execute($select)->fields;

                $table->data[] = array('<a href="'.$CFG->wwwroot.'/user/view.php?id='.$student_moodle->id.'" title="'.get_string('accessuserprofile','block_cegep').'">'.$student_sisdb['username'].'</a>', $student_sisdb['lastname'], $student_sisdb['firstname'], $program['title'] . " (" . $student_sisdb['program_id'] . ")");
                $lastnames[] = $student_sisdb['lastname'];
            } else {
                $select = "SELECT `title` FROM `$CFG->sisdb_name`.`program` WHERE `id` = '" . $student_sisdb['program_id'] . "'";
                $program = $sisdb->Execute($select)->fields;

                $table->data[] = array($student_sisdb['username'], $student_sisdb['firstname'], $student_sisdb['lastname'], $program['title'] . " (" . $student_sisdb['program_id'] . ")");
                $lastnames[] = $student_sisdb['lastname'];
            }
        }
        $enrolments_rs->MoveNext();
    }
    array_multisort($lastnames, SORT_ASC, $table->data);
    return $table;
}

echo $OUTPUT->footer();

$enroldb->Close();
$sisdb->Close();

?>
