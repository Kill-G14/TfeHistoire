<?php

namespace App\Utils;

use PDO;
use PDOException;

class Database {
  private static ?PDO $connection = null;

  // Pattern Singleton - empêche l'instantiation
  private function __construct() {}

  // Récupérer la connexion PDO (Singleton)
  public static function getConnection(): PDO {
    if (self::$connection === null) {
      try {
        // Charger la configuration
        $config = require __DIR__ . '/../../config.php';
        
        $dsn = sprintf(
          'mysql:host=%s;dbname=%s;charset=%s',
          $config['database']['host'],
          $config['database']['name'],
          $config['database']['charset']
        );

        self::$connection = new PDO(
          $dsn, 
          $config['database']['user'], 
          $config['database']['password'], 
          [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
          ]
        );
      } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        throw new PDOException('Erreur de connexion à la base de données');
      }
    }

    return self::$connection;
  }

  // Fermer la connexion
  public static function closeConnection(): void {
    self::$connection = null;
  }
}
