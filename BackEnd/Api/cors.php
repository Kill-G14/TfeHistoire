<?php

// Origines autorisées à appeler l'API depuis un autre site (liste ALLOWED_ORIGINS du .env,
// séparée par des virgules). Les appels du site lui-même (même origine) n'ont pas besoin de CORS.
// Inclus en tête de chaque API : l'en-tête n'est envoyé que pour une origine connue.
require_once __DIR__ . '/../vendor/autoload.php';

\App\Utils\EnvLoader::load();

$allowedOrigins = array_filter(array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? '')));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
  header("Access-Control-Allow-Origin: " . $requestOrigin);
  // Le résultat dépend de l'origine : les caches ne doivent pas mélanger les réponses
  header("Vary: Origin");
}
