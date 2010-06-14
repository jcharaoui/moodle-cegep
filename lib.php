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

            $items[] = '<form action="' . $CFG->wwwroot . '/blocks/cegep/block_cegep_coursecreate.php" method="post" class="form_create">'.
            '<div class="coursenumber create_button">'.
            '<input type="hidden" name="cegepcoursenumber" value="'. $enrolment['coursecode'] .'" />'.
            '<input type="hidden" name="term" value="' . $enrolment['term']. '" />' .
            (!empty($enrolment['coursetitle']) ? '<input type="hidden" name="coursetitle" value="' . $coursetitle . '" />' : '') . 
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

    $select = "
            SELECT DISTINCT
                cg.coursecode AS coursecode, 
                c.title AS coursetitle, 
                cg.term AS term 
            FROM `$CFG->sisdb_name`.teacher_enrolment te 
            LEFT JOIN `$CFG->sisdb_name`.coursegroup cg ON cg.id = te.coursegroup_id 
            LEFT JOIN `$CFG->sisdb_name`.course c ON c.coursecode = cg.coursecode 
            WHERE 
                te.idnumber = '$idnumber' AND 
                cg.term >= $term 
            ORDER BY term, coursecode;";

    $sisdb_rs = $sisdb->Execute($select);

    while ($sisdb_rs && !$sisdb_rs->EOF) {
        $enrolment = array();
        $enrolment['coursecode'] = $sisdb_rs->fields['coursecode'];
        $enrolment['coursetitle'] = $sisdb_rs->fields['coursetitle'];
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
function cegep_local_create_course($coursecode = '', $term = '', $coursetitle = '', $coursegroup = '', $meta = false) {

    global $USER, $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_create_course')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_create_course', $coursecode, $term, $coursetitle, $coursegroup, $meta);
    } else {

        $coursemaxid = get_record_sql("SELECT MAX(CONVERT(SUBSTRING_INDEX(`idnumber`, '_', -1), UNSIGNED)) as num FROM `mdl_course` WHERE idnumber LIKE '$coursecode%'");

        if ($coursemaxid->num === NULL) {
            $seqnum = '0';
        } 
        else {
            $seqnum = $coursemaxid->num + 1;
        }

        $curterm = cegep_local_term_to_string($term);

        $site = get_site();
        $sisdb = sisdb_connect();

        if (!($courseid = _cegep_local_create_course($coursecode, $seqnum, $meta, $coursetitle, $coursedescription, $curterm))) {
            print_error("An error occurred when trying to create the course.");
            break;
        }

        $role = get_record('role', 'shortname', 'editingteacher');
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        if(!role_assign($role->id, $USER->id, 0, $context->id, 0, 0, 0, 'database')) {
            debugging("Problem calling role_assign", DEBUG_DEVELOPER);
        }

        /* Enroll all teachers that are in this coursegroup */
        if (!empty($coursegroup) && strlen($coursegroup) > 0) {

            if ($sisdb = sisdb_connect()) {
                // do nothing
            }
            else {
                error_log('[SIS_DB] Could not make a connection');
                print_error('dbconnectionfailed','error');
            }

            $select = "
                SELECT
                    te.idnumber
                FROM teacher_enrolment te
                LEFT JOIN coursegroup cg ON cg.id = te.coursegroup_id
                LEFT JOIN course c ON c.coursecode = cg.coursecode
                WHERE 
                    cg.coursecode = '" . $coursecode . "' AND
                    cg.group = '$coursegroup' AND
                    cg.term >= '$term'
            ";

            $sisdb_rs = $sisdb->Execute($select); 

            $teacher_list = '';
            $first_teacher = true;

            while ($sisdb_rs && !$sisdb_rs->EOF) {
                $tmp_usr = get_record('user', 'idnumber', $sisdb_rs->fields['idnumber']);

                if (!$tmp_usr) {
                    $sisdb_rs->moveNext();
                    continue;
                }

                if ($USER->idnumber != $sisdb_rs->fields['idnumber']) {
                    if(!role_assign($role->id, $tmp_usr->id, 0, $context->id, 0, 0, 0, 'manual')) { 
                        debugging("Problem calling role_assign", DEBUG_DEVELOPER);
                    }
                }
                if ($sisdb_rs->_numOfRows == 1) {
                    $teacher_list = '<h3 style="text-align: center;">Teacher: ' . $tmp_usr->firstname . ' ' . $tmp_usr->lastname . '</h3>';
                }
                else {
                    if ($first_teacher) {
                        $teacher_list .= '<h3 style="text-align: center;">Teachers:</h3>';
                        $teacher_list .= '<ul>';
                        $teacher_list .= '<li>' . $tmp_usr->firstname . ' ' . $tmp_usr->lastname . '</li>';
                        $first_teacher = false;
                    }
                    else {
                        $teacher_list .= '<li>' . $tmp_usr->firstname . ' ' . $tmp_usr->lastname . '</li>';
                    }
                }
                $sisdb_rs->moveNext();
            }
            if (strpos($teacher_list, "<ul>") !== false) {
                $teacher_list .= '</ul>';
            }
            $sisdb->Close();

            /* Edit topic 0 for this course to have the Course name + teacher names */
            if ($CFG->block_cegep_autotopic == true && $section = get_record('course_sections', 'course', $courseid, 'section', 0)) {
                $topic_summary = '<h1 style="text-align: center;">' . $coursetitle . '</h1>' . $teacher_list;
                set_field("course_sections", "summary", $topic_summary, "id", $section->id);
            }
        }
        else {
            if ($CFG->block_cegep_autotopic == true && $section = get_record('course_sections', 'course', $courseid, 'section', 0)) {
                $teacher_list = '<h3 style="text-align: center;">Teacher: ' . $USER->firstname . ' ' . $USER->lastname . '</h3>';
                $topic_summary = '<h1 style="text-align: center;">' . $coursetitle . '</h1>' . $teacher_list;
                set_field("course_sections", "summary", $topic_summary, "id", $section->id);
            }
        }
        return $courseid;
    }
}

/* '_cegep_local_create_course'
 *
 * This function is called from 'cegep_local_create_course'. It prepares
 * all the meta data and creates a course in Moodle.
 */
function _cegep_local_create_course($coursecode, $seqnum, $meta, $coursetitle = '', $coursedescription = "", $cursession = "") {
    global $CFG, $USER;

    if (function_exists('_cegep_' . $CFG->block_cegep_name . '_create_course')) {
        return call_user_func('_cegep_' . $CFG->block_cegep_name . '_create_course', $coursecode, $seqnum, $meta, $coursetitle, $coursedescription, $cursession);
    } 
    else {

        $site = get_site();
        $course = new StdClass;

        if (strlen($cursession) > 0) {
            $course->fullname  = $coursetitle .' ('. $coursecode .' - ' . $cursession . ')';
        }
        else {
            $course->fullname  = $coursetitle .' ('. $coursecode .')';
        }

        $course->shortname = $coursetitle .' ('. $coursecode .'-'. $seqnum .')';
        $course->idnumber = $coursecode . '_' . $seqnum;
        $course->metacourse = $meta;

        if (!$coursedescription) {
            $coursedescription = get_string("defaultcoursesummary");
        }

        $template = array(
            'startdate'      => time() + 3600 * 24,
            'summary'        => $coursedescription,
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

        // define the sortorder
        $sort = get_field_sql('SELECT COALESCE(MAX(sortorder)+1, 100) AS max FROM ' . $CFG->prefix . 'course  WHERE category=' . $course->category);
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
            add_to_log($newcourseid, "course", "new", "view.php?id=$newcourseid", "block_cegep/request course created");
        } else {
            trigger_error("Could not create new course $extcourse from  from database");
            notify("Serious Error! Could not create the new course!");
            return false;
        }
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
function cegep_local_create_enrolment($courseidnumber, $username, $request_id) {

    global $CFG;
    if (function_exists('cegep_' . $CFG->block_cegep_name . '_create_enrolment')) {
        return call_user_func('cegep_' . $CFG->block_cegep_name . '_create_enrolment', $courseidnumber, $username, $request_id);
    } else {
        return 1;
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
