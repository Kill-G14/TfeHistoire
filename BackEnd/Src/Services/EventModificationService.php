<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Repositories\EventModificationRepository;
use App\Repositories\UserRepository;
use App\Models\EventModification;
use App\Models\ModelsDTO\EventModificationDTO;
use App\Validators\EventModificationValidator;

class EventModificationService {
  private EventRepository $eventRepository;
  private EventModificationRepository $modificationRepository;
  private UserRepository $userRepository;
  private EmailService $emailService;
  private EventModificationValidator $validator;

  public function __construct(
    EventRepository $eventRepository,
    EventModificationRepository $modificationRepository,
    UserRepository $userRepository,
    EmailService $emailService,
    EventModificationValidator $validator
  ) {
    $this->eventRepository = $eventRepository;
    $this->modificationRepository = $modificationRepository;
    $this->userRepository = $userRepository;
    $this->emailService = $emailService;
    $this->validator = $validator;
  }

  /**
   * Créer une demande de modification de date/heure
   */
  public function requestModification(int $eventId, int $userId, string $newDate, string $newTime): array {
    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($eventId);
    if (!$event) {
      return ['success' => false, 'message' => 'Événement introuvable'];
    }

    // Vérifier que l'utilisateur est le créateur de l'événement
    if ($event->user_id !== $userId) {
      return ['success' => false, 'message' => 'Vous n\'êtes pas autorisé à modifier cet événement'];
    }

    // Vérifier que l'événement est approuvé
    if (!$event->is_approved) {
      return ['success' => false, 'message' => 'L\'événement doit être approuvé avant de pouvoir être modifié'];
    }

    // Vérifier qu'il n'y a pas déjà une modification en attente
    if ($event->has_pending_modification) {
      return ['success' => false, 'message' => 'Une modification est déjà en attente pour cet événement'];
    }

    // Vérifier que l'événement n'est pas en attente de suppression
    if ($event->deletion_requested) {
      return ['success' => false, 'message' => 'Cet événement est en attente de suppression'];
    }

    // Valider les nouvelles date et heure
    $errors = $this->validator->validate($newDate, $newTime);
    if (!empty($errors)) {
      return ['success' => false, 'message' => 'Données invalides', 'errors' => $errors];
    }

    // Créer la modification
    $modification = new EventModification();
    $modification->event_id = $eventId;
    $modification->new_date = $newDate;
    $modification->new_time = $newTime;
    $modification->old_date = $event->date;
    $modification->old_time = $event->time;

    $modificationId = $this->modificationRepository->createModification($modification);

    if (!$modificationId) {
      return ['success' => false, 'message' => 'Erreur lors de la création de la demande'];
    }

    // Marquer l'événement comme ayant une modification en attente
    $this->eventRepository->setHasPendingModification($eventId, true);

    // Notifier les admins
    $user = $this->userRepository->getUserById($userId);
    $this->emailService->notifyAdminsNewModificationRequest([
      'title' => $event->title,
      'old_date' => $event->date,
      'old_time' => $event->time,
      'new_date' => $newDate,
      'new_time' => $newTime
    ], [
      'name' => $user->name,
      'email' => $user->email
    ]);

    return [
      'success' => true,
      'message' => 'Demande de modification envoyée',
      'data' => ['modification_id' => $modificationId]
    ];
  }

  /**
   * Récupérer toutes les modifications en attente (admin)
   */
  public function getPendingModifications(): array {
    $modifications = $this->modificationRepository->getPendingModifications();
    
    return [
      'success' => true,
      'data' => $modifications
    ];
  }

  /**
   * Approuver une modification (admin)
   */
  public function approveModification(int $modificationId): array {
    $modification = $this->modificationRepository->getModificationById($modificationId);
    
    if (!$modification) {
      return ['success' => false, 'message' => 'Modification introuvable'];
    }

    if ($modification->status !== 'pending') {
      return ['success' => false, 'message' => 'Cette modification a déjà été traitée'];
    }

    // Récupérer l'événement
    $event = $this->eventRepository->getEventById($modification->event_id);
    if (!$event) {
      return ['success' => false, 'message' => 'Événement introuvable'];
    }

    // Mettre à jour la date et l'heure de l'événement
    $this->eventRepository->updateEventDateTime(
      $modification->event_id, 
      $modification->new_date, 
      $modification->new_time
    );

    // Approuver la modification
    $this->modificationRepository->approveModification($modificationId);

    // Récupérer les utilisateurs ayant acheté des billets
    $users = $this->eventRepository->getUsersWhoBoughtTickets($modification->event_id);

    // Récupérer l'email de l'organisateur
    $organizer = $this->userRepository->getUserById($event->user_id);

    // Envoyer un email à tous les acheteurs
    foreach ($users as $user) {
      $this->emailService->sendEventModificationEmail(
        $user['email'],
        $user['name'],
        [
          'title' => $event->title,
          'old_date' => $modification->old_date,
          'old_time' => $modification->old_time,
          'new_date' => $modification->new_date,
          'new_time' => $modification->new_time
        ],
        $organizer->email
      );
    }

    return ['success' => true, 'message' => 'Modification approuvée et notifications envoyées'];
  }

  /**
   * Rejeter une modification (admin)
   */
  public function rejectModification(int $modificationId, string $reason): array {
    $modification = $this->modificationRepository->getModificationById($modificationId);
    
    if (!$modification) {
      return ['success' => false, 'message' => 'Modification introuvable'];
    }

    if ($modification->status !== 'pending') {
      return ['success' => false, 'message' => 'Cette modification a déjà été traitée'];
    }

    // Rejeter la modification
    $this->modificationRepository->rejectModification($modificationId, $reason);

    // Retirer le flag has_pending_modification
    $this->eventRepository->setHasPendingModification($modification->event_id, false);

    return ['success' => true, 'message' => 'Modification rejetée'];
  }

  /**
   * Créer une demande de suppression d'événement
   */
  public function requestDeletion(int $eventId, int $userId, string $deletionMessage): array {
    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($eventId);
    if (!$event) {
      return ['success' => false, 'message' => 'Événement introuvable'];
    }

    // Vérifier que l'utilisateur est le créateur de l'événement
    if ($event->user_id !== $userId) {
      return ['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cet événement'];
    }

    // Vérifier que l'événement n'est pas déjà en attente de suppression
    if ($event->deletion_requested) {
      return ['success' => false, 'message' => 'Une demande de suppression est déjà en attente'];
    }

    // Valider le message
    if (empty(trim($deletionMessage))) {
      return ['success' => false, 'message' => 'Le message d\'excuse est obligatoire'];
    }

    // Marquer l'événement comme en attente de suppression
    $this->eventRepository->setDeletionRequested($eventId, true, $deletionMessage);

    // Notifier les admins
    $user = $this->userRepository->getUserById($userId);
    $this->emailService->notifyAdminsNewDeletionRequest([
      'title' => $event->title,
      'date' => $event->date,
      'time' => $event->time
    ], [
      'name' => $user->name,
      'email' => $user->email
    ], $deletionMessage);

    return [
      'success' => true,
      'message' => 'Demande de suppression envoyée. L\'événement ne peut plus être réservé en attendant la validation.'
    ];
  }

  /**
   * Récupérer tous les événements en attente de suppression (admin)
   */
  public function getPendingDeletions(): array {
    $events = $this->eventRepository->getEventsPendingDeletion();
    
    return [
      'success' => true,
      'data' => $events
    ];
  }

  /**
   * Approuver une suppression (admin)
   */
  public function approveDeletion(int $eventId, ?string $adminMessage = null): array {
    $event = $this->eventRepository->getEventById($eventId);
    
    if (!$event) {
      return ['success' => false, 'message' => 'Événement introuvable'];
    }

    if (!$event->deletion_requested) {
      return ['success' => false, 'message' => 'Aucune demande de suppression en attente pour cet événement'];
    }

    // Récupérer les utilisateurs ayant acheté des billets
    $users = $this->eventRepository->getUsersWhoBoughtTickets($eventId);

    // Récupérer l'organisateur
    $organizer = $this->userRepository->getUserById($event->user_id);

    // Message final (celui de l'organisateur + celui de l'admin si fourni)
    $finalMessage = $event->deletion_message;
    if ($adminMessage) {
      $finalMessage .= "\n\nNote de l'administration :\n" . $adminMessage;
    }

    // Envoyer un email à tous les acheteurs
    foreach ($users as $user) {
      $this->emailService->sendEventDeletionEmail(
        $user['email'],
        $user['name'],
        [
          'title' => $event->title,
          'date' => $event->date,
          'time' => $event->time,
          'address' => $event->address,
          'city' => $event->city,
          'country' => $event->country
        ],
        $finalMessage,
        $organizer->email
      );
    }

    // Envoyer une copie aux admins
    $adminSubject = 'Confirmation de suppression d\'événement : ' . $event->title;
    $adminMessage = "L'événement suivant a été supprimé et les notifications ont été envoyées :\n\n";
    $adminMessage .= "Événement : {$event->title}\n";
    $adminMessage .= "Créateur : {$organizer->name} ({$organizer->email})\n";
    $adminMessage .= "Nombre de participants notifiés : " . count($users);
    $this->emailService->sendEmailToAdmins($adminSubject, $adminMessage);

    // Supprimer l'événement (soft delete)
    $this->eventRepository->deleteEvent($eventId);

    return [
      'success' => true,
      'message' => 'Événement supprimé et ' . count($users) . ' notification(s) envoyée(s)'
    ];
  }

  /**
   * Rejeter une suppression (admin)
   */
  public function rejectDeletion(int $eventId, string $reason): array {
    $event = $this->eventRepository->getEventById($eventId);
    
    if (!$event) {
      return ['success' => false, 'message' => 'Événement introuvable'];
    }

    if (!$event->deletion_requested) {
      return ['success' => false, 'message' => 'Aucune demande de suppression en attente pour cet événement'];
    }

    // Retirer le flag deletion_requested
    $this->eventRepository->setDeletionRequested($eventId, false, null);

    return ['success' => true, 'message' => 'Demande de suppression rejetée'];
  }
}
