# Gestion des Signalements

## Vue d'ensemble

Ce module permet de gérer les signalements effectués par les utilisateurs de la plateforme. Les signalements peuvent concerner des messages, des sujets, des commentaires ou des utilisateurs.

## Fonctionnalités

### 1. Affichage dans le tableau de bord

Les signalements sont maintenant affichés dans la section "Statistiques Réseaux Sociaux" du tableau de bord (`/dashboard`).

**Informations affichées :**
- Nombre de signalements en attente
- Nombre total de signalements
- Liste des 5 derniers signalements avec :
  - Raison du signalement
  - Nom de l'utilisateur qui a signalé
  - Nom de l'utilisateur signalé (si applicable)
  - Date du signalement
  - Statut actuel
  - Lien vers les détails

**Lien rapide :**
- Bouton "Voir tout" pour accéder à la liste complète

### 2. Liste complète des signalements

Accessible via `/signalements`, cette page permet de :

**Filtrer les signalements :**
- Par statut (En attente, En cours, Résolu, Rejeté)
- Nombre d'éléments par page (10, 25, 50, 100)

**Statistiques :**
- Nombre de signalements en attente
- Nombre de signalements en cours
- Nombre de signalements résolus
- Nombre total de signalements

**Tableau de données :**
- ID du signalement
- Date de création
- Raison du signalement
- Utilisateur qui a signalé
- Utilisateur signalé
- Statut actuel
- Actions (bouton "Voir")

### 3. Détails d'un signalement

Accessible via `/signalements/{id}`, cette page permet de :

**Visualiser les informations :**
- ID du signalement
- Date de création
- Statut actuel
- Type de contenu signalé
- Raison détaillée
- Description complète
- Informations sur le rapporteur
- Informations sur l'utilisateur signalé
- ID du message concerné (si applicable)
- Date et responsable du traitement
- Notes de l'administrateur

**Actions possibles :**
- Changer le statut du signalement
- Ajouter/modifier des notes administrateur
- Voir le profil de l'utilisateur signalé
- Voir le message concerné (en développement)
- Supprimer le signalement (en développement)

**Guide d'aide :**
- Explication des statuts
- Actions recommandées

## Statuts disponibles

| Statut | Description | Badge |
|--------|-------------|-------|
| `pending` | En attente de traitement | Rouge |
| `reviewed` | En cours d'examen | Orange |
| `resolved` | Action corrective prise | Vert |
| `dismissed` | Signalement non fondé | Gris |

## Raisons de signalement

Les utilisateurs peuvent signaler du contenu pour les raisons suivantes :

- **Harcèlement** (`harassment`)
- **Spam** (`spam`)
- **Contenu inapproprié** (`inappropriate_content`)
- **Violence** (`violence`)
- **Discours de haine** (`hate_speech`)
- **Fausse information** (`fake_news`)
- **Autre** (`other`)

## Routes

### Routes WebApp (interface administrateur)

- `GET /signalements` - Liste tous les signalements
- `GET /signalements/{id}` - Affiche le détail d'un signalement
- `POST /signalements/{id}/update` - Met à jour un signalement

### Routes API Backend

- `POST /api/reports` - Créer un nouveau signalement (utilisateurs)
- `GET /api/reports` - Récupérer la liste des signalements (admin)
- `PUT /api/reports/{id}` - Mettre à jour un signalement (admin)

## Installation

### 1. Base de données

Exécuter le script de migration pour créer la table :

```bash
mysql -u username -p database_name < d:\wamp64\www\BackendPHP\database\migrations\create_reports_table.sql
```

### 2. Permissions

Les routes sont protégées et nécessitent une authentification administrateur.

### 3. Fichiers créés

**Contrôleur :**
- `d:\GEMENOS\WebApp2\app\Controllers\SignalementsController.php`

**Vues :**
- `d:\GEMENOS\WebApp2\app\Views\signalements\index.php`
- `d:\GEMENOS\WebApp2\app\Views\signalements\show.php`

**JavaScript :**
- `d:\GEMENOS\WebApp2\public\assets\js\signalements.js`
- `d:\GEMENOS\WebApp2\public\assets\js\signalement-detail.js`

**Migration SQL :**
- `d:\wamp64\www\BackendPHP\database\migrations\create_reports_table.sql`

## Utilisation

### Pour les utilisateurs

Les utilisateurs peuvent signaler du contenu via l'application mobile ou web. Le signalement est envoyé à l'API backend qui :
1. Enregistre le signalement dans la base de données
2. Envoie un email aux administrateurs
3. Retourne une confirmation

### Pour les administrateurs

Les administrateurs peuvent :

1. **Consulter les signalements** dans le tableau de bord
2. **Filtrer** les signalements par statut
3. **Examiner** les détails de chaque signalement
4. **Changer le statut** d'un signalement
5. **Ajouter des notes** pour documenter les actions prises
6. **Accéder au profil** de l'utilisateur signalé

### Workflow recommandé

1. **Nouveau signalement** → Statut `pending`
   - L'administrateur reçoit un email
   - Le signalement apparaît dans le tableau de bord

2. **Examen initial** → Statut `reviewed`
   - L'administrateur examine le contenu
   - Vérifie le profil de l'utilisateur signalé
   - Ajoute des notes sur ses observations

3. **Action finale** → Statut `resolved` ou `dismissed`
   - `resolved` : Action corrective prise (avertissement, suspension, etc.)
   - `dismissed` : Signalement non fondé
   - Notes finales ajoutées pour traçabilité

## Fonctionnalités JavaScript

### Page de liste (`signalements.js`)

- Initialisation de DataTables pour le tri et la recherche
- Auto-submit des formulaires de filtrage
- Animation des badges de statut
- Mise en évidence des signalements en attente

### Page de détail (`signalement-detail.js`)

- Validation du formulaire de mise à jour
- Auto-save des notes (brouillon local dans localStorage)
- Compteur de caractères pour les notes
- Animation du changement de statut
- Copie de l'ID au clic
- Auto-dismiss des alertes

## Améliorations futures

- [ ] Suppression de signalement
- [ ] Visualisation du message concerné
- [ ] Système de notifications en temps réel
- [ ] Historique des actions sur un signalement
- [ ] Export des signalements (CSV, PDF)
- [ ] Statistiques avancées
- [ ] Modération en masse

## Support

Pour toute question ou problème, consultez la documentation ou contactez l'équipe de développement.
