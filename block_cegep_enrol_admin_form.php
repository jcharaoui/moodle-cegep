<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_enrol_admin_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $current = cegep_local_current_term();
        $year = substr($current, 0, 4);
        $semester = substr($current, 4, 1);

        $enrol = array();
        $enrol[] =& $mform->createElement('select', 'semester', null, array('1' => get_string('winter','block_cegep'), '2' => get_string('summer','block_cegep'), '3' => get_string('autumn','block_cegep')));
        $enrol[] =& $mform->createElement('select', 'year', null, array($year-1 => $year-1, $year => $year, $year+1 => $year+1));

        $mform->addGroup($enrol, 'semester', get_string('semester','block_cegep').' :', '&nbsp;', false);
        $mform->addRule('semester', get_string('specifysemester','block_cegep'), 'required');
        $mform->addGroupRule('semester', array(
            'trimestre' => array(
        array(get_string('specifysemester','block_cegep'), 'required')
        ),
            'year' => array(
        array(get_string('specifyyear','block_cegep'), 'required')
        )
        ), 'required', null, 2);
        $mform->setType('semester', PARAM_INT);
        $mform->setType('year', PARAM_INT);

        $mform->addElement('text', 'coursecode', get_string('coursecode','block_cegep').' :', 'size="8", maxlength="8"');
        $mform->setType('coursecode', PARAM_TEXT);
        $mform->addElement('text', 'coursegroup', get_string('coursegroup','block_cegep').' :', 'size="6", maxlength="6"');
        $mform->addRule('coursegroup', get_string('specifycoursegroup','block_cegep'), 'required');
        $mform->addRule('coursegroup', get_string('coursegroupsixnumbersonly','block_cegep'), 'numeric');
        $mform->setType('coursegroup', PARAM_TEXT);

        $mform->SetDefaults(array(
            'year' => $year,
            'semester' => $semester,
        ));

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $COURSE;

        $errors = parent::validation($data, $files);

        $term = "$data[year]$data[semester]";
        if (empty($coursecode) || !has_capability('moodle/site:doanything', $context)) {
            $cc = explode('_', $COURSE->idnumber);
            $coursecode = $cc[0];
        } 
        else {
            $coursecode = $data['coursecode'];
        }
        $coursegroup = $data['coursegroup'];

        if (empty($data['semester']) || empty($data['year'])) {
            $errors['semester'] = get_string('semesterinvalid','block_cegep');
        }
        elseif (empty($coursegroup) || !is_numeric($coursegroup)) {
            $errors['coursegroup'] = get_string('coursegroupinvalid','block_cegep');
        }
        
        if (!empty($errors)) {
            return $errors;
        }
        // Verify if the coursegroup is available in the system
        elseif (!$coursegroup_id = cegep_local_get_coursegroup_id($coursecode, $data['coursegroup'], $term)) {
            $errors['coursegroup'] = get_string('coursegroupunavailable','block_cegep');
        }
        // Verify if the coursegroup is already enrolled into this course
        elseif (self::validate_coursegroup_enrolled($coursegroup_id)) {
            $errors['coursegroup'] = get_string('coursegroupalreadyenrolled','block_cegep');
        }
        // Verify if the coursegroup has students registered
        elseif (!self::validate_coursegroup_students_registered($coursegroup_id)) {
            $errors['coursegroup'] = get_string('coursegrouphasnostudents','block_cegep');
        }
        
        return $errors;
    }

    private function validate_coursegroup_exists($coursecode, $coursegroup, $term) {
        global $CFG, $COURSE, $sisdb;

        $context = get_context_instance(CONTEXT_SYSTEM);
        if (empty($coursecode) || !has_capability('moodle/site:doanything', $context)) {
            $cc = explode('_', $COURSE->idnumber);
            $coursecode = $cc[0];
        }

        $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE `coursecode` = '$coursecode' AND `term` = '$term' AND `group` = '$coursegroup' LIMIT 1";

        $result = $sisdb->Execute($select);
        
        if (!$result) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 
        elseif ($result->RecordCount() < 1) {
            return false;
        }
        else {
            return $result->fields['id'];
        }
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
    
    private function validate_coursegroup_students_registered($coursegroup_id) {
        global $CFG, $COURSE, $sisdb;

        $select = "SELECT * FROM `$CFG->sisdb_name`.`student_enrolment` WHERE `coursegroup_id` = '$coursegroup_id';";
        $result = $sisdb->Execute($select);
        
        if (!$result) {
            trigger_error($sisdb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
            return false;
        } 
        elseif ($result->RecordCount() < 1) {
            return false;
        }
        else {
            return true;
        }
    }
}

?>
