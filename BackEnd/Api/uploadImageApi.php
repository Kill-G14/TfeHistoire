<?php

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Gestion des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
\App\Utils\EnvLoader::load();

use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
use App\Validators\UserValidator;
use App\Services\AuthService;
use App\Services\SessionService;

/**
 * Convertit une valeur php.ini ("2M", "8M", "512K") en octets
 */
function iniSizeToBytes($value) {
  $value = trim((string) $value);
  $number = (int) $value;
  switch (strtolower(substr($value, -1))) {
    case 'g':
      $number *= 1024;
    case 'm':
      $number *= 1024;
    case 'k':
      $number *= 1024;
  }
  return $number;
}

/**
 * Redimensionne une image à 650px de largeur en préservant le ratio
 * (l'image est toujours ré-encodée, même si elle est déjà petite : cela retire
 * tout contenu caché dans les métadonnées du fichier d'origine)
 * 
 * @param string $sourcePath Chemin vers l'image source
 * @param string $mimeType Type MIME de l'image
 * @return bool True si succès, false sinon
 */
function resizeImage($sourcePath, $mimeType) {
  // Vérifier que l'extension GD est disponible
  if (!extension_loaded('gd')) {
    return false;
  }

  // Charger l'image source selon le type MIME
  $sourceImage = null;
  switch ($mimeType) {
    case 'image/jpeg':
      $sourceImage = @imagecreatefromjpeg($sourcePath);
      break;
    case 'image/png':
      $sourceImage = @imagecreatefrompng($sourcePath);
      break;
    case 'image/webp':
      $sourceImage = @imagecreatefromwebp($sourcePath);
      break;
  }

  if (!$sourceImage) {
    return false;
  }

  // Obtenir les dimensions originales
  $originalWidth = imagesx($sourceImage);
  $originalHeight = imagesy($sourceImage);

  // Largeur cible : 650px maximum (une image plus petite garde sa taille mais est ré-encodée)
  $targetWidth = min(650, $originalWidth);

  // Calculer la hauteur proportionnelle
  $ratio = $originalHeight / $originalWidth;
  $targetHeight = (int) round($targetWidth * $ratio);

  // Créer une nouvelle image avec les dimensions cibles
  $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

  // Préserver la transparence pour PNG et WEBP
  if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
    imagealphablending($resizedImage, false);
    imagesavealpha($resizedImage, true);
    $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
    imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $transparent);
  }

  // Redimensionner l'image avec imagecopyresized
  imagecopyresized(
    $resizedImage,
    $sourceImage,
    0, 0, 0, 0,
    $targetWidth,
    $targetHeight,
    $originalWidth,
    $originalHeight
  );

  // Sauvegarder l'image redimensionnée avec compression 75%
  $success = false;
  switch ($mimeType) {
    case 'image/jpeg':
      $success = imagejpeg($resizedImage, $sourcePath, 75);
      break;
    case 'image/png':
      // PNG : niveau de compression 0-9 (9 = maximum)
      $success = imagepng($resizedImage, $sourcePath, 7);
      break;
    case 'image/webp':
      $success = imagewebp($resizedImage, $sourcePath, 75);
      break;
  }

  // Libérer les ressources mémoire
  imagedestroy($sourceImage);
  imagedestroy($resizedImage);

  return $success;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
  exit;
}

// Requête plus grosse que post_max_size : PHP vide $_POST et $_FILES, il faut le détecter avant tout
$postMaxBytes = iniSizeToBytes(ini_get('post_max_size'));
if ($postMaxBytes > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $postMaxBytes) {
  echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux pour le serveur (max ' . ini_get('post_max_size') . ')']);
  exit;
}

// Seul un utilisateur connecté peut envoyer une image (le token arrive dans le formulaire multipart)
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, new UserValidator(), $sessionService);

$token = $_POST['token'] ?? null;
if (!$token || !$authService->checkToken((string) $token)) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour envoyer une image']);
  exit;
}

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
  echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']);
  exit;
}

$file = $_FILES['image'];

// Vérifier les erreurs d'upload
if ($file['error'] !== UPLOAD_ERR_OK) {
  $errorMessages = [
    UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur (max ' . ini_get('upload_max_filesize') . ')',
    UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée',
    UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
    UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
    UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
    UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload du fichier'
  ];
  
  $message = $errorMessages[$file['error']] ?? 'Erreur inconnue lors de l\'upload';
  echo json_encode(['success' => false, 'message' => $message]);
  exit;
}

// Taille maximale : 2 MB, alignée sur la valeur par défaut de php.ini (upload_max_filesize = 2M).
// Le navigateur réduit l'image avant l'envoi, un fichier normal pèse bien moins.
$maxSize = 2 * 1024 * 1024;
if ($file['size'] > $maxSize) {
  echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux (max 2 MB)']);
  exit;
}

// Extensions autorisées (SÉCURISÉ : JPG, PNG, WEBP uniquement)
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
  echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé. Formats acceptés : JPG, PNG, WEBP']);
  exit;
}

// Vérification du type MIME réel (protection contre le renommage d'extension)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimeTypes = [
  'image/jpeg',
  'image/png',
  'image/webp'
];

if (!in_array($mimeType, $allowedMimeTypes)) {
  echo json_encode(['success' => false, 'message' => 'Type MIME non autorisé. Le fichier n\'est pas une image valide']);
  exit;
}

// Vérification supplémentaire : s'assurer que c'est une vraie image
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
  echo json_encode(['success' => false, 'message' => 'Le fichier n\'est pas une image valide']);
  exit;
}

// Dimensions maximales : une image légère mais gigantesque saturerait la mémoire de GD au décodage
$maxSide = 4000;
$maxPixels = 12000000;
if ($imageInfo[0] > $maxSide || $imageInfo[1] > $maxSide || ($imageInfo[0] * $imageInfo[1]) > $maxPixels) {
  echo json_encode(['success' => false, 'message' => 'Les dimensions de l\'image sont trop grandes (max ' . $maxSide . ' px de côté)']);
  exit;
}

// Vérification des magic bytes (signature binaire) pour une sécurité maximale
$handle = fopen($file['tmp_name'], 'rb');
$magicBytes = fread($handle, 12); // Lire les 12 premiers octets
fclose($handle);

$isValidImage = false;

// JPEG: FF D8 FF
if (bin2hex(substr($magicBytes, 0, 3)) === 'ffd8ff') {
  $isValidImage = ($mimeType === 'image/jpeg');
}
// PNG: 89 50 4E 47 0D 0A 1A 0A
elseif (bin2hex(substr($magicBytes, 0, 8)) === '89504e470d0a1a0a') {
  $isValidImage = ($mimeType === 'image/png');
}
// WEBP: RIFF....WEBP
elseif (substr($magicBytes, 0, 4) === 'RIFF' && substr($magicBytes, 8, 4) === 'WEBP') {
  $isValidImage = ($mimeType === 'image/webp');
}

if (!$isValidImage) {
  echo json_encode(['success' => false, 'message' => 'La signature du fichier ne correspond pas à un format d\'image valide. Le fichier pourrait avoir été renommé.']);
  exit;
}

// Redimensionner l'image à 650px de large (ratio préservé)
if (!resizeImage($file['tmp_name'], $mimeType)) {
  echo json_encode(['success' => false, 'message' => 'Erreur lors du redimensionnement de l\'image. Vérifiez que l\'extension GD est activée.']);
  exit;
}

// Vérifier que le dossier de destination existe
$uploadDir = __DIR__ . '/../storage/images/';
if (!is_dir($uploadDir)) {
  if (!mkdir($uploadDir, 0755, true)) {
    echo json_encode(['success' => false, 'message' => 'Impossible de créer le dossier de destination']);
    exit;
  }
}

// Générer un nom de fichier unique et sécurisé
$uniqueId = uniqid('event_', true);
$timestamp = time();
// L'extension vient du contenu réel de l'image, jamais du nom envoyé par le client
$extensionsByMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$safeExtension = $extensionsByMime[$mimeType];
$newFileName = $uniqueId . '_' . $timestamp . '.' . $safeExtension;
$destinationPath = $uploadDir . $newFileName;

// Déplacer le fichier uploadé vers le dossier de destination
if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
  echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier']);
  exit;
}

// Définir les permissions appropriées
chmod($destinationPath, 0644);

// Journaliser l'upload
$logMessage = date('Y-m-d H:i:s') . " - Image uploadée : $newFileName (Taille: " . round($file['size'] / 1024, 2) . " KB, Type: $mimeType)\n";
$logFile = __DIR__ . '/../logs/uploads.log';
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Retourner le succès avec le nom du fichier
echo json_encode([
  'success' => true,
  'message' => 'Image uploadée avec succès',
  'data' => [
    'filename' => $newFileName,
    'size' => $file['size'],
    'type' => $mimeType
  ]
]);
