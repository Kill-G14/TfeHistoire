# Back Office Admin - MemoriaEventia

## Vue d'ensemble

Le back office permet aux administrateurs et modérateurs de gérer les événements et les utilisateurs de la plateforme.

## 📁 Structure

```
AdminOffice/
├── pages/
│   ├── login.html              # Page de connexion
│   ├── dashboard.html          # Dashboard principal
│   ├── events.html             # Gestion des événements
│   └── users.html              # Gestion des utilisateurs
├── assets/
│   ├── css/
│   │   └── custom.css          # Styles personnalisés
│   ├── js/
│   │   ├── managers/
│   │   │   ├── AuthManager.js
│   │   │   ├── EventManager.js
│   │   │   └── UserManager.js
│   │   ├── pages/
│   │   │   ├── login.js
│   │   │   ├── dashboard.js
│   │   │   ├── events.js
│   │   │   └── users.js
│   │   └── utils/
│   │       ├── auth.js
│   │       └── helpers.js
│   └── images/
└── README.md
```

## Pages disponibles

### 1. Login (`login.html`)

Page de connexion réservée aux administrateurs et modérateurs.

**Accès** : Seuls les utilisateurs avec `is_admin = true` ou `is_moderator = true` peuvent se connecter.

**Fonctionnalités** :

- Connexion avec email et mot de passe
- Option "Se souvenir de moi" (stocke le token dans localStorage)
- Redirection automatique vers dashboard si déjà connecté

### 2. Dashboard (`dashboard.html`)

Page d'accueil du back office avec statistiques globales.

**Statistiques affichées** :

- Nombre total d'événements
- Nombre d'événements en attente
- Nombre d'événements approuvés
- Nombre total d'utilisateurs

**Actions rapides** :

- Bouton vers la gestion des événements
- Bouton vers la gestion des utilisateurs

### 3. Gestion des Événements (`events.html`)

Page de gestion complète des événements avec 3 onglets :

#### Onglet "En Attente"

Liste des événements en attente de modération (`is_pending = true`).

**Actions disponibles** :

- ✅ **Approuver** : Valide l'événement (`is_approved = true`, `is_pending = false`)
- ❌ **Rejeter** : Refuse l'événement (`is_rejected = true`, `is_pending = false`)
- 🗑️ **Supprimer** : Supprime définitivement l'événement

#### Onglet "Approuvés"

Liste des événements déjà approuvés (`is_approved = true`).

**Actions disponibles** :

- 🗑️ **Supprimer** : Supprime l'événement

#### Onglet "Tous"

Liste complète de tous les événements avec leur statut.

**Statuts possibles** :

- 🟡 En attente
- 🟢 Approuvé
- 🔴 Rejeté

### 4. Gestion des Utilisateurs (`users.html`)

Page de gestion des utilisateurs et de leurs droits.

**Informations affichées** :

- ID, Nom, Email
- Rôles (Admin, Organisateur, Modérateur)
- Date de création

**Actions disponibles** :

- ✏️ **Modifier les droits** : Ouvre une modal pour gérer les rôles
- 🗑️ **Supprimer** : Supprime l'utilisateur (avec protection du dernier admin)

#### Rôles disponibles

- 🛡️ **Administrateur** (`is_admin`)
  - Accès complet au back office
  - Peut gérer tous les événements
  - Peut gérer tous les utilisateurs

- 📅 **Organisateur** (`is_organizer`)
  - Peut créer et gérer ses propres événements
  - Accès restreint au back office

- 🛡️ **Modérateur** (`is_moderator`)
  - Peut modérer les contenus
  - Accès partiel au back office

## Structure Technique

### Backend

#### API Admin Unifiée (`adminApi.php`)

Toutes les actions administratives passent par une seule API avec un système de **ressources** et **actions**.

**Format de requête** :

```json
{
  "resource": "events|users",
  "action": "action_name",
  "token": "admin_token",
  ...autres paramètres
}
```

**Sécurité** :

- Vérification obligatoire du token
- Vérification des droits admin/modérateur
- Certaines actions nécessitent le rôle admin uniquement

#### Ressource : `events`

Actions disponibles :

- `getAll` : Récupère tous les événements (admin/modérateur)
- `getPending` : Récupère les événements en attente (admin/modérateur)
- `approve` : Approuve un événement (admin uniquement)
- `reject` : Rejette un événement (admin uniquement)
- `delete` : Supprime un événement (admin uniquement)

#### Ressource : `users`

Actions disponibles (admin uniquement) :

- `getAll` : Récupère tous les utilisateurs
- `getById` : Récupère un utilisateur par ID
- `updateRoles` : Met à jour les droits d'un utilisateur
- `delete` : Supprime un utilisateur

### Frontend

#### Managers (`assets/js/managers/`)

- **EventManager.js** : Gestion des appels API pour les événements
- **UserManager.js** : Gestion des appels API pour les utilisateurs
- **AuthManager.js** : Gestion de l'authentification

#### Pages (`assets/js/pages/`)

- **login.js** : Logique de connexion
- **dashboard.js** : Logique du dashboard
- **events.js** : Logique de gestion des événements
- **users.js** : Logique de gestion des utilisateurs

#### Utilitaires (`assets/js/utils/`)

- **helpers.js** : Fonctions utilitaires (formatage dates, toasts, storage)

## Sécurité

### Vérifications Backend

Toutes les actions sensibles vérifient :

1. Présence du token
2. Validité du token
3. Droits administrateur/modérateur

### Protection

- Impossible de supprimer le dernier administrateur
- Token stocké en localStorage ou sessionStorage
- Vérification des droits à chaque requête

## Utilisation

### Connexion

1. Accéder à `AdminOffice/pages/login.html`
2. Se connecter avec les identifiants admin
3. Redirection automatique vers le dashboard

### Modération des événements

1. Aller dans "Événements"
2. Consulter l'onglet "En Attente"
3. Approuver ou rejeter les événements en attente
4. Possibilité de supprimer définitivement un événement

### Gestion des utilisateurs

1. Aller dans "Utilisateurs"
2. Voir la liste complète des utilisateurs
3. Modifier les droits via le bouton "Modifier droits"
4. Supprimer un utilisateur si nécessaire

## Design

Le back office utilise **AdminLTE 3.2** pour une interface moderne et responsive :

- Sidebar de navigation
- Statistiques avec des cards colorées
- Tableaux pour les listes
- Modals pour les actions
- Toasts pour les notifications

## Technologies

- **Frontend** : JavaScript ES6+, Bootstrap 4, AdminLTE 3.2
- **Backend** : PHP 8+, PDO, Architecture MVC
- **Base de données** : MySQL
