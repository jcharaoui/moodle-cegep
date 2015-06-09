<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_enrol_form extends moodleform {

    function definition() {
        global $COURSE, $USER;
    
        $mform =& $this->_form;

        // Extract enrolled coursegroups info
        $coursegroups = self::get_available_coursegroups_list();
        
        $coursegroup_select = $mform->createElement('select', 'coursegroup', null, $coursegroups);

        $coursegroup_select->setMultiple(true);

        $mform->addElement($coursegroup_select);
        $mform->addRule('coursegroup', get_string('specifycoursegroup','block_cegep'), 'required');
        $mform->setType('coursegroup', PARAM_INT);

        $this->add_action_buttons();
    }

    function validation($data, $files) {

        $errors = parent::validation($data, $files);

         if (empty($data['coursegroup'])) {
            $errors['coursegroup'] = get_string('nocoursegroupselected','block_cegep');
            return $errors;
        }

        // Verify if the coursegroup is already enrolled into this course
        foreach ($data['coursegroup'] as $coursegroup_id) {
            if (!is_numeric($coursegroup_id)) {
                $errors["coursegroup"] = get_string('coursegroupunavailable','block_cegep');
            }
            // Verify if the coursegroup is available in the system
            elseif (!$coursegroup_id = self::validate_coursegroup_exists($coursegroup_id)) {
                $errors["coursegroup"] = get_string('coursegroupunavailable','block_cegep');
            }
            // Verify if the coursegroup is already enrolled into this course
            elseif (self::validate_coursegroup_enrolled($coursegroup_id)) {
                $errors["coursegroup"] = get_string('coursegroupalreadyenrolled','block_cegep');
            }
            // Verify if the coursegroup has students registered
            elseif (!self::validate_coursegroup_students_registered($coursegroup_id)) {
                $errors["coursegroup"] = get_string('coursegrouphasnostudents','block_cegep');
            }

            array_push($coursegroup_ids, $coursegroup_id);
        }

        return $errors;
    }

    private function get_available_coursegroups_list() {
        global $COURSE, $USER;

        $coursegroups_list = array();

        if (!$coursegroups = cegep_local_get_unenrolled_coursegroups($COURSE->idnumber, $USER->idnumber)) {
            return false;
        }
        foreach ($coursegroups as $coursegroup) {
            $coursegroups_list[$coursegroup['id']] = "$coursegroup[coursecode] #$coursegroup[group] - " . cegep_local_term_to_string($coursegroup['term']) . " ($coursegroup[numberofstudents] ".get_string('students', 'block_cegep').')';
        }
        return $coursegroups_list;

    }

    private function validate_term($term) {
        global $CFG, $sisdb;

        $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `term` = '$term' LIMIT 1";

        $result = $sisdb->Execute($select);
        
        if (!$result) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 
        elseif ($result->RecordCount() < 1)
            return false;
        else
            return true;
    }

    private function validate_coursegroup_exists($coursegroup_id) {
        global $CFG, $COURSE, $sisdb;

        $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `id` = '$coursegroup_id';";
        $result = $sisdb->Execute($select);
        
        if (!$result) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } elseif ($result->RecordCount() < 1)
            return false;
        else
            return $result->fields['id'];
    }

    private function validate_coursegroup_enrolled($coursegroup_id) {
        global $CFG, $COURSE, $enroldb;

        $select = "SELECT COUNT(`coursegroup_id`) AS num FROM `$CFG->enrol_remoteenroltable` WHERE `$CFG->enrol_remotecoursefield` = '$COURSE->idnumber' AND `coursegroup_id` = '$coursegroup_id' LIMIT 1";

        $result = $enroldb->Execute($select);
        
        if (!$result) {
            trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 
        else
            return $result->fields['num'];
    }

    private function validate_coursegroup_students_registered($coursegroup_id) {
        global $CFG, $COURSE, $sisdb;

        $select = "SELECT * FROM `$CFG->sisdb_name`.`student_enrolment` WHERE `coursegroup_id` = $coursegroup_id;";
        $result = $sisdb->Execute($select);
        
        if (!$result) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } elseif ($result->RecordCount() < 1)
            return false;
        else
            return true;
    }

}

?>
