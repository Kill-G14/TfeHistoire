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

  // Stripe
  'stripe' => [
    'secret_key' => 'sk_test_VOTRE_CLE_SECRETE_ICI', // Clé secrète Stripe (test)
    'publishable_key' => 'pk_test_VOTRE_CLE_PUBLIQUE_ICI', // Clé publique Stripe (test)
    'webhook_secret' => 'whsec_VOTRE_SECRET_WEBHOOK_ICI', // Secret webhook Stripe
    'currency' => 'eur', // Devise par défaut
    'success_url' => 'http://localhost/tfeHistoire/#/payment/success', // URL de succès
    'cancel_url' => 'http://localhost/tfeHistoire/#/payment/cancel' // URL d'annulation
  ],

  // Autres configurations
  'app' => [
    'name' => 'MemoriaEventia',
    'environment' => 'development', // development, production
    'debug' => true
  ]
];
