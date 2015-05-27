<?php

require_once('../../config.php');
require('lib.php');
require('block_cegep_createcourse_form.php');

global $CFG, $DB, $USER, $PAGE, $OUTPUT;

$coursecode = optional_param('coursecode', null, PARAM_ALPHANUM);

require_login();

$coursetitle = cegep_local_get_course_title($coursecode);

$createcourse_form = new cegep_createcourse_form(null, array('coursecode' => $coursecode, 'coursetitle' => $coursetitle));

if ($createcourse_form->is_cancelled()){

    redirect($CFG->wwwroot);

}
elseif ($data = $createcourse_form->get_data()) {
    require_once('../../course/lib.php');

    $course = cegep_local_new_course_template($data->coursecode);
    $course->shortname = $course->idnumber;
    $course->fullname = $data->fullname;
    $course->visible = $data->visible;

    $course = create_course($course);

    if (is_object($course) && $course->id > 0) {
        // Enrol current user (teacher) into the new course (except if admin)
        if (!is_siteadmin($USER)) {
            cegep_local_enrol_user($course->idnumber, $USER->username, 'editingteacher');
        }
        // Redirect to the enrolment form
        redirect($CFG->wwwroot.'/blocks/cegep/block_cegep_enrolment.php?a=enrol&id=' . $course->id, get_string('coursecreatesuccess','block_cegep'));
    } else {
        print_error('errorcreatingcourse','block_cegep');
    }

}
else {

    $PAGE->navbar->add(get_string('coursecreate','block_cegep'));
    $PAGE->set_heading('heading');
    $PAGE->set_title(get_string('coursecreate','block_cegep'));
    echo $OUTPUT->header();

    $createcourse_form->display();

    echo $OUTPUT->footer();
}

?>
