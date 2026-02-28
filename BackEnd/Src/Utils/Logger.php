<?php

namespace App\Utils;

class Logger {
  private const LOG_FILE = __DIR__ . '/../../logs/app.log';
  private const ERROR_FILE = __DIR__ . '/../../logs/error.log';

  // Logger une information
  public static function info(string $message, array $context = []): void {
    self::log('INFO', $message, $context, self::LOG_FILE);
  }

  // Logger une erreur
  public static function error(string $message, array $context = []): void {
    self::log('ERROR', $message, $context, self::ERROR_FILE);
  }

  // Logger un avertissement
  public static function warning(string $message, array $context = []): void {
    self::log('WARNING', $message, $context, self::LOG_FILE);
  }

  // Logger un debug
  public static function debug(string $message, array $context = []): void {
    self::log('DEBUG', $message, $context, self::LOG_FILE);
  }

  // Méthode privée pour écrire dans le fichier
  private static function log(string $level, string $message, array $context, string $file): void {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = sprintf("[%s] [%s] %s%s\n", $timestamp, $level, $message, $contextStr);

    // Créer le dossier logs s'il n'existe pas
    $logDir = dirname($file);
    if (!is_dir($logDir)) {
      mkdir($logDir, 0777, true);
    }

    error_log($logMessage, 3, $file);
  }
}
