<?php

// Headers CORS
header('Access-Control-Allow-Origin: *');
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
$imagePath = __DIR__ . '/../Src/img/' . $imageName;

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

// Envoyer les headers appropriés
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($imagePath));
header('Cache-Control: public, max-age=31536000'); // Cache d'un an
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Envoyer le fichier
readfile($imagePath);
exit;
