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
  private string $driver;

  public function __construct(UserRepository $userRepository) {
    $this->userRepository = $userRepository;
    
    // Configuration depuis .env
    $this->fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? 'noreply@memoriaeventia.com';
    $this->fromName = $_ENV['SENDGRID_FROM_NAME'] ?? 'MemoriaEventia';
    // MAIL_DRIVER : smtp | sendgrid | simulation. Sans valeur, on garde l'ancien comportement (SENDGRID_ENABLED).
    $legacyEnabled = filter_var($_ENV['SENDGRID_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $this->driver = strtolower($_ENV['MAIL_DRIVER'] ?? ($legacyEnabled ? 'sendgrid' : 'simulation'));
    $this->enabled = $this->driver === 'sendgrid';

    // Initialiser SendGrid si activé et clé API disponible
    if ($this->enabled && !empty($_ENV['SENDGRID_API_KEY'])) {
      // Options pour résoudre les problèmes SSL en développement
      $options = [
        'curl' => [
          CURLOPT_SSL_VERIFYPEER => false, // Désactiver la vérification SSL en dev
          CURLOPT_SSL_VERIFYHOST => false
        ]
      ];
      
      $this->sendgrid = new SendGrid($_ENV['SENDGRID_API_KEY'], $options);
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
  private function sendEmail(string $to, string $toName, string $subject, string $htmlContent, string $textContent = '', array $attachments = []): bool {
    // Envoi par le serveur SMTP de l'hébergeur (boîte mail Infomaniak)
    if ($this->driver === 'smtp') {
      return $this->sendViaSmtp($to, $toName, $subject, $htmlContent, $textContent, $attachments);
    }

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

      // Pièces jointes (ex. billets PDF) : SendGrid attend le contenu en base64
      foreach ($attachments as $attachment) {
        $email->addAttachment(
          base64_encode($attachment['content']),
          $attachment['mime_type'],
          $attachment['filename'],
          'attachment'
        );
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
   * Envoyer un email via SMTP (PHPMailer). Paramètres lus dans le .env :
   * MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION (tls|ssl)
   */
  private function sendViaSmtp(string $to, string $toName, string $subject, string $htmlContent, string $textContent, array $attachments = []): bool {
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
      Logger::error('MAIL_DRIVER=smtp mais PHPMailer n\'est pas installé (composer require phpmailer/phpmailer)');
      return false;
    }

    try {
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = $_ENV['MAIL_HOST'] ?? '';
      $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);
      $mail->SMTPAuth = true;
      $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
      $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
      $mail->SMTPSecure = strtolower($_ENV['MAIL_ENCRYPTION'] ?? 'tls') === 'ssl'
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
      $mail->CharSet = 'UTF-8';
      $mail->Timeout = 10;

      // Infomaniak impose que l'expéditeur soit l'adresse de la boîte authentifiée
      $mail->setFrom($this->fromEmail, $this->fromName);
      $mail->addAddress($to, $toName);
      $mail->Subject = $subject;
      $mail->isHTML(true);
      $mail->Body = $htmlContent;
      if (!empty($textContent)) {
        $mail->AltBody = $textContent;
      }

      foreach ($attachments as $attachment) {
        $mail->addStringAttachment(
          $attachment['content'],
          $attachment['filename'],
          \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
          $attachment['mime_type']
        );
      }

      $mail->send();
      Logger::info('Email envoyé avec succès (SMTP)', ['to' => $to, 'subject' => $subject]);
      return true;
    } catch (\Exception $e) {
      // Le message d'erreur ne contient jamais le mot de passe
      Logger::error('Erreur lors de l\'envoi SMTP', ['to' => $to, 'error' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Envoyer un email de confirmation de réservation
   */
  /**
   * $reservationDetails (optionnel) : reservation_id, amount_total, organizer_name, organizer_email
   */
  public function sendReservationConfirmation(
    string $to,
    string $toName,
    array $eventData,
    int $quantity,
    array $reservationDetails = []
  ): bool {
    $subject = '✅ Réservation confirmée : ' . $eventData['title'];

    // Les valeurs viennent d'utilisateurs : échappées avant d'entrer dans le HTML
    $safe = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $profileUrl = $this->getProfileReservationsUrl();
    $reference = isset($reservationDetails['reservation_id'])
      ? 'MEM-' . str_pad((string) $reservationDetails['reservation_id'], 6, '0', STR_PAD_LEFT)
      : null;
    $amountText = (isset($reservationDetails['amount_total']) && (float) $reservationDetails['amount_total'] > 0)
      ? number_format((float) $reservationDetails['amount_total'], 2, ',', ' ') . ' €'
      : 'Gratuit';
    $organizerName = $reservationDetails['organizer_name'] ?? null;
    $organizerEmail = $reservationDetails['organizer_email'] ?? null;

    $referenceHtml = $reference ? "<p><strong>🔖 Référence :</strong> {$safe($reference)}</p>" : '';
    $organizerHtml = $organizerName
      ? "<p><strong>🎫 Organisé par :</strong> {$safe($organizerName)}" . ($organizerEmail ? " (<a href='mailto:{$safe($organizerEmail)}'>{$safe($organizerEmail)}</a>)" : '') . "</p>"
      : '';
    $profileButtonHtml = $profileUrl
      ? "<p style='text-align: center; margin: 25px 0;'>
          <a href='{$safe($profileUrl)}' style='background-color: #1a3a52; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; display: inline-block;'>Voir mes réservations</a>
        </p>"
      : '';

    $htmlContent = "
      <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #1a3a52;'>Réservation confirmée !</h2>
        <p>Bonjour <strong>{$safe($toName)}</strong>,</p>
        <p>Votre réservation a été confirmée avec succès pour l'événement suivant :</p>

        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
          <h3 style='color: #1a3a52; margin-top: 0;'>{$safe($eventData['title'])}</h3>
          <p><strong>📅 Date :</strong> {$safe($eventData['date'])} à {$safe($eventData['time'])}</p>
          <p><strong>📍 Lieu :</strong> {$safe($eventData['address'])}, {$safe($eventData['city'])}, {$safe($eventData['country'])}</p>
          <p><strong>👥 Nombre de places :</strong> {$quantity}</p>
          <p><strong>💶 Montant :</strong> {$amountText}</p>
          {$referenceHtml}
          {$organizerHtml}
        </div>

        {$profileButtonHtml}

        <p>Vos billets (un par place, avec QR code) sont joints à ce message en PDF. Vous pouvez aussi les retrouver à tout moment dans votre profil (connexion requise).</p>
        <p>Pour toute question ou demande de remboursement, contactez directement l'organisateur : MemoriaEventia met en relation les visiteurs et les organisateurs et n'encaisse aucun paiement.</p>

        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";

    $textContent = "Réservation confirmée !\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "Votre réservation a été confirmée pour :\n\n";
    $textContent .= "Événement : {$eventData['title']}\n";
    $textContent .= "Date : {$eventData['date']} à {$eventData['time']}\n";
    $textContent .= "Lieu : {$eventData['address']}, {$eventData['city']}, {$eventData['country']}\n";
    $textContent .= "Nombre de places : {$quantity}\n";
    $textContent .= "Montant : {$amountText}\n";
    if ($reference) {
      $textContent .= "Référence : {$reference}\n";
    }
    if ($organizerName) {
      $textContent .= "Organisé par : {$organizerName}" . ($organizerEmail ? " ({$organizerEmail})" : '') . "\n";
    }
    $textContent .= "\n";
    if ($profileUrl) {
      $textContent .= "Voir mes réservations (connexion requise) : {$profileUrl}\n\n";
    }
    $textContent .= "Pour toute question ou demande de remboursement, contactez directement l'organisateur.\n\n";
    $textContent .= "Cordialement,\nL'équipe MemoriaEventia";

    // Billets PDF en pièce jointe (généré par l'appelant, absent si la génération a échoué)
    $attachments = [];
    if (!empty($reservationDetails['pdf_content'])) {
      $attachments[] = [
        'content' => $reservationDetails['pdf_content'],
        'filename' => $reservationDetails['pdf_filename'] ?? 'billets.pdf',
        'mime_type' => 'application/pdf',
      ];
    }

    return $this->sendEmail($to, $toName, $subject, $htmlContent, $textContent, $attachments);
  }

  /**
   * Lien vers la section « réservations » du profil.
   * Ne contient aucun jeton : l'accès exige toujours la connexion.
   * Retourne null si FRONTEND_URL n'est pas configurée (le mail part alors sans lien).
   */
  private function getProfileReservationsUrl(): ?string {
    $frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? '', '/');
    return $frontendUrl !== '' ? $frontendUrl . '/profile?section=reservations' : null;
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
    $profileUrl = $this->getProfileReservationsUrl();

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
        " . ($profileUrl ? "<p><a href='" . htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') . "'>Voir mes réservations</a> (connexion requise)</p>" : '') . "

        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";

    $textContent = "Réservation annulée\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "Votre réservation a été annulée pour :\n\n";
    $textContent .= "Événement : {$eventData['title']}\n";
    $textContent .= "Date : {$eventData['date']} à {$eventData['time']}\n";
    $textContent .= "Lieu : {$eventData['address']}, {$eventData['city']}, {$eventData['country']}\n\n";
    if ($profileUrl) {
      $textContent .= "Voir mes réservations (connexion requise) : {$profileUrl}\n\n";
    }
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

  /**
   * Envoyer un email de réinitialisation de mot de passe
   */
  public function sendPasswordResetEmail($user, string $code): bool {
    $to = $user->email;
    $toName = $user->name;
    $subject = '🔐 Réinitialisation de votre mot de passe';
    
    $htmlContent = "
      <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #1a3a52;'>🔐 Réinitialisation de mot de passe</h2>
        <p>Bonjour <strong>{$toName}</strong>,</p>
        <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
        
        <div style='background-color: #f8f9fa; padding: 30px; border-radius: 8px; margin: 20px 0; text-align: center;'>
          <p style='margin: 0 0 15px 0; color: #6c757d; font-size: 14px;'>Votre code de réinitialisation :</p>
          <div style='font-size: 32px; font-weight: bold; color: #1a3a52; letter-spacing: 8px; font-family: monospace;'>
            {$code}
          </div>
          <p style='margin: 15px 0 0 0; color: #dc3545; font-size: 14px;'>⏱️ Ce code expire dans <strong>10 minutes</strong></p>
        </div>
        
        <p>Entrez ce code sur la page de réinitialisation pour créer un nouveau mot de passe.</p>
        
        <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
          <p style='margin: 0; color: #856404;'>
            ⚠️ <strong>Si vous n'avez pas demandé cette réinitialisation</strong>, ignorez cet email.<br>
            Votre mot de passe actuel reste inchangé.
          </p>
        </div>
        
        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";
    
    $textContent = "Réinitialisation de mot de passe\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "Vous avez demandé la réinitialisation de votre mot de passe.\n\n";
    $textContent .= "Votre code de réinitialisation : {$code}\n\n";
    $textContent .= "Ce code expire dans 10 minutes.\n\n";
    $textContent .= "Entrez ce code sur la page de réinitialisation pour créer un nouveau mot de passe.\n\n";
    $textContent .= "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\n";
    $textContent .= "Cordialement,\nL'équipe MemoriaEventia";

    return $this->sendEmail($to, $toName, $subject, $htmlContent, $textContent);
  }
}
