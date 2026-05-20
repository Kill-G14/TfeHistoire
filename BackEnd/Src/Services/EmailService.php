<?php

namespace App\Services;

use App\Repositories\UserRepository;
use SendGrid;
use SendGrid\Mail\Mail;
use App\Utils\Logger;

class EmailService {
  private UserRepository $userRepository;
  private string $fromEmail;
  private string $fromName;
  private ?SendGrid $sendgrid;
  private bool $enabled;

  public function __construct(UserRepository $userRepository) {
    $this->userRepository = $userRepository;
    
    // Configuration depuis .env
    $this->fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? 'noreply@memoriaeventia.com';
    $this->fromName = $_ENV['SENDGRID_FROM_NAME'] ?? 'MemoriaEventia';
    $this->enabled = filter_var($_ENV['SENDGRID_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    // Initialiser SendGrid si activé et clé API disponible
    if ($this->enabled && !empty($_ENV['SENDGRID_API_KEY'])) {
      $this->sendgrid = new SendGrid($_ENV['SENDGRID_API_KEY']);
    } else {
      $this->sendgrid = null;
      if ($this->enabled) {
        Logger::warning('SendGrid activé mais clé API manquante');
      }
    }
  }

  /**
   * Envoyer un email via SendGrid
   */
  private function sendEmail(string $to, string $toName, string $subject, string $htmlContent, string $textContent = ''): bool {
    // Si SendGrid est désactivé, logger et retourner true (mode simulation)
    if (!$this->enabled) {
      Logger::info('Email simulation (SendGrid désactivé)', [
        'to' => $to,
        'subject' => $subject
      ]);
      return true;
    }

    // Vérifier que SendGrid est initialisé
    if (!$this->sendgrid) {
      Logger::error('Tentative d\'envoi d\'email mais SendGrid non initialisé');
      return false;
    }

    try {
      $email = new Mail();
      $email->setFrom($this->fromEmail, $this->fromName);
      $email->setSubject($subject);
      $email->addTo($to, $toName);
      $email->addContent("text/html", $htmlContent);
      
      // Ajouter contenu texte si fourni
      if (!empty($textContent)) {
        $email->addContent("text/plain", $textContent);
      }

      $response = $this->sendgrid->send($email);
      
      if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
        Logger::info('Email envoyé avec succès', [
          'to' => $to,
          'subject' => $subject,
          'status' => $response->statusCode()
        ]);
        return true;
      } else {
        Logger::error('Erreur lors de l\'envoi d\'email', [
          'to' => $to,
          'status' => $response->statusCode(),
          'body' => $response->body()
        ]);
        return false;
      }
    } catch (\Exception $e) {
      Logger::error('Exception lors de l\'envoi d\'email', [
        'to' => $to,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Envoyer un email de confirmation de réservation
   */
  public function sendReservationConfirmation(
    string $to,
    string $toName,
    array $eventData,
    int $quantity
  ): bool {
    $subject = '✅ Réservation confirmée : ' . $eventData['title'];
    
    $htmlContent = "
      <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #1a3a52;'>Réservation confirmée !</h2>
        <p>Bonjour <strong>{$toName}</strong>,</p>
        <p>Votre réservation a été confirmée avec succès pour l'événement suivant :</p>
        
        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
          <h3 style='color: #1a3a52; margin-top: 0;'>{$eventData['title']}</h3>
          <p><strong>📅 Date :</strong> {$eventData['date']} à {$eventData['time']}</p>
          <p><strong>📍 Lieu :</strong> {$eventData['address']}, {$eventData['city']}, {$eventData['country']}</p>
          <p><strong>👥 Nombre de places :</strong> {$quantity}</p>
        </div>
        
        <p>Vous recevrez un email de rappel quelques jours avant l'événement.</p>
        <p>En cas de question, n'hésitez pas à nous contacter.</p>
        
        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";
    
    $textContent = "Réservation confirmée !\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "Votre réservation a été confirmée pour :\n\n";
    $textContent .= "Événement : {$eventData['title']}\n";
    $textContent .= "Date : {$eventData['date']} à {$eventData['time']}\n";
    $textContent .= "Lieu : {$eventData['address']}, {$eventData['city']}, {$eventData['country']}\n";
    $textContent .= "Nombre de places : {$quantity}\n\n";
    $textContent .= "Cordialement,\nL'équipe MemoriaEventia";

    return $this->sendEmail($to, $toName, $subject, $htmlContent, $textContent);
  }

  /**
   * Envoyer un email d'annulation de réservation
   */
  public function sendReservationCancellation(
    string $to,
    string $toName,
    array $eventData
  ): bool {
    $subject = '❌ Réservation annulée : ' . $eventData['title'];
    
    $htmlContent = "
      <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #dc3545;'>Réservation annulée</h2>
        <p>Bonjour <strong>{$toName}</strong>,</p>
        <p>Votre réservation pour l'événement suivant a été annulée :</p>
        
        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
          <h3 style='color: #1a3a52; margin-top: 0;'>{$eventData['title']}</h3>
          <p><strong>📅 Date :</strong> {$eventData['date']} à {$eventData['time']}</p>
          <p><strong>📍 Lieu :</strong> {$eventData['address']}, {$eventData['city']}, {$eventData['country']}</p>
        </div>
        
        <p>Si vous n'avez pas demandé cette annulation, veuillez nous contacter immédiatement.</p>
        
        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";
    
    $textContent = "Réservation annulée\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "Votre réservation a été annulée pour :\n\n";
    $textContent .= "Événement : {$eventData['title']}\n";
    $textContent .= "Date : {$eventData['date']} à {$eventData['time']}\n";
    $textContent .= "Lieu : {$eventData['address']}, {$eventData['city']}, {$eventData['country']}\n\n";
    $textContent .= "Cordialement,\nL'équipe MemoriaEventia";

    return $this->sendEmail($to, $toName, $subject, $htmlContent, $textContent);
  }

  /**
   * Envoyer un email de modification d'événement
   */
  public function sendEventModificationEmail(
    string $to, 
    string $toName, 
    array $eventData, 
    string $organizerEmail
  ): bool {
    $subject = '⚠️ Modification d\'événement : ' . $eventData['title'];
    
    $htmlContent = "
      <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #ffc107;'>Événement modifié</h2>
        <p>Bonjour <strong>{$toName}</strong>,</p>
        <p>L'événement pour lequel vous avez réservé a été modifié :</p>
        
        <div style='background-color: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
          <h3 style='color: #1a3a52; margin-top: 0;'>{$eventData['title']}</h3>
          <p><strong>📅 Ancienne date :</strong> {$eventData['old_date']} à {$eventData['old_time']}</p>
          <p><strong>📅 Nouvelle date :</strong> <span style='color: #ffc107;'>{$eventData['new_date']} à {$eventData['new_time']}</span></p>
        </div>
        
        <p>Pour toute question ou demande de remboursement, contactez l'organisateur :</p>
        <p><strong>📧 Email :</strong> <a href='mailto:{$organizerEmail}'>{$organizerEmail}</a></p>
        
        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";
    
    $textContent = "Événement modifié\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "L'événement pour lequel vous avez réservé a été modifié :\n\n";
    $textContent .= "Événement : {$eventData['title']}\n";
    $textContent .= "Ancienne date : {$eventData['old_date']} à {$eventData['old_time']}\n";
    $textContent .= "Nouvelle date : {$eventData['new_date']} à {$eventData['new_time']}\n\n";
    $textContent .= "Pour toute question, contactez l'organisateur : {$organizerEmail}\n\n";
    $textContent .= "Cordialement,\nL'équipe MemoriaEventia";

    return $this->sendEmail($to, $toName, $subject, $htmlContent, $textContent);
  }

  /**
   * Envoyer un email de suppression d'événement
   */
  public function sendEventDeletionEmail(
    string $to, 
    string $toName, 
    array $eventData, 
    string $deletionMessage, 
    string $organizerEmail
  ): bool {
    $subject = '🚫 Annulation d\'événement : ' . $eventData['title'];
    
    $htmlContent = "
      <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #dc3545;'>Événement annulé</h2>
        <p>Bonjour <strong>{$toName}</strong>,</p>
        <p>Nous vous informons que l'événement suivant a été annulé :</p>
        
        <div style='background-color: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545;'>
          <h3 style='color: #1a3a52; margin-top: 0;'>{$eventData['title']}</h3>
          <p><strong>📅 Date prévue :</strong> {$eventData['date']} à {$eventData['time']}</p>
          <p><strong>📍 Lieu :</strong> {$eventData['address']}, {$eventData['city']}, {$eventData['country']}</p>
        </div>
        
        <div style='background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>
          <p style='margin: 0;'><strong>Message de l'organisateur :</strong></p>
          <p style='margin: 10px 0 0 0; font-style: italic;'>{$deletionMessage}</p>
        </div>
        
        <p>Pour toute question ou demande de remboursement, contactez l'organisateur :</p>
        <p><strong>📧 Email :</strong> <a href='mailto:{$organizerEmail}'>{$organizerEmail}</a></p>
        
        <p>Nous nous excusons pour ce désagrément.</p>
        
        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";
    
    $textContent = "Événement annulé\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "L'événement suivant a été annulé :\n\n";
    $textContent .= "Événement : {$eventData['title']}\n";
    $textContent .= "Date prévue : {$eventData['date']} à {$eventData['time']}\n";
    $textContent .= "Lieu : {$eventData['address']}, {$eventData['city']}, {$eventData['country']}\n\n";
    $textContent .= "Message de l'organisateur :\n{$deletionMessage}\n\n";
    $textContent .= "Pour toute question, contactez l'organisateur : {$organizerEmail}\n\n";
    $textContent .= "Cordialement,\nL'équipe MemoriaEventia";

    return $this->sendEmail($to, $toName, $subject, $htmlContent, $textContent);
  }

  /**
   * Envoyer un email aux administrateurs
   */
  public function sendEmailToAdmins(string $subject, string $message): bool {
    $admins = $this->userRepository->getAllAdmins();
    
    if (empty($admins)) {
      Logger::warning('Tentative d\'envoi d\'email aux admins mais aucun admin trouvé');
      return false;
    }

    $success = true;
    foreach ($admins as $admin) {
      $htmlContent = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
          <h2 style='color: #1a3a52;'>Notification Administrateur</h2>
          <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            {$message}
          </div>
          <p style='margin-top: 30px;'>L'équipe MemoriaEventia</p>
        </div>
      ";
      
      $result = $this->sendEmail($admin['email'], $admin['name'], $subject, $htmlContent, $message);
      if (!$result) {
        $success = false;
      }
    }

    return $success;
  }

  /**
   * Envoyer un email de notification de nouvelle demande aux admins
   */
  public function notifyAdminsNewModificationRequest(array $eventData, array $userData): bool {
    $subject = '🔔 Nouvelle demande de modification d\'événement';
    
    $message = "Une nouvelle demande de modification d'événement a été soumise :\n\n";
    $message .= "Événement : {$eventData['title']}\n";
    $message .= "Créateur : {$userData['name']} ({$userData['email']})\n";
    $message .= "Ancienne date : {$eventData['old_date']} à {$eventData['old_time']}\n";
    $message .= "Nouvelle date : {$eventData['new_date']} à {$eventData['new_time']}\n\n";
    $message .= "Veuillez vous connecter à l'interface d'administration pour valider ou rejeter cette demande.";

    return $this->sendEmailToAdmins($subject, $message);
  }

  /**
   * Envoyer un email de notification de nouvelle demande de suppression aux admins
   */
  public function notifyAdminsNewDeletionRequest(array $eventData, array $userData, string $deletionMessage): bool {
    $subject = '🔔 Nouvelle demande de suppression d\'événement';
    
    $message = "Une nouvelle demande de suppression d'événement a été soumise :\n\n";
    $message .= "Événement : {$eventData['title']}\n";
    $message .= "Créateur : {$userData['name']} ({$userData['email']})\n";
    $message .= "Date : {$eventData['date']} à {$eventData['time']}\n\n";
    $message .= "Message de l'organisateur :\n";
    $message .= "---\n";
    $message .= $deletionMessage . "\n";
    $message .= "---\n\n";
    $message .= "Veuillez vous connecter à l'interface d'administration pour valider ou rejeter cette demande.";

    return $this->sendEmailToAdmins($subject, $message);
  }
}
