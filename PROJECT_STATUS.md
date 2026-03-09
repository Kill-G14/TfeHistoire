# État du projet - TfeHistoire

**Date de vérification :** 09/03/2026
**Architecture :** SPA (Single Page Application) - En transition

---

## 🔄 MIGRATION VERS SPA - EN COURS

### ⚠️ TRANSITION ARCHITECTURE

**État actuel :** Multi-pages (multiple fichiers HTML)
**État cible :** Single Page Application (SPA)
**Documentation :** Mise à jour dans AGENTS.md

### 📋 Changements requis pour passer en SPA

#### 1. **Structure des fichiers** ⭐⭐⭐ PRIORITÉ HAUTE

**À faire :**

- ✅ Créer un seul `index.html` à la racine de FrontEnd/
- ✅ Créer `assets/js/app.js` (point d'entrée principal)
- ✅ Créer `assets/js/router.js` (routeur SPA avec History API)
- ✅ Renommer `assets/js/pages/` en `assets/js/views/`
- ✅ Créer `assets/js/store/appState.js` (state management)
- ✅ Déplacer templates HTML dans `assets/templates/views/`
- ⚠️ Supprimer les anciens fichiers HTML de `pages/`

**Fichiers à créer :**

```
FrontEnd/
├── index.html                    # SEUL fichier HTML
├── assets/
│   ├── js/
│   │   ├── app.js               # Point d'entrée + init
│   │   ├── router.js            # Routeur SPA
│   │   ├── views/               # Ex-pages (renommé)
│   │   │   ├── home.js
│   │   │   ├── events.js
│   │   │   ├── eventDetail.js
│   │   │   ├── createEvent.js
│   │   │   ├── profile.js
│   │   │   ├── myOrders.js
│   │   │   ├── myTickets.js
│   │   │   └── map.js
│   │   ├── store/
│   │   │   └── appState.js      # State management
│   │   └── ...
│   └── templates/
│       └── views/
│           ├── home.html
│           ├── events.html
│           ├── eventDetail.html
│           └── ...
```

#### 2. **Adaptation des vues** ⭐⭐

**Chaque vue doit :**

- ✅ Exporter `meta` : `{ title, description }`
- ✅ Exporter `mount(container, params)` : montage de la vue
- ✅ Exporter `unmount()` : démontage et nettoyage
- ✅ Utiliser `appState` pour l'état global
- ✅ Nettoyer les event listeners dans `unmount()`

**Exemple de structure :**

```javascript
// views/home.js
export const meta = {
  title: "Accueil - EuroFêtes",
  description: "Découvrez les événements historiques",
};

export async function mount(container, params) {
  // Injection template + chargement données + événements
}

export async function unmount() {
  // Nettoyage
}

export default { mount, unmount, meta };
```

#### 3. **Navigation SPA** ⭐⭐⭐ PRIORITÉ HAUTE

**À faire :**

- ✅ Tous les liens internes doivent avoir `data-link`
- ✅ URLs sans `.html` : `/events`, `/event/123`
- ✅ Interception des clics dans le routeur
- ✅ Support History API (boutons précédent/suivant)
- ✅ Lazy loading des vues avec `import()`

**Exemple :**

```html
<!-- Avant (multi-pages) -->
<a href="/pages/events.html">Événements</a>

<!-- Après (SPA) -->
<a href="/events" data-link>Événements</a>
```

#### 4. **State Management** ⭐⭐

**À faire :**

- ✅ Créer `store/appState.js`
- ✅ Implémenter pattern subscribe/notify
- ✅ Gérer l'état global : `user`, `cart`, `events`, `favorites`
- ✅ Synchroniser composants automatiquement
- ✅ Persister dans localStorage

#### 5. **Composants persistants** ⭐

**À faire :**

- ✅ Navbar et footer chargés UNE SEULE FOIS dans `app.js`
- ✅ Mise à jour dynamique selon l'état (user connecté/déconnecté)
- ✅ Pas de réinjection à chaque changement de vue

#### 6. **Métadonnées dynamiques** ⭐

**À faire :**

- ✅ `<title id="pageTitle">` dans index.html
- ✅ `<meta id="pageDescription">` dans index.html
- ✅ Mise à jour automatique par le routeur

---

## ✅ BACKEND - COMPLET

### APIs disponibles (7/7)

| Fichier                | Status | Fonctionnalités                                   |
| ---------------------- | ------ | ------------------------------------------------- |
| `auth.php`             | ✅     | Login, Register, Logout, CheckToken               |
| `events.php`           | ✅     | CRUD événements, GetAll, GetById, GetByOrganizer  |
| `orders.php`           | ✅     | Créer commande, Liste commandes, Détails, Annuler |
| `tickets.php`          | ✅     | CRUD types de billets (organizers)                |
| `favorites.php`        | ✅     | Ajouter/Retirer/Lister favoris                    |
| `ticketsGenerated.php` | ✅     | Mes billets achetés, Détails billet               |
| `scanTicket.php`       | ✅     | Validation QR code (organizers)                   |

### Models (7/7)

- ✅ User
- ✅ Event
- ✅ Order
- ✅ OrderItem
- ✅ Ticket
- ✅ TicketGenerated
- ✅ Favorite

### DTOs (7/7)

- ✅ UserDTO
- ✅ EventDTO
- ✅ OrderDTO
- ✅ OrderItemDTO
- ✅ TicketDTO
- ✅ TicketGeneratedDTO
- ✅ FavoriteDTO

### Repositories (8/8)

- ✅ UserRepository
- ✅ EventRepository
- ✅ OrderRepository
- ✅ OrderItemRepository
- ✅ TicketRepository
- ✅ PurchasedTicketRepository
- ✅ FavoriteRepository
- ✅ SessionRepository

### Services (6/6)

- ✅ AuthService
- ✅ EventService
- ✅ OrderService (avec génération QR codes)
- ✅ TicketService
- ✅ FavoriteService
- ✅ SessionService

### Validators (4/4)

- ✅ UserValidator
- ✅ EventValidator
- ✅ OrderValidator
- ✅ TicketValidator

---

## ⚠️ CE QUI MANQUE

### 1. **Intégration Stripe** ⭐⭐⭐

**Priorité :** HAUTE

Le système de paiement avec Stripe n'est pas encore implémenté.

**À faire :**

- Créer un service `StripeService.php` dans `Src/Services/`
- Intégrer la librairie Stripe via Composer : `composer require stripe/stripe-php`
- Ajouter une action `initPayment` dans `orders.php` pour créer le paiement Stripe
- Créer un endpoint `webhook.php` pour recevoir les confirmations de paiement
- Appeler `OrderService->confirmPayment()` après confirmation du webhook

**Fichiers à créer :**

```
BackEnd/
├── Api/
│   └── webhook.php               # Webhook Stripe
└── Src/
    └── Services/
        └── StripeService.php     # Intégration Stripe API
```

---

### 2. **Envoi d'emails SendGrid** ⭐⭐⭐

**Priorité :** HAUTE

Le système d'envoi d'emails après achat n'est pas implémenté.

**À faire :**

- Créer un service `EmailService.php` dans `Src/Services/`
- Intégrer SendGrid via Composer : `composer require sendgrid/sendgrid`
- Créer des templates d'emails (confirmation, billets PDF)
- Appeler `EmailService` après génération des billets dans `OrderService`

**Fichiers à créer :**

```
BackEnd/
└── Src/
    ├── Services/
    │   └── EmailService.php      # Envoi d'emails SendGrid
    └── Templates/
        ├── email-confirmation.html
        └── email-ticket.html
```

---

### 3. **Génération PDF des billets** ✅

**Priorité :** MOYENNE

**✅ INTÉGRÉ - COMPLET**

- ✅ TCPDF intégré via Composer (version 6.11.2)
- ✅ Service `PdfService.php` créé avec toutes les règles de sécurité
- ✅ PDF générés avec QR codes (TCPDF natif)
- ✅ Stockage dans `BackEnd/storage/tickets/` (protégé par .htaccess)
- ✅ Action `downloadTicket` implémentée dans `ticketsGenerated.php`
- ✅ Vérification de propriété des billets (sécurité)
- ✅ Échappement de toutes les données utilisateur

**Fichiers créés :**

```
BackEnd/
├── storage/
│   ├── .htaccess                 # Protection du dossier
│   └── tickets/                  # Dossier pour les PDF générés
├── Src/
│   └── Services/
│       └── PdfService.php        # Génération de PDF sécurisée
└── TCPDF_INTEGRATION.md          # Documentation complète
```

---

### 4. **Géolocalisation et distance** ⭐⭐

**Priorité :** MOYENNE

Le calcul de distance entre l'utilisateur et les événements n'est pas implémenté.

**À faire :**

- Ajouter les champs `latitude` et `longitude` lors de la création d'événements
- Créer une méthode dans `EventService->getEventsNearUser($lat, $long, $radius)`
- Utiliser la formule Haversine pour calculer la distance
- Ajouter l'action `getNearbyEvents` dans `events.php`

**Modification :**

- `EventService.php` : ajouter méthode `getEventsNearUser()`
- `events.php` : ajouter action `getNearbyEvents`

---

### 5. **Upload d'images** ⭐

**Priorité :** BASSE

Actuellement les images d'événements sont des URLs externes.

**À faire :**

- Créer un service `FileUploadService.php`
- Créer un dossier `BackEnd/storage/images/events/`
- Ajouter une API `uploadImage.php` pour gérer les uploads
- Valider les types de fichiers (jpg, png, webp)
- Limiter la taille des fichiers (max 5MB)

**Fichiers à créer :**

```
BackEnd/
├── Api/
│   └── uploadImage.php           # Upload d'images
├── storage/
│   └── images/
│       └── events/               # Images d'événements
└── Src/
    └── Services/
        └── FileUploadService.php
```

---

### 6. **Frontend - Intégration des APIs** ⚠️

**Priorité :** HAUTE

Le frontend utilise encore des données mockées. Il faut le connecter aux vraies APIs.

**À faire :**

- Remplacer les données mockées dans `home.js` par des appels API
- Créer des pages pour :
  - Détails d'un événement avec réservation
  - Page "Mes commandes"
  - Page "Mes billets"
  - Page "Mes favoris"
- Intégrer le système de paiement Stripe côté frontend
- Créer une page de confirmation de paiement

**Pages à créer :**

```
FrontEnd/views/
├── eventDetail.js                # Détails événement
├── myOrders.js                   # Mes commandes
├── myTickets.js                  # Mes billets
├── myFavorites.js                # Mes favoris
└── paymentSuccess.js             # Confirmation paiement

FrontEnd/assets/templates/views/
├── eventDetail.html
├── myOrders.html
├── myTickets.html
├── myFavorites.html
└── paymentSuccess.html
```

---

### 7. **Google Maps API** ⭐

**Priorité :** BASSE

La page `map.html` existe mais n'est pas encore fonctionnelle.

**À faire :**

- Configurer Google Maps API avec une clé API
- Afficher les événements sur une carte
- Ajouter des marqueurs cliquables
- Afficher une infobulle avec les détails de l'événement

**Modification :**

- `FrontEnd/assets/js/pages/map.js` : intégrer Google Maps API

---

### 8. **AdminOffice** ❌ NON COMMENCÉ

**Priorité :** FUTURE

L'interface d'administration avec AdminLTE n'est pas encore créée.

**À faire :**

- Créer un dossier séparé `AdminOffice/`
- Intégrer AdminLTE
- Créer les pages de modération :
  - Validation des événements (pending → approved/rejected)
  - Gestion des utilisateurs
  - Statistiques
  - Gestion des signalements

Cette partie sera développée séparément du frontend public.

---

## 📊 RÉSUMÉ

### Backend API : 100% ✅

- Structure complète
- Tous les endpoints créés
- Architecture respectée (AGENTS.md)

### Intégrations externes : 25% ⚠️

- ❌ Stripe (paiement)
- ❌ SendGrid (emails)
- ✅ PDF (billets) - **INTÉGRÉ**
- ❌ Google Maps

### Frontend : 20% ⚠️

- Structure de base créée
- Données mockées
- Pas d'intégration avec le backend

---

## 🎯 PRIORITÉS DE DÉVELOPPEMENT

### Phase 1 - Critique (pour MVP)

1. ✅ APIs backend complètes
2. ❌ Intégration Stripe (paiement)
3. ❌ Envoi d'emails SendGrid
4. ✅ **Génération PDF des billets - INTÉGRÉ**
5. ❌ Connecter frontend aux APIs

### Phase 2 - Important

6. ❌ Géolocalisation et distance
7. ❌ Google Maps intégration
8. ❌ Upload d'images

### Phase 3 - Future

9. ❌ AdminOffice (modération)
10. ❌ Statistiques et analytics
11. ❌ Système de notation/avis

---

## 🔧 COMMANDES COMPOSER NÉCESSAIRES

```bash
# Se placer dans le dossier BackEnd
cd c:\wamp64\www\tfeHistoire\BackEnd

# Installer Stripe
composer require stripe/stripe-php

# Installer SendGrid
composer require sendgrid/sendgrid

# Installer TCPDF (génération PDF)
composer require tecnickcom/tcpdf
```

---

**Note :** Le backend est fonctionnel à 100% pour les opérations CRUD. Les intégrations tierces (Stripe, SendGrid, PDF) et le frontend sont les prochaines étapes critiques.
