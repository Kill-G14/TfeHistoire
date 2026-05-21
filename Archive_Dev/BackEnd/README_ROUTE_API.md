# Configuration de l'API OpenRouteService

## Clé API

La clé API OpenRouteService est stockée de manière sécurisée dans le backend :
`BackEnd/Api/routeApi.php`

## Obtenir votre propre clé API

1. Inscrivez-vous sur [openrouteservice.org](https://openrouteservice.org/dev/#/signup)
2. Activez votre compte par email
3. Créez un nouveau token dans [Dashboard → Tokens](https://openrouteservice.org/dev/#/home)
4. Copiez votre clé API

## Configuration pour la production

1. Ouvrez le fichier `BackEnd/Api/routeApi.php`
2. Remplacez la valeur de la constante `OPENROUTESERVICE_API_KEY` :

```php
const OPENROUTESERVICE_API_KEY = 'VOTRE_CLE_API_ICI';
```

## Limites de l'API gratuite

- **2000 requêtes par jour**
- **40 requêtes par minute**

Pour une utilisation en production intensive, envisagez un plan payant sur OpenRouteService.

## Alternative : Variables d'environnement

Pour une sécurité maximale, utilisez des variables d'environnement :

1. Créez un fichier `.env` à la racine du projet Backend
2. Ajoutez : `OPENROUTESERVICE_API_KEY=votre_cle`
3. Modifiez `routeApi.php` pour lire depuis `.env`
4. Ajoutez `.env` au `.gitignore`
