<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_unenrolprogram_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        
        // Extract enrolled coursegroups info
        $programs = self::get_enrolled_programs(); 
        
        $programs_select = $mform->createElement('select', 'program', null, $programs);
        $programs_select->setMultiple(true);
        
        $mform->addElement($programs_select);
        $mform->addRule('program', get_string('specifyprogram','block_cegep'), 'required');

        $mform->setType('program', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('unenrolbutton', 'block_cegep'));
    }

    function validation($data, $files) {
        
        $errors = parent::validation($data, $files);

        if (empty($data['program'])) {
            $errors['program'] = get_string('programinvalid','block_cegep');
            return $errors;
        }

        // Verify if the program is enrolled into this course
        foreach ($data['program'] as $program_idyear) {
            if (!self::validate_program_enrolled($program_idyear))
                $errors['program'] = get_string('programnotenrolled','block_cegep');
        }
        
        return $errors;
    }

    private function get_enrolled_programs() {
        global $CFG, $COURSE, $enroldb, $sisdb;
        
        $select = "SELECT DISTINCT `program_idyear`, COUNT(`program_idyear`) AS num FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' AND `program_idyear` IS NOT NULL GROUP BY `program_idyear` ORDER BY `program_idyear`";

        $programs_rs = $enroldb->Execute($select);

        if (!$programs_rs) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 

        $program_id = '';
        $program_year = '';
        $programs = array();

        while (!$programs_rs->EOF) {

            $program_idyear = $programs_rs->fields['program_idyear'];
            $program_idyear = explode('_', $program_idyear);
            $program_id = $program_idyear[0];
            $program_year = $program_idyear[1];
            $num_students = $programs_rs->fields['num'];

            $select = "SELECT * FROM `$CFG->sisdb_name`.`program` WHERE id = '$program_id'";
            $program = $sisdb->Execute($select)->fields;
            $programs[$program_id.'_'.$program_year] = "$program_id - $program[title] - ".get_string('programyear'.$program_year,'block_cegep')." ($num_students ".get_string('students', 'block_cegep').')';
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
        else {
            return $result->fields['num'];
        }
    }

}

?>
