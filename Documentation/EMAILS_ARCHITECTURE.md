# 📧 SYSTÈME D'EMAILS - Documentation technique

Documentation du système d'envoi d'emails via SendGrid dans MemoriaEventia.

---

## 🏗️ Architecture

### Fichiers concernés

```
BackEnd/
├── .env                              # Configuration SendGrid
├── .env.example                      # Template de configuration
├── composer.json                     # Dépendance sendgrid/sendgrid
├── Src/
│   └── Services/
│       ├── EmailService.php          # Service centralisé d'envoi d'emails
│       └── ReservationService.php    # Utilise EmailService pour notifications
└── Api/
    └── reservationsApi.php           # Instancie EmailService
```

---

## 📨 Types d'emails

### 1. Emails Utilisateurs

#### ✅ Confirmation de réservation

- **Déclencheur** : Après création d'une réservation
- **Méthode** : `EmailService::sendReservationConfirmation()`
- **Contenu** : Détails de l'événement, nombre de places, date et lieu

#### ❌ Annulation de réservation

- **Déclencheur** : Après annulation par l'utilisateur
- **Méthode** : `EmailService::sendReservationCancellation()`
- **Contenu** : Confirmation d'annulation avec détails de l'événement

#### ⚠️ Modification d'événement

- **Déclencheur** : Quand un organisateur modifie un événement (après validation admin)
- **Méthode** : `EmailService::sendEventModificationEmail()`
- **Contenu** : Ancienne date vs nouvelle date, contact de l'organisateur

#### 🚫 Annulation d'événement

- **Déclencheur** : Quand un événement est annulé (après validation admin)
- **Méthode** : `EmailService::sendEventDeletionEmail()`
- **Contenu** : Détails de l'événement annulé, message de l'organisateur, contact

### 2. Emails Administrateurs

#### 🔔 Nouvelle demande de modification

- **Déclencheur** : Organisateur soumet une demande de modification
- **Méthode** : `EmailService::notifyAdminsNewModificationRequest()`
- **Destinataires** : Tous les administrateurs
- **Contenu** : Détails de la modification, lien vers l'admin

#### 🔔 Nouvelle demande de suppression

- **Déclencheur** : Organisateur soumet une demande de suppression
- **Méthode** : `EmailService::notifyAdminsNewDeletionRequest()`
- **Destinataires** : Tous les administrateurs
- **Contenu** : Détails de l'événement, message de l'organisateur

---

## 🎨 Templates d'emails

Tous les emails sont envoyés en **double format** :

- **HTML** : Design professionnel avec styles CSS inline
- **Texte brut** : Fallback pour clients email sans support HTML

### Structure HTML

```html
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <h2 style="color: #1a3a52;">Titre de l'email</h2>
  <p>Contenu...</p>

  <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
    <!-- Bloc d'information principal -->
  </div>

  <p style="margin-top: 30px;">
    Cordialement,<br />
    <strong>L'équipe MemoriaEventia</strong>
  </p>
</div>
```

### Palette de couleurs

- **Primaire** : `#1a3a52` (bleu foncé)
- **Succès** : `#28a745` (vert)
- **Attention** : `#ffc107` (jaune/orange)
- **Danger** : `#dc3545` (rouge)
- **Fond** : `#f8f9fa` (gris clair)

---

## 🔧 Utilisation dans le code

### Exemple : Envoyer un email de confirmation

```php
// Dans ReservationService.php

// 1. Préparer les données
$eventData = [
    'title' => $event->title,
    'date' => $event->date,
    'time' => $event->time,
    'address' => $event->address,
    'city' => $event->city,
    'country' => $event->country
];

// 2. Appeler EmailService
$this->emailService->sendReservationConfirmation(
    $user->email,      // Destinataire
    $user->name,       // Nom du destinataire
    $eventData,        // Détails de l'événement
    $quantity          // Nombre de places
);
```

### Exemple : Ajouter un nouveau type d'email

```php
// Dans EmailService.php

/**
 * Envoyer un email de rappel avant événement
 */
public function sendEventReminder(
    string $to,
    string $toName,
    array $eventData,
    int $daysBeforeEvent
): bool {
    $subject = '📅 Rappel : ' . $eventData['title'] . ' dans ' . $daysBeforeEvent . ' jours';

    $htmlContent = "
      <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #1a3a52;'>N'oubliez pas votre événement !</h2>
        <p>Bonjour <strong>{$toName}</strong>,</p>
        <p>Nous vous rappelons que l'événement suivant aura lieu dans <strong>{$daysBeforeEvent} jours</strong> :</p>

        <div style='background-color: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>
          <h3 style='color: #1a3a52; margin-top: 0;'>{$eventData['title']}</h3>
          <p><strong>📅 Date :</strong> {$eventData['date']} à {$eventData['time']}</p>
          <p><strong>📍 Lieu :</strong> {$eventData['address']}, {$eventData['city']}</p>
        </div>

        <p>À bientôt !</p>
        <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe MemoriaEventia</strong></p>
      </div>
    ";

    $textContent = "N'oubliez pas votre événement !\n\n";
    $textContent .= "Bonjour {$toName},\n\n";
    $textContent .= "L'événement suivant aura lieu dans {$daysBeforeEvent} jours :\n\n";
    $textContent .= "Événement : {$eventData['title']}\n";
    $textContent .= "Date : {$eventData['date']} à {$eventData['time']}\n";
    $textContent .= "Lieu : {$eventData['address']}, {$eventData['city']}\n\n";
    $textContent .= "À bientôt !\nL'équipe MemoriaEventia";

    return $this->sendEmail($to, $toName, $subject, $htmlContent, $textContent);
}
```

---

## 🔒 Sécurité et logs

### Logging automatique

Le système log automatiquement :

- ✅ **Emails envoyés avec succès** (destinataire, sujet, statut HTTP)
- ❌ **Erreurs d'envoi** (code erreur, message)
- ⚠️ **Tentatives avec SendGrid désactivé** (mode simulation)

**Emplacement des logs** : `BackEnd/logs/`

### Exemple de log

```
[2026-05-20 14:32:15] INFO: Email envoyé avec succès
  - to: user@example.com
  - subject: ✅ Réservation confirmée : Bataille de Waterloo
  - status: 202

[2026-05-20 14:35:22] WARNING: Email simulation (SendGrid désactivé)
  - to: user@example.com
  - subject: ❌ Réservation annulée : Bataille de Waterloo
```

---

## ⚙️ Configuration

### Variables d'environnement (.env)

```bash
# Clé API SendGrid (obtenue sur app.sendgrid.com)
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxx

# Email expéditeur (doit être vérifié dans SendGrid)
SENDGRID_FROM_EMAIL=noreply@memoriaeventia.com

# Nom de l'expéditeur
SENDGRID_FROM_NAME=MemoriaEventia

# Activer/Désactiver l'envoi réel d'emails
SENDGRID_ENABLED=true
```

### Mode simulation (développement)

```bash
SENDGRID_ENABLED=false
```

- Les emails ne sont **pas envoyés**
- Les actions sont **loggées**
- Pas de consommation du quota SendGrid

---

## 🧪 Tests

### Tester l'envoi d'emails

1. **Mode simulation** : Vérifier les logs

   ```bash
   tail -f BackEnd/logs/app.log
   ```

2. **Mode production** : Créer une vraie réservation
   - Se connecter à l'application
   - Réserver un événement
   - Vérifier la réception de l'email

3. **Dashboard SendGrid** : Consulter l'activity feed
   - https://app.sendgrid.com/
   - Onglet **Activity**
   - Voir les emails envoyés et leur statut

---

## 📈 Performance

### Temps de réponse

L'appel à l'API SendGrid est **asynchrone** dans le flux :

- La réservation est créée **immédiatement**
- L'email est envoyé **après**
- Si l'email échoue, **la réservation reste valide**

### Gestion des erreurs

Si l'envoi échoue :

- ❌ L'erreur est **loggée**
- ✅ L'opération principale **continue** (réservation créée)
- 🔔 L'administrateur peut consulter les logs pour investiguer

---

## 🚀 Améliorations futures

### Fonctionnalités à ajouter

- [ ] **Templates SendGrid** : Utiliser les templates visuels de SendGrid
- [ ] **Emails planifiés** : Rappels automatiques X jours avant événement
- [ ] **Personnalisation** : Permettre aux organisateurs de personnaliser les emails
- [ ] **Tracking** : Suivre les ouvertures et clics d'emails
- [ ] **Traductions** : Envoyer les emails dans la langue de l'utilisateur
- [ ] **Queue system** : Utiliser une queue (Redis/RabbitMQ) pour les envois en masse

### Migration vers les Dynamic Templates SendGrid

Au lieu de HTML inline, utiliser les templates visuels de SendGrid :

```php
// Au lieu de définir le HTML dans le code
$email->setTemplateId('d-xxxxxxxxxxxxx'); // ID du template SendGrid
$email->addDynamicTemplateData([
    'user_name' => $toName,
    'event_title' => $eventData['title'],
    'event_date' => $eventData['date']
]);
```

**Avantages** :

- ✅ Design plus professionnel
- ✅ Modification sans redéploiement
- ✅ A/B testing intégré
- ✅ Responsive par défaut

---

## 📚 Ressources

- **SendGrid PHP Library** : https://github.com/sendgrid/sendgrid-php
- **SendGrid API Docs** : https://docs.sendgrid.com/api-reference/how-to-use-the-sendgrid-v3-api/authentication
- **Email Best Practices** : https://docs.sendgrid.com/ui/sending-email/email-best-practices

---

**Dernière mise à jour** : 20 Mai 2026
