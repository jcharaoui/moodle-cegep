<?php

function cegep_maisonneuve_sisdbsource_select_students($trimester) {

    $select = "
        DECLARE @AnSession_IN smallint;
        SET @AnSession_IN = $trimester;
        SELECT uo.Numero AS CourseCampus
            ,c.Numero AS CourseNumber
            ,c.TitreMoyen AS CourseTitle
            ,g.Numero AS CourseGroup
            ,e.Numero AS StudentNumber
            ,e.Nom AS StudentLastName
            ,e.Prenom AS StudentFirstName
            ,es.AnSession AS CourseTrimester
            ,p.Numero AS StudentProgram
            ,CEILING(CAST(es.SPE AS FLOAT)/2) AS StudentProgramYear
            ,p.TitreLong AS StudentProgramName
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
        ORDER BY e.Numero, c.Numero;";

    return $select;
}

function cegep_maisonneuve_sisdbsource_select_teachers($term) {

   $select = "
       DECLARE @AnSession_IN smallint;
       SET @AnSession_IN = $term;
       SELECT DISTINCT
           g.AnSession CourseTerm,
           e.Numero TeacherNumber,
           c.Numero CourseNumber,
           g.Numero CourseGroup
        FROM
            Employes.Employe e
            JOIN Horaires.RencontreEmploye hre ON hre.IDEmploye = e.IDEmploye
            JOIN Horaires.RencontreGroupe hrg ON hrg.IDRencontre = hre.IDRencontre
            JOIN Groupes.Groupe g ON g.IDGroupe = hrg.IDGroupe
            JOIN BanqueCours.Cours c ON g.IDCours = c.IDCours
        WHERE
            e.IDTypeEmploye = 1 AND
            g.AnSession >= @AnSession_IN
       ORDER BY
            g.AnSession, e.Numero, c.Numero, g.Numero;";

    return $select;
}

function cegep_maisonneuve_current_trimester() {

    // Year
    $trimester = date('Y');

    // Trimester
    switch (date('m')) {
        case '01':
        case '02':
        case '03':
        case '04':
            $trimester .= '1';
            break;
        case '05':
        case '06':
        case '07':
        case '08':
            $trimester .= '2';
            break;
        case '09':
        case '10':
        case '11':
        case '12':
            $trimester .= '3';
            break;
    }

    return $trimester;
}

function cegep_maisonneuve_sisdbsource_decode($field, $data) {
    switch ($field) {

    case 'studentnumber':
        // Replace two leading numbers by 'e'
        return 'e' . substr($data, 2);
        break;

    case 'coursenumber':
        // Remove hyphens
        return str_replace('-', '', $data);
        break;

    case 'coursegroup':
        // Remove hyphens
        return str_pad($data, 6, '0', STR_PAD_LEFT);
        break;

    case 'coursetrimester':
    case 'courseterm':
        // Break into array of year and trimester
        return array('year' => substr($data, 0, 4), 'trimester' => substr($data, 4, 1));
        break;

    case 'program':
        // Remove hyphens
        return str_replace('.', '', $data);
        break;

    default:
        // Do nothing
        return $data;
        break;
    }
    
}

function cegep_maisonneuve_course_category($category_code) {
    switch ($category_code) {
        case ('101') :
            $category = 2; // biologie
            break;
        case ('202') :
            $category = 3; // chimie
            break;
        case ('109') :
            $category = 4; // Éducation physique
            break;
        case ('501') :
        case ('502') :
        case ('530') :
        case ('601') :
            $category = 5; // Français
            break;
        case ('320') :
        case ('330') :
            $category = 6; // Histoire-géographie
            break;
        case ('520') :
            $category = 7; // Histoire de l'art
            break;
        case ('420') :
            $category = 8; // Informatique
            break;
        case ('210') :
            $category = 9; // ICP
            break;
        case ('604') :
        case ('607') :
        case ('609') :
            $category = 10; // Langues modernes
            break;
        case ('201') :
        case ('360') :
            $category = 11; // Mathématiques
            break;
        case ('340') :
            $category = 12; // Philosophie
            break;
        case ('203') :
            $category = 13; // Physique
            break;
        case ('350') :
            $category = 14; // Psychologie
            break;
        case ('300') :
        case ('383') :
        case ('385') :
        case ('387') :
            $category = 15; // Sciences sociales
            break;
        case ('180') :
            $category = 16; // SIN
            break;
        case ('310') :
            $category = 17; // TAJ
            break;
        case ('412') :
            $category = 18; // TBU
            break;
        case ('401') :
        case ('410') :
            $category = 19; // TAD
            break;
        case ('120') :
            $category = 20; // TDI
            break;
        case ('393') :
            $category = 21; // TDOC
            break;
        case ('111') :
            $category = 22; // THD
            break;
        case ('243') :
            $category = 23; // TGE
            break;
        case ('582') :
            $category = 24; // TIM
            break;
        default:
            $category = 1; // misc, catch-all
    }

    return $category;
}

function cegep_maisonneuve_create_course($coursecode, $meta = 0) {
    global $CFG;

    // Generate unique course idnumber
    $coursecode = strtoupper($coursecode);
    $coursemaxid = get_record_sql("SELECT MAX(CONVERT(SUBSTRING_INDEX(`idnumber`, '_', -1), UNSIGNED)) as num FROM `mdl_course` WHERE idnumber LIKE '$coursecode%'");
    if ($coursemaxid->num === null) {
        $seqnum = '0';
    } else {
        $seqnum = $coursemaxid->num + 1;
    }

    $site = get_site();
    $sisdb = sisdb_connect();

    $select_course = "select * from `$CFG->sisdb_name`.`course` where `coursecode` = '$coursecode' limit 1";
    $coursetitle = cegep_maisonneuve_sisdbsource_decode('coursetitle',$sisdb->execute($select_course)->fields['title']);

    $course = new stdclass;
    $course->fullname  = $coursetitle;
    $course->shortname = $coursecode . '_' . $seqnum;
    $course->idnumber = $coursecode . '_' . $seqnum;
    $course->metacourse = $meta;

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

    // overlay template
    foreach (array_keys($template) as $key) {
        if (empty($course->$key)) {
            $course->$key = $template[$key];
        }
    }

    $category = cegep_local_course_category(substr($coursecode, 0, 3));

    if ($category) {
        $course->category = $category;
    } else {
         trigger_error("could not create new course $extcourse from database");
        notify("serious error! could not create the new course!");
        $sisdb->close();
        return false;
   }

    // define the sortorder
    $sort = get_field_sql('select coalesce(max(sortorder)+1, 100) as max ' .
            ' from ' . $CFG->prefix . 'course ' .
            ' where category=' . $course->category);
    $course->sortorder = $sort;

    // override with local data
    $course->startdate   = time() + 3600 * 24;
    $course->timecreated = time();
    $course->visible     = 0;
    $course->enrollable  = 0;

    // clear out id just in case
    unset($course->id);

    // store it and log
    if ($newcourseid = insert_record("course", addslashes_object($course))) {  // set up new course
        $section = null;
        $section->course = $newcourseid;   // create a default section.
        $section->section = 0;
        $section->id = insert_record("course_sections", $section);
        $page = page_create_object(PAGE_COURSE_VIEW, $newcourseid);
        blocks_repopulate_page($page); // return value no

        fix_course_sortorder();

        add_to_log($newcourseid, "course", "new", "view.php?id=$newcourseid", "block_cegep/request course created");

    } else {
        trigger_error("could not create new course $extcourse from  from database");
        notify("serious error! could not create the new course!");
        $sisdb->close();
        return false;
    }

    $sisdb->close();

    return $newcourseid;
}

function cegep_maisonneuve_create_enrolment($courseidnumber, $username, $request_id = '') {
    global $CFG;
    
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

