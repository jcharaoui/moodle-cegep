<?php

if (file_exists($CFG->dirroot .'/blocks/cegep/lib_'. $CFG->block_cegep_name .'.php')) {
    require_once($CFG->dirroot .'/blocks/cegep/lib_'. $CFG->block_cegep_name .'.php');
}

/**
 * Return current trimester in yyyyt format
 */
function cegep_local_current_trimester() {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_current_trimester')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_current_trimester');
    } else {
        return 1;
    }
}

/* Converts SISDBSOURCE (eg: Clara/Datamart) data format into SISDB standard format
 */
function cegep_local_sisdbsource_decode($field, $data) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_sisdbsource_decode')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_sisdbsource_decode', $field, $data);
    } else {
        return 1;
    }
}

/**
 * Return the Moodle category id according to course type
 */
function cegep_local_course_category($category) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_course_category')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_course_category', $category);
    } else {
        return 1;
    }
}

/**
 * Return the SIS db source select to execute (students)
 * This select statement must return the following columns :
 * - CourseCampus
 * - CourseTrimester
 * - CourseNumber
 * - CourseTitle 
 * - CourseGroup
 * - StudentNo
 * - StudentFirstName
 * - StudentLastName
 * - StudentProgram
 * - StudentProgramName
 * - StudentProgramYear
 */
function cegep_local_sisdbsource_select_students($trimester) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select_students')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select_students', $trimester);
    } else {
        return 1;
    }
}

/**
 * Return the SIS db source select to execute (teachers)
 * This select statement must return the following columns :
 * - TeacherNumber
 * - CourseNumber
 * - CourseGroup
 * - CourseTerm
 */
function cegep_local_sisdbsource_select_teachers($trimester) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select', $trimester);
    } else {
        return 1;
    }
}

/**
 * Create course based on Moodle defaults and return generated idnumber
 */
function cegep_local_create_course($coursecode, $meta) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_create_course')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_create_course', $coursecode, $meta);
    } else {
        return 1;
    }
}

/**
 * Create an enrolment in the external enrolments database
 */
function cegep_local_create_enrolment($courseidnumber, $username, $request_id) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_create_enrolment')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_create_enrolment', $courseidnumber, $username, $request_id);
    } else {
        return 1;
    }
}

/*
 * Delete all course enrolments from external enrolments DB
 * (ie. when a course is deleted)
 */
function cegep_delete_course_enrolments($course) {
    global $CFG;
    
    $enroldb = enroldb_connect();

    $delete = "DELETE FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$course->idnumber';";

    $result = $enroldb->Execute($delete);
    
    if (!$result) {
        notify(get_string('errordeletingenrolment','block_cegep'));
        $enroldb->Close();
        return false;
    } else {
        notify(get_string('coursegroupunenrolled','block_cegep',array($enroldb->Affected_Rows())));
        $enroldb->Close();
        return true;
    }
}

function sisdbsource_connect() {
    global $CFG;
    return cegep_dbconnect($CFG->sisdbsource_type, $CFG->sisdbsource_host, $CFG->sisdbsource_name, $CFG->sisdbsource_user, $CFG->sisdbsource_pass);
}

function sisdb_connect() {
    global $CFG;
    return cegep_dbconnect($CFG->sisdb_type, $CFG->sisdb_host, $CFG->sisdb_name, $CFG->sisdb_user, $CFG->sisdb_pass);
}

function enroldb_connect() {
    global $CFG;
    return cegep_dbconnect($CFG->enrol_dbtype, $CFG->enrol_dbhost, $CFG->enrol_dbname, $CFG->enrol_dbuser, $CFG->enrol_dbpass);
}

function cegep_dbconnect($type, $host, $name, $user, $pass) {

    // Try to connect to the external database (forcing new connection)
    $db = &ADONewConnection($type);
    if ($db->Connect($host, $user, $pass, $name, true)) {
        $db->SetFetchMode(ADODB_FETCH_ASSOC); ///Set Assoc mode always after DB connection
        return $db;
    } else {
        trigger_error("Error connecting to DB backend with: "
                      . "$host, $user, $pass, $name");
        return false;
    }
}

?>
