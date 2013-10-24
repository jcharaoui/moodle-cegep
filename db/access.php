<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'block/cegep:enroladmin_course' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),
    'block/cegep:enroladmin_program' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    )
);

?>

