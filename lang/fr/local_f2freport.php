<?php
// Chaînes pour le composant 'local_f2freport', langue 'fr'.
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Rapport des sessions Face-to-Face';
$string['report_heading'] = 'F2F – Rapport des sessions';
$string['report_title'] = 'Sessions de formation';

// Capability (affichage propre dans la gestion des rôles).
$string['f2freport:view'] = 'Voir le rapport F2F';

// Erreurs.
$string['errortablenotfound'] = 'La classe de table des sessions est introuvable. Assure-toi que classes/table/sessions_table.php existe.';

// ─────────────────────────────────────────────────────────────────────────────
// Réglages d’administration (utilisés par local/f2freport/settings.php)
// Libellés des colonnes
$string['courseid']          = 'ID du cours';
$string['sessionid']         = 'ID de la session';
$string['timestart']         = 'Heure de début';
$string['timefinish']        = 'Heure de fin';
$string['city']              = 'Ville';
$string['venue']             = 'Lieu';
$string['room']              = 'Salle';
$string['totalparticipants'] = 'Nombre de participants';
$string['coursefullname']    = 'Nom complet du cours';

// Page de réglages & descriptions
$string['settings_columns'] = 'Colonnes affichées par défaut';
$string['settings_columns_desc'] =
    'Choisissez les colonnes affichées par défaut dans le rapport F2F. Les utilisateurs peuvent les modifier si les préférences de table sont activées.';

$string['settings_aliases_city'] = 'Alias du champ « Ville »';
$string['settings_aliases_city_desc'] =
    'Si votre modèle de données utilise un autre nom de champ pour la ville, indiquez-le ici (ex. <code>location_city</code>). Laissez vide pour utiliser la valeur par défaut.';

$string['settings_aliases_venue'] = 'Alias du champ « Lieu »';
$string['settings_aliases_venue_desc'] =
    'Si votre modèle de données utilise un autre nom de champ pour le lieu, indiquez-le ici (ex. <code>location_venue</code>). Laissez vide pour utiliser la valeur par défaut.';

$string['settings_aliases_room'] = 'Alias du champ « Salle »';
$string['settings_aliases_room_desc'] =
    'Si votre modèle de données utilise un autre nom de champ pour la salle, indiquez-le ici (ex. <code>location_room</code>). Laissez vide pour utiliser la valeur par défaut.';

$string['settings_pagesize'] = 'Taille de page';
$string['settings_pagesize_desc'] =
    'Nombre de lignes par page pour le tableau des sessions.';

// ─────────────────────────────────────────────────────────────────────────────
// Filtres (formulaire)
$string['filters_title'] = 'Filtres';
$string['filters_intro'] = 'Affinez l’ensemble avant de lister les sessions.';

$string['applyfilters'] = 'Appliquer';
$string['resetfilters'] = 'Réinitialiser';

$string['filter_period_header'] = 'Période';
$string['filter_datefrom'] = 'Date de début';
$string['filter_datefrom_help'] = 'Afficher uniquement les sessions à partir de cette date.';
$string['filter_dateto'] = 'Date de fin';
$string['filter_dateto_help'] = 'Afficher uniquement les sessions jusqu’à cette date.';

$string['filter_other_header'] = 'Autres filtres';
$string['filter_location'] = 'Lieu (contient)';
$string['filter_location_help'] = 'Filtrer les sessions dont le lieu contient ce texte.';
$string['filter_trainers'] = 'Formateurs';
$string['filter_trainers_help'] = 'Filtrer les sessions assurées par un ou plusieurs formateurs.';
$string['filter_trainers_noselect'] = 'Choisir des formateurs…';
$string['filter_status'] = 'Statut';
$string['filter_any'] = 'Tous';

$string['status_planned'] = 'Planifiée';
$string['status_completed'] = 'Complétée';
$string['status_cancelled'] = 'Annulée';

// Entêtes de table (si utilisés)
$string['th_session'] = 'Session';
$string['th_date'] = 'Date';
$string['th_location'] = 'Lieu';
$string['th_trainer'] = 'Formateur';
$string['th_status'] = 'Statut';


$string['error_daterange'] = 'La date de fin doit être postérieure ou égale à la date de début.';
$string['notspecified'] = 'Non spécifié';
