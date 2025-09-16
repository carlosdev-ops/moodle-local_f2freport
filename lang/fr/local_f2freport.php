<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * French language strings for the face-to-face report.
 *
 * @package    local_f2freport
 * @copyright  2025 Gemini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Rapport des sessions Face-to-face';
$string['trainingreporttitle'] = 'Sessions de formation';
$string['trainingreportheading'] = 'Sessions de formation';
$string['filtercourse'] = 'Cours';
$string['allcourses'] = 'Tous les cours';
$string['futureonly'] = 'Afficher seulement les sessions à venir';
$string['filter'] = 'Filtrer';
$string['reset'] = 'Réinitialiser';
$string['city'] = 'Ville';
$string['venue'] = 'Lieu';
$string['room'] = 'Salle';
$string['timestart'] = 'Début';
$string['timefinish'] = 'Fin';
$string['courseid'] = 'ID du cours';
$string['sessionid'] = 'ID de session';
$string['coursename'] = 'Cours';
$string['totalparticipants'] = 'Participants';
$string['notrainer'] = 'Aucun formateur';
$string['trainer'] = 'Formateur';
$string['notspecified'] = 'Non spécifié';
$string['invalidcourse'] = 'Cours invalide sélectionné. Affichage de tous les cours.';
$string['nosessions'] = 'Aucune session à afficher avec les filtres actuels.';
$string['filters'] = 'Filtres';
$string['showingcount'] = 'Affichage de {$a} session(s)';
$string['missingfields'] = 'Les champs personnalisés requis (ville/lieu/salle) sont introuvables. Vérifiez la configuration Face-to-face.';
$string['f2freport:viewreport'] = 'Voir le rapport Face-to-face';

// Paramètres (nouveaux).
$string['settings_columns'] = 'Colonnes à afficher';
$string['settings_columns_desc'] = 'Sélectionnez les colonnes à afficher dans le tableau des sessions.';
$string['settings_aliases_city'] = 'Alias du champ « Ville »';
$string['settings_aliases_city_desc'] = 'Liste des shortnames/noms reconnus comme “Ville” (séparés par des virgules, ex. city, ville, location).';
$string['settings_aliases_venue'] = 'Alias du champ « Lieu »';
$string['settings_aliases_venue_desc'] = 'Liste des shortnames/noms reconnus comme “Lieu” (séparés par des virgules, ex. venue, lieu, building, site, centre, center, campus).';
$string['settings_aliases_room'] = 'Alias du champ « Salle »';
$string['settings_aliases_room_desc'] = 'Liste des shortnames/noms reconnus comme “Salle” (séparés par des virgules, ex. room, salle, classroom, roomnumber).';
$string['settings_pagesize'] = 'Lignes par page';
$string['settings_pagesize_desc'] = 'Nombre de lignes affichées par page (par défaut : 25).';
$string['datefrom'] = 'Date de début';
$string['dateto'] = 'Date de fin';
$string['showcustomcols'] = 'Afficher les colonnes personnalisées';
$string['showcustomcols_desc'] = 'Si coché, le rapport inclura des colonnes personnalisées supplémentaires.';
$string['privacy:metadata'] = 'Le plugin Rapport Face-to-face ne stocke aucune donnée personnelle. Il ne fait qu\'afficher des données provenant d\'autres plugins Moodle.';
$string['gotocourse'] = 'Aller au cours';
$string['filter_startdate'] = 'Date de début';
$string['filter_enddate'] = 'Date de fin';
$string['filter_upcoming'] = 'Afficher seulement les sessions à venir';
$string['filter_course']   = 'Cours';
$string['notapplicable'] = '—';
