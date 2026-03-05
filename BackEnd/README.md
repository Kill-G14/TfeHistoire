# Backend EuroFêtes Historiques

Backend PHP pour la plateforme d'événements historiques européens.

## Structure

```
BackEnd/
├── Api/                          # Points d'entrée HTTP
│   ├── auth.php                  # Authentification
│   ├── events.php                # Gestion des événements
│   ├── orders.php                # Gestion des commandes
│   ├── tickets.php               # Gestion des types de billets
│   ├── ticketsGenerated.php      # Billets achetés
│   ├── favorites.php             # Favoris utilisateurs
│   └── scanTicket.php            # Validation QR codes
│
├── Src/
│   ├── Models/                   # Entités métier
│   │   ├── User.php
│   │   ├── Event.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Ticket.php
│   │   ├── TicketGenerated.php
│   │   ├── Favorite.php
│   │   └── ModelsDTO/            # Data Transfer Objects
│   │       ├── UserDTO.php
│   │       ├── EventDTO.php
│   │       ├── OrderDTO.php
│   │       ├── OrderItemDTO.php
│   │       ├── TicketDTO.php
│   │       ├── TicketGeneratedDTO.php
│   │       └── FavoriteDTO.php
│   │
│   ├── Repositories/             # Accès base de données
│   │   ├── UserRepository.php
│   │   ├── EventRepository.php
│   │   ├── OrderRepository.php
│   │   ├── OrderItemRepository.php
│   │   ├── TicketRepository.php
│   │   ├── PurchasedTicketRepository.php
│   │   ├── FavoriteRepository.php
│   │   └── SessionRepository.php
│   │
│   ├── Services/                 # Logique métier
│   │   ├── AuthService.php
│   │   ├── EventService.php
│   │   ├── OrderService.php
│   │   ├── TicketService.php
│   │   ├── FavoriteService.php
│   │   └── SessionService.php
│   │
│   ├── Validators/               # Validation des données
│   │   ├── UserValidator.php
│   │   ├── EventValidator.php
│   │   ├── OrderValidator.php
│   │   └── TicketValidator.php
│   │
│   └── Utils/                    # Utilitaires transversaux
│       ├── Database.php          # Connexion PDO
│       ├── Logger.php            # Logs
│       └── Helpers.php           # Fonctions utilitaires
│
├── vendor/                       # Autoload Composer
├── database.sql                  # Script de création de la base
├── composer.json                 # Configuration autoload
└── .htaccess                     # Configuration Apache
```

## Installation

### 1. Configuration de la base de données

1. Créez la base de données en exécutant le fichier `database.sql`
2. Modifiez les paramètres de connexion dans `Src/Utils/Database.php` si nécessaire

```php
private const DB_HOST = 'localhost';
private const DB_NAME = 'eurofetes_db';
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

### Authentification (`Api/auth.php`)

#### Inscription

```json
POST /Api/auth.php
{
  "action": "register",
  "email": "user@example.com",
  "password": "password123",
  "name": "John Doe"
}
```

#### Connexion

```json
POST /Api/auth.php
{
  "action": "login",
  "email": "user@example.com",
  "password": "password123"
}
```

#### Récupérer l'utilisateur connecté

```json
POST /Api/auth.php
Headers: Authorization: Bearer {token}
{
  "action": "getCurrentUser"
}
```

### Événements (`Api/events.php`)

#### Récupérer tous les événements

```json
POST /Api/events.php
{
  "action": "getAll"
}
```

#### Récupérer un événement par ID

```json
POST /Api/events.php
{
  "action": "getById",
  "id": 1
}
```

#### Rechercher des événements

```json
POST /Api/events.php
{
  "action": "search",
  "search": "carnaval"
}
```

#### Créer un événement (authentification requise)

```json
POST /Api/events.php
Headers: Authorization: Bearer {token}
{
  "action": "create",
  "title": "Titre de l'événement",
  "description": "Description de l'événement",
  "country": "France",
  "city": "Paris",
  "postal_code": "75001",
  "address": "1 Rue de Rivoli",
  "date": "2026-06-15",
  "time": "14:00",
  "price": 25.00,
  "category": "Festival Médiéval",
  "available_tickets": 500,
  "image_url": "https://example.com/image.jpg"
}
```

#### Mettre à jour un événement (authentification requise)

```json
POST /Api/events.php
Headers: Authorization: Bearer {token}
{
  "action": "update",
  "id": 1,
  "title": "Nouveau titre",
  ...
}
```

#### Supprimer un événement (authentification requise)

```json
POST /Api/events.php
Headers: Authorization: Bearer {token}
{
  "action": "delete",
  "id": 1
}
```

### Commandes (`Api/orders.php`)

Toutes les routes de commande nécessitent l'authentification.

#### Récupérer mes commandes

```json
POST /Api/orders.php
Headers: Authorization: Bearer {token}
{
  "action": "getMyOrders",
  "token": "user_token_here"
}
```

#### Créer une commande

```json
POST /Api/orders.php
Headers: Authorization: Bearer {token}
{
  "action": "create",
  "token": "user_token_here",
  "items": [
    {
      "ticket_id": 1,
      "quantity": 2
    },
    {
      "ticket_id": 2,
      "quantity": 1
    }
  ]
}
```

#### Annuler une commande

```json
POST /Api/orders.php
Headers: Authorization: Bearer {token}
{
  "action": "cancel",
  "token": "user_token_here",
  "id": 1
}
```

### Billets (`Api/tickets.php`)

#### Récupérer les billets d'un événement (public)

```json
POST /Api/tickets.php
{
  "action": "getByEvent",
  "event_id": 1
}
```

#### Créer un type de billet (organizer)

```json
POST /Api/tickets.php
{
  "action": "create",
  "token": "organizer_token_here",
  "event_id": 1,
  "name": "Adult Ticket",
  "description": "Billet adulte",
  "price": 25.00,
  "quantity": 100,
  "start_sale_date": "2026-01-01 00:00:00",
  "end_sale_date": "2026-12-31 23:59:59"
}
```

### Favoris (`Api/favorites.php`)

#### Ajouter aux favoris

```json
POST /Api/favorites.php
{
  "action": "add",
  "token": "user_token_here",
  "event_id": 1
}
```

#### Retirer des favoris

```json
POST /Api/favorites.php
{
  "action": "remove",
  "token": "user_token_here",
  "event_id": 1
}
```

### Validation de billets (`Api/scanTicket.php`)

#### Valider un billet (organizer)

```json
POST /Api/scanTicket.php
{
  "action": "validate",
  "token": "organizer_token_here",
  "unique_code": "A1B2C3D4E5F6"
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
