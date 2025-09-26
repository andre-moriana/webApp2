# Guide d"utilisation des Événements

##  Fonctionnalités implémentées

###  Gestion complète des événements
- **Création** : Formulaire avec nom, description, date, lieu, nombre max de participants
- **Modification** : Édition de tous les champs
- **Suppression** : Avec confirmation
- **Affichage** : Liste et détails

###  Inscription/Désinscription
- **Boutons d"action** sur la page de détails de chaque événement
- **Statut d"inscription** affiché clairement
- **Messages de confirmation** après action

###  Filtrage temporel
- **Seuls les événements futurs** s"affichent dans la liste
- **Filtrage automatique** côté serveur
- **Dates passées exclues** automatiquement

###  Chat intégré
- **Messages de chat** pour chaque événement
- **Interface identique** aux groupes
- **Pièces jointes** supportées
- **Temps réel** avec auto-refresh

##  Comment utiliser

### 1. Accéder aux événements
- Cliquez sur **"Événements"** dans le menu de navigation
- URL directe : `http://votre-serveur/events`

### 2. Créer un événement
- Cliquez sur **"Nouvel événement"**
- Remplissez le formulaire :
  - **Nom** : Obligatoire
  - **Date et heure** : Obligatoire (doit être dans le futur)
  - **Description** : Optionnel
  - **Lieu** : Optionnel
  - **Participants max** : Optionnel (vide = illimité)

### 3. S"inscrire à un événement
- Cliquez sur un événement dans la liste
- Sur la page de détails, cliquez sur **"S"inscrire"**
- Le bouton devient **"Se désinscrire"**

### 4. Utiliser le chat
- Le chat s"affiche automatiquement
- Tapez votre message et appuyez sur Entrée
- Cliquez sur l"icône trombone pour joindre un fichier

### 5. Gérer les événements
- **Modifier** : Bouton crayon sur chaque événement
- **Supprimer** : Bouton poubelle avec confirmation
- **Voir détails** : Cliquer sur l"événement

##  Structure technique

### Fichiers créés/modifiés :
- `app/Controllers/EventController.php` - Contrôleur principal
- `app/Views/events/` - Toutes les vues
- `app/Services/ApiService.php` - Méthodes API ajoutées
- `app/Config/Router.php` - Routes ajoutées
- `public/assets/js/events-chat.js` - JavaScript du chat

### Routes disponibles :
- `GET /events` - Liste des événements
- `GET /events/create` - Formulaire de création
- `POST /events` - Création
- `GET /events/{id}` - Détails
- `GET /events/{id}/edit` - Formulaire de modification
- `PUT /events/{id}` - Modification
- `DELETE /events/{id}` - Suppression
- `POST /events/{id}/register` - Inscription
- `POST /events/{id}/unregister` - Désinscription

##  Test

Pour tester l"implémentation :
1. Accédez à `http://votre-serveur/test_events.php`
2. Vérifiez que tous les tests passent
3. Accédez à `http://votre-serveur/events`

##  Notes importantes

- **Mode démonstration** : Les données de test s"affichent pour l"admin
- **Filtrage automatique** : Seuls les événements futurs sont visibles
- **Chat intégré** : Fonctionne comme pour les groupes
- **Responsive** : Interface adaptée mobile/desktop
- **Sécurisé** : Authentification requise pour toutes les actions

L"implémentation est complète et prête à l"utilisation !
