<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_f2freport';

// Version du plugin : Format YYYYMMDDXX 
// ⚠️ Incrémentée pour forcer l'upgrade et enregistrer la nouvelle capability.
$plugin->version = 2025090600;  

// Version minimum de Moodle requise (ici Moodle 4.2.0)
$plugin->requires = 2023042400;

// Niveau de maturité du plugin
$plugin->maturity = MATURITY_STABLE;

// Version de release (format sémantique)
$plugin->release = 'v1.0.1';

// Dépendances
$plugin->dependencies = [
    // Vérifie seulement que le plugin facetoface existe.
    // Pas de contrainte de version stricte pour éviter les conflits.
    'mod_facetoface' => ANY_VERSION,
];

// Alternative : supprimer complètement les dépendances si nécessaire
// $plugin->dependencies = [];
