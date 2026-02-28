<?php

namespace App\Models\ModelsDTO;

use App\Models\User;

class UserDTO {
  public int $id;
  public string $email;
  public string $name;
  public string $created_at;

  public function __construct(User $user) {
    $this->id = $user->id;
    $this->email = $user->email;
    $this->name = $user->name;
    $this->created_at = $user->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'email' => $this->email,
      'name' => $this->name,
      'created_at' => $this->created_at
    ];
  }
}
