# AdminOffice - TfeHistoire

Interface d'administration pour la plateforme TfeHistoire, construite avec AdminLTE 3.2.

## 📁 Structure

```
AdminOffice/
├── pages/
│   └── login.html                # Page de connexion
├── assets/
│   ├── css/
│   │   └── custom.css           # Styles personnalisés
│   ├── js/
│   │   ├── pages/
│   │   │   └── login.js         # Logique de connexion
│   │   └── utils/
│   │       └── auth.js          # Gestion authentification
│   └── images/
└── README.md
```

## 🚀 Fonctionnalités implémentées

### ✅ Page de Login

- Interface AdminLTE intégrée via CDN
- Formulaire de connexion sécurisé
- Validation des rôles (admin/moderator uniquement)
- Gestion "Se souvenir de moi" (localStorage/sessionStorage)
- Messages d'erreur
- Design moderne avec gradient

### ✅ Gestion de l'authentification

- Module `auth.js` complet :
  - Sauvegarde du token et des données utilisateur
  - Vérification du rôle (admin/moderator)
  - Vérification du token avec le backend
  - Déconnexion
- Connexion à l'API backend existante (`BackEnd/Api/auth.php`)

## 🔐 Sécurité

- Seuls les utilisateurs avec rôle `admin` ou `moderator` peuvent se connecter
- Vérification du token à chaque requête
- Support localStorage (se souvenir) et sessionStorage
- Redirection automatique si non authentifié

## 🎨 AdminLTE CDN

Les ressources AdminLTE sont chargées via CDN :

- **CSS** : AdminLTE 3.2
- **JS** : AdminLTE 3.2 + jQuery + Bootstrap 4
- **Icons** : Font Awesome 6.4.0
- **Fonts** : Google Fonts (Source Sans Pro)

## 📝 Utilisation

### Connexion

1. Accéder à `AdminOffice/pages/login.html`
2. Se connecter avec un compte admin ou moderator
3. Redirection automatique vers le dashboard (à créer)

### Déconnexion

```javascript
import { auth } from "../utils/auth.js";
await auth.logout();
```

## ⚠️ À faire

- [ ] Créer la page dashboard (`index.html`)
- [ ] Créer le layout avec sidebar AdminLTE
- [ ] Page de gestion des événements (validation)
- [ ] Page de gestion des utilisateurs
- [ ] Page de statistiques
- [ ] Page de gestion des signalements
- [ ] Système de notifications

## 🔗 Backend

L'AdminOffice utilise les APIs backend existantes :

- `BackEnd/Api/auth.php` : Authentification
- `BackEnd/Api/events.php` : Gestion événements
- `BackEnd/Api/orders.php` : Gestion commandes

## 💡 Notes

- Le frontend AdminOffice suit les mêmes standards que le frontend public (voir `AGENTS.md`)
- Structure modulaire avec `pages/` et `assets/js/{pages,utils}/`
- Modules ES6 avec `import/export`
- Pas de jQuery dans le code custom (utilisé uniquement pour AdminLTE)
