<?php

namespace App\Utils;

use PDO;
use PDOException;

class Database {
  private static ?PDO $connection = null;

  // Configuration de la base de données
  private const DB_HOST = 'localhost';
  private const DB_NAME = 'eurofetes_db';
  private const DB_USER = 'root';
  private const DB_PASS = '';
  private const DB_CHARSET = 'utf8mb4';

  // Pattern Singleton - empêche l'instantiation
  private function __construct() {}

  // Récupérer la connexion PDO (Singleton)
  public static function getConnection(): PDO {
    if (self::$connection === null) {
      try {
        $dsn = sprintf(
          'mysql:host=%s;dbname=%s;charset=%s',
          self::DB_HOST,
          self::DB_NAME,
          self::DB_CHARSET
        );

        self::$connection = new PDO($dsn, self::DB_USER, self::DB_PASS, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
        ]);
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
