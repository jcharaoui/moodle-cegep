<?php 

global $DB;

$roles = $DB->get_records('role');
$options = array();
foreach($roles as $role){
    $options[$role->shortname]=$role->name;
}

$dbtypes = array("access","ado_access", "ado", "ado_mssql", "borland_ibase", "csv", "db2", "fbsql", "firebird", "ibase", "informix72", "informix", "mssql", "mssql_n", "mysql", "mysqlt", "oci805", "oci8", "oci8po", "odbc", "odbc_mssql", "odbc_oracle", "oracle", "postgres64", "postgres7", "postgres", "proxy", "sqlanywhere", "sybase", "vfp");
foreach ($dbtypes as $dbtype) {
   $dboptions[$dbtype] = $dbtype;
}

$settings->add(new admin_setting_configselect('block_cegep_studentrole', get_string('studentrole', 'block_cegep'),
                   get_string('studentrole_help', 'block_cegep'), 'student', $options));

$settings->add(new admin_setting_configtext('block_cegep_name', get_string('cegepname', 'block_cegep'),
                   null, '', PARAM_TEXT));

$settings->add(new admin_setting_configtext('block_cegep_cron_password', get_string('cronpassword', 'block_cegep'),
                   get_string('cronpassword_help', 'block_cegep'), '', PARAM_TEXT));

$settings->add(new admin_setting_configselect('block_cegep_autotopic', get_string('autotopic', 'block_cegep'),
                  get_string('autotopic_help', 'block_cegep'), '0', array(0=>get_string('no'), 1=>get_string('yes'))));

$settings->add(new admin_setting_heading('sisdb', get_string('sisdb_heading', 'block_cegep'), get_string('sisdb_help', 'block_cegep')));
                   
$settings->add(new admin_setting_configselect('sisdb_type', get_string('sisdb_type', 'block_cegep'),
                  null, 'mysql', $dboptions));
                   
$settings->add(new admin_setting_configtext('sisdb_host', get_string('sisdb_host', 'block_cegep'),
                   null, 'localhost', PARAM_TEXT));

$settings->add(new admin_setting_configtext('sisdb_name', get_string('sisdb_name', 'block_cegep'),
                   null, null, PARAM_TEXT));
                   
$settings->add(new admin_setting_configtext('sisdb_user', get_string('sisdb_user', 'block_cegep'),
                   null, null, PARAM_TEXT));

$settings->add(new admin_setting_configpasswordunmask('sisdb_pass', get_string('sisdb_pass', 'block_cegep'),
                   null, null, PARAM_TEXT));
                   
$settings->add(new admin_setting_configselect('sisdb_logging', get_string('sisdb_logging', 'block_cegep'),
                  null, 0, array(0=>get_string('no'), 1=>get_string('yes'))));

$settings->add(new admin_setting_heading('sisdbsource', '', get_string('sisdbsource_help', 'block_cegep')));
                   
$settings->add(new admin_setting_configselect('sisdbsource_type', get_string('sisdbsource_type', 'block_cegep'),
                  null, 'odbc', $dboptions));
                   
$settings->add(new admin_setting_configtext('sisdbsource_host', get_string('sisdbsource_host', 'block_cegep'),
                   null, '', PARAM_TEXT));

$settings->add(new admin_setting_configtext('sisdbsource_name', get_string('sisdbsource_name', 'block_cegep'),
                   null, null, PARAM_TEXT));
                   
$settings->add(new admin_setting_configtext('sisdbsource_user', get_string('sisdbsource_user', 'block_cegep'),
                   null, null, PARAM_TEXT));

$settings->add(new admin_setting_configpasswordunmask('sisdbsource_pass', get_string('sisdbsource_pass', 'block_cegep'),
                   null, null, PARAM_TEXT));


$linklist = '<ul>';
$linklist .='<li><a href="'.$CFG->wwwroot.'/blocks/cegep/sisdb/sisdb.php?op=sync">'.get_string('sisdb_maintenance', 'block_cegep').'</a></li>';
$linklist .= '</ul>';


$settings->add(new admin_setting_heading('sisdb_sync_heading', '', $linklist));
?>
