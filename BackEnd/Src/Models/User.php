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
  // Compte Stripe Connect de l'organisateur : ne doit jamais sortir du back-end
  public ?string $stripe_account_id = null;
  public bool $stripe_onboarding_completed = false;
  public bool $is_deleted;
  public string $created_at;
  public string $updated_at;
}
