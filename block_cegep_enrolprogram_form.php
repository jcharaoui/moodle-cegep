<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_enrolprogram_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $programs = self::get_programs();

        $mform->addElement('select', 'program_id', get_string('program','block_cegep'), $programs);
        $mform->setType('program_id', PARAM_TEXT);
        $mform->addRule('program_id', get_string('specifyprogram','block_cegep'), 'required');

        $mform->addElement('select', 'program_year', get_string('programyear','block_cegep'), array('1' => get_string('programyear1', 'block_cegep'), '2' => get_string('programyear2', 'block_cegep'), '3' => get_string('programyear3', 'block_cegep')));
        $mform->setType('program_year', PARAM_INT);
        $mform->addRule('program_year', get_string('specifyprogramyear','block_cegep'), 'required');

        $this->add_action_buttons();
    }

    function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if (empty($data['program_id'])) {
            $errors['program_id'] = get_string('programinvalid','block_cegep');
        }
         if (empty($data['program_year'])) {
            $errors['program_year'] = get_string('programyearinvalid','block_cegep');
        }
       
        if (!empty($errors)) {
            return $errors;
        }

        $program_idyear = $data['program_id'] . '_' . $data['program_year'];

        // Verify if the program isn't already enrolled in this course
        if (self::validate_program_enrolled($program_idyear)) {
            $errors['program_id'] = get_string('programalreadyenrolled','block_cegep');
        }

        return $errors;
    }

    private function get_programs() {
        global $sisdb;
        
        $select = "SELECT * FROM `program` ORDER BY `id`";

        $programs_rs = $sisdb->Execute($select);

        if (!$programs_rs) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 

        $program_id = '';
        $programs = array();
        while (!$programs_rs->EOF) {
            $program_id = $programs_rs->fields['id'];
            $program_title = $programs_rs->fields['title'];
            $programs[$program_id] = "$program_id - $program_title";
            $programs_rs->MoveNext();
        }
        return $programs;

    }

    private function validate_program_enrolled($program_idyear) {
        global $CFG, $COURSE, $enroldb;

        $select = "SELECT COUNT(`program_idyear`) AS num FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `program_idyear` = '$program_idyear' LIMIT 1";

        $result = $enroldb->Execute($select);
        
        if (!$result) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 
        else
            return $result->fields['num'];
    }

}

?>
