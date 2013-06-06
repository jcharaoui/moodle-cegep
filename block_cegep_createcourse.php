<?php

require_once('../../config.php');
require('lib.php');

global $CFG, $DB, $USER;

$coursecode = optional_param('coursecode', null, PARAM_ALPHANUM);
$term = optional_param('term', null, PARAM_INT);
$redirect = optional_param('redirect', null, PARAM_URL);

require_login();

$access = FALSE;
$is_admin = FALSE;

// Admins and teachers can create courses
if (is_siteadmin($USER))) {
    $access = TRUE;
    $is_admin = TRUE;
}
else {
    // Check if the current user is a teacher enrolled in this course
    $enrolments = cegep_local_get_teacher_enrolments($USER->idnumber, $term);
    foreach ($enrolments as $enrolment) {
        if ($enrolment['coursecode'] == $coursecode) {
            $access = TRUE;
            break;
        }
    }
}

if ($access) {
    if ($newcourseid = cegep_local_create_course($coursecode, $term)) {
        // Enrol current user (teacher) into the new course (except if admin)
        $course = $DB->get_record('course', 'id', $newcourseid);
        if (!$is_admin) {
            cegep_local_enrol_user($course->idnumber, $USER->username, 'editingteacher');
        }
        // Redirect to the enrolment form
        redirect($CFG->wwwroot.'/blocks/cegep/block_cegep_enrolment.php?a=enrol&id=' . $newcourseid, get_string('coursecreatesuccess','block_cegep'));
    } else {
        print_error('errorcreatingcourse','block_cegep');
    }
} else {
    print_error('errormustbeteacher','block_cegep');
}


?>
