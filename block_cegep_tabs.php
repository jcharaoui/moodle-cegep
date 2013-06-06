<?php
    global $COURSE, $USER, $num_enrolments, $num_programenrolments;

    $row = $tabs = array();
    $row[] = new tabobject('studentlist',
                           'block_cegep_enrolment.php?id='.$COURSE->id,
                           get_string('studentlist','block_cegep'));

    $row[] = new tabobject('enrol',
                           'block_cegep_enrolment.php?a=enrol&id='.$COURSE->id,
                           get_string('enrol','block_cegep')); 

    if ($num_enrolments > 0) {
        $row[] = new tabobject('unenrol',
            'block_cegep_enrolment.php?a=unenrol&id='.$COURSE->id,
            get_string('unenrol','block_cegep'));
    }

    if (is_siteadmin($USER)) {
        $row[] = new tabobject('enrolprogram',
            'block_cegep_enrolment.php?a=enrolprogram&id='.$COURSE->id,
            get_string('enrolprogram','block_cegep'));

        if ($num_programenrolments > 0) {
            $row[] = new tabobject('unenrolprogram',
                'block_cegep_enrolment.php?a=unenrolprogram&id='.$COURSE->id,
                get_string('unenrolprogram','block_cegep'));
        }
    }

    $tabs[] = $row;

    print_tabs($tabs, $currenttab);
?>
