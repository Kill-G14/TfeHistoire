**Ajoutez vos propres directives ici**

# Directives générales

- N'utilisez le positionnement absolu que lorsque c'est nécessaire. Optez par défaut pour des mises en page responsives et bien structurées qui utilisent flexbox et grid
- Refactorisez le code au fur et à mesure pour garder le code propre
- Gardez les fichiers de petite taille et mettez les fonctions auxiliaires et les composants dans leurs propres fichiers.

---

# Directives du système de design

De plus, si vous sélectionnez un système de design à utiliser dans la zone de prompt, vous pouvez référencer
les composants, tokens, variables et composants de votre système de design.
Par exemple :

- Utilisez une taille de police de base de 14px
- Les formats de date doivent toujours être au format "Jun 10"
- La barre d'outils inférieure ne doit jamais avoir plus de 4 éléments
- N'utilisez jamais le bouton d'action flottant avec la barre d'outils inférieure
- Les tags doivent toujours venir par ensembles de 2 ou plus
- N'utilisez pas de menu déroulant s'il y a 2 options ou moins

Vous pouvez également créer des sous-sections et ajouter des détails plus spécifiques
Par exemple :

## Bouton

Le composant Bouton est un élément interactif fondamental dans notre système de design, conçu pour déclencher des actions ou naviguer
les utilisateurs à travers l'application. Il fournit un retour visuel et des affordances claires pour améliorer l'expérience utilisateur.

### Utilisation

Les boutons doivent être utilisés pour les actions importantes que les utilisateurs doivent effectuer, telles que les soumissions de formulaires, la confirmation de choix,
ou l'initiation de processus. Ils communiquent l'interactivité et doivent avoir des libellés clairs et orientés action.

### Variantes

- Bouton Principal
  - Objectif : Utilisé pour l'action principale dans une section ou une page
  - Style Visuel : Gras, rempli avec la couleur principale de la marque
  - Utilisation : Un bouton principal par section pour guider les utilisateurs vers l'action la plus importante
- Bouton Secondaire
  - Objectif : Utilisé pour les actions alternatives ou de soutien
  - Style Visuel : Contour avec la couleur principale, fond transparent
  - Utilisation : Peut apparaître à côté d'un bouton principal pour les actions moins importantes
- Bouton Tertiaire
  - Objectif : Utilisé pour les actions les moins importantes
  - Style Visuel : Texte uniquement sans bordure, utilisant la couleur principale
  - Utilisation : Pour les actions qui doivent être disponibles mais non mises en évidence

---

# Frameworks UI

## Bootstrap 5.3+ (Framework principal)

- Bootstrap est le framework UI principal du projet
- Toujours utiliser les classes Bootstrap en priorité
- Bootstrap CSS et JS chargés via CDN
- Bootstrap Icons pour toutes les icônes

## shadcn/ui (Composants complémentaires)

**Usage autorisé en complément de Bootstrap :**

- shadcn/ui peut être utilisé pour des composants avancés non disponibles dans Bootstrap
- Vérifier la compatibilité avant utilisation (pas de conflits de classes)
- Utilisation recommandée via CDN pour cohérence
- **Compatible si** :
  - Les composants shadcn n'écrasent pas les styles Bootstrap existants
  - Les classes CSS ne créent pas de conflits
  - Le design reste cohérent avec la palette de couleurs établie

**Cas d'usage recommandés pour shadcn/ui :**

- Composants de date picker avancés
- Composants de dialog/modal avec animations sophistiquées
- Composants de dropdown/select avec recherche
- Composants de data table complexes
- Toast notifications avancées

**Règles d'intégration :**

- Toujours tester la compatibilité avant d'intégrer un composant shadcn
- Adapter les couleurs shadcn aux variables CSS du projet
- Privilégier Bootstrap si le composant existe dans les deux
- Documenter l'utilisation de shadcn dans les commentaires du code

---

# Directives spécifiques au projet - MemoriaEventia

## Identité visuelle - Thème historique élégant

### Palette de couleurs (OBLIGATOIRE)

**Variables CSS à toujours utiliser :**

- `--color-primary: #1a3a52` - Bleu marine (élégant, historique)
- `--color-primary-hover: #2a4a62` - Bleu marine hover
- `--color-accent: #c9a961` - Or/Doré (prestige, histoire)
- `--color-accent-hover: #b39851` - Or hover
- `--color-background: #f8f9fa` - Gris très clair (fond de page)
- `--color-text: #212529` - Noir standard Bootstrap
- `--color-text-muted: #6c757d` - Gris Bootstrap pour texte secondaire

**Usage des couleurs :**

- Primaire (#1a3a52) : Titres, boutons principaux, header logo, liens actifs
- Accent (#c9a961) : Icônes importantes, focus states, détails décoratifs
- Background : Sections, cartes de profil
- Text muted : Dates, lieux, descriptions secondaires

### Typographie

**Fonts :**

- Police principale : **Inter** (Google Fonts) avec fallback système
- `font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif`

**Tailles établies :**

- **Page titles** : `2rem` (32px), weight 600, couleur primaire
- **Page subtitles** : `1.125rem` (18px), couleur text-muted
- **Card titles** : `1.125rem` (18px), weight 600, couleur primaire
- **Body text** : Bootstrap default (1rem/16px)
- **Small text** : Bootstrap `.small` ou `0.875rem` (14px)
- **Icons avec texte** : `.icon-accent` pour couleur dorée

**Line-height :**

- Titres : 1.3
- Corps : Bootstrap default (1.5)

### Cartes d'événements (STRUCTURE ÉTABLIE)

**Structure obligatoire (déjà en place) :**

- Image d'en-tête avec `overflow-hidden` pour effet zoom
- Badge catégorie positionné en `absolute` (top-right)
- Card-body avec :
  - Titre (`.card-title`)
  - Description courte avec `.line-clamp-2` (2 lignes max)
  - Bloc infos avec icônes :
    - Localisation (`.bi-geo-alt`)
    - Date (`.bi-calendar3`)
    - Heure (`.bi-clock`)
  - Footer avec border-top :
    - Prix à gauche
    - Bouton "Voir détails" à droite

**Dimensions images :**

- Card image : `height: 200px` (180px sur mobile)
- Event detail image : `height: 300px` (400px sur desktop)
- Toujours `object-fit: cover`

**Effets hover (déjà établis) :**

- Card : `transform: translateY(-4px)` + `box-shadow: 0 4px 12px rgba(0,0,0,0.15)`
- Image : `transform: scale(1.05)` avec `overflow-hidden` sur parent
- Transitions : `0.2s` pour card, `0.3s` pour image

**Espacement :**

- Card padding : Bootstrap default
- Border-radius : `0.375rem` (6px) pour cartes et éléments
- Gap entre cartes : `.g-4` (1.5rem)

### Layout & Grille (BOOTSTRAP ÉTABLI)

**Grid responsive (déjà en place) :**

- Mobile : 1 colonne (`row-cols-1`)
- Tablette : 2 colonnes (`row-cols-md-2`)
- Desktop : 3 colonnes (`row-cols-lg-3`)
- Gap : `.g-4` (1.5rem entre cartes)

**Container :**

- Toujours utiliser `.container` Bootstrap
- Max-width géré par Bootstrap (1200px sur xl)

**Filtres :**

- Composant `.filterContainer` : fond blanc, border, padding 1rem, shadow légère
- Structure : Recherche en haut, puis row avec 2 selects (pays, catégorie)
- Icônes : `.bi-search`, `.bi-funnel` en gris muted

### Navigation et Header (ÉTABLI)

**Structure actuelle à respecter :**

- `.header` : sticky top, fond blanc, border-bottom, shadow légère
- Height : auto avec `py-3` (padding vertical)
- Layout : flexbox space-between
  - Gauche : Logo + icône + texte "MemoriaEventia"
  - Centre : Nav links (hidden sur mobile avec `.d-none .d-md-flex`)
  - Droite : Boutons actions (conditionnels logged in/out)

**Nav links :**

- Couleur : text-muted
- Hover : couleur primaire
- Active : couleur primaire + weight 500
- Font-size : `0.875rem` (14px)

**Logo :**

- Icône : `.bi-calendar-event` + taille `.fs-4`
- Couleur : primaire
- Hover : `opacity: 0.8`

### Boutons (STANDARDS ÉTABLIS)

**Bouton principal (`.btn-primary`) :**

- Background : `var(--color-primary)`
- Hover : `var(--color-primary-hover)`
- Taille minimale : `btn-sm` ou default selon contexte
- Icons : toujours avec gap (`.gap-2`)

**Bouton outline :**

- `.btn-outline-primary` : contour bleu marine
- `.btn-outline-secondary` : pour profil
- `.btn-outline-danger` : pour logout

**États hover :**

- Transition : `0.2s`
- Cursor : pointer (automatique Bootstrap)

### Icônes (BOOTSTRAP ICONS)

**Usage établi :**

- Toujours Bootstrap Icons (`.bi-*`)
- Icônes accentuées : classe `.icon-accent` pour couleur dorée
- Icônes dans les cards :
  - `.bi-geo-alt` : localisation
  - `.bi-calendar3` : date
  - `.bi-clock` : heure
  - `.bi-currency-euro` : prix

**Avec texte :**

- Utiliser `.d-flex .align-items-center .gap-2`
- Icon en premier, texte ensuite

### Tags et Badges

**Usage :**

- Badge catégorie : `.badge` Bootstrap avec classe de couleur
- Position sur card : `position-absolute top-0.75rem right-0.75rem`
- Toujours background primaire par défaut
- Font-size : `0.75rem` (12px)
- **Minimum 2 tags** dans les contextes de filtres/catégories

**Types :**

- Catégorie principale : `.badge .bg-primary`
- Statut/Info : `.badge .bg-warning`, `.bg-danger`, `.bg-success`
- Style pill : ajouter `.rounded-pill` si besoin

### Formulaires (STYLE ÉTABLI)

**Sections de formulaire :**

- `.form-section` : fond blanc, border, padding 1.5rem, shadow
- `.form-section-title` : couleur primaire, weight 600, border-bottom, avec icône

**Inputs :**

- Focus state : border accent + shadow dorée `rgba(201,169,97,0.25)`
- Labels : weight 500

### Modals et overlays (ÉTABLI)

**Event Detail (modal custom) :**

- `.eventDetail` : fixed inset overlay avec `rgba(0,0,0,0.5)`
- `.eventDetail-content` : max-width 900px, border-radius 0.5rem, shadow importante
- Image : height 300px (400px sur md+)

**Bootstrap modals :**

- `.modal-title` : couleur primaire, weight 600
- Max-width : 600px default, 900px pour `.modal-dialog-large`

### États et feedback

**Loading :**

- Spinner : `.spinner-border` (Bootstrap)
- Spinner accent : ajouter `.spinner-border-accent` pour couleur dorée

**Empty state :**

- `.empty-state` : text-center, padding 3rem, couleur muted
- Message clair + suggestion d'action

**Toast notifications :**

- Position : `.toast-container .position-fixed .top-0 .end-0 .p-3`
- Shadow importante pour visibilité
- Auto-dismiss après 3-4 secondes

### Classes utilitaires custom (ÉTABLIES)

**À utiliser :**

- `.line-clamp-2` : limite à 2 lignes avec ellipsis
- `.line-clamp-3` : limite à 3 lignes avec ellipsis
- `.cursor-pointer` : cursor pointer
- `.transition-all` : transition 0.2s ease-in-out
- `.hidden` : display none important

### Responsive (BREAKPOINTS BOOTSTRAP)

**Adaptation établie :**

- Mobile (< 768px) :
  - Page title : `1.5rem`
  - Card image : `180px`
  - Nav links cachés
  - Boutons en colonne si besoin
- Tablette (768px - 992px) :
  - 2 colonnes
  - Nav visible
- Desktop (> 992px) :
  - 3 colonnes
  - Toutes fonctionnalités visibles

### Animations et transitions (STANDARDS)

**Durées établies :**

- Transitions rapides : `0.2s` (buttons, links, cards)
- Transitions d'images : `0.3s` (scale effects)
- Ease : `ease-in-out` ou default

**Effets obligatoires :**

- Cards hover : translateY + shadow
- Images hover : scale (avec overflow-hidden sur parent)
- Links/buttons : smooth color transitions

### Règles de cohérence

**TOUJOURS :**

- Utiliser les variables CSS custom définies
- Border-radius entre `0.375rem` et `0.5rem`
- Shadow légère sur les cartes : `0 1px 3px rgba(0,0,0,0.1)`
- Shadow hover : `0 4px 12px rgba(0,0,0,0.15)`
- Icons Bootstrap Icons uniquement
- Grid Bootstrap pour layouts
- Gap Bootstrap (`.g-3`, `.g-4`)
- Classes utilitaires Bootstrap en priorité

**JAMAIS :**

- Changer les couleurs principales sans modifier les variables
- Utiliser d'autres couleurs que celles définies
- Mélanger différents styles de border-radius
- Oublier les états hover sur éléments interactifs
- Omettre les icônes avec classe `.icon-accent` pour l'or
