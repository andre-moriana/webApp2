# Messages Privés - Documentation

## Vue d'ensemble

Cette fonctionnalité permet aux utilisateurs d'échanger des messages privés (conversations 1-à-1) entre eux. Elle est accessible à tous les utilisateurs connectés via le menu principal.

## Fichiers créés/modifiés

### Nouveaux fichiers

1. **Contrôleur** : `app/Controllers/PrivateMessagesController.php`
   - Gère l'affichage de la page des messages privés
   - Récupère les conversations de l'utilisateur connecté
   - Récupère la liste des utilisateurs pour démarrer de nouvelles conversations

2. **Vue** : `app/Views/private-messages/index.php`
   - Interface utilisateur pour afficher les conversations
   - Liste des conversations avec indicateur de messages non lus
   - Zone de chat pour échanger des messages
   - Modal pour démarrer une nouvelle conversation
   - Support des pièces jointes (images et fichiers)

3. **JavaScript** : `public/assets/js/private-messages.js`
   - Gestion des interactions côté client
   - Chargement et affichage des messages
   - Envoi de messages (texte et pièces jointes)
   - Marquage des messages comme lus
   - Polling automatique pour les nouveaux messages (toutes les 5 secondes)
   - Recherche d'utilisateurs dans la modal

### Fichiers modifiés

1. **Router** : `app/Config/Router.php`
   - Ajout de la route `/private-messages` pour la page principale
   - Ajout des routes API pour les messages privés :
     - `GET /api/private-messages/conversations` - Liste des conversations
     - `GET /api/private-messages/{userId}/history` - Historique avec un utilisateur
     - `POST /api/private-messages/send` - Envoyer un message
     - `POST /api/private-messages/{userId}/read` - Marquer comme lu
     - `DELETE /api/private-messages/{userId}/delete` - Supprimer une conversation

2. **ApiController** : `app/Controllers/ApiController.php`
   - Ajout des méthodes de proxy vers le backend PHP :
     - `getPrivateConversations()` - Récupère toutes les conversations
     - `getPrivateHistory($userId)` - Récupère l'historique d'une conversation
     - `sendPrivateMessage()` - Envoie un message privé
     - `markPrivateMessagesAsRead($userId)` - Marque les messages comme lus
     - `deletePrivateConversation($userId)` - Supprime une conversation

3. **Header** : `app/Views/layouts/header.php`
   - Ajout de l'élément de menu "Messages" avec icône d'enveloppe
   - Positionné après "Utilisateurs" et visible par tous les utilisateurs connectés

## Fonctionnalités

### 1. Liste des conversations
- Affiche toutes les conversations de l'utilisateur avec d'autres utilisateurs
- Indicateur visuel du nombre de messages non lus (badge rouge)
- Date et heure du dernier message
- Aperçu du dernier message (50 premiers caractères)

### 2. Zone de chat
- Affichage des messages avec différenciation visuelle (messages envoyés/reçus)
- Support des pièces jointes (images affichées en miniature, autres fichiers en lien de téléchargement)
- Horodatage de chaque message
- Scroll automatique vers les nouveaux messages
- Indication de l'utilisateur avec qui on discute dans l'en-tête

### 3. Envoi de messages
- Zone de texte pour saisir le message
- Bouton d'envoi
- Support des pièces jointes (bouton trombone)
- Prévisualisation de la pièce jointe avant envoi
- Envoi avec Ctrl+Enter

### 4. Nouvelle conversation
- Modal pour rechercher et sélectionner un utilisateur
- Barre de recherche pour filtrer les utilisateurs
- Affichage du nom et de l'email des utilisateurs

### 5. Marquage automatique comme lu
- Les messages sont automatiquement marqués comme lus quand on ouvre une conversation
- Mise à jour du badge de messages non lus

### 6. Polling automatique
- Rafraîchissement automatique des messages toutes les 5 secondes
- Uniquement pour la conversation actuellement ouverte
- Arrêt automatique quand on quitte la page

### 7. Suppression de conversation
- Bouton "Supprimer" dans l'en-tête de la conversation active
- Demande de confirmation avant suppression
- Supprime tous les messages de la conversation (envoyés et reçus)
- Supprime également les pièces jointes associées
- Retire la conversation de la liste
- Réinitialise l'interface après suppression

## API Backend utilisée

Le frontend communique avec le backend PHP via les endpoints suivants :

- `GET /private-messages/conversations` - Récupère toutes les conversations
- `GET /private-messages/private/{userId}/history` - Récupère l'historique d'une conversation
- `POST /private-messages/private/send` - Envoie un message privé
- `POST /private-messages/private/{userId}/read` - Marque les messages comme lus
- `DELETE /private-messages/private/{userId}/delete` - Supprime une conversation
- `GET /private-messages/private/{userId}/unreadCount` - Compte les messages non lus

## Sécurité

- Toutes les routes nécessitent une authentification via JWT
- Les utilisateurs ne peuvent voir que leurs propres conversations
- Les messages sont marqués comme lus uniquement par le destinataire
- Validation des données côté serveur

## Interface utilisateur

- Design Bootstrap 5 avec thème vert (couleur du club)
- Responsive (adapté aux mobiles et tablettes)
- Icônes Font Awesome pour les actions
- Messages de feedback pour les erreurs et succès
- Animations et transitions fluides

## Améliorations possibles

1. **Notifications en temps réel** : Utiliser WebSocket au lieu du polling
2. **Historique paginé** : Charger les messages par lots pour les longues conversations
3. **Recherche dans les messages** : Rechercher du texte dans l'historique
4. **Suppression de messages individuels** : Permettre de supprimer un message spécifique
5. **Modification de messages** : Permettre d'éditer ses messages récents
6. **Indicateur de saisie** : Afficher quand l'autre utilisateur est en train d'écrire
7. **Réactions aux messages** : Ajouter des emojis de réaction
8. **Messages vocaux** : Support de l'enregistrement audio
9. **Partage de localisation** : Partager sa position
10. **Groupes de discussion** : Extension vers des conversations de groupe

## Dépendances

- Bootstrap 5.3.0
- Font Awesome 6.4.0
- jQuery (déjà présent dans le projet)
- API Backend PHP (BackendPHP)

## Tests à effectuer

1. Envoi de message texte simple
2. Envoi de message avec pièce jointe (image et PDF)
3. Marquage automatique comme lu
4. Recherche d'utilisateurs dans la modal
5. Polling automatique des nouveaux messages
6. Suppression d'une conversation avec confirmation
7. Suppression d'une conversation avec pièces jointes
8. Comportement sur mobile
9. Gestion des erreurs (réseau, serveur, etc.)
10. Gestion des sessions expirées

## Support

Pour toute question ou problème, consulter :
- Les logs du serveur web Apache/Nginx
- Les logs PHP (error_log)
- La console JavaScript du navigateur
- Les requêtes réseau dans l'onglet Network des DevTools
