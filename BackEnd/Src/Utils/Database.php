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
        // Charger les variables d'environnement
        \App\Utils\EnvLoader::load();
        
        $dsn = sprintf(
          'mysql:host=%s;dbname=%s;charset=%s',
          \App\Utils\EnvLoader::get('DB_HOST', 'localhost'),
          \App\Utils\EnvLoader::get('DB_NAME'),
          \App\Utils\EnvLoader::get('DB_CHARSET', 'utf8mb4')
        );

        self::$connection = new PDO(
          $dsn, 
          \App\Utils\EnvLoader::get('DB_USER'),
          \App\Utils\EnvLoader::get('DB_PASSWORD', ''), 
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
