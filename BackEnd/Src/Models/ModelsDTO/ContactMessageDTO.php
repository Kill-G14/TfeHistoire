<?php

namespace App\Models\ModelsDTO;

use App\Models\ContactMessage;

class ContactMessageDTO {
  public int $id;
  public string $name;
  public string $email;
  public string $subject;
  public string $message;
  public bool $is_treated;
  public string $created_at;

  public function __construct(ContactMessage $contactMessage) {
    $this->id = $contactMessage->id;
    $this->name = $contactMessage->name;
    $this->email = $contactMessage->email;
    $this->subject = $contactMessage->subject;
    $this->message = $contactMessage->message;
    $this->is_treated = $contactMessage->is_treated;
    $this->created_at = $contactMessage->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'email' => $this->email,
      'subject' => $this->subject,
      'message' => $this->message,
      'is_treated' => $this->is_treated,
      'created_at' => $this->created_at
    ];
  }
}
