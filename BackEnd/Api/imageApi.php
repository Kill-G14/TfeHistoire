<?php

// Headers CORS
require_once __DIR__ . '/cors.php';
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Vérifier que c'est une requête GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
  exit;
}

// Récupérer le nom de l'image depuis l'URL
$imageName = $_GET['name'] ?? null;

if (!$imageName) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Nom de l\'image manquant']);
  exit;
}

// Nettoyer le nom de fichier pour éviter les attaques de type path traversal
$imageName = basename($imageName);

// Chemin vers le dossier des images
$imagePath = __DIR__ . '/../storage/images/' . $imageName;

// Vérifier que le fichier existe
if (!file_exists($imagePath)) {
  http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'Image non trouvée']);
  exit;
}

// Vérifier que c'est bien un fichier image
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé']);
  exit;
}

// Définir le type MIME approprié
$mimeTypes = [
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'png' => 'image/png',
  'gif' => 'image/gif',
  'webp' => 'image/webp'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Miniature demandée (cartes d'événements) : largeurs autorisées uniquement,
// générée une fois puis réutilisée. En cas d'échec on sert l'image d'origine.
$allowedWidths = [480, 960];
$requestedWidth = (int) ($_GET['w'] ?? 0);

if (in_array($requestedWidth, $allowedWidths, true) && function_exists('imagecreatetruecolor')) {
  $thumbnailDir = __DIR__ . '/../storage/thumbnails/' . $requestedWidth . '/';
  $thumbnailPath = $thumbnailDir . $imageName;

  $isThumbnailUpToDate = file_exists($thumbnailPath) && filemtime($thumbnailPath) >= filemtime($imagePath);

  if (!$isThumbnailUpToDate) {
    if (!is_dir($thumbnailDir)) {
      mkdir($thumbnailDir, 0755, true);
    }
    createThumbnail($imagePath, $thumbnailPath, $requestedWidth, $extension);
  }

  if (file_exists($thumbnailPath)) {
    $imagePath = $thumbnailPath;
  }
}

// Réduit une image à la largeur demandée (jamais agrandie), en gardant le format d'origine
function createThumbnail(string $sourcePath, string $targetPath, int $targetWidth, string $extension): bool {
  $loaders = [
    'jpg' => 'imagecreatefromjpeg',
    'jpeg' => 'imagecreatefromjpeg',
    'png' => 'imagecreatefrompng',
    'webp' => 'imagecreatefromwebp',
  ];

  // GIF animés : on ne touche pas à l'image d'origine
  if (!isset($loaders[$extension]) || !function_exists($loaders[$extension])) {
    return false;
  }

  $source = @$loaders[$extension]($sourcePath);
  if (!$source) {
    return false;
  }

  $sourceWidth = imagesx($source);
  $sourceHeight = imagesy($source);

  // Image déjà plus petite que la miniature : inutile de la recompresser
  if ($sourceWidth <= $targetWidth) {
    imagedestroy($source);
    return false;
  }

  $targetHeight = (int) round($sourceHeight * $targetWidth / $sourceWidth);
  $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

  // Conserver la transparence des PNG et WebP
  imagealphablending($thumbnail, false);
  imagesavealpha($thumbnail, true);

  imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

  $saved = match ($extension) {
    'png' => imagepng($thumbnail, $targetPath, 7),
    'webp' => imagewebp($thumbnail, $targetPath, 82),
    default => imagejpeg($thumbnail, $targetPath, 82),
  };

  imagedestroy($source);
  imagedestroy($thumbnail);

  return $saved;
}

// Envoyer les headers appropriés
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($imagePath));
header('Cache-Control: public, max-age=31536000'); // Cache d'un an
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Envoyer le fichier
readfile($imagePath);
exit;
