# État du projet - TfeHistoire

**Date de vérification :** 05/03/2026

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

### 1. **Intégration Mollie** ⭐⭐⭐

**Priorité :** HAUTE

Le système de paiement avec Mollie n'est pas encore implémenté.

**À faire :**

- Créer un service `MollieService.php` dans `Src/Services/`
- Intégrer la librairie Mollie via Composer : `composer require mollie/mollie-api-php`
- Ajouter une action `initPayment` dans `orders.php` pour créer le paiement Mollie
- Créer un endpoint `webhook.php` pour recevoir les confirmations de paiement
- Appeler `OrderService->confirmPayment()` après confirmation du webhook

**Fichiers à créer :**

```
BackEnd/
├── Api/
│   └── webhook.php               # Webhook Mollie
└── Src/
    └── Services/
        └── MollieService.php     # Intégration Mollie API
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
- Intégrer le système de paiement Mollie côté frontend
- Créer une page de confirmation de paiement

**Pages à créer :**

```
FrontEnd/pages/
├── eventDetail.html              # Détails événement
├── myOrders.html                 # Mes commandes
├── myTickets.html                # Mes billets
├── myFavorites.html              # Mes favoris
└── paymentSuccess.html           # Confirmation paiement

FrontEnd/assets/js/pages/
├── eventDetail.js
├── myOrders.js
├── myTickets.js
├── myFavorites.js
└── paymentSuccess.js
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

- ❌ Mollie (paiement)
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
2. ❌ Intégration Mollie (paiement)
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

# Installer Mollie
composer require mollie/mollie-api-php

# Installer SendGrid
composer require sendgrid/sendgrid

# Installer TCPDF (génération PDF)
composer require tecnickcom/tcpdf
```

---

**Note :** Le backend est fonctionnel à 100% pour les opérations CRUD. Les intégrations tierces (Mollie, SendGrid, PDF) et le frontend sont les prochaines étapes critiques.
