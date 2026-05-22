# MemoriaEventia 🎭

---

## 🎯 Description

**MemoriaEventia** (du latin _Memoria_ = mémoire, _Eventia_ = événements) est une Single Page Application moderne qui permet de :

- 🗺️ Découvrir des événements historiques sur une **carte interactive**
- 🎟️ **Réserver gratuitement** des places pour des événements
- ✨ **Créer et gérer** ses propres événements historiques
- ⭐ Sauvegarder ses événements favoris
- 📅 Visualiser les événements dans un **calendrier**

---

## 📋 Fonctionnalités

### 👤 Utilisateurs

- **Réservation gratuite** d'événements avec gestion de places
- **Système de favoris** pour sauvegarder les événements intéressants
- **Visualisation géographique** sur carte interactive (Leaflet + OpenStreetMap)
- **Calendrier** avec filtres par date, catégorie et pays
- **Recherche avancée** avec filtres multiples
- **Gestion du profil** avec historique des réservations
- **Annulation de réservations** avec confirmation par email

### 🎪 Organisateurs

- **Création d'événements** avec formulaire complet (titre, description, date, lieu, images)
- **Modification d'événements** (système de modération pour changements de date/heure)
- **Gestion des événements créés** (statut, participants)
- **Upload d'images** sécurisé (7 couches de validation)
- **Suppression d'événements** (soft delete avec modération)

### 👨‍💼 Administrateurs

- **Modération des événements** (validation, rejet, suppression)
- **Gestion des utilisateurs** (voir, bloquer, supprimer)
- **Validation des modifications** d'événements (date/heure)
- **Dashboard dédié** (AdminOffice) avec statistiques
- **Gestion des réservations** (vue globale)

### 🔧 Fonctionnalités transversales

- **Authentification** utilisateur
- **Emails automatiques** (confirmations, annulations, réinitialisation mot de passe)
- **State management** centralisé
- **Navigation SPA** sans rechargement
- **Responsive design** (Bootstrap 5)

---

## 🛠️ Technologies

### Front-end

| Technologie       | Version  | Usage                                     |
| ----------------- | -------- | ----------------------------------------- |
| **JavaScript**    | ES6+     | Vanilla JS, modules, async/await, classes |
| **Bootstrap**     | 5.x      | Framework CSS via CDN                     |
| **Leaflet**       | Dernière | Carte interactive                         |
| **OpenStreetMap** | API      | Données cartographiques (10,000 req/jour) |
| **History API**   | Native   | Navigation SPA sans rechargement          |

### Back-end

| Technologie       | Version  | Usage                                            |
| ----------------- | -------- | ------------------------------------------------ |
| **PHP**           | 8.3.6    | Backend natif (sans framework)                   |
| **MySQL/MariaDB** | Dernière | Base de données relationnelle                    |
| **PDO**           | Native   | Abstraction base de données (requêtes préparées) |
| **SendGrid**      | API      | Service d'envoi d'emails (100/jour gratuit)      |
| **TCPDF**         | Composer | Génération de billets PDF (en cours)             |
| **Composer**      | Dernière | Gestionnaire de dépendances PHP                  |

### Architecture

- **Pattern Repository** : Séparation données / logique métier
- **Service Layer** : Logique métier centralisée
- **Single Page Application** : Navigation sans rechargement de page
- **API REST** : Communication front-end / back-end

---

## 📁 Structure du projet

```
TfeHistoire/
├── index.html                          # Point d'entrée SPA (utilisateurs)
├── README.md                           # Documentation (ce fichier)
├── DOCUMENTATION_TECHNIQUE.md          # Documentation technique complète
│
├── AdminOffice/                        # Interface administrateur
│   ├── index.html                      # Point d'entrée admin
│   ├── pages/                          # Pages admin
│   │   ├── dashboard.html              # Tableau de bord
│   │   ├── events.html                 # Gestion événements
│   │   ├── users.html                  # Gestion utilisateurs
│   │   └── login.html                  # Connexion admin
│   └── assets/
│       ├── css/custom.css              # Styles admin
│       └── js/
│           ├── managers/               # Managers API admin
│           ├── pages/                  # Scripts pages admin
│           └── utils/                  # Utilitaires admin
│
├── assets/                             # Ressources front-end
│   ├── components/                     # Templates HTML
│   │   ├── header.html                 # En-tête
│   │   ├── footer.html                 # Pied de page
│   │   ├── eventCard.html              # Carte événement
│   │   ├── eventDetail.html            # Détail événement
│   │   ├── loginModal.html             # Modal connexion
│   │   ├── reservationModal.html       # Modal réservation
│   │   ├── cancelReservationModal.html # Modal annulation
│   │   ├── forgotPasswordModal.html    # Modal oubli MDP
│   │   └── resetPasswordModal.html     # Modal réinitialisation MDP
│   │
│   ├── templates/views/                # Templates des vues
│   │   ├── home.html                   # Accueil
│   │   ├── calendar.html               # Calendrier
│   │   ├── createEvent.html            # Création événement
│   │   ├── profile.html                # Profil utilisateur
│   │   ├── map.html                    # Carte interactive
│   │   ├── about.html                  # À propos
│   │   ├── terms.html                  # CGU
│   │   ├── privacy.html                # Politique de confidentialité
│   │   └── faq.html                    # FAQ
│   │
│   ├── css/
│   │   └── custom.css                  # Styles personnalisés + animations SPA
│   │
│   ├── images/                         # Images statiques
│   │
│   └── js/
│       ├── app.js                      # Point d'entrée application
│       ├── router.js                   # Routeur SPA (History API)
│       ├── config.js                   # Configuration (API_BASE_URL)
│       │
│       ├── components/                 # Composants JavaScript
│       │   ├── header.js               # Gestion header
│       │   ├── footer.js               # Gestion footer
│       │   ├── eventCard.js            # Gestion cartes événement
│       │   ├── eventDetail.js          # Gestion détail événement
│       │   ├── loginModal.js           # Gestion modal connexion
│       │   ├── reservationModal.js     # Gestion modal réservation
│       │   ├── cancelReservationModal.js
│       │   ├── forgotPasswordModal.js
│       │   └── resetPasswordModal.js
│       │
│       ├── views/                      # Vues SPA (mount/unmount)
│       │   ├── home.js                 # Vue accueil
│       │   ├── calendar.js             # Vue calendrier
│       │   ├── createEvent.js          # Vue création événement
│       │   ├── profile.js              # Vue profil
│       │   ├── map.js                  # Vue carte (Leaflet)
│       │   ├── about.js                # Vue à propos
│       │   ├── terms.js                # Vue CGU
│       │   ├── privacy.js              # Vue confidentialité
│       │   └── faq.js                  # Vue FAQ
│       │
│       ├── managers/                   # Couche communication API
│       │   ├── AuthManager.js          # API authentification
│       │   ├── EventManager.js         # API événements
│       │   ├── FavoriteManager.js      # API favoris
│       │   └── ReservationManager.js   # API réservations
│       │
│       ├── store/
│       │   └── appState.js             # State management (Observer pattern)
│       │
│       ├── utils/                      # Utilitaires
│       │   ├── auth.js                 # Gestion authentification
│       │   ├── storage.js              # Gestion localStorage
│       │   ├── helpers.js              # Fonctions utilitaires
│       │   ├── filters.js              # Système de filtres événements
│       │   ├── countries.js            # Liste pays européens
│       │   └── migrateLocalStorage.js  # (OBSOLÈTE - supprimé)
│       │
│       └── validators/                 # Validation front-end
│           ├── authValidator.js        # Validation auth (email, MDP)
│           ├── formValidator.js        # Validation formulaires
│           └── imageValidator.js       # Validation images
│
└── BackEnd/                            # Backend PHP
    ├── composer.json                   # Dépendances PHP
    ├── composer.local.json             # Config locale Composer
    ├── database.sql                    # Schéma complet base de données
    ├── database_production.sql         # Dump production
    │
    ├── Api/                            # Points d'entrée API
    │   ├── authApi.php                 # Authentification
    │   ├── eventsApi.php               # Événements CRUD
    │   ├── favoritesApi.php            # Favoris
    │   ├── reservationsApi.php         # Réservations
    │   ├── adminApi.php                # Administration
    │   ├── imageApi.php                # Récupération images
    │   ├── uploadImageApi.php          # Upload images sécurisé
    │   ├── routeApi.php                # Calcul itinéraires
    │   └── debug.php                   # Debug (dev only)
    │
    ├── Src/
    │   ├── Models/                     # Modèles de données
    │   │   ├── User.php                # Modèle utilisateur
    │   │   ├── Event.php               # Modèle événement
    │   │   ├── Reservation.php         # Modèle réservation
    │   │   ├── Favorite.php            # Modèle favori
    │   │   ├── EventModification.php   # Modèle modification événement
    │   │   ├── PasswordReset.php       # Modèle réinitialisation MDP
    │   │   └── ModelsDTO/              # Data Transfer Objects
    │   │
    │   ├── Repositories/               # Couche accès données (SQL)
    │   │   ├── EventRepository.php     # Requêtes événements
    │   │   ├── EventModificationRepository.php
    │   │   ├── ReservationRepository.php
    │   │   ├── FavoriteRepository.php
    │   │   ├── SessionRepository.php   # Gestion tokens
    │   │   ├── PasswordResetRepository.php
    │   │   └── (...)                   # Autres repositories
    │   │
    │   ├── Services/                   # Logique métier
    │   │   ├── EventService.php        # Logique événements
    │   │   ├── AuthService.php         # Logique authentification
    │   │   ├── ReservationService.php  # Logique réservations
    │   │   ├── SessionService.php      # Gestion sessions/tokens
    │   │   ├── EmailService.php        # Envoi emails (SendGrid)
    │   │   └── (...)                   # Autres services
    │   │
    │   ├── Utils/                      # Utilitaires backend
    │   │   ├── Database.php            # Connexion PDO singleton
    │   │   ├── RateLimiter.php         # Protection brute-force
    │   │   ├── Validator.php           # Validation backend
    │   │   └── (...)                   # Autres utilitaires
    │   │
    │   ├── Validators/                 # Validation spécifique
    │   │
    │   └── img/                        # Images backend
    │
    ├── storage/                        # Stockage fichiers
    │   ├── images/                     # Images uploadées
    │   └── tickets/                    # Billets PDF (futur)
    │
    ├── logs/                           # Logs application
    │   └── uploads.log                 # Log uploads images
    │
    └── vendor/                         # Dépendances Composer
        ├── autoload.php                # Autoloader
        ├── sendgrid/                   # SendGrid SDK
        ├── starkbank/                  # EcDSA (signatures)
        ├── tecnickcom/                 # TCPDF (PDFs)
        └── (...)                       # Autres dépendances
```

---

## Utilisation

### Navigation (SPA)

L'application utilise un **routeur custom** basé sur l'History API :

- Navigation **sans rechargement** de page
- URLs propres et lisibles (`/calendar`, `/profile`, etc.)
- Boutons **précédent/suivant** du navigateur fonctionnels
- Transitions fluides entre les vues

### Routes disponibles

| Route           | Description                            | Auth requise |
| --------------- | -------------------------------------- | ------------ |
| `/`             | Page d'accueil avec liste d'événements | Non          |
| `/calendar`     | Calendrier des événements              | Non          |
| `/map`          | Carte interactive (Leaflet)            | Non          |
| `/create-event` | Création d'événement                   | ✅ Oui       |
| `/profile`      | Profil utilisateur + réservations      | ✅ Oui       |
| `/about`        | À propos du projet                     | Non          |
| `/terms`        | Conditions générales d'utilisation     | Non          |
| `/privacy`      | Politique de confidentialité           | Non          |
| `/faq`          | Foire aux questions                    | Non          |

### Interface administrateur

Accès via `/AdminOffice/`

- Dashboard avec statistiques
- Modération des événements
- Gestion des utilisateurs
- Validation des modifications d'événements

---

## 🏗️ Architecture

### Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────┐
│                        FRONT-END (SPA)                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Views (home.js, calendar.js, profile.js, map.js)   │   │
│  │                 ↕ mount/unmount                       │   │
│  │  Router (History API) + appState (Observer pattern)  │   │
│  └──────────────────────────────────────────────────────┘   │
│                           ↕ fetch()                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │     Managers (EventManager, AuthManager, etc.)       │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              ↕ HTTP
┌─────────────────────────────────────────────────────────────┐
│                        BACK-END (PHP)                        │
│  ┌──────────────────────────────────────────────────────┐   │
│  │     API Endpoints (eventsApi.php, authApi.php)      │   │
│  └──────────────────────────────────────────────────────┘   │
│                           ↕                                  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │     Services (EventService, AuthService, etc.)       │   │
│  │             (Logique métier)                         │   │
│  └──────────────────────────────────────────────────────┘   │
│                           ↕                                  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Repositories (EventRepository, UserRepository...)   │   │
│  │             (Requêtes SQL)                           │   │
│  └──────────────────────────────────────────────────────┘   │
│                           ↕ PDO                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              MySQL/MariaDB (8 tables)                │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Patterns architecturaux

#### 1. Repository Pattern

Séparation claire entre accès données et logique métier :

- **Repositories** : Toutes les requêtes SQL (SELECT, INSERT, UPDATE, DELETE)
- **Services** : Logique métier, validations, orchestration
- **Avantages** : Testabilité, maintenabilité, sécurité centralisée

#### 2. State Management

Gestion d'état centralisée permettant de synchroniser les données entre les différents composants de l'application.

#### 3. Single Page Application (SPA)

Routeur personnalisé permettant la navigation sans rechargement de page, avec gestion du cycle de vie des vues (montage/démontage).

#### 4. Component System

Système de composants réutilisables basé sur des templates HTML, permettant une structure modulaire et maintenable.

### Base de données (8 tables)

- **users** : Informations des utilisateurs et rôles
- **sessions** : Gestion des sessions utilisateur
- **events** : Données des événements historiques
- **event_modifications** : Suivi des modifications d'événements
- **reservations** : Réservations des utilisateurs
- **favorites** : Événements favoris des utilisateurs
- **password_resets** : Gestion de la réinitialisation des mots de passe
- **rate_limiter** : Protection contre les abus

### Flux d'authentification

1. L'utilisateur saisit ses identifiants
2. Validation des données côté client
3. Envoi sécurisé au serveur
4. Vérification des identifiants
5. Création d'une session
6. Stockage de la session
7. Authentification réussie

### Workflow modification d'événement

1. L'organisateur demande une modification de date/heure
2. La demande est enregistrée en attente de validation
3. L'administrateur est notifié
4. L'administrateur valide ou rejette la modification
5. Les participants sont notifiés de la décision
6. L'événement est mis à jour si validé

- Lazy loading des vues
- Support des paramètres dynamiques (`:id`)
- Mise à jour des métadonnées (title, description)

### Views (Vues)

Chaque vue suit le pattern :

- Export `meta` : métadonnées (title, description)
- Export `mount(container, params)` : montage de la vue
- Export `unmount()` : démontage et nettoyage
- Chargement des données via Managers
- Gestion locale des event listeners

### Components (Composants)

Composants réutilisables :

- **Persistants** : navbar, footer (chargés une seule fois)
- **Dynamiques** : cards, modals (rechargés à la demande)
- Chargement de templates HTML
- Gestion d'événements
- Abonnement au state

### State Management (Store)

- **appState.js** : Store centralisé
- Pattern subscribe/notify
- État global : user, cart, events, favorites
- Synchronisation automatique des composants
- Persistance dans localStorage

### Managers

- Centralisation des appels API
- Retour standardisé : `{ success, message, data }`
- Gestion des erreurs
- Un manager par domaine métier

### Utilitaires

- **auth.js** : Gestion de l'authentification
- **storage.js** : Abstraction du localStorage
- **helpers.js** : Fonctions utilitaires (formatage, filtres, toasts)

## 📱 Responsive Design

L'application est entièrement responsive grâce à Bootstrap :

- Mobile first
- Breakpoints adaptés
- Grille flexible
- Navigation mobile adaptée

## 🌐 Compatibilité navigateurs

- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

Nécessite un navigateur compatible :

- ES6+ Modules
- History API
- Async/await
- DOMParser

## 📝 Conventions de code

- **Nommage** : camelCase pour variables/fonctions, PascalCase pour classes
- **Indentation** : 2 espaces
- **Modules** : ES6 import/export + import() dynamique
- **Async** : async/await (pas de .then())
- **DOM** : querySelector/getElementById
- **Navigation** : History API + data-link
- **URLs** : Sans .html (`/products`, `/product/123`)

## 🔄 Cycle de vie d'une vue

1. **Navigation** : Utilisateur clique sur un lien avec `data-link`
2. **Unmount** : La vue précédente est démontée (nettoyage)
3. **Lazy load** : La nouvelle vue est chargée via `import()`
4. **Mount** : La nouvelle vue est montée (template + données + événements)
5. **Update** : Mise à jour des métadonnées (title, description)

---

## 📚 Documentation

### Documentation technique complète

Pour une analyse approfondie du projet, consultez [DOCUMENTATION_TECHNIQUE.md](DOCUMENTATION_TECHNIQUE.md) :

- Architecture détaillée (front-end + back-end)
- Analyse complète de la base de données (8 tables, 23 indexes)
- Flux de sécurité et authentification
- Système de composants et state management
- Performance et optimisation
- Statistiques du code (~15,500 lignes)

### Fichiers de référence

- **README.md** (ce fichier) : Vue d'ensemble et guide de démarrage
- **DOCUMENTATION_TECHNIQUE.md** : Documentation technique approfondie pour le TFE
- **BackEnd/database.sql** : Schéma complet de la base de données
- **assets/js/utils/FILTERS_DOCUMENTATION.md** : Documentation du système de filtres
- **assets/js/views/README.md** : Guide de création de vues SPA

---

## 📊 Statistiques du projet

- **~91 fichiers** JavaScript/PHP (hors vendors)
- **~15,500 lignes de code** (estimé)
- **8 tables** en base de données
- **23 indexes** (12 explicites + 11 automatiques)
- **9 routes** front-end (SPA)
- **7 API endpoints** backend
- **4 Managers** (communication API)
- **7 Services** (logique métier)
- **6 Repositories** (accès données)

---

## ✨ Points forts du projet

### 1. Architecture modulaire et maintenable

- **Repository Pattern** pour séparer données et logique
- **Service Layer** pour centraliser la logique métier
- Code organisé et facilement extensible

### 2. Système robuste

- Validation des données utilisateur
- Protection de l'application
- Gestion sécurisée des données

### 3. Workflow innovant

- Système de validation des modifications d'événements
- Traçabilité des changements
- Notifications automatiques par email

### 4. Expérience utilisateur fluide

- **SPA** sans rechargement de page
- **State management** centralisé
- **Transitions** CSS fluides
- **Responsive** design complet

### 5. Performance optimisée

- Chargement à la demande des vues
- Base de données optimisée
- Système de cache pour les favoris
- Requêtes optimisées

### 6. Solutions personnalisées

- Routeur SPA développé sur mesure
- Système de gestion d'état
- Système de composants réutilisables
- Développement sans framework pour une meilleure compréhension

---

## 🚀 Améliorations futures

### Court terme

- ✅ Système de favoris (TERMINÉ)
- ✅ Carte interactive Leaflet (TERMINÉ)
- 🔄 Génération de billets PDF (TCPDF - en cours)
- 🔄 Système de notation des événements
- 🔄 Commentaires et avis utilisateurs

### Moyen terme

- Notifications push (Progressive Web App)
- Système de recommandations personnalisées
- Export calendrier (.ics pour Google Calendar, Outlook)
- Partage sur réseaux sociaux
- Multi-langues (i18n)

### Long terme

- Application mobile (React Native / Flutter)
- Système de paiement (Stripe) pour événements payants
- API publique pour développeurs tiers
- Webhooks pour intégrations
- Analytics avancées (tableau de bord organisateur)

---

## 🎓 Contexte académique

### Projet de Travail de Fin d'Études (TFE)

**Institution** : IFAPME Tournai  
**Formation** : Développeur web front-end  
**Année** : 2026  
**Présentation** : 15 minutes présentation + 15 minutes questions

### Objectifs pédagogiques

- Maîtrise du développement **front-end** (JavaScript ES6+, SPA, state management)
- Compréhension de l'architecture **back-end** (PHP, API, base de données)
- Implémentation de la **sécurité** web (authentification, validation, protection)
- Gestion de **projet** (Git, planning, documentation)
- **Design** et expérience utilisateur (responsive, accessibilité)

### Compétences démontrées

1. **Architecture** : Patterns Repository, Service Layer, Observer, SPA
2. **Fiabilité** : Application robuste et stable
3. **Performance** : Optimisation des requêtes et chargement à la demande
4. **UX/UI** : Design cohérent, responsive, transitions fluides, carte interactive
5. **Autonomie** : Solutions custom (router, state), recherche documentation
6. **Innovation** : Workflow validation modifications, emails automatiques

### Critères de notation (400 points)

- **Qualité formelle** (80 pts) : Orthographe, structure, présentation
- **Qualité du contenu** (120 pts) : Profondeur analyse, justifications techniques
- **Maîtrise de la réalisation** (80 pts) : Code qualité, architecture
- **Présentation orale** (120 pts) : Clarté, réponses aux questions, démonstration

---

## 🤝 Contribution

Respecter les conventions définies dans `AGENTS.md` (architecture SPA) pour toute modification.

## 🆕 Changements SPA vs Multi-pages

- ✅ Un seul fichier HTML au lieu de plusieurs
- ✅ Routeur JavaScript pour la navigation
- ✅ Lazy loading des vues
- ✅ State management centralisé
- ✅ Transitions fluides sans rechargement
- ✅ URLs propres sans .html
- ✅ Support boutons précédent/suivant
- ✅ Métadonnées dynamiques

---

## 👤 Auteur

**Projet développé dans le cadre du TFE IFAPME Tournai 2026**

---

## 📄 Licence

Projet éducatif - Libre d'utilisation

## 🎓 Crédits

Images : Unsplash
Icons : Bootstrap Icons
Framework : Bootstrap 5.3 
