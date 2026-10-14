<?php

// Plan du site pour les moteurs de recherche : pages publiques + événements approuvés.
// Accessible à /sitemap.xml via la règle de réécriture du .htaccess.
require_once __DIR__ . '/../vendor/autoload.php';

\App\Utils\EnvLoader::load();

use App\Repositories\EventRepository;

$frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? '', '/');

// Pages publiques fixes (le profil, la création et les paiements ne sont pas indexés)
$staticPages = ['', 'calendar', 'map', 'about', 'faq', 'contact', 'terms', 'privacy'];

header("Content-Type: application/xml; charset=UTF-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticPages as $page) {
  $url = $frontendUrl . '/' . $page;
  echo "  <url><loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc></url>\n";
}

foreach ((new EventRepository())->getAllEvents() as $event) {
  $url = $frontendUrl . '/event/' . $event->id;
  $lastModified = date('Y-m-d', strtotime($event->updated_at));
  echo "  <url><loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc><lastmod>" . $lastModified . "</lastmod></url>\n";
}

echo '</urlset>' . "\n";
