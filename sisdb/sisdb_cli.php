<?php

define('CLI_SCRIPT', true);

error_reporting(E_ALL | E_STRICT);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot .'/blocks/cegep/lib.php');
require_once($CFG->dirroot .'/blocks/cegep/sisdb/lib.php');

list($options, $unrecognized) = cli_get_params(array('sync' => false,
                                                     'prune' => false,
                                                     'help' => false),
                                               array('s' => 'sync',
                                                     'p' => 'prune',
                                                     'h' => 'help')
                                               );

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Execute SISDB maintenance.

Options:
-s, --sync            Sync internal SIS BD with source.
-p, --prune           Prune old SIS information (1 year +)
-h, --help            Print out this help
";

    echo $help;
    die;
}

set_time_limit(600);

cegep_sisdb_init();

if ($options['prune']) {
    cegep_sisdb_prune(3);
}

if ($options['sync']) {
    cegep_sisdb_sync(cegep_local_current_term());
}

cegep_sisdb_close();

exit(0);
