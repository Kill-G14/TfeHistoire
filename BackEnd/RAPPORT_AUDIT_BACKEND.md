# 📋 RAPPORT D'AUDIT DU BACKEND

**Date**: 27 avril 2026  
**Contexte**: Vérification après migration de la structure de base de données

---

## 🔴 PROBLÈMES CRITIQUES IDENTIFIÉS

### 1. **Table `tickets` inexistante mais code encore présent**

#### ❌ **Problème**

La nouvelle structure de base de données **n'a plus de table `tickets`**. Les informations de prix et quantité sont maintenant **directement dans la table `events`** avec les colonnes :

- `ticket_price` DECIMAL(10, 2)
- `ticket_quantity` INT

**MAIS** le code backend contient encore toute la logique pour gérer une table `tickets` séparée qui n'existe plus.

#### 📁 **Fichiers obsolètes à supprimer**

1. ❌ `BackEnd/Src/Models/Ticket.php` - Model pour table inexistante
2. ❌ `BackEnd/Src/Repositories/TicketRepository.php` - Repository pour table inexistante
3. ❌ `BackEnd/Src/Services/TicketService.php` - Service pour table inexistante
4. ❌ `BackEnd/Api/ticketsApi.php` - API endpoint pour table inexistante
5. ❌ `BackEnd/Src/Validators/TicketValidator.php` - Validator pour table inexistante
6. ❌ `assets/js/managers/TicketManager.js` - Frontend manager appelant l'API obsolète

#### ⚠️ **Fichiers utilisant les classes obsolètes**

**BackEnd/Api/eventsApi.php** (ligne 12, 24, 33)

```php
use App\Repositories\TicketRepository;  // ❌ OBSOLETE
$ticketRepository = new TicketRepository();  // ❌ OBSOLETE
$eventService = new EventService($eventRepository, $ticketRepository, $eventValidator);  // ❌ OBSOLETE
```

**BackEnd/Api/stripeApi.php** (ligne 17, 29)

```php
use App\Repositories\TicketRepository;  // ❌ OBSOLETE
$ticketRepository = new TicketRepository();  // ❌ OBSOLETE
```

**BackEnd/Api/ticketsGeneratedApi.php** (ligne 14, 30, 39)

```php
use App\Repositories\TicketRepository;  // ❌ OBSOLETE
$ticketRepository = new TicketRepository();  // ❌ OBSOLETE
$pdfService = new PdfService($eventRepository, $ticketRepository, $orderItemRepository);  // ❌ OBSOLETE
```

**BackEnd/Src/Services/EventService.php** (lignes 8, 13, 18, 150-165)

```php
use App\Models\Ticket;  // ❌ OBSOLETE
use App\Repositories\TicketRepository;  // ❌ OBSOLETE
private TicketRepository $ticketRepository;  // ❌ OBSOLETE

// Dans createEvent() - LOGIQUE OBSOLETE
if (isset($data['ticket_price']) && isset($data['ticket_quantity'])) {
  $ticket = new Ticket();  // ❌ Crée un ticket dans table inexistante
  $ticket->event_id = $eventId;
  // ... code inutile
  $ticketId = $this->ticketRepository->createTicket($ticket);  // ❌ ERREUR SQL
}
```

---

### 2. **EventRepository fait des JOIN sur table inexistante**

#### ❌ **Problème critique dans `BackEnd/Src/Repositories/EventRepository.php`**

**Lignes 59-63** et autres requêtes :

```php
$query = "SELECT e.*, t.id as ticket_id, t.price as ticket_price, t.quantity as ticket_quantity
          FROM events e
          LEFT JOIN tickets t ON e.id = t.event_id AND t.is_deleted = FALSE  // ❌ TABLE INEXISTANTE
          WHERE e.id = :id AND e.is_deleted = FALSE";
```

**Problème** : La table `tickets` n'existe plus ! Les colonnes `ticket_price` et `ticket_quantity` sont **directement dans `events`**.

**Lignes 52-54** - Mapping erroné :

```php
$event->ticket_id = isset($row['ticket_id']) ? (int) $row['ticket_id'] : null;  // ❌ N'existe plus
$event->ticket_price = isset($row['ticket_price']) ? (float) $row['ticket_price'] : null;  // ✅ Existe mais dans events
$event->ticket_quantity = isset($row['ticket_quantity']) ? (int) $row['ticket_quantity'] : null;  // ✅ Existe mais dans events
```

#### 📋 **Méthodes affectées**

- `getEventById()`
- `getAllEvents()`
- `getEventsByCountry()`
- `getEventsByCategory()`
- `searchEvents()`
- Et potentiellement d'autres...

---

### 3. **Confusion de nommage : PurchasedTicketRepository**

#### ⚠️ **Problème de nomenclature**

**Fichier** : `BackEnd/Src/Repositories/PurchasedTicketRepository.php`

**Problème** :

- Ce fichier gère la table `tickets_generated` (billets achetés avec QR code)
- Le nom `PurchasedTicketRepository` n'est pas clair
- Les méthodes s'appellent `getTicketById()` au lieu de `getTicketGeneratedById()`
- Confusion avec l'ancien `TicketRepository`

**Recommandation** : Renommer en `TicketGeneratedRepository` pour clarté

---

### 4. **Model Event manque de cohérence**

#### ⚠️ **Fichier** : `BackEnd/Src/Models/Event.php`

**Lignes 17-18** :

```php
public float $ticket_price;  // ✅ Existe dans DB
public int $ticket_quantity;  // ✅ Existe dans DB
```

✅ **Ces propriétés sont correctes** et correspondent à la nouvelle structure DB.

**MAIS** : EventRepository essaie d'ajouter dynamiquement `ticket_id` qui n'existe plus.

---

## ✅ CE QUI FONCTIONNE CORRECTEMENT

### 1. **Table `tickets_generated`**

- ✅ Table existe bien dans la DB
- ✅ `TicketGenerated.php` (Model) est correct
- ✅ `PurchasedTicketRepository.php` fonctionne (mais à renommer)
- ✅ `ticketsGeneratedApi.php` est fonctionnel

### 2. **Tables principales**

- ✅ `users` - OK
- ✅ `events` - OK avec nouvelles colonnes `ticket_price` et `ticket_quantity`
- ✅ `orders` - OK
- ✅ `order_items` - OK
- ✅ `favorites` - OK
- ✅ `sessions` - OK
- ✅ `payments` - OK

---

## 🔧 SOLUTIONS PROPOSÉES

### **SOLUTION 1 : Nettoyer le code obsolète (RECOMMANDÉ)**

#### Étape 1 : Supprimer les fichiers obsolètes

```
❌ DELETE BackEnd/Src/Models/Ticket.php
❌ DELETE BackEnd/Src/Repositories/TicketRepository.php
❌ DELETE BackEnd/Src/Services/TicketService.php
❌ DELETE BackEnd/Api/ticketsApi.php
❌ DELETE BackEnd/Src/Validators/TicketValidator.php
❌ DELETE assets/js/managers/TicketManager.js
```

#### Étape 2 : Corriger EventRepository.php

**Retirer tous les LEFT JOIN tickets** et mapper directement depuis events :

```php
// AVANT (FAUX)
$query = "SELECT e.*, t.id as ticket_id, t.price as ticket_price, t.quantity as ticket_quantity
          FROM events e
          LEFT JOIN tickets t ON e.id = t.event_id AND t.is_deleted = FALSE
          WHERE e.id = :id AND e.is_deleted = FALSE";

// APRÈS (CORRECT)
$query = "SELECT * FROM events WHERE id = :id AND is_deleted = FALSE";
$stmt = $this->getPdo()->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
$event = $stmt->fetch();
return $event ?: null;
```

#### Étape 3 : Corriger EventService.php

**Retirer** :

- Import de `Ticket`
- Import de `TicketRepository`
- Propriété `$ticketRepository`
- Paramètre dans le constructeur
- Logique de création de ticket dans `createEvent()` (lignes 150-165)

**Nouveau constructeur** :

```php
public function __construct(EventRepository $eventRepository, EventValidator $eventValidator) {
    $this->eventRepository = $eventRepository;
    $this->eventValidator = $eventValidator;
}
```

**Dans createEvent()** : Les valeurs `ticket_price` et `ticket_quantity` sont maintenant assignées directement à l'objet Event :

```php
$event->ticket_price = isset($data['ticket_price']) ? (float) $data['ticket_price'] : 0.00;
$event->ticket_quantity = isset($data['ticket_quantity']) ? (int) $data['ticket_quantity'] : 0;
```

#### Étape 4 : Corriger les API Files

**eventsApi.php** :

```php
// RETIRER
use App\Repositories\TicketRepository;
$ticketRepository = new TicketRepository();

// MODIFIER
$eventService = new EventService($eventRepository, $eventValidator);  // Sans ticketRepository
```

**stripeApi.php** :

```php
// RETIRER
use App\Repositories\TicketRepository;
$ticketRepository = new TicketRepository();
```

**ticketsGeneratedApi.php** :

```php
// RETIRER
use App\Repositories\TicketRepository;
$ticketRepository = new TicketRepository();

// MODIFIER PdfService (si nécessaire)
$pdfService = new PdfService($eventRepository, $orderItemRepository);  // Sans ticketRepository
```

#### Étape 5 : Renommer PurchasedTicketRepository (optionnel mais recommandé)

```
RENAME: PurchasedTicketRepository.php → TicketGeneratedRepository.php
```

Et mettre à jour tous les imports/usages.

---

### **SOLUTION 2 : Garder la table tickets (NON RECOMMANDÉ)**

Si vous voulez garder le système de gestion de tickets multiples par événement :

#### Ajouter la table dans database.sql

```sql
CREATE TABLE IF NOT EXISTS tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    start_sale_date DATETIME,
    end_sale_date DATETIME,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

⚠️ **MAIS** : Cela créerait une incohérence car `events` a déjà `ticket_price` et `ticket_quantity`.

---

## 📊 RÉCAPITULATIF

### Fichiers à modifier (SOLUTION 1 - RECOMMANDÉE)

| Fichier                                          | Action       | Priorité    |
| ------------------------------------------------ | ------------ | ----------- |
| `Src/Models/Ticket.php`                          | ❌ SUPPRIMER | 🔴 CRITIQUE |
| `Src/Repositories/TicketRepository.php`          | ❌ SUPPRIMER | 🔴 CRITIQUE |
| `Src/Services/TicketService.php`                 | ❌ SUPPRIMER | 🔴 CRITIQUE |
| `Api/ticketsApi.php`                             | ❌ SUPPRIMER | 🔴 CRITIQUE |
| `Src/Validators/TicketValidator.php`             | ❌ SUPPRIMER | 🔴 CRITIQUE |
| `assets/js/managers/TicketManager.js`            | ❌ SUPPRIMER | 🔴 CRITIQUE |
| `Src/Repositories/EventRepository.php`           | 🔧 CORRIGER  | 🔴 CRITIQUE |
| `Src/Services/EventService.php`                  | 🔧 CORRIGER  | 🔴 CRITIQUE |
| `Api/eventsApi.php`                              | 🔧 CORRIGER  | 🟠 HAUTE    |
| `Api/stripeApi.php`                              | 🔧 CORRIGER  | 🟠 HAUTE    |
| `Api/ticketsGeneratedApi.php`                    | 🔧 CORRIGER  | 🟠 HAUTE    |
| `Src/Repositories/PurchasedTicketRepository.php` | 🔄 RENOMMER  | 🟡 MOYENNE  |

### Routes API actuelles

| Route                     | Status                      | Commentaire                                  |
| ------------------------- | --------------------------- | -------------------------------------------- |
| `eventsApi.php`           | ⚠️ FONCTIONNE PARTIELLEMENT | Utilise TicketRepository obsolète            |
| `ticketsApi.php`          | ❌ NE FONCTIONNE PAS        | Table inexistante                            |
| `ticketsGeneratedApi.php` | ✅ FONCTIONNE               | OK mais utilise TicketRepository inutilement |
| `ordersApi.php`           | ✅ FONCTIONNE               | OK                                           |
| `authApi.php`             | ✅ FONCTIONNE               | OK                                           |
| `favoritesApi.php`        | ✅ FONCTIONNE               | OK                                           |
| `stripeApi.php`           | ⚠️ FONCTIONNE PARTIELLEMENT | Utilise TicketRepository obsolète            |
| `adminApi.php`            | ✅ FONCTIONNE               | OK                                           |

---

## 🎯 RECOMMANDATION FINALE

**Je recommande SOLUTION 1** car :

1. ✅ Structure DB plus simple et cohérente
2. ✅ Moins de code à maintenir
3. ✅ Performance améliorée (pas de JOIN inutile)
4. ✅ Correspond à la nouvelle structure de la base de données

**Ordre d'exécution recommandé** :

1. ✅ Sauvegarder/backup du code actuel
2. 🔧 Corriger EventRepository.php (PRIORITÉ 1)
3. 🔧 Corriger EventService.php (PRIORITÉ 1)
4. 🔧 Corriger les API Files (eventsApi, stripeApi, ticketsGeneratedApi)
5. ❌ Supprimer les fichiers obsolètes
6. ✅ Tester toutes les routes API
7. 🔄 Renommer PurchasedTicketRepository (optionnel)

---

## 📝 NOTES ADDITIONNELLES

- ⚠️ Le frontend (TicketManager.js) appelle `ticketsApi.php` qui ne fonctionnera pas
- ⚠️ Vérifier si PdfService a besoin de TicketRepository ou si EventRepository suffit
- ✅ La table `tickets_generated` et son repository fonctionnent correctement
- ✅ La logique de paiement Stripe est OK

---

**Souhaitez-vous que je procède aux corrections ?**
