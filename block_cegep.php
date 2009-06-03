<?php

include_once($CFG->dirroot . '/course/lib.php');

class block_cegep extends block_list {
    function init() {
        $this->title = get_string('admincegep', 'block_cegep');
        $this->version = 20090603;
    }
    
    function has_config() {return true;}

    function get_content() {
        global $USER, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if (!has_capability('moodle/course:update', $context)) {  // Just return
            return $this->content;
        }

        $metachild = is_object(get_record('course_meta', 'child_course', $COURSE->id));
        $this->content = new stdClass;

		$this->content->items[] = '<a href="../blocks/cegep/block_cegep_enrolment.php?id='.$COURSE->id.'">'.get_string('enrolment', 'block_cegep').'</a>';
		$this->content->icons[] = "&bull;";

        $this->content->footer = '';

        return $this->content;
    }
}

?>
