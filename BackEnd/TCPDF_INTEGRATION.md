# 🎫 Intégration TCPDF - Documentation

## ✅ Installation terminée

TCPDF a été installé et intégré avec succès dans le projet.

---

## 📁 Fichiers créés

### 1. **PdfService.php** (`BackEnd/Src/Services/PdfService.php`)

Service sécurisé de génération de PDF pour les billets.

**Règles de sécurité appliquées :**

1. ✅ **Échappement des données** : Toutes les données utilisateur sont échappées avec `htmlspecialchars()`
2. ✅ **Validation des droits** : Vérification que l'utilisateur possède le billet avant génération
3. ✅ **Chemins sécurisés** : Utilisation de constantes (`STORAGE_PATH`) et non de chemins fournis par l'utilisateur
4. ✅ **Validation des données** : Vérification de l'existence de toutes les données nécessaires
5. ✅ **Liste blanche** : Logos autorisés via liste blanche
6. ✅ **Noms de fichiers sécurisés** : Format `ticket_{uniqueCode}_{timestamp}.pdf` avec regex de nettoyage
7. ✅ **Protection du dossier** : `.htaccess` empêche l'accès direct aux fichiers

**Méthodes principales :**

- `generateTicketPdf(TicketGenerated $ticket, int $userId)` : Génère un PDF pour un billet
- `sanitizeData(array $data)` : Échappe toutes les données
- `createPdf(array $data, string $uniqueCode)` : Crée le fichier PDF avec TCPDF

### 2. **Action downloadTicket** (dans `ticketsGenerated.php`)

Endpoint API pour télécharger un billet au format PDF.

**Sécurité :**

- Authentification obligatoire (token)
- Vérification de propriété du billet (via `order->user_id`)
- Headers sécurisés pour l'envoi du fichier
- Nettoyage du buffer avant envoi

---

## 🔐 Sécurité TCPDF

### Règles implémentées

#### 1️⃣ Échappement des données utilisateur

```php
private function sanitizeData(array $data): array {
  $sanitized = [];
  foreach ($data as $key => $value) {
    if (is_string($value)) {
      $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
  }
  return $sanitized;
}
```

#### 2️⃣ Contrôle des chemins de fichiers

```php
// Chemin sécurisé défini par constante
private const STORAGE_PATH = __DIR__ . '/../../storage/tickets/';

// Liste blanche pour les logos
private const ALLOWED_LOGOS = [
  'default' => __DIR__ . '/../../storage/images/logo.png'
];
```

#### 3️⃣ Protection du dossier storage

Fichier `.htaccess` créé dans `storage/` :

```apache
# Protection du dossier storage
Deny from all
```

Les fichiers ne sont accessibles **que via l'API** après vérification des droits.

#### 4️⃣ Validation des données

```php
// Vérifier que le billet existe et n'est pas supprimé
if ($ticketGenerated->is_deleted) {
  return ['success' => false, 'message' => 'Ce billet n\'existe plus'];
}

// Vérifier toutes les données nécessaires
if (!$orderItem || !$ticket || !$event) {
  return ['success' => false, 'message' => 'Données manquantes'];
}
```

#### 5️⃣ Vérification des droits d'accès

```php
// Dans ticketsGenerated.php
$order = $orderRepository->getOrderById($orderItem->order_id);
if (!$order || $order->user_id !== $userId) {
  echo json_encode([
    'success' => false,
    'message' => 'Accès non autorisé à ce billet'
  ]);
  exit;
}
```

#### 6️⃣ Noms de fichiers sécurisés

```php
// Format : ticket_{uniqueCode}_{timestamp}.pdf
$timestamp = time();
$filename = 'ticket_' . preg_replace('/[^A-Z0-9]/', '', $uniqueCode) . '_' . $timestamp . '.pdf';
```

#### 7️⃣ Librairie à jour

- Version installée : **TCPDF 6.11.2** (dernière version stable)

---

## 🧪 Test de l'API

### Endpoint : `Api/ticketsGenerated.php`

**Action :** `downloadTicket`

**Méthode :** `POST`

**Headers :**

```
Content-Type: application/json
```

**Body :**

```json
{
  "action": "downloadTicket",
  "token": "votre_token_auth",
  "id": 1
}
```

**Réponse (succès) :**
Le serveur envoie directement le fichier PDF avec les headers :

```
Content-Type: application/pdf
Content-Disposition: attachment; filename="ticket_ABC123_1234567890.pdf"
```

**Réponses (erreur) :**

```json
{
  "success": false,
  "message": "ID non fourni"
}
```

```json
{
  "success": false,
  "message": "Billet non trouvé"
}
```

```json
{
  "success": false,
  "message": "Accès non autorisé à ce billet"
}
```

---

## 📄 Structure du PDF généré

Le PDF contient :

1. **Titre de l'événement** (centré, grand)
2. **Type de billet** et description
3. **Prix** du billet
4. **Informations de l'événement** :
   - Date et heure
   - Lieu complet (adresse, code postal, ville, pays)
   - Description de l'événement
5. **Code unique** (format : `ABC123DEF456`)
6. **QR Code** (généré avec TCPDF, taille 60x60mm, centré)
7. **Instructions** pour l'utilisateur

### Exemple de rendu :

```
┌────────────────────────────────────┐
│                                    │
│      Festival de Musique 2026      │
│                                    │
│  Type de billet : VIP              │
│  Prix : 150,00 €                   │
│                                    │
│  ────────────────────────────────  │
│                                    │
│  Informations de l'événement       │
│  Date : 2026-06-15 à 20:00         │
│  Lieu : 123 Rue de la Fête         │
│         75001 Paris, France        │
│                                    │
│  Description :                     │
│  Un festival inoubliable...        │
│                                    │
│  ────────────────────────────────  │
│                                    │
│  Votre billet électronique         │
│                                    │
│        ABC123DEF456                │
│                                    │
│        ┌─────────────┐             │
│        │  QR  CODE   │             │
│        │   ███████   │             │
│        │   ███████   │             │
│        └─────────────┘             │
│                                    │
│  Présentez ce billet à l'entrée    │
│                                    │
└────────────────────────────────────┘
```

---

## 🛠️ Configuration

### Chemin de stockage

Les PDF sont stockés dans :

```
BackEnd/storage/tickets/
```

Ce dossier est protégé par `.htaccess` :

- ❌ Pas d'accès direct via URL
- ✅ Téléchargement uniquement via l'API après vérification

### Composer

TCPDF a été installé via :

```bash
composer require tecnickcom/tcpdf
```

**Version :** 6.11.2

---

## 🚀 Utilisation dans le code

### Dans un Service

```php
use App\Services\PdfService;
use App\Repositories\EventRepository;
use App\Repositories\TicketRepository;
use App\Repositories\OrderItemRepository;

$eventRepository = new EventRepository();
$ticketRepository = new TicketRepository();
$orderItemRepository = new OrderItemRepository();

$pdfService = new PdfService($eventRepository, $ticketRepository, $orderItemRepository);

// Générer un PDF
$result = $pdfService->generateTicketPdf($ticketGenerated, $userId);

if ($result['success']) {
  $pdfPath = $result['data']['pdf_path'];
  $filename = $result['data']['filename'];

  // Envoyer le fichier au client
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  readfile($pdfPath);
}
```

---

## ⚠️ Points importants

### Ne jamais :

❌ **Accepter des chemins de fichiers de l'utilisateur**

```php
// MAUVAIS
$logo = $_POST['logo_path'];
$pdf->Image($logo, ...);
```

❌ **Injecter directement des données utilisateur**

```php
// MAUVAIS
$pdf->writeHTML($_POST['message']);
```

❌ **Permettre l'accès direct aux fichiers PDF**

```php
// MAUVAIS : pas de .htaccess
// L'utilisateur peut accéder à /storage/tickets/ticket_123.pdf
```

### Toujours :

✅ **Échapper les données**

```php
// BON
$message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
$pdf->writeHTML($message);
```

✅ **Utiliser une liste blanche pour les fichiers**

```php
// BON
private const ALLOWED_LOGOS = [
  'default' => __DIR__ . '/../../storage/images/logo.png'
];
$logoPath = self::ALLOWED_LOGOS['default'];
```

✅ **Vérifier les droits d'accès**

```php
// BON
if ($order->user_id !== $userId) {
  return ['success' => false, 'message' => 'Accès refusé'];
}
```

---

## 📊 Prochaines étapes

L'intégration TCPDF est complète. Vous pouvez maintenant :

1. ✅ Tester l'API avec Postman ou un client HTTP
2. ✅ Intégrer l'appel dans le frontend
3. ⏳ Ajouter le logo du projet (créer `storage/images/logo.png`)
4. ⏳ Personnaliser le design du PDF si nécessaire
5. ⏳ Intégrer SendGrid pour envoyer le PDF par email

---

## 🔗 Ressources

- Documentation TCPDF : https://tcpdf.org/docs/
- Dernière version : https://github.com/tecnickcom/TCPDF
- Exemples : https://tcpdf.org/examples/

---

**✅ TCPDF intégré avec succès !**
