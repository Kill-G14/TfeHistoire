<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

header("Content-Type: application/json");

// Charger la configuration
$config = require __DIR__ . '/../config.php';

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {

  case 'getRoute':
    // Récupérer les coordonnées de départ et d'arrivée
    if (!isset($request['startLat']) || !isset($request['startLng']) || 
        !isset($request['endLat']) || !isset($request['endLng'])) {
      $response = [
        'success' => false,
        'message' => 'Coordonnées manquantes'
      ];
      break;
    }

    $startLat = floatval($request['startLat']);
    $startLng = floatval($request['startLng']);
    $endLat = floatval($request['endLat']);
    $endLng = floatval($request['endLng']);

    // Valider les coordonnées
    if ($startLat < -90 || $startLat > 90 || $endLat < -90 || $endLat > 90 ||
        $startLng < -180 || $startLng > 180 || $endLng < -180 || $endLng > 180) {
      $response = [
        'success' => false,
        'message' => 'Coordonnées invalides'
      ];
      break;
    }

    // Construire l'URL de l'API OpenRouteService
    $url = $config['openroute']['base_url'] . '/directions/driving-car';
    $url .= '?api_key=' . $config['openroute']['api_key'];
    $url .= '&start=' . $startLng . ',' . $startLat;
    $url .= '&end=' . $endLng . ',' . $endLat;

    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Accept: application/json'
    ]);

    // Exécuter la requête
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Vérifier les erreurs
    if ($curlError) {
      $response = [
        'success' => false,
        'message' => 'Erreur de connexion à OpenRouteService'
      ];
      break;
    }

    if ($httpCode !== 200) {
      $response = [
        'success' => false,
        'message' => 'Impossible de calculer l\'itinéraire'
      ];
      break;
    }

    // Décoder la réponse
    $routeData = json_decode($result, true);

    if (!$routeData || !isset($routeData['features'])) {
      $response = [
        'success' => false,
        'message' => 'Réponse invalide de OpenRouteService'
      ];
      break;
    }

    // Retourner les données de l'itinéraire
    $response = [
      'success' => true,
      'message' => 'Itinéraire calculé avec succès',
      'data' => $routeData
    ];
    break;

  default:
    $response = [
      'success' => false,
      'message' => 'Action non reconnue'
    ];
    break;
}

echo json_encode($response);
exit;
