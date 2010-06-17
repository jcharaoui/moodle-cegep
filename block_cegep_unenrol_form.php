<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_unenrol_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        
        // Extract enrolled coursegroups info
        $coursegroups = self::get_enrolled_coursegroups_list(); 
        
        $coursegroup_select = $mform->createElement('select', 'coursegroup', null, $coursegroups);
        $coursegroup_select->setMultiple(true);
        
        $mform->addElement($coursegroup_select);
        $mform->addRule('coursegroup', get_string('specifycoursegroup','block_cegep'), 'required');

        $mform->setType('coursegroup', PARAM_INT);

        $this->add_action_buttons(true, get_string('unenrolbutton', 'block_cegep'));
    }

    function validation($data, $files) {
        
        $errors = parent::validation($data, $files);
        
        if (empty($data['coursegroup'])) {
            $errors['coursegroup'] = get_string('coursegroupinvalid','block_cegep');
            return $errors;
        }

        // Verify if the coursegroup is already enrolled into this course
        foreach ($data['coursegroup'] as $coursegroup_id) {
            if (!self::validate_coursegroup_enrolled($coursegroup_id))
                $errors['coursegroup'] = get_string('coursegroupnotenrolled','block_cegep');
        }
        
        return $errors;
    }

    private function get_enrolled_coursegroups_list() {
        global $CFG, $COURSE, $enroldb, $sisdb;
        
        $coursegroups_list = array();

        if (!$coursegroups = cegep_local_get_enrolled_coursegroups($COURSE->idnumber)) {
            return false;
        } 

        foreach ($coursegroups as $coursegroup) {
            $coursegroups_list[$coursegroup['id']] = "$coursegroup[coursecode] #$coursegroup[group] - " . cegep_local_term_to_string($coursegroup['term']) . " ($coursegroup[numberofstudents] ".get_string('students', 'block_cegep').')';
        }
        return $coursegroups_list;

    }
    
    private function validate_coursegroup_enrolled($coursegroup_id) {
        global $CFG, $COURSE, $enroldb;

        $select = "SELECT COUNT(`coursegroup_id`) AS num FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `coursegroup_id` = '$coursegroup_id' LIMIT 1";

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
