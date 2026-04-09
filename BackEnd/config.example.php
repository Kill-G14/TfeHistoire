<?php

/**
 * Configuration de l'application - TEMPLATE
 * 
 * Copiez ce fichier en config.php et remplissez vos valeurs.
 */

return [
  // API OpenRouteService
  'openroute' => [
    'api_key' => 'VOTRE_CLE_API_ICI',
    'base_url' => 'https://api.openrouteservice.org/v2'
  ],

  // Base de données
  'database' => [
    'host' => 'localhost',
    'name' => 'nom_base_de_donnees',
    'user' => 'utilisateur',
    'password' => 'mot_de_passe'
  ],

  // Autres configurations
  'app' => [
    'name' => 'MemoriaEventia',
    'environment' => 'development', // development, production
    'debug' => true
  ]
];
