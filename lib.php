<?php

if (file_exists($CFG->dirroot .'/blocks/cegep/lib_'. $CFG->block_cegep_name .'.php')) {
    require_once($CFG->dirroot .'/blocks/cegep/lib_'. $CFG->block_cegep_name .'.php');
}

/**
 * Get the content for the admin cegep block
 */
function cegep_local_get_block_content() {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_get_block_content')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_get_block_content');
    } else {
        $content = new stdClass;
        return $content;
    }
}

/**
 * Return current term in yyyys (year/semester) format
 */
function cegep_local_current_term() {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_current_term')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_current_term');
    } else {
        // Year
        $term = date('Y');
        // Semester
        switch (date('m')) {
            case '01':
            case '02':
            case '03':
            case '04':
            case '05':
                $term .= '1';
                break;
            case '06':
            case '07':
            case '08':
                $term .= '2';
                break;
            case '09':
            case '10':
            case '11':
            case '12':
                $term .= '3';
                break;
        }
        return $term;
    }
}

/* Converts SISDBSOURCE (eg: Clara/Datamart) data format into SISDB standard format
 */
function cegep_local_sisdbsource_decode($field, $data) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_sisdbsource_decode')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_sisdbsource_decode', $field, $data);
    } else {
        return 1;
    }
}

/**
 * Return the Moodle category id according to course type
 */
function cegep_local_course_category($category) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_course_category')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_course_category', $category);
    } else {
        return 1;
    }
}

/**
 * Return the SIS db source select to execute (students)
 * This select statement must return the following columns :
 * - CourseCampus
 * - CourseTerm
 * - CourseNumber
 * - CourseTitle 
 * - CourseGroup
 * - StudentNo
 * - StudentFirstName
 * - StudentLastName
 * - StudentProgram
 * - StudentProgramName
 * - StudentProgramYear
 */
function cegep_local_sisdbsource_select_students($term) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select_students')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select_students', $term);
    } else {
        return 1;
    }
}

/**
 * Return the SIS db source select to execute (teachers)
 * This select statement must return the following columns :
 * - TeacherNumber
 * - CourseNumber
 * - CourseGroup
 * - CourseTerm
 */
function cegep_local_sisdbsource_select_teachers($term) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select_teachers')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_sisdbsource_select_teachers', $term);
    } else {
        return 1;
    }
}

/**
 * Return course creation buttons for the MyMoodle block variant.
 * Allows teacher to easily create courses.
 */
function cegep_local_get_create_course_buttons() {
    global $CFG, $USER;

    if (function_exists('cegep_' . $CFG->block_cegep_name . '_get_create_course_buttons')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_get_create_course_buttons', $term);
    } else {

        $items = array();
        $previous_term_str = '';
        $enrolments = cegep_local_get_teacher_enrolments($USER->idnumber, cegep_local_current_term());

        foreach ($enrolments as $enrolment) {

            // Check if course title is empty
            if (!empty($enrolment['coursetitle'])) {
                $coursetitle = $enrolment['coursetitle'];
            } else {
                $coursetitle = get_string('cousetitlemissing','block_cegep');
            }

            // Display term string
            $current_term_str = cegep_local_term_to_string($enrolment['term']);
            if ($current_term_str != $previous_term_str) {
                $items[] = '<div style="font-weight: bold; font-size: 1.2em;">' . $current_term_str . '</div>';
            }
            $previous_term_str = $current_term_str;

            $items[] = '<form action="' . $CFG->wwwroot . '/blocks/cegep/block_cegep_createcourse.php" method="post" class="form_create">'.
            '<div class="coursenumber create_button">'.
            '<input type="hidden" name="coursecode" value="'. $enrolment['coursecode'] .'" />'.
            '<input type="hidden" name="term" value="' . $enrolment['term']. '" />' .
            '<input type="submit" value="'.get_string('create','block_cegep').'" name="submit" style="margin-right: 5px;" />'.
            $enrolment['coursecode'].'</div><div class="coursetitle">'. $coursetitle .'</div></form>';

        }

        return $items;
    }
}

/**
 * Return enrolment data for a specified teacher idnumber and term
 */
function cegep_local_get_teacher_enrolments($idnumber, $term) {
    global $CFG;

    $enrolments = array();

    // Prepare external SIS database connection
    if ($sisdb = sisdb_connect()) {
        $sisdb->Execute("SET NAMES 'utf8'");
    }
    else {
        error_log('[SIS_DB] Could not make a connection');
        print_error('dbconnectionfailed','error');
    }

    $select = "SELECT cg.coursecode AS coursecode, cg.group AS coursegroup, c.title AS coursetitle, cg.term AS term FROM `$CFG->sisdb_name`.teacher_enrolment te LEFT JOIN `$CFG->sisdb_name`.coursegroup cg ON cg.id = te.coursegroup_id LEFT JOIN `$CFG->sisdb_name`.course c ON c.coursecode = cg.coursecode WHERE te.idnumber = '$idnumber' AND cg.term >= $term ORDER BY term, coursecode, coursegroup;";

    $sisdb_rs = $sisdb->Execute($select);

    while ($sisdb_rs && !$sisdb_rs->EOF) {
        $enrolment = array();
        $enrolment['coursecode'] = $sisdb_rs->fields['coursecode'];
        $enrolment['coursetitle'] = $sisdb_rs->fields['coursetitle'];
        $enrolment['coursegroup'] = $sisdb_rs->fields['coursegroup'];
        $enrolment['term'] = $sisdb_rs->fields['term'];
        array_push($enrolments, $enrolment);
        $sisdb_rs->moveNext();
    }

    $sisdb->Close();

    return $enrolments;
}

/**
 * Return teachers enrolled into a course
 */
function cegep_local_get_course_teacher_enrolments($cousecode, $term) {
    global $CFG;

    $enrolments = array();

    // Prepare external SIS database connection
    if ($sisdb = sisdb_connect()) {
        $sisdb->Execute("SET NAMES 'utf8'");
    }
    else {
        error_log('[SIS_DB] Could not make a connection');
        print_error('dbconnectionfailed','error');
    }

    $select = "SELECT cg.coursecode AS coursecode, cg.group AS coursegroup, c.title AS coursetitle, cg.term AS term FROM `$CFG->sisdb_name`.teacher_enrolment te LEFT JOIN `$CFG->sisdb_name`.coursegroup cg ON cg.id = te.coursegroup_id LEFT JOIN `$CFG->sisdb_name`.course c ON c.coursecode = cg.coursecode WHERE te.idnumber = '$idnumber' AND cg.term >= $term ORDER BY term, coursecode, coursegroup;";

    $sisdb_rs = $sisdb->Execute($select);

    while ($sisdb_rs && !$sisdb_rs->EOF) {
        $enrolment = array();
        $enrolment['coursecode'] = $sisdb_rs->fields['coursecode'];
        $enrolment['coursetitle'] = $sisdb_rs->fields['coursetitle'];
        $enrolment['coursegroup'] = $sisdb_rs->fields['coursegroup'];
        $enrolment['term'] = $sisdb_rs->fields['term'];
        array_push($enrolments, $enrolment);
        $sisdb_rs->moveNext();
    }

    $sisdb->Close();

    return $enrolments;
}


/**
 * Create course
 */
function cegep_local_create_course($coursecode, $term = '', $meta = false) {
    global $CFG, $USER;

    if (function_exists('cegep_' . $CFG->block_cegep_name . '_create_course')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_create_course', $coursecode, $term, $meta);
    } else {

        $site = get_site();

        // Prepare external SIS database connection
        if ($sisdb = sisdb_connect()) {
            $sisdb->Execute("SET NAMES 'utf8'");
        }
        else {
            error_log('[SIS_DB] Could not make a connection');
            print_error('dbconnectionfailed','error');
        }
        
        // Validate coursecode
        $select_course = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = '$coursecode' LIMIT 1";
        $result = $sisdb->execute($select_course);
        if ($result->RecordCount() < 1) {
            $sisdb->close();
            return false;
        }
        
        // Build course object
        $course = new StdClass;
        
        // Course metacourse
        $course->metacouse = $meta;

        // Course idnumber
        $coursecode = strtoupper($coursecode);
        $coursemaxid = get_record_sql("SELECT MAX(CONVERT(SUBSTRING_INDEX(`idnumber`, '_', -1), UNSIGNED)) as num FROM `mdl_course` WHERE idnumber LIKE '$coursecode%'");
        if ($coursemaxid->num === null) {
            $course->idnumber = $coursecode . '_0';
        } else {
            $course->idnumber = $coursecode . '_' . ($coursemaxid->num + 1);
        }
        
        // Course fullname and shortname
        $select_course = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = '$coursecode' LIMIT 1";
        $coursetitle = cegep_maisonneuve_sisdbsource_decode('coursetitle',$sisdb->execute($select_course)->fields['title']);
        if (!empty($coursetitle)) {
            if (!empty($term)) {
                $course->fullname = $coursetitle . ' (' . $coursecode . ' - ' . cegep_local_term_to_string($term) . ')';
            } else {
                $course->fullname = $coursetitle;
            }
            $course->shortname = $coursetitle . ' (' . $course->idnumber . ')';
        } else {
            if (!empty($term)) {
                $course->fullname = $coursecode . ' - ' . cegep_local_term_to_string($term);
            } else {
                $course->fullname = $coursecode;
            }
            $course->shortname = $course->idnumber;
        }

        // Course category
        if (($p = strpos($course->idnumber, '_')) !== false) {
            $course_category_lenght = $p;
        } else {
            $course_category_lenght = strlen($course->idnumber);
        }
        if (strlen($course_category_lenght) > 8) {
            $course_category = substr($course->idnumber, 3, 3);
        } else {
            $course_category = substr($course->idnumber, 0, 3);
        }
        $course->category = cegep_local_course_category($course_category);
        if (!$course->category) {
            $sisdb->close();
            return false;
       }

        // Course sortorder
        $sort = get_field_sql('SELECT COALESCE(MAX(sortorder)+1, 100) AS max FROM ' . $CFG->prefix . 'course  WHERE category=' . $course->category);
        $course->sortorder = $sort;

        // Course times
        $course->startdate = time() + 3600 * 24;
        $course->timecreated = time();
        
        // Course visible
        $course->visible = 0;
        
        // Course enrollable
        $course->enrollable = 0;

        // Get course defaults
        $courseconfig = get_config('moodlecourse');
        $template = array(
                'summary'        => get_string("defaultcoursesummary"),
                'format'         => $courseconfig->format,
                'password'       => "",
                'guest'          => 0,
                'numsections'    => $courseconfig->numsections,
                'hiddensections' => $courseconfig->hiddensections,
                'cost'           => '',
                'maxbytes'       => $courseconfig->maxbytes,
                'newsitems'      => $courseconfig->newsitems,
                'showgrades'     => $courseconfig->showgrades,
                'showreports'    => $courseconfig->showreports,
                'groupmode'      => 0,
                'groupmodeforce' => 0,
                'student'  => $site->student,
                'students' => $site->students,
                'teacher'  => $site->teacher,
                'teachers' => $site->teachers,
                );

        // Apply defaults to course object
        foreach (array_keys($template) as $key) {
            if (empty($course->$key)) {
                $course->$key = $template[$key];
            }
        }

        // Course id (just in case)
        unset($course->id);
        
        // Store new course in database
        if ($newcourseid = insert_record("course", addslashes_object($course))) {        
            $page = page_create_object(PAGE_COURSE_VIEW, $newcourseid);
            blocks_repopulate_page($page); // Return value no
            fix_course_sortorder();
            add_to_log($newcourseid, "course", "new", "view.php?id=$newcourseid", "block_cegep/request course created");
        } else {
            print_error("An error occurred when trying to create the course.");
        }

        // Create a default section
        $section = new stdClass;
        $section->course = $newcourseid;
        $section->section = 0;
        // Autotopic (add course title & teacher name to topic 0) (skip if admin)
        $context = get_context_instance(CONTEXT_SYSTEM);
        if ($CFG->block_cegep_autotopic && !has_capability('moodle/site:doanything', $context)) {
            $section->summary = '<h1 style="text-align: center;">' . $course->fullname . '</h1>';
            $section->summary .= '<h3 style="text-align: center;">' . get_string('teacher','block_cegep') . ': ' . $USER->firstname . ' ' . $USER->lastname . '</h3>';
        }
        $section->id = insert_record("course_sections", $section);

        return $newcourseid;
    }
}

/**
 * Prepare a select query for execution. Most Cegeps won't need this.
 */
function cegep_local_prepare_select_query($query) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_prepare_select_query')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_prepare_select_query', $query);
    } 
    else {
        return $query;
    }
}

/**
 * Convert a term code (YYYYS) into a string,
 * like 'Fall 2009' or 'Winter 2010'.
 */
function cegep_local_term_to_string($code) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_term_to_string')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_term_to_string', $code);
    } 
    else {
        $year = substr($code, 0, 4);
        $semester = substr($code, 4, 1);

        $str = '';

        switch ($semester) {
            case '1':
                $str = get_string("winter", "block_cegep") . ' ';
                break;
            case '2':
                $str = get_string("summer", "block_cegep") . ' ';
                break;
            case '3':
                $str = get_string("fall", "block_cegep") . ' ';
                break;
            default:
                $str = get_string("fall", "block_cegep") . ' ';
                break;
        }

        $str .= $year;
        return $str;
    }
}

/* Decrements a term, in the form of YYYYS. */
function cegep_local_decrement_term($term, $number = 1) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_decrement_term')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_decrement_term', $code);
    } else {
        $year = substr($term, 0, 4);
        $semester = substr($term, 4, 1);
        while ($number > 0) {
            if ($semester == 1) {
                $semester = 3;
                $year--;
            }
            else {
                $semester--;
            }
            $number--;
        }
        return $year . $semester;
    }
}

/**
 * Create an enrolment in the external enrolments database
 */
function cegep_local_create_enrolment($courseidnumber, $username, $request_id = '') {
    global $CFG;

    if (function_exists('cegep_' . $CFG->block_cegep_name . '_create_enrolment')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_create_enrolment', $courseidnumber, $username, $request_id);
    } else {
    
        if (empty($courseidnumber) or empty($username)) {
            print_error("Le cours ou l'utilisateur spécifié est invalide!");
            return false;
        }
        
        $enroldb = enroldb_connect();
        $insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield` , `$CFG->enrol_db_remoterolefield` , `request_id`) VALUES ('${courseidnumber}', '$username', 'editingteacher', '$request_id');";    
        
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
}

/*
 * Delete all course enrolments from external enrolments DB
 * (ie. when a course is deleted)
 */
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

function sisdbsource_connect() {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_sisdbsource_connect')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_sisdbsource_connect', $CFG->sisdbsource_type, $CFG->sisdbsource_host, $CFG->sisdbsource_name, $CFG->sisdbsource_user, $CFG->sisdbsource_pass);
    }
    else {
        return cegep_dbconnect($CFG->sisdbsource_type, $CFG->sisdbsource_host, $CFG->sisdbsource_name, $CFG->sisdbsource_user, $CFG->sisdbsource_pass);
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
    $db = &ADONewConnection($type);
    if ($db->Connect($host, $user, $pass, $name, true)) {
        $db->SetFetchMode(ADODB_FETCH_ASSOC); ///Set Assoc mode always after DB connection
        return $db;
    } else {
        trigger_error("Error connecting to DB backend with: "
                      . "$host, $user, $pass, $name");
        return false;
    }
}

function cegep_local_student_needs_updating($resultat) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_student_needs_updating')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_student_needs_updating', $resultat);
    }
    else {
        // to be implemented
    }
}

function cegep_local_get_sisdb_student_insert($code_etudiant, $last_name, $first_name, $program, $programyear) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_get_sisdb_student_insert')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_get_sisdb_student_insert', $code_etudiant, $last_name, $first_name, $program, $programyear);
    }
    else {
        // to be implemented
    }
}

function cegep_local_get_sisdb_student_update($code_etudiant, $last_name, $first_name, $program, $programyear) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_get_sisdb_student_update')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_get_sisdb_student_update', $code_etudiant, $last_name, $first_name, $program, $programyear);
    }
    else {
        // to be implemented
    }
}

function cegep_local_update_program_enrolment($code_etudiant, $student_role, $program_idyear) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_update_program_enrolment')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_update_program_enrolment', $code_etudiant, $student_role, $program_idyear);
    }
    else {
        // to be implemented
        return;
    }
}

function cegep_local_date_to_datecode($date = null) {
    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_date_to_datecode')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_date_to_datecode', $date);
    }
    else {
        if(!$date) {
            $date = date('Y-m-d');
        }

        $code = date('Y', strtotime($date));

        switch (substr($date, 5, 2)) {
            case '01':
            case '02':
            case '03':
            case '04':
            case '05':
                $code .= '1';
                break;

            case '06':
            case '07':
            case '08':
                $code .= '2';
                break;

            case '09':
            case '10':
            case '11':
            case '12':
                $code .= '3';
                break;
            default:
                trigger_error('Wrong date given', E_USER_WARNING);
        }
        return $code;
    }
}

/* Get an array of sections that have been enrolled in a given
 * course. Use the global $COURSE.
 */
function cegep_local_get_enrolled_sections() {
    global $CFG, $COURSE, $enroldb, $sisdb;

    $course = $COURSE->idnumber;

    $coursecode = substr($course, 0, 8);

    $select = "SELECT DISTINCT `coursegroup_id`, COUNT(`coursegroup_id`) AS num FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` like '". $coursecode ."_%' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IS NOT NULL GROUP BY `coursegroup_id` ORDER BY `coursegroup_id`";

    $coursegroups_rs = $enroldb->Execute($select);

    if (!$coursegroups_rs) {
        trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
        return false;
    } 

    $coursegroup_id = '';
    $terms_coursegroups = array();

    while (!$coursegroups_rs->EOF) {
        $coursegroup_id = $coursegroups_rs->fields['coursegroup_id'];
        $coursegroup_num = $coursegroups_rs->fields['num'];
        $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE id = '$coursegroup_id'";
        $coursegroup = $sisdb->Execute($select)->fields;

        if (!is_array($terms_coursegroups[$coursegroup['term']])) {
            $terms_coursegroups[$coursegroup['term']] = array();
        }
        $terms_coursegroups[$coursegroup['term']][] = $coursegroup['group'];

        $coursegroups_rs->MoveNext();
    }
    return $terms_coursegroups;
}

?>
