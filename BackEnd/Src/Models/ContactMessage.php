<?php

namespace App\Models;

class ContactMessage {
  public int $id;
  public string $name;
  public string $email;
  public string $subject;
  public string $message;
  public bool $is_treated;
  public bool $is_deleted;
  public string $created_at;
  public string $updated_at;
}
