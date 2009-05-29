<?php 

$roles=get_records('role');
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
                   
?>
