<?php


function sisdb_connect() {
    global $CFG;
    
    return cegep_dbconnect($CFG->sisdb_type, $CFG->sisdb_host, $CFG->sisdb_name, $CFG->sisdb_user, $CFG->sisdb_pass);
}

function enroldb_connect() {
    global $CFG;
    
    return cegep_dbconnect($CFG->enrol_dbtype, $CFG->enrol_dbhost, $CFG->enrol_dbname, $CFG->enrol_dbuser, $CFG->enrol_dbpass);
}

function cegep_dbconnect($type, $host, $name, $user, $pass) {

    // Try to connect to the external database (forcing new connection)
    $sisdb = &ADONewConnection($type);
    if ($sisdb->Connect($host, $user, $pass, $name, true)) {
        $sisdb->SetFetchMode(ADODB_FETCH_ASSOC); ///Set Assoc mode always after DB connection
        
        return $sisdb;
    } else {
        trigger_error("Error connecting to DB backend with: "
                      . "$host, $user, $pass, $name");
        return false;
    }
}

?>
