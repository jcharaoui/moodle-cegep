<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_enrol_form extends moodleform {

    function definition() {
        global $COURSE, $USER;
    
        $mform =& $this->_form;
        
        if (!$coursegroups = cegep_local_get_coursegroups($COURSE->idnumber, $USER->idnumber)) {
            notify(get_string('nocoursegroupsavailable','block_cegep'));
        } else {
            foreach ($coursegroups as $coursegroup) {
                ($coursegroup['numberofstudents'] < 1) ? ($disabled = array( 'disabled' => 'disabled' )) : ($disabled = array());
                $mform->addElement('checkbox', "coursegroup_$coursegroup[id]", null, "$coursegroup[coursecode] #$coursegroup[group] - " . cegep_local_term_to_string($coursegroup['term']) . " ($coursegroup[numberofstudents] ".get_string('students', 'block_cegep').')', $disabled);
            }
            $this->add_action_buttons();
        }
    }

    function validation($data, $files) {

        $errors = parent::validation($data, $files);
        $coursegroup_ids = array();
        
        foreach ($data as $key => $value) {
            if (substr($key, 0, 11) == 'coursegroup' && $value == 1) {
                $cg = explode('_', $key);
                $coursegroup_id = $cg[1];
                
                if (!is_numeric($coursegroup_id)) {
                    $errors["coursegroup_$coursegroup_id"] = get_string('coursegroupunavailable','block_cegep');
                }
                // Verify if the coursegroup is available in the system
                elseif (!$coursegroup_id = self::validate_coursegroup_exists($coursegroup_id)) {
                    $errors["coursegroup_$coursegroup_id"] = get_string('coursegroupunavailable','block_cegep');
                }
                // Verify if the coursegroup is already enrolled into this course
                elseif (self::validate_coursegroup_enrolled($coursegroup_id)) {
                    $errors["coursegroup_$coursegroup_id"] = get_string('coursegroupalreadyenrolled','block_cegep');
                }
                // Verify if the coursegroup has students registered
                elseif (!self::validate_coursegroup_students_registered($coursegroup_id)) {
                    $errors["coursegroup_$coursegroup_id"] = get_string('coursegrouphasnostudents','block_cegep');
                }

                array_push($coursegroup_ids, $coursegroup_id);
            }
        }

        if (count($coursegroup_ids) < 1) {
            $errors[] = 1;
            notify(get_string('nocoursegroupselected','block_cegep'));
        }

        return $errors;
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
