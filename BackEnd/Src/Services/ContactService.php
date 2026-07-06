<?php

namespace App\Services;

use App\Models\ModelsDTO\ContactMessageDTO;
use App\Repositories\ContactMessageRepository;
use App\Validators\ContactValidator;
use App\Utils\Logger;

class ContactService {
  private ContactMessageRepository $contactMessageRepository;
  private ContactValidator $contactValidator;

  public function __construct(ContactMessageRepository $contactMessageRepository, ContactValidator $contactValidator) {
    $this->contactMessageRepository = $contactMessageRepository;
    $this->contactValidator = $contactValidator;
  }

  // Soumettre un nouveau message de contact
  public function submitMessage(array $data): array {
    $errors = $this->contactValidator->validate($data);

    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Veuillez corriger les erreurs du formulaire',
        'errors' => $errors
      ];
    }

    $id = $this->contactMessageRepository->create(
      trim($data['name']),
      trim($data['email']),
      trim($data['subject']),
      trim($data['message'])
    );

    if (!$id) {
      Logger::error('Failed to create contact message');
      return [
        'success' => false,
        'message' => 'Erreur lors de l\'envoi de votre message'
      ];
    }

    Logger::info('Contact message created successfully', ['id' => $id]);

    return [
      'success' => true,
      'message' => 'Votre message a bien été envoyé'
    ];
  }

  // Récupérer tous les messages (AdminOffice)
  public function getAllMessages(): array {
    $messages = $this->contactMessageRepository->getAll();

    $messageDTOs = array_map(function ($message) {
      return (new ContactMessageDTO($message))->toArray();
    }, $messages);

    return [
      'success' => true,
      'message' => 'Messages récupérés avec succès',
      'data' => $messageDTOs
    ];
  }

  // Marquer un message comme traité (AdminOffice)
  public function markAsTreated(int $id): array {
    $contactMessage = $this->contactMessageRepository->getById($id);

    if (!$contactMessage) {
      return [
        'success' => false,
        'message' => 'Message non trouvé'
      ];
    }

    $success = $this->contactMessageRepository->markAsTreated($id);

    if (!$success) {
      Logger::error('Failed to mark contact message as treated', ['id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du message'
      ];
    }

    return [
      'success' => true,
      'message' => 'Message marqué comme traité'
    ];
  }
}
