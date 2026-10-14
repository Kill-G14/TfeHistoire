<?php

// Page /event/{id} servie avec ses balises de référencement déjà remplies.
// Les robots (Google, aperçus WhatsApp/Facebook) ne lancent pas toujours le JavaScript de la SPA :
// on renvoie donc index.html avec le titre, la description, l'image et les données structurées
// de l'événement. La SPA démarre ensuite normalement.
require_once __DIR__ . '/../vendor/autoload.php';

\App\Utils\EnvLoader::load();

use App\Repositories\EventRepository;

header("Content-Type: text/html; charset=UTF-8");

$html = file_get_contents(__DIR__ . '/../../index.html');
if ($html === false) {
  http_response_code(500);
  exit;
}

// Remplace l'attribut content d'une balise <meta> identifiée par son id
function replaceMetaContent(string $html, string $metaId, string $value): string {
  $safeValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  return preg_replace(
    '/(<meta[^>]*id="' . preg_quote($metaId, '/') . '"[^>]*content=")[^"]*(")/',
    '${1}' . str_replace(['\\', '$'], ['\\\\', '\\$'], $safeValue) . '${2}',
    $html,
    1
  );
}

$eventId = (int) ($_GET['id'] ?? 0);
$event = $eventId > 0 ? (new EventRepository())->getEventById($eventId) : null;

// Événement inconnu ou non publié : page normale (la SPA affiche « introuvable »), non indexée
if (!$event || !$event->is_approved) {
  http_response_code(404);
  echo str_replace(
    '</head>',
    '<meta name="robots" content="noindex, nofollow" /></head>',
    $html
  );
  exit;
}

$frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? '', '/');
$pageUrl = $frontendUrl . '/event/' . $event->id;
$imageUrl = $event->image_event
  ? $frontendUrl . '/BackEnd/Api/imageApi.php?name=' . rawurlencode($event->image_event)
  : $frontendUrl . '/assets/images/MemoriaEventiaShare-1200x630.png';

$title = $event->title . ' - MemoriaEventia';
$summary = trim(preg_replace('/\s+/', ' ', $event->description));
$description = mb_strlen($summary) > 155 ? mb_substr($summary, 0, 152) . '...' : $summary;

// Titre et description
$html = preg_replace(
  '/<title id="pageTitle">.*?<\/title>/s',
  '<title id="pageTitle">' . str_replace(['\\', '$'], ['\\\\', '\\$'], htmlspecialchars($title, ENT_QUOTES, 'UTF-8')) . '</title>',
  $html,
  1
);
$html = replaceMetaContent($html, 'pageDescription', $description);
$html = replaceMetaContent($html, 'ogTitle', $title);
$html = replaceMetaContent($html, 'ogDescription', $description);
$html = replaceMetaContent($html, 'ogUrl', $pageUrl);
$html = replaceMetaContent($html, 'ogImage', $imageUrl);
$html = preg_replace(
  '/(<link rel="canonical" id="pageCanonical" href=")[^"]*(")/',
  '${1}' . str_replace(['\\', '$'], ['\\\\', '\\$'], htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8')) . '${2}',
  $html,
  1
);

// Données structurées schema.org
$structuredData = [
  '@context' => 'https://schema.org',
  '@type' => 'Event',
  'name' => $event->title,
  'description' => $event->description,
  'startDate' => $event->date . 'T' . substr($event->time, 0, 5),
  'eventStatus' => 'https://schema.org/EventScheduled',
  'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
  'image' => [$imageUrl],
  'location' => [
    '@type' => 'Place',
    'name' => $event->city,
    'address' => [
      '@type' => 'PostalAddress',
      'streetAddress' => $event->address,
      'addressLocality' => $event->city,
      'addressCountry' => $event->country,
    ],
  ],
  'offers' => [
    '@type' => 'Offer',
    'price' => number_format($event->price, 2, '.', ''),
    'priceCurrency' => 'EUR',
    'availability' => 'https://schema.org/InStock',
    'url' => $pageUrl,
  ],
];
if ($event->organizer_name) {
  $structuredData['organizer'] = ['@type' => 'Person', 'name' => $event->organizer_name];
}

// JSON_HEX_TAG : un texte contenant "</script>" ne peut pas sortir de la balise
$json = json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
$html = str_replace(
  '</head>',
  '<script type="application/ld+json" id="structuredData">' . $json . '</script></head>',
  $html
);

echo $html;
