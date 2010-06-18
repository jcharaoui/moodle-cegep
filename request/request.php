<?php

require_once('../../../config.php');
require_once('../../../auth/ldap/auth.php');
require_once('request_form.php');

require_login();

// Check if capability exists, create it if not
/*if (!get_record('capabilities', 'name', 'moodle/site:ddcrequest')) {
 update_capabilities('ddc');
 }
if (!has_capability('moodle/site:ddcrequest', get_context_instance(CONTEXT_SYSTEM))) {
*/

$authldap = new auth_plugin_ldap;
$context = get_context_instance(CONTEXT_SYSTEM);

if (!has_capability('moodle/site:doanything', $context) && !$authldap->ldap_isgroupmember($USER->username, 'CN=g_profs,OU=Groupes,OU=Pedagogie,OU=cmaisonneuve,DC=cmaisonneuve,DC=qc,DC=ca;OU=Users,OU=IT,OU=cmaisonneuve,DC=cmaisonneuve,DC=qc,DC=ca;OU=Users,OU=Dev_pedagogique,OU=Admin,OU=cmaisonneuve,DC=cmaisonneuve,DC=qc,DC=ca')) {
     print_error(get_string('errormustbeteacher','block_cegep'));
}

$requestform = new cegep_request_form();

if (has_capability('moodle/site:doanything', $context)) {
    $requestform->_form->insertElementBefore($requestform->_form->createElement('text', 'username', get_string('username'), 'maxlength="16"'), 'request1');
    $requestform->_form->setType('username', PARAM_TEXT);
}

$strtitle = get_string('courserequest','block_cegep');
$navlinks = array();
$navlinks[] = array('name' => $strtitle, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);

print_header($strtitle, $strtitle, $navigation, $requestform->focus());

if ($requestform->is_cancelled()){

    redirect($CFG->wwwroot);

} elseif ($data = $requestform->get_data()) {

    $insdata = new stdClass();
    
    $context = get_context_instance(CONTEXT_SYSTEM);
    if (has_capability('moodle/site:doanything', $context) && !empty($data->username)) {
        $insdata->username = $data->username;
    } else {
        $insdata->username = $USER->username;
    }
    $insdata->comments = $data->comments;
    $insdata->created = time();

    $cours = array();
    for ($i = 1; $i <= constant('cegep_request_form::LIGNES'); $i++) {
        if (!empty($data->{'coursecode'.$i}) && !empty($data->{'num'.$i})) {
            $cours[] = array('coursecode' => strtoupper($data->{'coursecode'.$i}), 'num' => $data->{'num'.$i}, 'meta' => $data->{'meta'.$i});
        }
    }
    $insdata->coursecodes = serialize($cours);

    if (insert_record('cegep_request', $insdata)) {
        notify(get_string('courserequest_success','block_cegep'),'notifysuccess');
        print_continue($CFG->wwwroot);
    } else {
        notify(get_string('courserequest_failed','block_cegep'));
        print_continue($CFG->wwwroot);
    }

} else {

    print_box_start('informationbox boxaligncenter boxwidthnormal');
    print(get_string('courserequest_instructions','block_cegep'));
    print_box_end();

    $requestform->display();
}

print_footer();

?>
