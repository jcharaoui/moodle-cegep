<?php

error_reporting(E_ALL | E_STRICT);

// as seen in /auth/ldap/auth_ldap_sync_users.php
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot .'/blocks/cegep/lib.php');
require_once($CFG->dirroot .'/blocks/cegep/sisdb/lib.php');

// Get parameters
$op = optional_param('op', null, PARAM_ALPHANUMEXT);
$start_term  = optional_param('start_term', null, PARAM_INT);
$keep_terms  = optional_param('keep_terms', null, PARAM_INT);

set_time_limit(600);

// Check access permissions
require_login();
if (!is_siteadmin($USER)) {
    print_error("Désolé, cette page n'est accessible qu'aux administrateurs du système.");
}

$strtitle = 'SIS DB maintenance';
print_header($strtitle,$strtitle,build_navigation(array(array('name'=>get_string('admincegep','block_cegep'),'link'=>'','type'=>'misc'),array('name'=>get_string('sisdb_maintenance','block_cegep'),'link'=>'','type'=>'misc'))));

cegep_sisdb_init();

// Main switch
switch ($op) {
    case 'prune' :
        (empty($keep_terms)) ? (cegep_sisdb_prune_form()) : (cegep_sisdb_prune($keep_terms));
        break;
    case 'sync' :
        (empty($start_term)) ? (cegep_sisdb_sync_form()) : (cegep_sisdb_sync($start_term));
        break;
    default :
        cegep_sisdb_sync_form();
        break;
}

cegep_sisdb_close();

exit(0);

?>
