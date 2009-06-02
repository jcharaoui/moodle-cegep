<?php

function cegep_delete_course_enrolments($course) {
    global $CFG;
    
    $enroldb = enroldb_connect();

    $delete = "DELETE FROM `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$course->idnumber';";

    $result = $enroldb->Execute($delete);
    
    if (!$result) {
        notify(get_string('errordeletingenrolment','block_cegep'));
        $enroldb->Close();
        return false;
    } else {
        notify(get_string('coursegroupunenrolled','block_cegep',array($enroldb->Affected_Rows())));
        $enroldb->Close();
        return true;
    }
}

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
