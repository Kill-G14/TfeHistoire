<?php

namespace App\Services;

use App\Repositories\UserRepository;

class EmailService {
  private UserRepository $userRepository;
  private string $fromEmail;
  private string $fromName;

  public function __construct(UserRepository $userRepository) {
    $this->userRepository = $userRepository;
    $this->fromEmail = 'noreply@memoriaeventia.com';
    $this->fromName = 'MemoriaEventia';
  }

  /**
   * Envoyer un email de notification de modification d'événement
   */
  public function sendEventModificationEmail(
    string $to, 
    string $toName, 
    array $eventData, 
    string $organizerEmail
  ): bool {
    $subject = 'Modification d\'événement : ' . $eventData['title'];
    
    $message = "Bonjour {$toName},\n\n";
    $message .= "L'événement pour lequel vous avez réservé des billets a été modifié :\n\n";
    $message .= "Événement : {$eventData['title']}\n";
    $message .= "Ancienne date : {$eventData['old_date']} à {$eventData['old_time']}\n";
    $message .= "Nouvelle date : {$eventData['new_date']} à {$eventData['new_time']}\n\n";
    $message .= "Pour toute question, litige ou demande de remboursement, veuillez contacter l'organisateur de l'événement :\n";
    $message .= "Email : {$organizerEmail}\n\n";
    $message .= "Cordialement,\n";
    $message .= "L'équipe MemoriaEventia";

    $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
    $headers .= "Reply-To: {$organizerEmail}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers);
  }

  /**
   * Envoyer un email de notification de suppression d'événement
   */
  public function sendEventDeletionEmail(
    string $to, 
    string $toName, 
    array $eventData, 
    string $deletionMessage, 
    string $organizerEmail
  ): bool {
    $subject = 'Annulation d\'événement : ' . $eventData['title'];
    
    $message = "Bonjour {$toName},\n\n";
    $message .= "Nous vous informons que l'événement suivant a été annulé :\n\n";
    $message .= "Événement : {$eventData['title']}\n";
    $message .= "Date prévue : {$eventData['date']} à {$eventData['time']}\n";
    $message .= "Lieu : {$eventData['address']}, {$eventData['city']}, {$eventData['country']}\n\n";
    $message .= "Message de l'organisateur :\n";
    $message .= "---\n";
    $message .= $deletionMessage . "\n";
    $message .= "---\n\n";
    $message .= "Pour toute question, litige ou demande de remboursement, veuillez contacter l'organisateur de l'événement :\n";
    $message .= "Email : {$organizerEmail}\n\n";
    $message .= "Nous nous excusons pour ce désagrément.\n\n";
    $message .= "Cordialement,\n";
    $message .= "L'équipe MemoriaEventia";

    $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
    $headers .= "Reply-To: {$organizerEmail}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers);
  }

  /**
   * Envoyer un email aux administrateurs
   */
  public function sendEmailToAdmins(string $subject, string $message): bool {
    $admins = $this->userRepository->getAllAdmins();
    
    if (empty($admins)) {
      return false;
    }

    $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $success = true;
    foreach ($admins as $admin) {
      $result = mail($admin['email'], $subject, $message, $headers);
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
    $subject = 'Nouvelle demande de modification d\'événement';
    
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
    $subject = 'Nouvelle demande de suppression d\'événement';
    
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
