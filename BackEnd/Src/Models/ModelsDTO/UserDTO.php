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
  public string $created_at;

  public function __construct(User $user) {
    $this->id = $user->id;
    $this->email = $user->email;
    $this->name = $user->name;
    $this->is_admin = $user->is_admin;
    $this->is_organizer = $user->is_organizer;
    $this->is_moderator = $user->is_moderator;
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
      'created_at' => $this->created_at
    ];
  }
}
