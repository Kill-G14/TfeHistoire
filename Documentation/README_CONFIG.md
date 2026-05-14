# Configuration du Backend

## 📋 Installation

### 1. Configurer l'environnement

Le fichier `config.php` contient toutes les configurations sensibles de l'application.

**Première installation :**

```bash
# Copier le fichier d'exemple
cp config.example.php config.php
```

### 2. Remplir vos configurations

Ouvrez `config.php` et remplissez vos valeurs :

#### API OpenRouteService

```php
'openroute' => [
  'api_key' => 'VOTRE_CLE_API_ICI',
  'base_url' => 'https://api.openrouteservice.org/v2'
]
```

Pour obtenir une clé API :

1. Inscrivez-vous sur [openrouteservice.org](https://openrouteservice.org/dev/#/signup)
2. Créez un token dans le dashboard
3. Copiez la clé dans `config.php`

#### Base de données

```php
'database' => [
  'host' => 'localhost',
  'name' => 'memoriaeventia',
  'user' => 'root',
  'password' => ''
]
```

### 3. Sécurité

⚠️ **IMPORTANT** : Le fichier `config.php` est ignoré par Git (`.gitignore`)

- ✅ Commiter : `config.example.php`
- ❌ Ne JAMAIS commiter : `config.php`

## 📂 Structure

```
BackEnd/
├── config.php              # Configuration réelle (ignoré par Git)
├── config.example.php      # Template de configuration (commité)
├── Api/
│   ├── routeApi.php       # Utilise $config['openroute']['api_key']
│   └── ...
```

## 🔧 Utilisation dans le code

Pour utiliser la configuration dans vos fichiers API :

```php
// Charger la configuration
$config = require __DIR__ . '/../config.php';

// Accéder aux valeurs
$apiKey = $config['openroute']['api_key'];
$dbHost = $config['database']['host'];
$appName = $config['app']['name'];
```

## 🚀 Déploiement

Lors du déploiement sur un serveur :

1. Copiez `config.example.php` vers `config.php`
2. Remplissez les valeurs de production
3. Ne commitez jamais `config.php`

## 🔐 Avantages

✅ **Sécurité** : Clés API et mots de passe non exposés  
✅ **Propreté** : Code sans valeurs en dur  
✅ **Flexibilité** : Configurations différentes dev/prod  
✅ **Collaboration** : Chaque développeur a ses propres configs
