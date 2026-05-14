<?php

namespace App\Utils;

/**
 * Chargeur de configuration depuis fichier .env
 * 
 * Ce fichier lit le fichier .env et charge toutes les variables
 * d'environnement dans $_ENV et via getenv()
 */
class EnvLoader {
  private static bool $loaded = false;
  
  /**
   * Charger le fichier .env
   * 
   * @param string $path Chemin vers le fichier .env
   * @return bool True si chargé avec succès
   */
  public static function load(string $path = __DIR__ . '/../../.env'): bool {
    // Éviter de charger plusieurs fois
    if (self::$loaded) {
      return true;
    }
    
    if (!file_exists($path)) {
      throw new \Exception("Fichier .env introuvable : $path");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
      // Ignorer les commentaires
      $line = trim($line);
      if (empty($line) || strpos($line, '#') === 0) {
        continue;
      }
      
      // Parser la ligne KEY=VALUE
      if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Retirer les guillemets si présents
        $value = trim($value, '"\'');
        
        // Remplacer les variables ${VAR} par leur valeur
        $value = preg_replace_callback('/\$\{([A-Z_]+)\}/', function($matches) {
          return self::get($matches[1], '');
        }, $value);
        
        // Définir dans $_ENV et avec putenv()
        $_ENV[$key] = $value;
        putenv("$key=$value");
      }
    }
    
    self::$loaded = true;
    return true;
  }
  
  /**
   * Récupérer une variable d'environnement
   * 
   * @param string $key Nom de la variable
   * @param mixed $default Valeur par défaut si non trouvée
   * @return mixed
   */
  public static function get(string $key, $default = null) {
    // Chercher dans $_ENV d'abord
    if (isset($_ENV[$key])) {
      return self::parseValue($_ENV[$key]);
    }
    
    // Puis avec getenv()
    $value = getenv($key);
    if ($value !== false) {
      return self::parseValue($value);
    }
    
    return $default;
  }
  
  /**
   * Parser une valeur pour convertir true/false/null
   * 
   * @param string $value
   * @return mixed
   */
  private static function parseValue(string $value) {
    $lower = strtolower($value);
    
    // Conversion des booléens
    if ($lower === 'true') {
      return true;
    }
    if ($lower === 'false') {
      return false;
    }
    
    // Conversion de null
    if ($lower === 'null') {
      return null;
    }
    
    // Sinon retourner la valeur telle quelle
    return $value;
  }
  
  /**
   * Vérifier si une variable existe
   * 
   * @param string $key
   * @return bool
   */
  public static function has(string $key): bool {
    return isset($_ENV[$key]) || getenv($key) !== false;
  }
  
  /**
   * Récupérer toutes les variables d'environnement
   * 
   * @return array
   */
  public static function all(): array {
    return $_ENV;
  }
}
