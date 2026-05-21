# Backend MemoriaEventia

Backend PHP pour la plateforme d'événements historiques européens.

## ⚙️ Configuration

Le backend utilise un système de configuration centralisé via le fichier `config.php`.

**Première installation :**

```bash
# Copier le fichier de configuration exemple
cp config.example.php config.php
```

Ensuite, éditez `config.php` pour configurer :

- Clé API OpenRouteService (pour calcul d'itinéraires)
- Paramètres de base de données
- Configuration de l'application

📖 Voir [README_CONFIG.md](README_CONFIG.md) pour plus de détails

## Structure

```
BackEnd/
├── Api/                          # Points d'entrée HTTP
│   ├── adminApi.php              # API Admin (gestion complète)
│   ├── authApi.php               # Authentification
│   ├── eventsApi.php             # Gestion des événements
│   ├── favoritesApi.php          # Favoris utilisateurs
│   ├── reservationsApi.php       # Gestion des réservations
│   ├── imageApi.php              # Récupération d'images
│   ├── uploadImageApi.php        # Upload d'images
│   └── routeApi.php              # Calcul d'itinéraires
│
├── Src/
│   ├── Models/                   # Entités métier
│   │   ├── User.php
│   │   ├── Event.php
│   │   ├── EventModification.php
│   │   ├── Reservation.php
│   │   ├── Favorite.php
│   │   └── ModelsDTO/            # Data Transfer Objects
│   │       ├── UserDTO.php
│   │       ├── EventDTO.php
│   │       ├── EventModificationDTO.php
│   │       ├── ReservationDTO.php
│   │       └── FavoriteDTO.php
│   │
│   ├── Repositories/             # Accès base de données
│   │   ├── UserRepository.php
│   │   ├── EventRepository.php
│   │   ├── EventModificationRepository.php
│   │   ├── ReservationRepository.php
│   │   ├── FavoriteRepository.php
│   │   └── SessionRepository.php
│   │
│   ├── Services/                 # Logique métier
│   │   ├── AuthService.php
│   │   ├── EventService.php
│   │   ├── EventModificationService.php
│   │   ├── ReservationService.php
│   │   ├── FavoriteService.php
│   │   ├── UserService.php
│   │   ├── SessionService.php
│   │   └── EmailService.php
│   │
│   ├── Validators/               # Validation des données
│   │   ├── UserValidator.php
│   │   ├── EventValidator.php
│   │   └── EventModificationValidator.php
│   │
│   └── Utils/                    # Utilitaires transversaux
│       ├── Database.php          # Connexion PDO
│       ├── Logger.php            # Logs
│       ├── EnvLoader.php         # Chargement variables d'environnement
│       └── Helpers.php           # Fonctions utilitaires
│
├── storage/                      # Stockage fichiers
│   ├── images/                   # Images d'événements
│   └── tickets/                  # Billets PDF (réservé)
│
├── logs/                         # Logs applicatifs
├── vendor/                       # Autoload Composer
├── database.sql                  # Script de création de la base
├── reset_database.sql            # Script de réinitialisation
├── composer.json                 # Configuration autoload
└── .htaccess                     # Configuration Apache
```

## Installation

### 1. Configuration de la base de données

1. Créez la base de données en exécutant le fichier `database.sql`
2. Modifiez les paramètres de connexion dans `Src/Utils/Database.php` si nécessaire

```php
private const DB_HOST = 'localhost';
private const DB_NAME = 'memoriaeventia';
private const DB_USER = 'root';
private const DB_PASS = '';
```

### 2. Installation des dépendances

```bash
composer install
```

### 3. Génération de l'autoload

```bash
composer dump-autoload
```

## API Endpoints

### Authentification (`Api/authApi.php`)

#### Inscription

```json
POST /Api/authApi.php
{
  "action": "register",
  "email": "user@example.com",
  "password": "password123",
  "name": "John Doe"
}
```

#### Connexion

```json
POST /Api/authApi.php
{
  "action": "login",
  "email": "user@example.com",
  "password": "password123"
}
```

#### Déconnexion

```json
POST /Api/authApi.php
{
  "action": "logout",
  "token": "user_token_here"
}
```

#### Récupérer l'utilisateur connecté

```json
POST /Api/authApi.php
{
  "action": "getCurrentUser",
  "token": "user_token_here"
}
```

#### Mettre à jour le profil

```json
POST /Api/authApi.php
{
  "action": "updateProfile",
  "token": "user_token_here",
  "name": "Nouveau Nom",
  "email": "newemail@example.com"
}
```

#### Changer le mot de passe

```json
POST /Api/authApi.php
{
  "action": "changePassword",
  "token": "user_token_here",
  "old_password": "ancien",
  "new_password": "nouveau"
}
```

---

### Événements (`Api/eventsApi.php`)

#### Récupérer tous les événements approuvés

```json
POST /Api/eventsApi.php
{
  "action": "getAll"
}
```

#### Récupérer un événement par ID

```json
POST /Api/eventsApi.php
{
  "action": "getById",
  "id": 1
}
```

#### Rechercher des événements

```json
POST /Api/eventsApi.php
{
  "action": "search",
  "search": "carnaval",
  "country": "France",
  "category": "Festival"
}
```

#### Récupérer mes événements (authentification requise)

```json
POST /Api/eventsApi.php
{
  "action": "getMyEvents",
  "token": "user_token_here"
}
```

#### Créer un événement (authentification requise)

```json
POST /Api/eventsApi.php
{
  "action": "create",
  "token": "user_token_here",
  "title": "Titre de l'événement",
  "description": "Description de l'événement",
  "country": "France",
  "city": "Paris",
  "postal_code": "75001",
  "address": "1 Rue de Rivoli",
  "latitude": 48.8566,
  "longitude": 2.3522,
  "date": "2026-06-15",
  "time": "14:00:00",
  "category": "Festival Médiéval",
  "is_free": false,
  "ticket_price": 25.00,
  "ticket_quantity": 500,
  "image_event": "event_image.jpg"
}
```

#### Mettre à jour un événement (authentification requise)

```json
POST /Api/eventsApi.php
{
  "action": "update",
  "token": "user_token_here",
  "id": 1,
  "title": "Nouveau titre",
  "description": "Nouvelle description"
  // ... autres champs
}
```

#### Demander modification date/heure (authentification requise)

```json
POST /Api/eventsApi.php
{
  "action": "requestModification",
  "token": "user_token_here",
  "event_id": 1,
  "new_date": "2026-07-20",
  "new_time": "16:00:00"
}
```

#### Demander suppression (authentification requise)

```json
POST /Api/eventsApi.php
{
  "action": "requestDeletion",
  "token": "user_token_here",
  "event_id": 1,
  "deletion_message": "Raison de la suppression"
}
```

---

### Réservations (`Api/reservationsApi.php`)

Toutes les routes de réservation nécessitent l'authentification.

#### Créer une réservation

```json
POST /Api/reservationsApi.php
{
  "action": "create",
  "token": "user_token_here",
  "event_id": 1,
  "quantity": 2
}
```

#### Récupérer mes réservations

```json
POST /Api/reservationsApi.php
{
  "action": "getMyReservations",
  "token": "user_token_here"
}
```

#### Annuler une réservation

```json
POST /Api/reservationsApi.php
{
  "action": "cancel",
  "token": "user_token_here",
  "reservation_id": 1
}
```

---

### Favoris (`Api/favoritesApi.php`)

#### Ajouter aux favoris (authentification requise)

```json
POST /Api/favoritesApi.php
{
  "action": "add",
  "token": "user_token_here",
  "event_id": 1
}
```

#### Retirer des favoris (authentification requise)

```json
POST /Api/favoritesApi.php
{
  "action": "remove",
  "token": "user_token_here",
  "event_id": 1
}
```

#### Récupérer mes favoris (authentification requise)

```json
POST /Api/favoritesApi.php
{
  "action": "getByUser",
  "token": "user_token_here"
}
```

---

### Admin (`Api/adminApi.php`)

**Nécessite un token admin pour toutes les actions.**

#### Gestion des utilisateurs

```json
// Liste tous les utilisateurs
POST /Api/adminApi.php
{
  "action": "getAllUsers",
  "token": "admin_token_here"
}

// Promouvoir en organisateur
POST /Api/adminApi.php
{
  "action": "promoteToOrganizer",
  "token": "admin_token_here",
  "user_id": 5
}

// Promouvoir en modérateur
POST /Api/adminApi.php
{
  "action": "promoteToModerator",
  "token": "admin_token_here",
  "user_id": 5
}

// Supprimer un utilisateur
POST /Api/adminApi.php
{
  "action": "deleteUser",
  "token": "admin_token_here",
  "user_id": 5
}
```

#### Gestion des événements

```json
// Liste tous les événements (y compris pending)
POST /Api/adminApi.php
{
  "action": "getAllEvents",
  "token": "admin_token_here"
}

// Approuver un événement
POST /Api/adminApi.php
{
  "action": "approveEvent",
  "token": "admin_token_here",
  "event_id": 1
}

// Rejeter un événement
POST /Api/adminApi.php
{
  "action": "rejectEvent",
  "token": "admin_token_here",
  "event_id": 1
}

// Supprimer un événement
POST /Api/adminApi.php
{
  "action": "deleteEvent",
  "token": "admin_token_here",
  "event_id": 1
}
```

#### Gestion des modifications d'événements

```json
// Liste les modifications en attente
POST /Api/adminApi.php
{
  "action": "getPendingModifications",
  "token": "admin_token_here"
}

// Approuver une modification
POST /Api/adminApi.php
{
  "action": "approveModification",
  "token": "admin_token_here",
  "modification_id": 1
}

// Rejeter une modification
POST /Api/adminApi.php
{
  "action": "rejectModification",
  "token": "admin_token_here",
  "modification_id": 1,
  "rejection_reason": "Raison du rejet"
}
```

#### Gestion des demandes de suppression

```json
// Liste les demandes de suppression
POST /Api/adminApi.php
{
  "action": "getDeletionRequests",
  "token": "admin_token_here"
}

// Approuver une suppression
POST /Api/adminApi.php
{
  "action": "approveDeletion",
  "token": "admin_token_here",
  "event_id": 1
}

// Rejeter une suppression
POST /Api/adminApi.php
{
  "action": "rejectDeletion",
  "token": "admin_token_here",
  "event_id": 1
}
```

---

### Images (`Api/imageApi.php` et `Api/uploadImageApi.php`)

#### Récupérer une image

```
GET /Api/imageApi.php?name=event_image.jpg
```

#### Uploader une image (authentification requise)

```
POST /Api/uploadImageApi.php
Content-Type: multipart/form-data

FormData:
- image: [fichier image]
```

---

### Itinéraires (`Api/routeApi.php`)

#### Calculer un itinéraire

```json
POST /Api/routeApi.php
{
  "action": "getRoute",
  "startLat": 48.8566,
  "startLng": 2.3522,
  "endLat": 48.8606,
  "endLng": 2.3376
}
```

## Architecture

Le backend suit une architecture en couches :

1. **API Files** : Points d'entrée HTTP, gestion des headers CORS, routing
2. **Validators** : Validation des données entrantes
3. **Services** : Logique métier, transformation des données
4. **Repositories** : Accès à la base de données via PDO
5. **Models** : Représentation des entités
6. **DTOs** : Objets de transfert pour l'API (sans données sensibles)

## Sécurité

- Requêtes préparées pour toutes les interactions avec la base de données
- Hash des mots de passe avec `password_hash()`
- Exclusion des données sensibles dans les DTOs
- Headers CORS configurés
- Gestion des tokens d'authentification

## Logs

Les logs sont sauvegardés dans le dossier `logs/` :

- `app.log` : Logs généraux
- `error.log` : Logs d'erreurs

## Conventions

- **Namespaces** : PSR-4 avec préfixe `App\`
- **Typage** : Strict sur tous les paramètres et retours
- **Nommage** : camelCase pour les fichiers API, PascalCase pour les classes
- **Retours Services** : Format `['success' => bool, 'message' => string, 'data' => mixed]`
