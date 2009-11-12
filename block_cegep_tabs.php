<?php
    global $COURSE, $num_enrolments, $num_programenrolments;

    $row = $tabs = array();
    $row[] = new tabobject('studentlist',
                           'block_cegep_enrolment.php?id='.$COURSE->id,
                           get_string('studentlist','block_cegep'));

    if (!$COURSE->metacourse) {                           
        $row[] = new tabobject('enrol',
                               'block_cegep_enrolment.php?a=enrol&id='.$COURSE->id,
                               get_string('enrol','block_cegep')); 
                              
       if ($num_enrolments > 0) {
            $row[] = new tabobject('unenrol',
                                   'block_cegep_enrolment.php?a=unenrol&id='.$COURSE->id,
                                   get_string('unenrol','block_cegep'));
       }

        $context = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('moodle/site:doanything', $context)) {
            $row[] = new tabobject('enrolprogram',
                'block_cegep_enrolment.php?a=enrolprogram&id='.$COURSE->id,
                get_string('enrolprogram','block_cegep'));

            if ($num_programenrolments > 0) {
                $row[] = new tabobject('unenrolprogram',
                    'block_cegep_enrolment.php?a=unenrolprogram&id='.$COURSE->id,
                    get_string('unenrolprogram','block_cegep'));
            }
        }


    }
    
    $tabs[] = $row;

    print_tabs($tabs, $currenttab);
?>
