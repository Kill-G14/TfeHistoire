<?php

namespace App\Models\ModelsDTO;

use App\Models\User;

class UserDTO {
  public int $id;
  public string $email;
  public string $name;
  public bool $is_admin;
  public bool $is_organizer;
  public bool $is_moderator;
  // Indique seulement si l'organisateur peut encaisser : l'identifiant Stripe reste côté serveur
  public bool $stripe_connected;
  public string $created_at;

  public function __construct(User $user) {
    $this->id = $user->id;
    $this->email = $user->email;
    $this->name = $user->name;
    $this->is_admin = $user->is_admin;
    $this->is_organizer = $user->is_organizer;
    $this->is_moderator = $user->is_moderator;
    $this->stripe_connected = $user->stripe_onboarding_completed && !empty($user->stripe_account_id);
    $this->created_at = $user->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'email' => $this->email,
      'name' => $this->name,
      'is_admin' => $this->is_admin,
      'is_organizer' => $this->is_organizer,
      'is_moderator' => $this->is_moderator,
      'stripe_connected' => $this->stripe_connected,
      'created_at' => $this->created_at
    ];
  }
}
