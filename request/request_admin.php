<?php

require_once('../../../config.php');
require_once('../../../auth/ldap/auth.php');
require_once('../lib.php');
require_once('request_form.php');

require_login();

// Check if capability exists, create it if not
/*if (!get_record('capabilities', 'name', 'moodle/site:ddcmanage')) {
 update_capabilities('ddc');
 }
 require_capability('moodle/site:ddcmanage', get_context_instance(CONTEXT_SYSTEM));
 */

global $CFG, $USER;

$authldap = new auth_plugin_ldap;

if (isguest() or !$authldap->ldap_isgroupmember($USER->username, 'OU=IT,OU=cmaisonneuve,DC=cmaisonneuve,DC=qc,DC=ca;OU=Users,OU=Dev_pedagogique,OU=Admin,OU=cmaisonneuve,DC=cmaisonneuve,DC=qc,DC=ca')) {
	print_error(get_string('errormustbeadmin','block_cegep'));
}

define('REQUEST_STATES',serialize(array('new','waiting','accepted','denied','delete','modify')));

$id      = optional_param('id', null, PARAM_INT);
$state   = optional_param('state', null, PARAM_ALPHA);

$requestform = new cegep_request_form();
$requestform->_form->insertElementBefore($requestform->_form->createElement('text', 'username', get_string('username'), 'maxlength="16"'), 'request1');
$requestform->_form->setType('username', PARAM_TEXT);
		
$strtitle = get_string('coursespending');
$strheading = get_string(((!empty($reject)) ? 'coursereject' : 'coursespending'));
print_header($strtitle,$strheading,build_navigation(array(array('name'=>$strheading,'link'=>'','type'=>'misc'))));

if ($requestform->is_cancelled()){

	cegep_requestadmin_display();

} elseif ($requestform->is_submitted()) {

	if (!$requestform->is_validated()) {
		$request = get_record('cegep_request', 'id', $requestform->get_submitted_data()->id);
		$user = get_record('user','username',$request->username);
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

		if (update_record('cegep_request', $upddata)) {
			notify(get_string('courserequest_modsuccess','block_cegep').'<br />','notifysuccess');
			print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
		} else {
			notify(get_string('courserequest_modfailed','block_cegep').'<br />');
			print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
		}
	}

} elseif (!empty($state)) {

	if ($request = get_record('cegep_request', 'id', $id) AND in_array($state, unserialize(REQUEST_STATES))) {

		switch ($state) {

			case 'accepted' :
				$course = unserialize($request->coursecodes);
				foreach ($course as $c) {
					$coursecode = strtoupper($c['coursecode']);
					($c['meta'] == 1) ? ($meta = 1) : ($meta = 0);
					$coursemaxid = get_record_sql("SELECT MAX(CONVERT(SUBSTRING_INDEX(`idnumber`, '_', -1), UNSIGNED)) as num FROM `mdl_course` WHERE idnumber LIKE '$coursecode%'");
					if ($coursemaxid->num === NULL) { $seqnum = '0'; } else { $seqnum = $coursemaxid->num + 1; }

					for ($i = 1; $i <= $c['num']; $i++) {
						if (!cegep_create_course($coursecode, $seqnum, $meta)) {
							print_error("Une erreur s'est produite lors de la création des cours!");
							break;
						}
						if (!cegep_create_enrolment($coursecode, $seqnum, $request->username, $request->id)) {
							print_error("Une erreur s'est produite lors de l'inscription du cours!");
							break;
						}
						$seqnum++;
					}
				}
				
				$request->state = $state;
				if (update_record('cegep_request', $request)) {
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
						$user = get_record('user','username',$request->username);
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
						if (delete_records('cegep_request', 'id', $request->id)) {
							notify(get_string('courserequest_delsuccess','block_cegep').'<br />','notifysuccess');
							print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
						} else {
							notify(get_string('courserequest_modfailed','block_cegep').'<br />');
							print_continue($CFG->wwwroot.'/blocks/cegep/request/request_admin.php');
						}
						break;

					case 'denied'  :
						$request->etat = $state;
						if (update_record('cegep_request', $request)) {
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
						if (update_record('cegep_request', $request)) {
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

		global $CFG;

		if ($requests = get_records_select('cegep_request', "`state` = 'new' OR `state` = 'waiting'", 'created DESC')) {

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

				$user = get_record('user','username',$request->username);
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

function cegep_create_course ($coursecode, $seqnum, $meta) {
	global $CFG;

	$site = get_site();
	$sisdb = sisdb_connect();

	$select_course = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = '$coursecode' LIMIT 1";
	$coursetitle = $sisdb->Execute($select_course)->fields['title'];

	$course = new StdClass;
	$course->fullname  = $coursetitle;
	$course->shortname = $coursecode . '_' . $seqnum;
	$course->idnumber = $coursecode . '_' . $seqnum;
	$course->metacourse = $meta;

	$template = array(
                      'startdate'      => time() + 3600 * 24,
                      'summary'        => get_string("defaultcoursesummary"),
                      'format'         => "topics",
                      'password'       => "",
                      'guest'          => 0,
                      'numsections'    => 10,
                      'cost'           => '',
    				  'maxbytes'       => 8388608,
                      'newsitems'      => 5,
                      'showgrades'     => 0,
                      'groupmode'      => 0,
                      'groupmodeforce' => 0,
                      'student'  => $site->student,
                      'students' => $site->students,
                      'teacher'  => $site->teacher,
                      'teachers' => $site->teachers,
	);

	// overlay template
	foreach (array_keys($template) AS $key) {
		if (empty($course->$key)) {
			$course->$key = $template[$key];
		}
	}

	$course->category = 1;     // the misc 'catch-all' category

	// Place the course in the correct category
	$dept = substr($course->idnumber, 0, 3);
	switch ($dept) {
		case ('101') :
			$course->category = 2; // Biologie
			break;
		case ('202') :
			$course->category = 3; // Chimie
			break;
		case ('109') :
			$course->category = 4; // Éducation physique
			break;
		case ('501') :
		case ('502') :
		case ('530') :
		case ('601') :
			$course->category = 5; // Français
			break;
		case ('320') :
		case ('330') :
			$course->category = 6; // Histoire-géographie
			break;
		case ('520') :
			$course->category = 7; // Histoire de l'art
			break;
		case ('420') :
			$course->category = 8; // Informatique
			break;
		case ('210') :
			$course->category = 9; // ICP
			break;
		case ('604') :
		case ('607') :
		case ('609') :
			$course->category = 10; // Langues modernes
			break;
		case ('201') :
		case ('360') :
			$course->category = 11; // Mathématiques
			break;
		case ('340') :
			$course->category = 12; // Philosophie
			break;
		case ('203') :
			$course->category = 13; // Physique
			break;
		case ('350') :
			$course->category = 14; // Psychologie
			break;
		case ('300') :
		case ('383') :
		case ('385') :
		case ('387') :
			$course->category = 15; // Sciences sociales
			break;
		case ('180') :
			$course->category = 16; // SIN
			break;
		case ('310') :
			$course->category = 17; // TAJ
			break;
		case ('412') :
			$course->category = 18; // TBU
			break;
		case ('401') :
		case ('410') :
			$course->category = 19; // TAD
			break;
		case ('120') :
			$course->category = 20; // TDI
			break;
		case ('393') :
			$course->category = 21; // TDOC
			break;
		case ('111') :
			$course->category = 22; // THD
			break;
		case ('243') :
			$course->category = 23; // TGE
			break;
		case ('582') :
			$course->category = 24; // TIM
			break;
	}

	// define the sortorder
	$sort = get_field_sql('SELECT COALESCE(MAX(sortorder)+1, 100) AS max ' .
                          ' FROM ' . $CFG->prefix . 'course ' .
                          ' WHERE category=' . $course->category);
	$course->sortorder = $sort;

	// override with local data
	$course->startdate   = time() + 3600 * 24;
	$course->timecreated = time();
	$course->visible     = 0;
	$course->enrollable  = 0;

	// clear out id just in case
	unset($course->id);

	// store it and log
	if ($newcourseid = insert_record("course", addslashes_object($course))) {  // Set up new course
		$section = NULL;
		$section->course = $newcourseid;   // Create a default section.
		$section->section = 0;
		$section->id = insert_record("course_sections", $section);
		$page = page_create_object(PAGE_COURSE_VIEW, $newcourseid);
		blocks_repopulate_page($page); // Return value no

		fix_course_sortorder();

		// assign teacher role for course
		//$context = get_context_instance(CONTEXT_COURSE, $newcourseid);
		//role_assign($CFG->creatornewroleid, $teacherid, 0, $context->id);

		// assign teacher role for site
		// $sitecontext = get_context_instance(CONTEXT_SYSTEM);
		// role_assign(3, $teacherid, 0, $sitecontext->id);

		add_to_log($newcourseid, "course", "new", "view.php?id=$newcourseid", "block_cegep/request course created");

	} else {
		trigger_error("Could not create new course $extcourse from  from database");
		notify("Serious Error! Could not create the new course!");
		$sisdb->Close();
		return false;
	}

	$sisdb->Close();
	
	return true;
}

function cegep_create_enrolment($courseidnumber, $seqnum, $username, $request_id) {
	global $CFG;
	
	if (empty($courseidnumber) or empty($username)) {
		print_error("Le cours ou l'utilisateur spécifié est invalide!");
		return false;
	}
	
	$enroldb = enroldb_connect();
	$insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield` , `$CFG->enrol_db_remoterolefield` , `request_id`) VALUES ('${courseidnumber}_${seqnum}', '$username', 'editingteacher', '$request_id');";	
	
	$result = $enroldb->Execute($insert);

	if (!$result) {
		trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $insert, E_USER_ERROR);
		$enroldb->Close();		
		return false;
	}
	else {
		$enroldb->Close();
		return true;
	}
}

?>