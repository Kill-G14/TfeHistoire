<?php

namespace App\Models;

class User {
  public int $id;
  public string $email;
  public string $password;
  public string $name;
  public string $created_at;
  public string $updated_at;
}
