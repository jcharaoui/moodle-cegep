<?php

function cegep_dawson_sisdbsource_select_students($term) {
    
    $select = "DECLARE @AnSession_IN smallint;
             SET @AnSession_IN = $term;
             SELECT 
                uo.Numero AS CourseUnit,
                c.Numero AS CourseNumber,
                ISNULL(c.TitreMoyenTraduit,c.TitreMoyen)  As CourseTitle,
                g.Numero AS CourseGroup,
                e.Numero AS StudentNumber,
                e.Nom AS StudentLastName,
                e.Prenom AS StudentFirstName,
                es.AnSession AS CourseTerm,
                p.Numero AS StudentProgram,
                CEILING(CAST(es.SPE AS FLOAT)/2) AS StudentProgramYear,
                p.TitreLong AS StudentProgramName
            FROM Etudiants.Etudiant e
            JOIN Etudiants.EtudiantSession es ON es.IDEtudiant = e.IDEtudiant
            JOIN Inscriptions.Inscription i ON i.IDEtudiantSession = es.IDEtudiantSession
            JOIN Groupes.Groupe g ON g.IDGroupe = i.IDGroupe
            JOIN BanqueCours.Cours c ON c.IDCours = i.IDCours
            JOIN Programmes.Programme p on p.IDProgramme = es.IDProgramme
            JOIN Reference.UniteOrg uo ON uo.IDUniteOrg = i.IDUniteOrg
            WHERE es.Etat > 0
                AND i.Etat > 0
                AND uo.IndicateurLocal = 1
                AND es.AnSession >= @AnSession_IN
            ORDER BY e.Numero, c.Numero";
    return cegep_dawson_prepare_select_query($select);
}

function cegep_dawson_sisdbsource_select_teachers($term) {
    $select = "DECLARE @AnSession_IN smallint;
                SET @AnSession_IN = $term; 
            SELECT DISTINCT 
                g.AnSession CourseTerm, 
                e.Numero TeacherNumber, 
                c.Numero CourseNumber, 
                g.Numero CourseGroup, 
                ISNULL(c.TitreMoyenTraduit,c.TitreMoyen)  As CourseTitle
            FROM 
                Employes.Employe e 
            JOIN Groupes.EmployeGroupe ge ON e.IDEmploye = ge.IDEmploye 
            JOIN Groupes.Groupe g ON g.IDGroupe = ge.IDGroupe 
            JOIN BanqueCours.Cours c ON g.IDCours = c.IDCours 
            WHERE g.AnSession >= @AnSession_IN 
            ORDER BY g.AnSession, e.Numero, c.Numero, g.Numero;";

    return cegep_dawson_prepare_select_query($select);
}

function cegep_dawson_sisdbsource_decode($field, $data) {
    switch ($field) {
        case 'studentnumber':
            /* Check LDAP if the numeric student number exists. If it does, return it.
             * Otherwise, return the alphanumeric. */

            $studno = cegep_dawson_convert_longstudentno_to_dawno($data);

            if (cegep_dawson_search_ldap_student_number($studno)) {
                return $studno;
            }
            else {
                return cegep_dawson_convert_dawno_to_studentno($studno); 
            }
            break;

        case 'courseterm':
            // Break into array of year and semester
            return array('year' => substr($data, 0, 4), 'semester' => substr($data, 4, 1));
            break;

        case 'studentlastname':
        case 'studentfirstname':
            return utf8_encode($data);

        default:
            return $data;
            break;
    }
}

/**
 * Place the course in the correct category
 */
function cegep_dawson_course_category($category_code) {
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

/**
 * Returns the list of section numbers enrolled in a given course
 */
function cegep_dawson_courses_get_sections($courseidnumber, $courseid = 0, &$has_sections = false, $filter_term = 'all', &$echo_course = false) {
  global $CFG;

  $echo_course = false;

  if ($filter_term == 'all') {
      $echo_course = true;
  }

  $enroldb = enroldb_connect();
  $select = "SELECT DISTINCT `coursegroup_id` FROM `$CFG->enrol_dbtable` WHERE `$CFG->enrol_remotecoursefield` = '$courseidnumber' AND `$CFG->enrol_db_remoterolefield` = '$CFG->block_cegep_studentrole' AND `coursegroup_id` IS NOT NULL ORDER BY `coursegroup_id`";

  $coursegroups_rs = $enroldb->Execute($select);

  if (!$coursegroups_rs) {
    trigger_error($enroldb->ErrorMsg() .' STATEMENT: '. $select, E_USER_ERROR);
    return false;
  }

  $sisdb = sisdb_connect();
  $coursegroup_id = '';

  $has_sections = false;

  $html = '';

  $on_first = true;

  while (!$coursegroups_rs->EOF) {
    if($on_first) {
        $html .= '<span class="cegep_section">';
    }

    $coursegroup_id = $coursegroups_rs->fields['coursegroup_id'];
    $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE id = '$coursegroup_id'";
    $coursegroup = $sisdb->Execute($select)->fields;

    if (!$on_first) {
        $html .= ', ' . strtolower(get_string("cegepsection", "block_cegep")) . ' ' . $coursegroup['group'] .' (' . cegep_dawson_term_to_string($coursegroup['term']) . ')';
    }
    else {
        $html .= get_string("cegepsection", "block_cegep") . ' ' . $coursegroup['group'] .' (' . cegep_dawson_term_to_string($coursegroup['term']) . ')';
    }

    if (!empty($filter_term) && $filter_term != 'all') {
        if ($coursegroup['term'] == $filter_term) {
            $echo_course = true;
        }
    }

    $has_sections = true;
    $coursegroups_rs->MoveNext();

    if ($on_first) {
        $on_first = false;
    }
  }

  if (!$has_sections) {
        $html .= '<span class="error">' . get_string("cegepnosections", "block_cegep") . '</span>';
  }
  else {
        $html .= '</span>';
  }

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
 * Convert The given date (or current date if no date is given) into a term code
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


/* Convert the long student numbers with leading years to the
 * dawson 7-digit numbers, with leading #s and letters.
 */
function cegep_dawson_convert_longstudentno_to_dawno($studentno) {
    $substr = substr($studentno, 0, 2);
    $newno = '';

    switch ($substr) {
        case '20':
            $newno = substr($studentno, 2, 7);
            if ($newno[0] == '0') {
                $newno[0] = 'A';
            }
            else if ($newno[0] == '1') {
                $newno[0] = 'B';
            }
            else if ($newno[0] == '2') {
                $newno[0] = 'C';
            }
            else if ($newno[0] == '3') {
                $newno[0] = 'D';
            }
            break;

        case '19':
            $newno = substr($studentno, 2, 7);
            break;

        default:
            break;
    }
    return $newno;
}

/**
 * Convert a term code (YYYYS) into a string,
 * like 'Fall 2009' or 'Winter 2010'.
 */
function cegep_dawson_term_to_string($code) {
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

function cegep_dawson_prepare_select_query($query) {
    $query = str_replace("'","''",$query);
    $query = "SELECT * FROM OPENQUERY(CLARAREPROTPRODLINK, '$query')";
    return $query;
}

function cegep_dawson_sisdbsource_connect($type, $host, $name, $user, $pass) {
    // Try to connect to the external database (forcing new connection)
    $db = &ADONewConnection($type);
    if ($db->Connect($host, $user, $pass, $name, true)) {
        $db->SetFetchMode(ADODB_FETCH_ASSOC); ///Set Assoc mode always after DB connection
        $db->Execute("SET ANSI_WARNINGS ON");
        $db->Execute("SET ANSI_NULLS ON");
        return $db;
    } else {
        trigger_error("Error connecting to DB backend with: "
                      . "$host, $user, $pass, $name");
        return false;
    }
}

function cegep_dawson_print_course_information($course, $view_filters = array(), &$echo_course = false) {
    global $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $course->id);


    $linkcss = '';
    if (empty($course->visible)) {
        $linkcss = 'class="dimmed"';
    }

    $can_edit = has_capability('moodle/course:update', $context);

    if ($can_edit) { //display for teachers

        if (!empty($view_filters['filter_visible']) && $view_filters['filter_visible'] == 'visible' && empty($course->visible)) {
            $echo_course = false;
            return '';
        }

        if (!empty($view_filters['filter_visible']) && $view_filters['filter_visible'] == 'invisible' && $course->visible) {
            $echo_course = false;
            return '';
        }

        if (empty($view_filters['filter_term'])) {
            $view_filters['filter_term'] = 'all';
        }

        $html = '<table class="cegep_course_status">';

        $html .= '<thead><tr><th colspan="3"><a title="' . format_string($course->fullname) . '" ' . $linkcss . ' href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' . format_string($course->fullname) . '</a> <span class="course_settings_link">(<a href="' . $CFG->wwwroot . '/course/edit.php?id=' . $course->id . '">' . get_string("cegepeditsettings", "block_cegep") . '</a>)</span></th></tr></thead>';

        $html .= '<tbody>';

        if ($course->idnumber != '') {
            $html .= '<tr><td class="left">' . get_string("cegepenrolled", "block_cegep") . '</td><td>' . cegep_dawson_courses_get_sections($course->idnumber, $course->id, $has_sections, $view_filters['filter_term'], $echo_course) .'</td>';

            /* if we find the term name in the title of the course, consider it
             * as part of this term. */
             if (stripos($course->fullname, cegep_dawson_term_to_string($view_filters['filter_term'])) !== false) {
                 $echo_course = true;
            }
        }
        else {
            $html .= '<tr><td class="left">' . get_string("cegepenrolled", "block_cegep") . '</td><td>N/A&nbsp;&nbsp;<a onclick="this.target=\'popup\'; return openpopup(\'/help.php?module=cegep&file=no.sections.html\', \'popup\',\'menubar=0,location=0,scrollbars,resizable,width=500,height=400\',0);" href="' . $CFG->wwwroot . '/blocks/cegep/no.sections.html"><img src="' . $CFG->wwwroot . '/pix/help.gif" alt="Why no sections?" title="Why no sections?" class="iconhelp" /></a></td>';
        }

        if ($can_edit) {
            if (!empty($has_sections) && $has_sections) {
                $html .= '<td><input type="button" onclick="window.location=\'' . $CFG->wwwroot . '/blocks/cegep/block_cegep_enrolment.php?a=unenrol&id=' . $course->id . '\';" value="Manage sections" /></td>';
            }
            else {
                $html .= '<td><input type="button" onclick="window.location=\'' . $CFG->wwwroot . '/blocks/cegep/block_cegep_enrolment.php?a=enrol&id=' . $course->id . '\';" value="Manage sections" /></td>';
            }
        }
        else {
            $html .= '<td></td>';
        }

        $html .= '</tr>';


        $html .= '<tr><td class="left">' . get_string("cegepvisible", "block_cegep") . '</td><td>' . ($course->visible ? '<span class="good">' . get_string('cegepvisibleyes', 'block_cegep') . '</span>' : '<span class="error">' . get_string('cegepvisibleno', 'block_cegep') . '</span></td>') . '<td class="right"><input type="button" onclick="window.location=\'' . $CFG->wwwroot . '/course/edit.php?id=' . $course->id . '&change_avail=1#avail\';" value="Change" /></td></tr>';
    }
    else { //display for students / non-editing teachers
        if (!empty($view_filters['filter_visible']) && $view_filters['filter_visible'] == 'visible' && empty($course->visible)) {
            $echo_course = false;
            return '';
        }

        if (!empty($view_filters['filter_visible']) && $view_filters['filter_visible'] == 'invisible' && $course->visible) {
            $echo_course = false;
            return '';
        }

        $html = '<table class="cegep_course_status">';

        $html .= '<thead><tr><th colspan="3"><a title="' . format_string($course->fullname) . '" ' . $linkcss . ' href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' . format_string($course->fullname) . '</a></th></tr></thead>';

        $html .= '<tbody>';

        $teachers = cegep_dawson_get_teachers($course->id);
        if (strlen($teachers) > 0) {
            $html .= '<tr><td class="left">Teacher' . (count(explode(",", $teachers)) > 1 ? 's' : '') . ':</td><td>' . $teachers . '</td></tr>';
        }

        $sections = cegep_dawson_courses_get_sections($course->idnumber, $course->id, $has_sections, $view_filters['filter_term'], $echo_course);

        if (strpos($sections, "None") === false) {
            $html .= '<tr><td class="left">Section' . (count(explode(",",$sections)) > 1 ? 's' : '') . ':</td><td>' . $sections .'</td></tr>';
        }
    }
    if (!$echo_course) {
        $html = '';
    }
    return $html;
}

function cegep_dawson_print_delete_button($course) {
    global $CFG;
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $can_edit = has_capability('moodle/course:update', $context);
    $html = '';
    if ($can_edit) {
        $html .= '<tr><td colspan="3"><table style="width: 100%;"><tr><td style="text-align: center; border: none;"><form method="get" action="' . $CFG->wwwroot . '/course/delete.php"><input type="submit" value="Delete this course" /><input type="hidden" name="id" value="' . $course->id . '" /></form></td>';
    }
    return $html;
}

function cegep_dawson_print_backup_button($course) {
    global $CFG;
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $can_edit = has_capability('moodle/course:update', $context);
    $html = '';
    if ($can_edit) {
        $html .= '<td style="text-align: center; border: none;"><form method="get" action="' . $CFG->wwwroot . '/backup/backup.php"><input type="submit" value="Backup this course" /><input type="hidden" name="id" value="' . $course->id . '" /></form></td></tr></table></td></tr>';
    }
    return $html;
}

/* Return a comma-separated list of teachers */
function cegep_dawson_get_teachers ($courseid) {
    if (! $course = get_record('course', 'id', $courseid)) {
        return '';
    }

    if (! $context = get_context_instance(CONTEXT_COURSE, $course->id)) {
        return '';
    }

    $query = "SELECT * FROM mdl_role WHERE shortname='editingteacher' OR shortname='teacher'";
    if (! $roleset = get_recordset_sql($query)) {
        return '';
    }

    $roleids = array();

    foreach ($roleset as $role) {
        $roleids[] = $role['id'];
    }

    $roleidlist = join(",", $roleids);

    $query = "
    SELECT DISTINCT u.id, u.username, u.firstname, u.lastname,
                      u.email, u.city, u.country, u.picture,
                      u.lang, u.timezone, u.emailstop, u.maildisplay, u.imagealt,
                      COALESCE(ul.timeaccess, 0) AS lastaccess,
                      r.hidden,
                      ctx.id AS ctxid, ctx.path AS ctxpath,
                      ctx.depth AS ctxdepth, ctx.contextlevel AS ctxlevel FROM mdl_user u
                LEFT OUTER JOIN mdl_context ctx
                    ON (u.id=ctx.instanceid AND ctx.contextlevel = 30)
                JOIN mdl_role_assignments r
                    ON u.id=r.userid
                LEFT OUTER JOIN mdl_user_lastaccess ul
                    ON (r.userid=ul.userid and ul.courseid = $course->id) WHERE (r.contextid = $context->id)
            AND u.deleted = 0  AND r.roleid IN (" . $roleidlist . ")
            AND (ul.courseid = $course->id OR ul.courseid IS NULL)
            AND u.username != 'guest'
            AND r.roleid NOT IN (1,11,81)
              ORDER BY lastaccess DESC, r.hidden DESC";

    $userlist = get_recordset_sql($query);
    $teachers = '';
    $first = true;
    foreach ($userlist as $usr) {
        if ($first) {
            $teachers .= $usr['firstname'] . ' ' . $usr['lastname'];
            $first = false;
        }
        else {
            $teachers .= ', ' . $usr['firstname'] . ' ' . $usr['lastname'];
        }
    }
    return $teachers;
}

function cegep_dawson_get_enrolled_sections() {
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
    while (!$coursegroups_rs->EOF) {
        $coursegroup_id = $coursegroups_rs->fields['coursegroup_id'];
        $coursegroup_num = $coursegroups_rs->fields['num'];
        $select = "SELECT * FROM `$CFG->sisdb_name`.`coursegroup` WHERE id = '$coursegroup_id'";
        $coursegroup = $sisdb->Execute($select)->fields;

        if (!is_array($terms_sections[$coursegroup['term']])) {
            $terms_sections[$coursegroup['term']] = array();
        }
        $terms_sections[$coursegroup['term']][] = $coursegroup['group'];

        $coursegroups_rs->MoveNext();
    }
    return $terms_sections;
}

function cegep_dawson_get_sisdb_student_insert($code_etudiant, $last_name, $first_name, $program, $programyear) {
    global $CFG;
    return "INSERT INTO `$CFG->sisdb_name`.`student` (`username` , `lastname`, `firstname`, `program`) VALUES ('$code_etudiant', \"$last_name\", \"$first_name\", \"$program\"); ";
}

function cegep_dawson_get_sisdb_student_update($code_etudiant, $last_name, $first_name, $program, $programyear) {
    global $CFG;
    return "UPDATE `$CFG->sisdb_name`.`student` SET `lastname` = \"$last_name\", `firstname` = \"$first_name\", `program` = \"$program\" WHERE `username` = '$code_etudiant'; ";
}

function cegep_dawson_convert_dawno_to_studentno($studentno) {
    $newno = $studentno;

    switch(strtolower($studentno[0])) {
        case 'a':
            $newno[0] = '0';
            break;
        case 'b':
            $newno[0] = '1';
            break;
        case 'c':
            $newno[0] = '2';
            break;
        case 'd':
            $newno[0] = '3';
            break;
        default:
            break;
    }
    return $newno;
}

/* 'cegep_dawson_search_ldap_student_number'
 *
 * Does an anonymous bind to LDAP, returns true if
 * the username / student number was found, false otherwise.
 */
function cegep_dawson_search_ldap_student_number($search) {
    $filter = "(&(objectclass=person)(cn=$search))";
    $attributes = array("cn");
    $context = "o=ds";
    $conn = ldap_connect("dc1.dawsoncollege.qc.ca", 389);
    ldap_bind($conn);
    $search = ldap_search($conn, $context, $filter, $attributes);
    $results = ldap_get_entries($conn, $search);
    if ($results['count'] == 0) {
        return false;
    }   
    else {
        return true;
    }   
}

