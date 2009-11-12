<?php

require_once($CFG->libdir.'/formslib.php');

class cegep_request_form extends moodleform {

    const LIGNES = 8;

    function definition() {
        $mform =& $this->_form;

        for ($i = 1; $i <= self::LIGNES; $i++) {
            ${"request$i"} = array();
            ${"request$i"}[] =& $mform->createElement('text', 'coursecode'.$i, null, 'maxlength="8", size="12"');
            ${"request$i"}[] =& $mform->createElement('select', 'num'.$i, null, array(get_string('number','block_cegep'),'1','2','3','4','5','6'));
            ${"request$i"}[] =& $mform->createElement('checkbox', 'meta'.$i, null, get_string('metacourse'));
            $mform->addGroup(${"request$i"}, 'request'.$i, get_string('coursecode','block_cegep'), '&nbsp;&nbsp;', false);
            if ($i != 1) { $mform->addGroupRule('request'.$i, array('coursecode'.$i => array(array(get_string('invalidcoursecode','block_cegep'), 'alphanumeric')))); }
            $mform->setType('coursecode'.$i, PARAM_TEXT);
        }
        $mform->addGroupRule('request1', array(
    		'coursecode1' => array(
                array(get_string('atleastonecoursecode','block_cegep'), 'required'),
                array(get_string('invalidcoursecode','block_cegep'), 'alphanumeric')
        ),
    		'num1' => array(
                array(get_string('specifycoursenumber','block_cegep'), 'required'),
                array(get_string('specifycoursenumber','block_cegep'), 'nonzero')
        )
        ), 'required', null, 2);

        $mform->addElement('textarea', 'comments', get_string('comments','block_cegep'), array('rows'=>'10', 'cols'=>'50'));
        $mform->setType('comments', PARAM_TEXT);

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);
        
        for ($i = 1; $i <= self::LIGNES; $i++) {
	        if (empty($data["coursecode$i"]) XOR empty($data["num$i"]) OR (empty($data["coursecode$i"]) AND empty($data["num$i"]))) {
                if (empty($data["coursecode$i"]) AND !empty($data["num$i"]))
		        	$errors['request'.$i] = get_string('specifycoursecode','block_cegep');
		        elseif (empty($data["num$i"]) AND !empty($data["coursecode$i"]))
		        	$errors['request'.$i] = get_string('specifycoursenumber','block_cegep');
	        	continue;
	        }
            $coursecode = $data["coursecode$i"];
            // Make sure codecode is alphanumeric
            if (!ctype_alnum($coursecode)) {
                $errors['request'.$i] = get_string('courserequest_exists','block_cegep');
            }
            $num = $data["num$i"];
	        $recordcount = count_records_select('cegep_request', "`username` = '$USER->username' AND `coursecodes` LIKE '%\"coursecode\";s:6:\"$coursecode\";%' AND (`state` = 'new' OR `state` = 'waiting')");
	        if ($recordcount > 0 AND empty($data['id'])) {
	        	$errors['request'.$i] = get_string('courserequest_exists','block_cegep');
	        }
	        // Check for duplicate coursecodes in request form
	        $keys = array_keys($data, $data["coursecode$i"]);
            if (count($keys) > 1) {
            	foreach ($keys as $key) {
                	if ($data[str_replace('coursecode','meta',$key)] == $data["meta$i"] AND substr($key,strlen($key)-1,1) != $i)
                		$errors["request$i"] = get_string('courserequest_duplicate','block_cegep');
                }
            }
            // TODO : Vérifier si le code de cours existe dans la BD moodle_enrol
        }
        return $errors;
    }
}

?>
