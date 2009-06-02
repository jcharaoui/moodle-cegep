<?php

// NOTE : Events is still in early stages, if it fails
// once for some reason, it will not be run again until
// Moodle enables event queue processing in cron...

$handlers = array (
    'course_deleted ' => array (
         'handlerfile'      => '/blocks/cegep/lib.php',
         'handlerfunction'  => 'cegep_delete_course_enrolments',
         'schedule'         => 'instant'
     )
);

?>
