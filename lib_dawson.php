<?php

/**
 * Connect to datamart, select database datamart
 */
function cegep_dawson_get_dm_connection() {

  global $dm, $dm_user, $dm_pass;

  $conn_dm = odbc_connect("DSN=$dm;Database=DataMart;UID=$dm_user;PWD=$dm_pass","","");

  if (!$conn_dm) {
    print_box("Connection to Datamart failed: script interupted. Please retry later or contact a system administrator.");
    die();
  }

  return $conn_dm;
}

/**
 * cegep_createcourse
 * changes from Maisonneuve's version:
 *  - first param is fullcoursecode (SessionCoursecodeSection instead of only Coursecode)
 *  - verifications done straight from datamart, Sections table (section exists, full name of course, etc.)
 */
function cegep_dawson_createcourse($fullcoursecode, $meta = false) {
  global $USER, $CFG;

  // TODO: extract this into separate function cegep_get_course_title($coursenumber)
  $conn_dm = cegep_dawson_get_dm_connection();
  // first query, looking for specific teacher - but the teacher might not have a section for his course.
  $select = "SELECT s.DawsonTitle, d.Description FROM Sections s LEFT JOIN Descriptions d ON s.CourseNumber = d.CourseNumber WHERE s.Instructor='". $USER->idnumber ."' and s.SectionId = '". $fullcoursecode ."' order by d.Latest DESC";
  //$select = "SELECT s.DawsonTitle, d.Description FROM Sections s LEFT JOIN Descriptions d ON s.CourseNumber = d.CourseNumber WHERE s.SectionId = '". $fullcoursecode ."' order by d.Latest DESC";
  $result = odbc_exec($conn_dm, $select);
  if (!($row = odbc_fetch_array($result))) {
    // TODO: display a message like "either there was a bad error, the course suddenly disappeared or you fooled the system into asking for a course that you are not teaching".
  echo '<!-- debug:3  '. $select .' -->';
    return false;
  }

  echo '<!-- debug:2 -->';
  $textlib = textlib_get_instance();

  $coursetitle = $row['DawsonTitle'];
  $coursedescription = stripslashes($row['Description']); // Datamart descriptions have been addslashesed, apparently.
  $coursedescription = $textlib->convert($coursedescription, 'iso-8859-1', 'utf-8');

  // TODO: verify that coursenumbers are *always* 8 chars long
  $coursecode = substr($fullcoursecode, 3, 8);
  $coursemaxid = get_record_sql("SELECT MAX(CONVERT(SUBSTRING_INDEX(`idnumber`, '_', -1), UNSIGNED)) as num FROM `mdl_course` WHERE idnumber LIKE '$coursecode%'");

  if ($coursemaxid->num === NULL) {
    $seqnum = '0';
  } else {
    $seqnum = $coursemaxid->num + 1;
  }

  if (!($courseid = _cegep_dawson_createcourse($coursecode, $seqnum, $meta, $coursetitle, $coursedescription))) {
    print_error("Une erreur s'est produite lors de la crÃ©ation des cours!");
    break;
  }

  // enrol teacher into it's course
  $enroldb = enroldb_connect();
  // TODO: This might be useless. Teachers get enrolled when the course is created, so...
  $insert = "INSERT INTO `$CFG->enrol_dbname`.`$CFG->enrol_dbtable` (`$CFG->enrol_remotecoursefield` , `$CFG->enrol_remoteuserfield`, `$CFG->enrol_db_remoterolefield`, `coursegroup_id`) VALUES ('". $coursecode ."_". $seqnum ."', '$USER->idnumber', 'editingteacher', '0'); ";
  if (!$resultat = $enroldb->Execute($insert)) {
    trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $insert);
    echo "Erreur : inscription process";
    break;
  }

  $role = get_record('role', 'shortname', 'editingteacher');
  $context = get_context_instance(CONTEXT_COURSE, $courseid);
  if(!role_assign($role->id, $USER->id, 0, $context->id, 0, 0, 0, 'database')) {
    debugging("Problem calling role_assign", DEBUG_DEVELOPER);
  }
}

function _cegep_dawson_createcourse($coursecode, $seqnum, $meta, $coursetitle = '', $coursedescription = "") {
        global $CFG, $USER;

        $site = get_site();
        //$sisdb = sisdb_connect();
        //$select_course = "SELECT * FROM `$CFG->sisdb_name`.`course` WHERE `coursecode` = '$coursecode' LIMIT 1";
        //$coursetitle = $sisdb->Execute($select_course)->fields['title'];

        $course = new StdClass;
        $course->fullname  = $coursetitle .' ('. $coursecode .')';
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

  $course->category = cegep_course_category($course_category);

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
    //$sisdb->Close();
    return false;
  }

  //$sisdb->Close();

  return $newcourseid;
}

/**
 * Returns the list of section numbers enrolled in a given course
 */
function cegep_courses_get_sections($courseidnumber) {
  global $CFG;

  $enroldb = enroldb_connect();
  $select = "SELECT DISTINCT `coursegroup_id` FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$courseidnumber' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IS NOT NULL ORDER BY `coursegroup_id`";
  $coursegroups_rs = $enroldb->Execute($select);

  if (!$coursegroups_rs) {
    trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
    return false;
  }

  $sisdb = sisdb_connect();
  $coursegroup_id = '';
  //$coursegroups = array();
  $html = '<div class="cegep_sections_list">';
  while (!$coursegroups_rs->EOF) {
    $coursegroup_id = $coursegroups_rs->fields['coursegroup_id'];
    $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE id = '$coursegroup_id'";
    $coursegroup = $sisdb->Execute($select)->fields;
    //$coursegroups[] = $coursegroup['group'];
    $html .= '<span class="cegep_section">Section '. $coursegroup['group'] .'</span>';
    $coursegroups_rs->MoveNext();
  }
  $html .= '</div>';
  $enroldb->Close();
  $sisdb->Close();

  return $html;
}

/**
 * Convert A93 into {2009, 3} and B01 into {2010, 1}
 * TODO: (postponed) make this generic to work beyond 2029.
 */
function cegep_dawson_code_to_date($string) {
  $date = array();

  switch (substr($string, 0, 1)) {
    case '8':
      $date['year'] = '198';
      break;
    case '9':
      $date['year'] = '199';
      break;
    case 'A':
      $date['year'] = '200';
      break;
    case 'B':
      $date['year'] = '201';
      break;
    case 'C':
      $date['year'] = '202';
      break;
  }
  $date['year'] .= substr($string, 1, 1);

  $date['term'] = substr($string, 2, 1);

  return $date;
}

/**
 * Convert The given date (or current date if no date is given) into a trimester code
 * Date must be given as yyyy-mm-dd
 */
function cegep_dawson_date_to_code($date = null) {

  if(!$date) {
    $date = date('Y-m-d');
  }

  $code = '';

  switch (substr($date, 0, 3)) {
    case '198':
      $cdoe = '8';
      break;
    case '199':
      $code = '9';
      break;
    case '200':
      $code = 'A';
      break;
    case '201':
      $code = 'B';
      break;
    case '202':
      $code = 'C';
      break;
    default:
      trigger_error('Wrong date given', E_USER_WARNING);
  }

  $code .= substr($date, 3, 1);

  switch (substr($date, 5, 2)) {
    case '01':
    case '02':
    case '03':
    case '04':
      $code .= '1';
      break;
    case '05':
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

///////////////////////// CEGEP HOOKS ////////////////////////////////////

/**
 * Place the course in the correct category
 */
function cegep_local_course_category($category_code) {
  switch ($category_code) {
    case ('401') :
    case ('410') :
      $category = 541; // administration /business administration
      break;

    case ('381') :
      $category = 551; // anthropology
      break;

    case ('101') :
      $category = 561; // biology
      break;

    case ('202') :
    case ('210') :
      $category = 571; // chemistry
      break;

    case ('221') :
    case ('311') :
      $category = 581; // civil engineering tech
      break;

    case ('332') :
      $category = 591; // classics
      break;

    case ('530') :
    case ('589') :
      $category = 601; // communication/cinema
      break;

    case ('391') :
      $category = 611; // community recreation leadership tech
      break;

    case ('420') :
      $category = 621; // computer science
      break;

    case ('320') :
    case ('383') :
      $category = 631; // economics/geography
      break;

    case ('243') :
     $category = 641; // electrical engineering tech
      break;

    case ('603') :
      $category = 651; // english
      break;

    case ('510') :
    case ('511') :
    case ('520') :
      $category = 661; // fine arts
      break;

    case ('602') :
      $category = 671; // french
      break;

    case ('320') :
      $category = 681; // geography
      break;

    case ('205') :
      $category = 691; // geology
      break;

    case ('330') :
    case ('332') :
    case ('370') :
      $category = 711; // history/classics/religion

    case ('340') :
    case ('345') :
      $category = 731; // humanities/philosophy
      break;

    case ('572') :
      $category = 971; // illustration design
      break;

    case ('577') :
      $category = 751; // industrial design
      break;

    case ('573') :
      $category = 761; // interior design
      break;

   case ('990') :
      $category = 1041; // liberal arts
      break;

    case ('201') :
      $category = 771; // mathematics
      break;

    case ('241') :
    case ('242') :
    case ('270') :
      $category = 781; // mechanical engineering tech
      break;

    case ('140') :
      $category = 791; // medical lab technology
      break;

    case ('502') :
    case ('607') :
    case ('608') :
    case ('609') :
    case ('611') :
    case ('613') :
    case ('615') :
      $category = 801; // modern languages
      break;

    case ('550') :
      $category = 811; // music
      break;

    case ('180') :
      $category = 821; // nursing tech
      break;

    case ('412') :
      $category = 831; // office systems tech
      break;

    case ('340') :
      $category = 841; // philosophy
      break;

    case ('574') :
    case ('581') :
      $category = 851; // photography
      break;

    case ('109') :
      $category = 861; // physical education
      break;

    case ('203') :
      $category = 871; // physics
      break;

    case ('560') :
    case ('561') :
      $category = 881; // professional theatre drama
      break;

    case ('350') :
    case ('381') :
      $category = 891; // psychology/anthropology
      break;

    case ('300') :
    case ('360') :
      $category = 911; // quantitative methods
      break;

    case ('142') :
      $category = 921; // radiography tech
      break;

    case ('370') :
      $category = 931; // religion
      break;

    case ('388') :
      $category = 941; // social service tech
      break;

    case ('385') :
    case ('387') :
      $category = 961; // sociology/political science
      break;

    case ('570') :
      $category = 1061; // visual arts
      break;

    default:
      $category = 1051; // misc, catch-all
  }

  return $category;
}


