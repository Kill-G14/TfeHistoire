# Dossier de stockage des billets PDF

Ce dossier contient les billets PDF générés par le système.

## 🔒 Sécurité

- **Accès protégé** : Le fichier `.htaccess` dans le dossier parent empêche l'accès direct via URL
- **Téléchargement sécurisé** : Les billets ne sont accessibles que via l'API `ticketsGenerated.php` après vérification des droits
- **Noms de fichiers** : Format sécurisé `ticket_{uniqueCode}_{timestamp}.pdf`

## 📁 Format des fichiers

Les fichiers sont nommés selon le pattern :

```
ticket_ABC123DEF456_1234567890.pdf
```

Où :

- `ABC123DEF456` : Code unique du billet
- `1234567890` : Timestamp de génération

## 🗑️ Nettoyage

Les fichiers peuvent être supprimés automatiquement après téléchargement (optionnel).

Décommenter cette ligne dans `ticketsGenerated.php` :

```php
// unlink($pdfPath);
```

Ou mettre en place un cron job pour nettoyer les anciens fichiers :

```bash
# Supprimer les PDF de plus de 7 jours
find /path/to/storage/tickets -name "ticket_*.pdf" -mtime +7 -delete
```
