<?php

require_once('../../../config.php');
require_once('../../../auth/ldap/auth.php');
require_once('../lib.php');
require_once('request_form.php');

require_login();

global $CFG, $DB, $USER;

$authldap = new auth_plugin_ldap;

if (!is_siteadmin($USER) && !$authldap->ldap_isgroupmember($USER->username, 'OU=IT,OU=cmaisonneuve,DC=cmaisonneuve,DC=qc,DC=ca;OU=Users,OU=Dev_pedagogique,OU=Admin,OU=cmaisonneuve,DC=cmaisonneuve,DC=qc,DC=ca')) {
    print_error(get_string('errormustbeadmin','block_cegep'));
}

define('REQUEST_STATES',serialize(array('new','waiting','accepted','denied','delete','modify')));

$id      = optional_param('id', null, PARAM_INT);
$state   = optional_param('state', null, PARAM_ALPHA);

$requestform = new cegep_request_form();

$strtitle = get_string('coursespending');
$strheading = get_string(((!empty($reject)) ? 'coursereject' : 'coursespending'));
print_header($strtitle,$strheading,build_navigation(array(array('name'=>$strheading,'link'=>'','type'=>'misc'))));

if ($requestform->is_cancelled()){

    cegep_requestadmin_display();

} elseif ($requestform->is_submitted()) {

    if (!$requestform->is_validated()) {
        $request = $DB->get_record('cegep_request', array('id' => $requestform->get_submitted_data()->id));
        $user = $DB->get_record('user', array('username' => $request->username));
        print("<fieldset style='width:80%;margin-left:auto;margin-right:auto;margin-top:16px;padding:12px;'><legend><strong>Modification de la demande de cours de : ".fullname($user)."</strong></legend>");
        $requestform->display();
        print("</fieldset><br /><br />");

    } else {
        $data = $requestform->get_data();
        $upddata = new stdClass();

        $courses = array();
        for ($i = 1; $i <= constant('cegep_request_form::LIGNES'); $i++) {
            if (!empty($data->{'coursecode'.$i}) && !empty($data->{'num'.$i})) {
                $courses[] = array('coursecode' => strtoupper($data->{'coursecode'.$i}), 'num' => $data->{'num'.$i}, 'meta' => $data->{'meta'.$i});
            }
        }
        
        $upddata->id = $data->id;
        $upddata->username = $data->username;
        $upddata->coursecodes = serialize($courses);
        $upddata->comments = $data->comments;

        if ($DB->update_record('cegep_request', $upddata)) {
            notify(get_string('courserequest_modsuccess','block_cegep').'<br />','notifysuccess');
            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
        } else {
            notify(get_string('courserequest_modfailed','block_cegep').'<br />');
            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
        }
    }

} elseif (!empty($state)) {

    if ($request = $DB->get_record('cegep_request', array('id' => $id)) AND in_array($state, unserialize(REQUEST_STATES))) {

        switch ($state) {

            case 'accepted' :
                $course = unserialize($request->coursecodes);
                foreach ($course as $c) {
                    $coursecode = strtoupper($c['coursecode']);
                    ($c['meta'] == 1) ? ($meta = 1) : ($meta = 0);
                    for ($i = 1; $i <= $c['num']; $i++) {
                        if ($courseid = cegep_local_create_course($coursecode, $meta)) {
                            $courseidnumber = $DB->get_record('course', array('id' => $courseid))->idnumber;
                            if (!cegep_local_enrol_user($courseidnumber, $request->username, 'editingteacher', NULL, NULL, $request->id)) {
                                print_error("Une erreur s'est produite lors de l'inscription au cours!");
                                break;
                            }
                        } else {
                            print_error("Une erreur s'est produite lors de la création des cours!");
                            break;
                        }
                    }
                }
                $request->state = $state;
                if ($DB->update_record('cegep_request', $request)) {
                    notify(get_string('courserequest_createsuccess','block_cegep').'<br />','notifysuccess');
                    print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                } else {
                    notify(get_string('courserequest_createfailed','block_cegep').'<br />');
                    print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                }
                /*
                 $messagetext = get_string('acceptedemailmessage','demande_gest');
                $demandeur_user = get_record('user','username',$demande->demandeur);
                if (is_object($demandeur_user)) { email_to_user($demandeur_user,'moodle@cmaisonneuve.qc.ca','Moodle : Demande de cours approuvée',$messagetext);
                */ 
                break;

                    case 'modify' :
                        $user = $DB->get_record('user', array('username' => $request->username));
                        if (is_object($user)) { $username = fullname($user); }
                        else { $username = $request->username; }
                        $courses = unserialize($request->coursecodes);
                        $requestform_data = new stdClass;
                        for ($i = 0; $i < count($courses); $i++) {
                            $requestform_data->{'coursecode'.($i+1)} = $courses[$i]['coursecode'];
                            $requestform_data->{'num'.($i+1)} = $courses[$i]['num'];
                            $requestform_data->{'meta'.($i+1)} = $courses[$i]['meta'];
                        }
                        $requestform_data->comments = $request->comments;
                        $requestform_data->id = $request->id;
                        $requestform_data->username = $request->username;
                        $requestform->set_data($requestform_data);
                        print("<fieldset style='width:80%;margin-left:auto;margin-right:auto;margin-top:16px;padding:12px;'><legend><strong>Modification de la demande de cours de : $username</strong></legend>");
                        $requestform->display();
                        print("</fieldset><br /><br />");
                        break;

                    case 'delete' :
                        if ($DB->delete_records('cegep_request', array('id' => $request->id))) {
                            notify(get_string('courserequest_delsuccess','block_cegep').'<br />','notifysuccess');
                            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                        } else {
                            notify(get_string('courserequest_modfailed','block_cegep').'<br />');
                            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                        }
                        break;

                    case 'denied'  :
                        $request->etat = $state;
                        if ($DB->update_record('cegep_request', $request)) {
                            notify(get_string('courserequest_modsuccess','block_cegep').'<br />','notifysuccess');
                            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                        } else {
                            notify(get_string('courserequest_modfailed','block_cegep').'<br />');
                            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                        }
                        // TODO : Enable and test email feedback
                        /*
                        $messagetext = get_string('rejectedemailmessage','demande_gest');
                        email_to_user($USER,'moodle@cmaisonneuve.qc.ca','Moodle : Demande de cours approuvée',$messagetext);
                        */
                        break;

                    case 'waiting' :
                    case 'new' :
                        $request->state = $state;
                        if ($DB->update_record('cegep_request', $request)) {
                            notify(get_string('courserequest_modsuccess','block_cegep').'<br />','notifysuccess');
                            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                        } else {
                            notify(get_string('courserequest_modfailed','block_cegep').'<br />');
                            print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
                        }
                }
        }
    }

    else {

        cegep_requestadmin_display();

    }

    print_footer();


    function cegep_requestadmin_display() {

        global $CFG, $DB;

        if ($requests = $DB->get_records_select('cegep_request', "`state` = 'new' OR `state` = 'waiting'", array('created DESC'))) {

            $table = new stdClass;
            $table->width = '100%';
            $table->cellpadding = 4;
            $table->cellspacing = 3;
            $table->align = array('left','left','left','left','left');
            $table->head = array(get_string('courserequest_by','block_cegep'),get_string('courserequest_since','block_cegep'),get_string('courserequest_courses','block_cegep'),get_string('comments','block_cegep'),get_string('courserequest_state','block_cegep'));
            $table->data = array();
            $table_new = clone $table;
            $table_waiting = clone $table;
            $states = unserialize(REQUEST_STATES);

            foreach ($requests as $request) {

                $user = $DB->get_record('user', array('username' => $request->username));
                $strcourse = '';
                foreach (unserialize($request->coursecodes) as $course) {
                    ($course['meta']) ? ($meta = 'm') : ($meta = '');
                    $strcourse .= "<strong>$course[coursecode]</strong> x$course[num]$meta&nbsp;&nbsp;";
                }
                $form_state = "<form name='frm$request->id' method='get'><select name='state' onChange='javascript:document.frm$request->id.submit();'>\n";
                foreach ($states as $state) {
                    $$state = '';
                }
                ${$request->state} = ' selected';
                foreach ($states as $state) {
                    $form_state .= "<option value='$state'${$state}>".get_string('courserequest_state'.$state,'block_cegep').'</option>\n';
            }
            $form_state .= "</select>\n<input type='hidden' name='id' value='$request->id' /></form>";
            (is_object($user)) ? ($user_link = "<a href=\"../user/view.php?id=$user->id&amp;course=".get_site()->id.'\">'.fullname($user).'</a>') : ($user_link = $request->username);
            ${'table_'.$request->state}->data[] = array($user_link,format_time($request->created - time()),format_string($strcourse),format_text($request->comments,FORMAT_PLAIN),$form_state);
        }

        print("<fieldset style='width:80%;margin-left:auto;margin-right:auto;margin-top:16px;padding:12px;'><legend><strong>".get_string('courserequest_new','block_cegep')."</strong></legend>");
        (count($table_new->data) > 0) ? (print_table($table_new)) : (print_box(get_string('courserequest_nonew','block_cegep').'<br /><br />'));
        print("</fieldset><br /><br />");
        print("<fieldset style='width:80%;margin-left:auto;margin-right:auto;margin-top:16px;padding:12px;'><legend><strong>".get_string('courserequest_waiting','block_cegep')."</strong></legend>");
        (count($table_waiting->data) > 0) ? (print_table($table_waiting)) : (print_box(get_string('courserequest_nowaiting','block_cegep').'<br /><br />'));
    }

    else {

        notice(get_string('courserequest_nothing','block_cegep').'<br /><br />', $CFG->wwwroot);

    }
}

?>
