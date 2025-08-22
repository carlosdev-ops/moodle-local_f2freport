<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_f2freport';

// Version du plugin : Format YYYYMMDDXX 
$plugin->version = 2025080500;

// Version minimum de Moodle requise
$plugin->requires = 2023042400; // Moodle 4.2.0

// Niveau de maturité du plugin
$plugin->maturity = MATURITY_STABLE;

// Version de release (format sémantique)
$plugin->release = 'v1.0.0';

// DÉPENDANCES AJUSTÉES - Plus flexibles
$plugin->dependencies = [
    // On vérifie seulement que le plugin facetoface existe, 
    // sans forcer une version spécifique qui pourrait être incompatible
    'mod_facetoface' => ANY_VERSION, // Accepte n'importe quelle version
];

// Alternative : supprimer complètement les dépendances si le problème persiste
// $plugin->dependencies = [];