<?php

include_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/blocks/cegep/lib.php');

class block_cegep extends block_list {
    function init() {
        $this->title = get_string('admincegep', 'block_cegep');
        $this->version = 20100527;
    }
    
    function has_config() {return true;}

    function get_content() {
        global $USER, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        // Show MyMoodle block
        if (strpos($_SERVER['PHP_SELF'], '/my') == true) {
            $this->content->items = cegep_local_get_create_course_buttons();
            $this->content->icons = null; // don't show any bullet
        }
        // Show course block
        elseif (has_capability('moodle/course:update', $context)) {
            $this->content->items[] = '<a href="../blocks/cegep/block_cegep_enrolment.php?id='.$COURSE->id.'">'.get_string('enrolment', 'block_cegep').'</a>';
            $this->content->icons[] = "&bull;";
        }
        $this->content->footer = '';
        return $this->content;
    }
}

?>
