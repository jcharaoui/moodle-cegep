<?php

function cegep_maisonneuve_sisdbsource_select_students($term) {
return <<< EOD
    DECLARE @AnSession_IN smallint;
    SET @AnSession_IN = $term;
    SELECT
        uo.Numero AS CourseUnit
        ,c.Numero AS CourseNumber
        ,c.TitreMoyen AS CourseTitle
        ,g.Numero AS CourseGroup
        ,e.Numero AS StudentNumber
        ,e.Nom AS StudentLastName
        ,e.Prenom AS StudentFirstName
        ,es.AnSession AS CourseTerm
        ,p.Numero AS StudentProgram
        ,CEILING(CAST(es.SPE AS FLOAT)/2) AS StudentProgramYear
        ,p.TitreLong AS StudentProgramName
    FROM
        Etudiants.Etudiant e
        JOIN Etudiants.EtudiantSession es ON es.IDEtudiant = e.IDEtudiant
        JOIN Inscriptions.Inscription i ON i.IDEtudiantSession = es.IDEtudiantSession
        JOIN Groupes.Groupe g ON g.IDGroupe = i.IDGroupe
        JOIN BanqueCours.Cours c ON c.IDCours = i.IDCours
        JOIN Programmes.Programme p on p.IDProgramme = es.IDProgramme
        JOIN Reference.UniteOrg uo ON uo.IDUniteOrg = i.IDUniteOrg
    WHERE
        es.Etat > 0
        AND i.Etat > 0
        AND uo.IndicateurLocal = 1
        AND es.AnSession >= @AnSession_IN
    ORDER BY
        e.Numero, c.Numero;
EOD;
}

function cegep_maisonneuve_sisdbsource_select_teachers($term) {
return <<< EOD
    DECLARE @AnSession_IN smallint;
    SET @AnSession_IN = $term;
    SELECT
        g.AnSession CourseTerm,
        e.Courriel TeacherNumber,
        c.Numero CourseNumber, 
        g.Numero CourseGroup,
        c.TitreMoyen AS CourseTitle
    FROM
        Employes.Employe e
        JOIN Groupes.EmployeGroupe ge ON e.IDEmploye = ge.IDEmploye
        JOIN Groupes.Groupe g ON g.IDGroupe = ge.IDGroupe
        JOIN BanqueCours.Cours c ON g.IDCours = c.IDCours
    WHERE
        g.AnSession >= @AnSession_IN
    ORDER BY
        g.AnSession, e.Numero, c.Numero, g.Numero;
EOD;
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

    case 'courseterm':
        // Break into array of year and semester
        return array('year' => substr($data, 0, 4), 'semester' => substr($data, 4, 1));
        break;

    case 'program':
        // Remove hyphens
        return str_replace('.', '', $data);
        break;

    case 'teachernumber':
        // Email : use only user part
        $parts = explode('@', $data);
        return $parts[0];

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
