Résumé du projet – Plateforme de découverte et réservation d’événements historiques
Vue générale du projet

Ce projet consiste à développer une plateforme web permettant de découvrir et réserver des événements historiques.

La plateforme s’inspire de l’expérience utilisateur des sites de réservation comme Booking, mais elle est spécialisée dans les événements historiques, les reconstitutions historiques, les festivals médiévaux, les événements fantasy et les événements culturels similaires.

L’objectif est de permettre aux utilisateurs de :

découvrir des événements

filtrer et rechercher des événements

visualiser les événements sur une carte

réserver des billets

recevoir leurs billets numériques

La plateforme inclut trois rôles principaux :

user

organizer

admin / moderator

Découverte des événements

Les utilisateurs peuvent :

parcourir les événements

filtrer les événements par type

trier les événements par distance

rechercher par ville ou localisation

voir les événements sur une carte

Les événements sont affichés dans une interface inspirée des plateformes de réservation :

filtres sur le côté

liste d'événements

carte interactive

Intégration de la carte

La plateforme utilise l’API Google Maps.

Chaque événement possède :

latitude

longitude

Ces coordonnées permettent :

d’afficher les événements sur une carte

de calculer la distance entre l’utilisateur et l’événement

d’afficher des marqueurs interactifs

Si l’utilisateur autorise la géolocalisation, le site récupère sa position et trie automatiquement les événements du plus proche au plus éloigné.

Comptes utilisateurs

Les utilisateurs peuvent :

créer un compte

se connecter

gérer leur profil

consulter leurs réservations

consulter leurs billets

ajouter des événements à leurs favoris

Création d’événements (Organizers)

Les utilisateurs ayant le rôle organizer peuvent :

créer un événement

ajouter une description

définir une adresse

définir une date de début et de fin

ajouter des images

créer différents types de billets

modifier leurs événements

supprimer leurs événements

Cependant, toutes les créations et modifications doivent être validées par un administrateur ou un modérateur avant d’être publiées.

Système de modération

Les événements utilisent un système de validation.

Champ :

status

Valeurs possibles :

pending
approved
rejected

Lorsqu’un organizer crée un événement :

status = pending

L’événement n’est pas visible publiquement tant qu’un admin ou moderator ne l’a pas validé.

Le même système est utilisé pour les modifications d’événements.

Système de billets

Un événement peut être :

entièrement gratuit

entièrement payant

mixte (événement gratuit avec certaines activités payantes)

Champ dans la table events :

is_free

Les billets sont définis dans la table tickets.

Chaque billet possède :

id
event_id
name
description
price
quantity
start_sale_date
end_sale_date

Exemples de billets :

Adult ticket

Child ticket

Weekend pass

Concert ticket

Si un événement est gratuit et ne contient aucun billet, aucune section de réservation n’est affichée.

Si un événement possède des billets, ils peuvent être réservés directement sur la plateforme.

Système de réservation

Lorsqu’un utilisateur réserve :

il choisit un ou plusieurs billets

il procède au paiement

une commande est créée dans la table orders

Champs principaux de orders :

id
user_id
total_price
payment_status
payment_provider
created_at

Valeurs possibles pour payment_status :

pending
paid
failed
cancelled

Le paiement est traité via :

payment_provider = mollie

Les billets achetés sont stockés dans order_items.

Génération des billets

Après un paiement réussi :

des billets individuels sont générés

chaque billet possède un code unique

un QR code est généré

un billet PDF est créé

Table utilisée :

tickets_generated

Champs :

id
order_item_id
qr_code
unique_code
is_used
created_at

Chaque billet contient :

un unique_code

un QR code

les informations de l’événement

Validation des billets

Lorsqu’un billet est scanné :

Le système vérifie :

si le billet existe

si le billet est valide

si le billet n’a pas déjà été utilisé

Champ utilisé :

is_used

Si le billet est valide, il peut être marqué comme utilisé.

Envoi d’emails

Après une réservation réussie :

Le système envoie un email à l’utilisateur contenant :

confirmation de réservation

billet PDF

QR code

détails de l’événement

Le service utilisé pour l’envoi d’emails est :

SendGrid
Paiements

Les paiements sont gérés via :

Mollie

Le système doit gérer :

la création du paiement

la confirmation via webhook

la mise à jour de payment_status

Favoris

Les utilisateurs peuvent ajouter des événements à leurs favoris.

Table utilisée :

favorites

Champs :

user_id
event_id
created_at
Localisation et distance

Si l’utilisateur autorise la géolocalisation :

Le site récupère :

latitude
longitude

Le tri par distance est effectué pour afficher les événements les plus proches en premier.

Internationalisation

La plateforme sera initialement disponible uniquement en français.

Cependant, l’architecture doit être préparée pour supporter une future internationalisation.

Cela signifie que le système doit pouvoir évoluer vers une version multilingue sans modification majeure de la base de données ou de la structure du projet.

Zone géographique

La plateforme cible principalement :

la France

la Belgique

Cependant, le système doit pouvoir accepter des événements provenant de n’importe quel pays.

Objectif du projet

Créer une plateforme moderne et intuitive permettant de découvrir facilement des événements historiques et de réserver des billets en ligne.

L’interface doit rester :

moderne

épurée

inspirée des plateformes de réservation comme Booking

Le contenu des événements apportera l’identité historique, tandis que l’interface restera moderne et claire.