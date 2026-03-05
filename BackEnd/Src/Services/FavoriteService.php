<?php

namespace App\Services;

use App\Models\ModelsDTO\FavoriteDTO;
use App\Repositories\FavoriteRepository;
use App\Repositories\EventRepository;
use App\Utils\Logger;

class FavoriteService {
  private FavoriteRepository $favoriteRepository;
  private EventRepository $eventRepository;

  public function __construct(FavoriteRepository $favoriteRepository, EventRepository $eventRepository) {
    $this->favoriteRepository = $favoriteRepository;
    $this->eventRepository = $eventRepository;
  }

  // Récupérer les favoris d'un utilisateur
  public function getFavoritesByUserId(int $userId): array {
    $favorites = $this->favoriteRepository->getFavoritesByUserId($userId);
    
    $favoriteDTOs = array_map(function($favorite) {
      return (new FavoriteDTO($favorite))->toArray();
    }, $favorites);

    return [
      'success' => true,
      'data' => $favoriteDTOs
    ];
  }

  // Récupérer les événements favoris avec leurs détails
  public function getFavoriteEventsWithDetails(int $userId): array {
    $favoriteEvents = $this->favoriteRepository->getFavoriteEventsWithDetails($userId);

    return [
      'success' => true,
      'data' => $favoriteEvents
    ];
  }

  // Vérifier si un événement est en favori
  public function isFavorite(int $userId, int $eventId): array {
    $isFavorite = $this->favoriteRepository->isFavorite($userId, $eventId);

    return [
      'success' => true,
      'data' => ['is_favorite' => $isFavorite]
    ];
  }

  // Ajouter un favori
  public function addFavorite(int $userId, int $eventId): array {
    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($eventId);
    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    // Vérifier si déjà en favori
    if ($this->favoriteRepository->isFavorite($userId, $eventId)) {
      return [
        'success' => false,
        'message' => 'Cet événement est déjà dans vos favoris'
      ];
    }

    // Ajouter le favori
    $favoriteId = $this->favoriteRepository->addFavorite($userId, $eventId);

    if (!$favoriteId) {
      Logger::error('Failed to add favorite', ['user_id' => $userId, 'event_id' => $eventId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de l\'ajout aux favoris'
      ];
    }

    Logger::info('Favorite added successfully', ['user_id' => $userId, 'event_id' => $eventId]);

    return [
      'success' => true,
      'message' => 'Événement ajouté aux favoris'
    ];
  }

  // Supprimer un favori
  public function deleteFavorite(int $userId, int $eventId): array {
    // Vérifier si le favori existe
    if (!$this->favoriteRepository->isFavorite($userId, $eventId)) {
      return [
        'success' => false,
        'message' => 'Cet événement n\'est pas dans vos favoris'
      ];
    }

    // Supprimer le favori
    $success = $this->favoriteRepository->deleteFavorite($userId, $eventId);

    if (!$success) {
      Logger::error('Failed to delete favorite', ['user_id' => $userId, 'event_id' => $eventId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la suppression du favori'
      ];
    }

    Logger::info('Favorite deleted successfully', ['user_id' => $userId, 'event_id' => $eventId]);

    return [
      'success' => true,
      'message' => 'Événement retiré des favoris'
    ];
  }

  // Alias pour getUserFavorites
  public function getUserFavorites(int $userId): array {
    return $this->getFavoritesByUserId($userId);
  }

  // Alias pour removeFavorite
  public function removeFavorite(int $userId, int $eventId): array {
    return $this->deleteFavorite($userId, $eventId);
  }
}
