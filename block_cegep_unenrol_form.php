<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_unenrol_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        
        // Extract enrolled coursegroups info
        $coursegroups = self::get_enrolled_coursegroups(); 
        
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

    private function get_enrolled_coursegroups() {
        global $CFG, $COURSE, $enroldb, $sisdb;
        
        $select = "SELECT DISTINCT `coursegroup_id`, COUNT(`coursegroup_id`) AS num FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IS NOT NULL GROUP BY `coursegroup_id` ORDER BY `coursegroup_id`";

        $coursegroups_rs = $enroldb->Execute($select);

        if (!$coursegroups_rs) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 

        $coursegroup_id = '';
        $coursegroups = array();
        while (!$coursegroups_rs->EOF) {
            $coursegroup_id = $coursegroups_rs->fields['coursegroup_id'];
            $coursegroup_num = $coursegroups_rs->fields['num'];
            $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE id = '$coursegroup_id'";
            $coursegroup = $sisdb->Execute($select)->fields;
            switch (substr($coursegroup['semester'],-1)) {
                case '1' : $semester = get_string('winter', 'block_cegep'); break;
                case '2' : $semester = get_string('summer', 'block_cegep'); break;
                case '3' : $semester = get_string('autumn', 'block_cegep'); break;
            }
            $year = substr($coursegroup['semester'],0,4);
            $coursegroups[$coursegroup_id] = "$coursegroup[coursecode] gr. $coursegroup[group] - $semester $year ($coursegroup_num ".get_string('students', 'block_cegep').')';
            $coursegroups_rs->MoveNext();
        }
        return $coursegroups;

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
