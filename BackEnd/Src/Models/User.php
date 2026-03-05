<?php

namespace App\Models;

class User {
  public int $id;
  public string $email;
  public string $password;
  public string $name;
  public bool $is_admin;
  public bool $is_organizer;
  public bool $is_moderator;
  public bool $is_deleted;
  public string $created_at;
  public string $updated_at;
}
