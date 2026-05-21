<?php

namespace App\Models;

class PasswordReset
{
    public int $id;
    public int $user_id;
    public string $code;
    public string $expires_at;
    public int $attempts;
    public string $created_at;
}
