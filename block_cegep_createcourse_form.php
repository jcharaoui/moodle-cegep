<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_createcourse_form extends moodleform {

    const LIGNES = 8;

    function definition() {
        global $PAGE;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'coursecode', $this->_customdata['coursecode']);
        $mform->setType('coursecode', PARAM_ALPHANUM);
        $mform->addRule('coursecode', get_string('required'), 'required');

        $mform->addElement('static', '_coursecode', get_string('coursecode', 'block_cegep'), $mform->_elements[0]->_attributes['value']);

        $mform->addElement('text', 'fullname', get_string('fullnamecourse').' :', 'size="64", maxlength="255"');
        $mform->setDefault('fullname', $this->_customdata['coursetitle']);
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required');

        $choices = array();
        $choices['0'] = get_string('hide');
        $choices['1'] = get_string('show');
        $mform->addElement('select', 'visible', get_string('visible'), $choices);
        $mform->addHelpButton('visible', 'visible');
        $mform->setDefault('visible', 0);
        $mform->addRule('visible', get_string('required'), 'required');

        $mform->addElement('html', "<p>Ce cours sera créé immédiatement lorsque vous cliquez le bouton <strong>Enregistrer</strong>.</p><p>Vous serez ensuite redirigé vers le formulaire d'inscriptions des groupes-cours.</p>");

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $DB, $USER;
        $errors = parent::validation($data, $files);

        $allowed = FALSE;

        // Admins and teachers can create courses
        if (is_siteadmin($USER)) {
            $allowed = TRUE;
        }
        else {
            // Check if the current user is a teacher enrolled in this course
            $enrolments = cegep_local_get_teacher_enrolments($USER->idnumber, cegep_local_current_term());
            foreach ($enrolments as $enrolment) {
                if ($enrolment['coursecode'] == $data['coursecode']) {
                    $allowed = TRUE;
                    break;
                }
            }
        }

        if (!$allowed) {
            $errors['_coursecode'] = get_string('errormustbeteachercourse', 'block_cegep');
        }

        return $errors;
    }
}

?>
