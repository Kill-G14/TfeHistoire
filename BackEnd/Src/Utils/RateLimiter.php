<?php

namespace App\Utils;

/**
 * Système de limitation de tentatives (Rate Limiting)
 * 
 * Protège l'application contre les attaques par force brute
 * en limitant le nombre de tentatives autorisées sur une période donnée.
 * 
 * FONCTIONNEMENT :
 * 1. Chaque tentative est enregistrée avec l'IP et l'identifiant (email, etc.)
 * 2. Si le nombre max de tentatives est atteint, l'utilisateur est bloqué temporairement
 * 3. Après la durée de blocage, les tentatives sont réinitialisées
 * 4. Une tentative réussie réinitialise le compteur
 * 
 * UTILISATION :
 * - Avant de traiter une connexion : RateLimiter::check('login', $email)
 * - Après une tentative échouée : RateLimiter::recordAttempt('login', $email)
 * - Après une connexion réussie : RateLimiter::reset('login', $email)
 */
class RateLimiter {
  /**
   * Vérifier si une action est autorisée (pas bloquée)
   * 
   * @param string $action Type d'action (ex: 'login', 'register', 'api_call')
   * @param string $identifier Identifiant unique (email, IP, user_id)
   * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => ?int]
   */
  public static function check(string $action, string $identifier): array {
    // Récupérer la configuration
    $config = require __DIR__ . '/../../config.php';
    
    // Récupérer les limites selon l'action
    $limits = self::getLimitsForAction($action, $config);
    
    // Clé unique pour stocker les tentatives
    $key = self::generateKey($action, $identifier);
    
    // Récupérer les tentatives depuis la base de données
    $attempts = self::getAttempts($key);
    
    // Si aucune tentative, c'est autorisé
    if (empty($attempts)) {
      return [
        'allowed' => true,
        'remaining' => $limits['max_attempts'],
        'reset_at' => null
      ];
    }
    
    // Vérifier si la période de blocage est expirée
    $now = time();
    $blockUntil = $attempts['block_until'] ?? 0;
    
    if ($blockUntil > $now) {
      // Toujours bloqué
      return [
        'allowed' => false,
        'remaining' => 0,
        'reset_at' => $blockUntil,
        'message' => 'Trop de tentatives. Réessayez dans ' . ceil(($blockUntil - $now) / 60) . ' minutes.'
      ];
    }
    
    // Si la période de blocage est expirée, réinitialiser
    if ($blockUntil > 0 && $blockUntil <= $now) {
      self::reset($action, $identifier);
      return [
        'allowed' => true,
        'remaining' => $limits['max_attempts'],
        'reset_at' => null
      ];
    }
    
    // Vérifier le nombre de tentatives
    $attemptCount = $attempts['count'] ?? 0;
    $remaining = max(0, $limits['max_attempts'] - $attemptCount);
    
    if ($attemptCount >= $limits['max_attempts']) {
      // Bloquer pour la durée configurée
      $blockUntil = $now + ($limits['block_duration_minutes'] * 60);
      self::setBlockUntil($key, $blockUntil);
      
      return [
        'allowed' => false,
        'remaining' => 0,
        'reset_at' => $blockUntil,
        'message' => 'Trop de tentatives. Veuillez réessayer dans ' . $limits['block_duration_minutes'] . ' minutes.'
      ];
    }
    
    // Autorisé, retourner le nombre de tentatives restantes
    return [
      'allowed' => true,
      'remaining' => $remaining,
      'reset_at' => null
    ];
  }
  
  /**
   * Enregistrer une tentative échouée
   * 
   * @param string $action
   * @param string $identifier
   * @return void
   */
  public static function recordAttempt(string $action, string $identifier): void {
    $key = self::generateKey($action, $identifier);
    $attempts = self::getAttempts($key);
    
    $count = isset($attempts['count']) ? $attempts['count'] + 1 : 1;
    
    self::saveAttempts($key, $count);
  }
  
  /**
   * Réinitialiser les tentatives (après succès)
   * 
   * @param string $action
   * @param string $identifier
   * @return void
   */
  public static function reset(string $action, string $identifier): void {
    $key = self::generateKey($action, $identifier);
    self::deleteAttempts($key);
  }
  
  /**
   * Générer une clé unique pour l'action + identifiant
   * 
   * @param string $action
   * @param string $identifier
   * @return string
   */
  private static function generateKey(string $action, string $identifier): string {
    // Ajouter l'IP pour plus de sécurité
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $action . ':' . $identifier . ':' . $ip);
  }
  
  /**
   * Récupérer les limites selon le type d'action
   * 
   * @param string $action
   * @param array $config
   * @return array
   */
  private static function getLimitsForAction(string $action, array $config): array {
    $defaults = [
      'max_attempts' => $config['security']['max_login_attempts'] ?? 5,
      'block_duration_minutes' => $config['security']['login_block_duration_minutes'] ?? 15
    ];
    
    // Limites spécifiques par action
    $actionLimits = [
      'login' => $defaults,
      'register' => ['max_attempts' => 3, 'block_duration_minutes' => 30],
      'password_reset' => ['max_attempts' => 3, 'block_duration_minutes' => 60],
      'api_call' => ['max_attempts' => 100, 'block_duration_minutes' => 5]
    ];
    
    return $actionLimits[$action] ?? $defaults;
  }
  
  /**
   * Récupérer les tentatives depuis la base de données
   * 
   * @param string $key
   * @return array|null
   */
  private static function getAttempts(string $key): ?array {
    try {
      $pdo = Database::getConnection();
      
      // Créer la table si elle n'existe pas
      self::ensureTableExists($pdo);
      
      $stmt = $pdo->prepare("
        SELECT attempts_count, block_until, created_at 
        FROM rate_limiter 
        WHERE lookup_key = :key
        AND (block_until IS NULL OR block_until > :now)
      ");
      
      $now = time();
      $stmt->bindParam(':key', $key);
      $stmt->bindParam(':now', $now, \PDO::PARAM_INT);
      $stmt->execute();
      
      $result = $stmt->fetch(\PDO::FETCH_ASSOC);
      
      if (!$result) {
        return null;
      }
      
      return [
        'count' => (int)$result['attempts_count'],
        'block_until' => $result['block_until'] ? (int)$result['block_until'] : null,
        'created_at' => $result['created_at']
      ];
    } catch (\Exception $e) {
      Logger::error('RateLimiter getAttempts error: ' . $e->getMessage());
      return null;
    }
  }
  
  /**
   * Sauvegarder les tentatives
   * 
   * @param string $key
   * @param int $count
   * @return void
   */
  private static function saveAttempts(string $key, int $count): void {
    try {
      $pdo = Database::getConnection();
      
      $stmt = $pdo->prepare("
        INSERT INTO rate_limiter (lookup_key, attempts_count, created_at, updated_at)
        VALUES (:key, :count, :now, :now)
        ON DUPLICATE KEY UPDATE 
          attempts_count = :count,
          updated_at = :now
      ");
      
      $now = time();
      $stmt->bindParam(':key', $key);
      $stmt->bindParam(':count', $count, \PDO::PARAM_INT);
      $stmt->bindParam(':now', $now, \PDO::PARAM_INT);
      $stmt->execute();
    } catch (\Exception $e) {
      Logger::error('RateLimiter saveAttempts error: ' . $e->getMessage());
    }
  }
  
  /**
   * Définir le timestamp de fin de blocage
   * 
   * @param string $key
   * @param int $blockUntil
   * @return void
   */
  private static function setBlockUntil(string $key, int $blockUntil): void {
    try {
      $pdo = Database::getConnection();
      
      $stmt = $pdo->prepare("
        UPDATE rate_limiter 
        SET block_until = :block_until, updated_at = :now
        WHERE lookup_key = :key
      ");
      
      $now = time();
      $stmt->bindParam(':key', $key);
      $stmt->bindParam(':block_until', $blockUntil, \PDO::PARAM_INT);
      $stmt->bindParam(':now', $now, \PDO::PARAM_INT);
      $stmt->execute();
    } catch (\Exception $e) {
      Logger::error('RateLimiter setBlockUntil error: ' . $e->getMessage());
    }
  }
  
  /**
   * Supprimer les tentatives (reset)
   * 
   * @param string $key
   * @return void
   */
  private static function deleteAttempts(string $key): void {
    try {
      $pdo = Database::getConnection();
      
      $stmt = $pdo->prepare("DELETE FROM rate_limiter WHERE lookup_key = :key");
      $stmt->bindParam(':key', $key);
      $stmt->execute();
    } catch (\Exception $e) {
      Logger::error('RateLimiter deleteAttempts error: ' . $e->getMessage());
    }
  }
  
  /**
   * S'assurer que la table rate_limiter existe
   * 
   * @param \PDO $pdo
   * @return void
   */
  private static function ensureTableExists(\PDO $pdo): void {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS rate_limiter (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lookup_key VARCHAR(64) NOT NULL UNIQUE,
        attempts_count INT NOT NULL DEFAULT 0,
        block_until INT NULL,
        created_at INT NOT NULL,
        updated_at INT NOT NULL,
        INDEX idx_lookup (lookup_key),
        INDEX idx_block (block_until)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
  }
  
  /**
   * Nettoyer les anciennes entrées (à appeler via cron quotidien)
   * 
   * @param int $daysOld Nombre de jours à garder (défaut: 7)
   * @return int Nombre de lignes supprimées
   */
  public static function cleanup(int $daysOld = 7): int {
    try {
      $pdo = Database::getConnection();
      
      $threshold = time() - ($daysOld * 86400);
      
      $stmt = $pdo->prepare("
        DELETE FROM rate_limiter 
        WHERE updated_at < :threshold
        AND (block_until IS NULL OR block_until < :now)
      ");
      
      $now = time();
      $stmt->bindParam(':threshold', $threshold, \PDO::PARAM_INT);
      $stmt->bindParam(':now', $now, \PDO::PARAM_INT);
      $stmt->execute();
      
      return $stmt->rowCount();
    } catch (\Exception $e) {
      Logger::error('RateLimiter cleanup error: ' . $e->getMessage());
      return 0;
    }
  }
}
