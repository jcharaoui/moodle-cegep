<?php

// Block menu
$string['admincegep'] = 'Admin CEGEP';
$string['enrolment'] = 'Enrolments';
$string['delete'] = 'Destroy';

// Tabs
$string['studentlist'] = 'Student list';
$string['enrol'] = 'Enrol coursegroup';
$string['unenrol'] = 'Unenrol coursegroup';
$string['enrolprogram'] = 'Enrol program';
$string['unenrolprogram'] = 'Unenrol program';

// Common
$string['summer'] = 'Summer';
$string['autumn'] = $string['fall'] = 'Fall';
$string['winter'] = 'Winter';
$string['year'] = 'Year';
$string['semester'] = $string['term'] = 'Semester';
$string['coursegroup'] = $string['section'] = 'Section';
$string['number'] = 'Number';
$string['comments'] = "Comments";
$string['create'] = "Create";
$string['teacher'] = 'Teacher';
$string['teachers'] = 'Teachers';

// My moodle page
$string['cegepsection'] = 'Section';
$string['cegepenrolled'] = 'Enrolled Sections:';
$string['cegepenrolledstud'] = 'Section(s):';
$string['cegepvisible'] = 'Visible to students:';
$string['cegepnosections'] = 'None';
$string['cegepvisibleyes'] = 'Yes';
$string['cegepvisibleno'] = 'No';
$string['cegepeditsettings'] = 'Edit settings';

// Student list page
$string['studentlisttitle'] = 'List of students enrolled in this course';
$string['childcoursetitle'] = 'Child course title';
$string['coursecode'] = 'Course code';
$string['coursegroupnumber'] = 'Coursegroup number';
$string['program'] = 'Program';
$string['nocoursegroupsenrolled'] = 'No coursegroups are enrolled in this course';
$string['accessuserprofile'] = 'Access user\'s profile';
$string['nostudentsenrolled'] = 'There are no students enrolled in this course.';

// Enrol form
$string['coursegroupenrolled'] = '<strong>Enrolment sucessfully completed.</strong><br /><br />These are the students that were enrolled into this course :<br /><br />{$a}<br /<br />Have a nice semester!<br /><br />';
$string['enrolanother'] = 'Enrol another coursegroup';
$string['make_visible'] = 'Make this course available to students';
$string['enrolhelp'] = 'You can choose which section(s) you want to enrol in this course. Please take note: if you want to have <b>separate courses for each section</b>, you must enrol <b>only one section here</b> and create a course for each section you have.';
$string['enrolcoursegroup'] = 'Enrol this section';
$string['nocoursegroupsavailable'] = 'No sections are available for enrolment in this course.';
$string['nocoursegroupselected'] = 'Please select a section to enrol.';

// Unenrol form
$string['students'] = 'students';
$string['unenrolbutton'] = 'Unenrol';
$string['coursegroupunenrolled'] = '<strong>Unenrolment successfully completed.</strong></br /><br />{$a} students removed from the course.<br /><br />';

// Course Creation
$string['coursecreatesuccess'] = 'The course was created successfully. Click \"Continue\" to go to your new course.';
$string['coursecreate'] = 'Create course';

// Program enrol form
$string['programenrolled'] = 'The program was enrolled successfully.';
$string['programyear'] = 'Program year';
$string['programyear1'] = '1st';
$string['programyear2'] = '2nd';
$string['programyear3'] = '3rd';

// Program unenrol form
$string['unenrolprogrambutton'] = 'Unenrol';
$string['programunenrolled'] = 'The program was unenrolled successfully.';

// Validation
$string['specifyyear'] = 'Please specify the year.';
$string['specifysemester'] = 'Please specify the semester.';
$string['specifycoursegroup'] = 'Please specify a section.';
$string['specifyprogram'] = 'Please specify a program.';
$string['specifyprogramyear'] = 'Please specify a program year.';
$string['semesterunavailable'] = 'The specified semester is not available in the system.';
$string['coursegroupsixnumbersonly'] = 'The section number must contain six numbers.';
$string['coursegroupalreadyenrolled'] = 'The section specified is already enroled in this course.';
$string['coursegroupnotenrolled'] = 'The section specified is not enrolled in this course.';
$string['coursegroupunavailable'] = 'The section specified is not available in the system.';
$string['coursegroupinvalid'] = 'The section specified is invalid.';
$string['programinvalid'] = 'The program specified is invalid.';
$string['programalreadyenrolled'] = 'The program specified is already enrolled in this course.';

// Settings
$string['studentrole'] = 'Student role';
$string['studentrole_help'] = 'Role that will be assigned to students created from the external database.';
$string['cronpassword'] = 'Cron password';
$string['cronpassword_help'] = 'Enter a password for automated synchronisation mode via cron. When calling the script, add this string to the \'password\' parameter. If you leave this field blank, you will need to run the synchronisation scripts manually while logged in as site administrator.';
$string['sisdb_heading'] = 'SIS external database';
$string['sisdb_help'] = 'External SIS database access information.';
$string['sisdb_type'] = 'Database';
$string['sisdb_host'] = 'Host';
$string['sisdb_name'] = 'DB Name';
$string['sisdb_user'] = 'DB User';
$string['sisdb_pass'] = 'Password';
$string['sisdb_sync_csv'] = 'Synchronize database with a CSV file';
$string['sisdb_sync_db'] = 'Synchronize database via PHP script';
$string['cegepname'] = "Cegep Name";
$string['sisdbsource_type'] = "SIS Database Type";
$string['sisdbsource_host'] = "SIS Database Host";
$string['sisdbsource_name'] = "SIS Database Name";
$string['sisdbsource_user'] = "SIS Database User";
$string['sisdbsource_pass'] = "SIS Database Password";
$string['sisdbsource_help'] = "Source SIS database access information";

$string['autotopic'] = "Auto fill topic 0";
$string['autotopic_help'] = "Answering yes will automatically fill courses' topic 0 with the course name and the list of teachers.";

$string['autogroups'] = "Create groups";
$string['autogroups_help'] = "Answering yes will automatically create and add students to groups, which are named according to course sections.";

// Course creation
$string['coursecreatesuccess'] = "The course was successfully created. Click 'Continue' to access the enrolment page for your new course.";
$string['coursecreate'] = 'Create course';

// Course request
$string['courserequest'] = '';
$string['courserequest_instructions'] = '';
$string['courserequest_username'] = '';
$string['courserequest_success'] = '';
$string['courserequest_failed'] = '';
$string['courserequest_nothing'] = '';
$string['courserequest_nonew'] = '';
$string['courserequest_nowaiting'] = '';
$string['courserequest_by'] = '';
$string['courserequest_since'] = '';
$string['courserequest_courses'] = '';
$string['courserequest_comments'] = '';
$string['courserequest_state'] = '';
$string['courserequest_new'] = '';
$string['courserequest_waiting'] = '';
$string['courserequest_statenew'] = '';
$string['courserequest_statewaiting'] = '';
$string['courserequest_stateaccepted'] = '';
$string['courserequest_statedenied'] = '';
$string['courserequest_statedelete'] = '';
$string['courserequest_statemodify'] = '';
$string['courserequest_modsuccess'] = '';
$string['courserequest_delsuccess'] = '';
$string['courserequest_modfailed'] = '';
$string['courserequest_createsuccess'] = '';
$string['courserequest_createfailed'] = '';
$string['courserequest_exists'] = '';
$string['courserequest_duplicate'] = '';
$string['invalidcoursecode'] = '';
$string['atleastonecoursecode'] = '';
$string['specifycoursenumber'] = '';
$string['specifycoursenumber'] = '';

// Errors
$string['errorenroldbnotavailable'] = 'The external database enrolment plugin is not activated. Please activate it and retry this operation.';
$string['erroractionnotavailable'] = '';
$string['errormustbeteacher'] = '';
$string['errorcreatingcourse'] = 'Error: could not create course';
$string['errorimportingstudentlist'] = '';
$string['errordeletingenrolment'] = '';

// SIS DB maintenance
$string['sisdb_maintenance'] = 'SIS DB maintenance';
$string['sisdb_sync'] = 'Synchronize';
$string['sisdb_prune'] = 'Prune';

?>
