# Système de Gestion des Permissions

## Vue d'ensemble

Ce système permet de gérer finement les droits d'accès des utilisateurs en fonction de leur rôle au sein d'un club.

## Hiérarchie des Rôles

Le système utilise une hiérarchie de rôles où chaque niveau hérite des permissions du niveau inférieur :

1. **Archer** (niveau 1) - Accès de base
2. **Coach** (niveau 2) - Accès Archer + gestion des entraînements
3. **Dirigeant** (niveau 3) - Accès Coach + gestion complète du club
4. **Admin** (niveau 99) - Accès total à toutes les fonctionnalités, tous les clubs

### Restrictions par Club

- Les utilisateurs **Archer**, **Coach** et **Dirigeant** n'ont accès qu'aux informations de leur club
- Les utilisateurs **Admin** ont accès à tous les clubs et toutes les fonctionnalités

## Fonctionnalités Configurables

Les Dirigeants peuvent configurer les permissions de leur club via `/clubs/{id}/permissions`.

### Ressources Gérées

1. **Groupes**
   - Consulter
   - Modifier
   - Créer
   - Supprimer

2. **Événements**
   - Consulter
   - Modifier
   - Créer
   - Supprimer

3. **Utilisateurs**
   - Consulter la liste
   - Modifier ses propres informations
   - Modifier tous les utilisateurs
   - Créer
   - Supprimer

4. **Exercices**
   - Consulter
   - Créer
   - Modifier
   - Supprimer

5. **Progression d'entraînement**
   - Consulter
   - Modifier

6. **Stats des autres archers**
   - Consulter les statistiques des autres membres du club

7. **Tir compté / Chronomètre**
   - Consulter
   - Créer
   - Modifier

8. **Feuille de marque**
   - Consulter
   - Créer

9. **Carnet d'entraînement**
   - Consulter
   - Créer
   - Modifier

## Configuration des Permissions

### Accès à la Configuration

Seuls les **Dirigeants** et **Admin** peuvent configurer les permissions d'un club.

1. Aller sur la page de détails du club : `/clubs/{id}`
2. Cliquer sur le bouton **"Permissions"**
3. Configurer le rôle minimum requis pour chaque action
4. Enregistrer les modifications

### Permissions par Défaut

```php
'groups_view' => 'Archer',
'groups_edit' => 'Coach',
'groups_create' => 'Coach',
'groups_delete' => 'Dirigeant',

'events_view' => 'Archer',
'events_edit' => 'Coach',
'events_create' => 'Coach',
'events_delete' => 'Dirigeant',

'users_view' => 'Coach',
'users_self_edit' => 'Archer',
'users_edit' => 'Dirigeant',
'users_create' => 'Dirigeant',
'users_delete' => 'Dirigeant',

'exercises_view' => 'Archer',
'exercises_create' => 'Coach',
'exercises_edit' => 'Coach',
'exercises_delete' => 'Coach',

'training_progress_view' => 'Archer',
'training_progress_edit' => 'Coach',

'stats_other_view' => 'Coach',

'scored_training_view' => 'Archer',
'scored_training_create' => 'Archer',
'scored_training_edit' => 'Archer',

'score_sheet_view' => 'Archer',
'score_sheet_create' => 'Archer',

'trainings_view' => 'Archer',
'trainings_create' => 'Coach',
'trainings_edit' => 'Coach'
```

## Utilisation dans le Code

### 1. PermissionHelper (Recommandé)

#### Vérifier et rediriger automatiquement

```php
use App\Config\PermissionHelper;
use App\Services\PermissionService;

// Rediriger si l'utilisateur n'a pas la permission
PermissionHelper::requirePermission(
    PermissionService::RESOURCE_GROUPS,
    PermissionService::ACTION_CREATE,
    $clubId
);
```

#### Vérifier sans rediriger

```php
// Retourne true/false
if (PermissionHelper::can(PermissionService::RESOURCE_GROUPS, PermissionService::ACTION_EDIT, $clubId)) {
    // L'utilisateur peut modifier
}
```

#### Vérifier le rôle

```php
// Vérifier si l'utilisateur est Admin
if (PermissionHelper::isAdmin()) {
    // Code pour admin
}

// Vérifier si l'utilisateur a au moins le rôle Coach
if (PermissionHelper::hasRole('Coach')) {
    // Code pour Coach et Dirigeant
}
```

#### Vérifier l'appartenance au club

```php
if (PermissionHelper::belongsToClub($clubId)) {
    // L'utilisateur appartient au club
}
```

#### Vérifier les permissions utilisateur

```php
// Peut voir un utilisateur
if (PermissionHelper::canViewUser($targetUserId, $clubId)) {
    // Afficher les infos
}

// Peut modifier un utilisateur
if (PermissionHelper::canEditUser($targetUserId, $clubId)) {
    // Modifier
}
```

### 2. Dans les Vues PHP

```php
<!-- Afficher conditionnellement un bouton -->
<?php if (PermissionHelper::can(PermissionService::RESOURCE_GROUPS, PermissionService::ACTION_CREATE, $clubId)): ?>
    <a href="/groups/create" class="btn btn-primary">Créer un groupe</a>
<?php endif; ?>

<!-- Section réservée aux Coachs -->
<?php if (PermissionHelper::hasRole('Coach')): ?>
    <div class="coach-panel">
        <!-- Contenu pour les coachs -->
    </div>
<?php endif; ?>

<!-- Section Admin uniquement -->
<?php if (PermissionHelper::isAdmin()): ?>
    <div class="admin-panel">
        <!-- Contenu admin -->
    </div>
<?php endif; ?>
```

### 3. PermissionService (Avancé)

```php
use App\Services\PermissionService;

$permissionService = new PermissionService();

// Vérifier une permission
$hasPermission = $permissionService->hasPermission(
    $user,
    PermissionService::RESOURCE_EXERCISES,
    PermissionService::ACTION_CREATE,
    $clubId
);

// Récupérer les permissions du club
$permissions = $permissionService->getClubPermissions($clubId);

// Mettre à jour les permissions (Dirigeant/Admin uniquement)
$result = $permissionService->updateClubPermissions($clubId, $permissions, $user);
```

## Constantes Disponibles

### Ressources

```php
PermissionService::RESOURCE_GROUPS            // Groupes
PermissionService::RESOURCE_EVENTS            // Événements
PermissionService::RESOURCE_USERS             // Utilisateurs (liste)
PermissionService::RESOURCE_USERS_SELF        // Ses propres infos
PermissionService::RESOURCE_USERS_ALL         // Tous les utilisateurs
PermissionService::RESOURCE_EXERCISES         // Exercices
PermissionService::RESOURCE_TRAINING_PROGRESS // Progression
PermissionService::RESOURCE_STATS_OTHER       // Stats autres archers
PermissionService::RESOURCE_SCORED_TRAINING   // Tir compté
PermissionService::RESOURCE_SCORE_SHEET       // Feuille de marque
PermissionService::RESOURCE_TRAININGS         // Entraînements
```

### Actions

```php
PermissionService::ACTION_VIEW    // Consulter
PermissionService::ACTION_EDIT    // Modifier
PermissionService::ACTION_CREATE  // Créer
PermissionService::ACTION_DELETE  // Supprimer
PermissionService::ACTION_MANAGE  // Gérer
```

## Structure de la Base de Données

Les permissions sont stockées par club dans la base de données via l'API. Structure recommandée :

```json
{
  "clubId": 123,
  "permissions": {
    "groups_view": "Archer",
    "groups_edit": "Coach",
    "groups_create": "Coach",
    "groups_delete": "Dirigeant",
    // ... autres permissions
  }
}
```

## Intégration Backend (API)

L'API doit fournir ces endpoints :

```
GET  /api/clubs/{id}/permissions  - Récupérer les permissions du club
PUT  /api/clubs/{id}/permissions  - Mettre à jour les permissions du club
```

### Exemple de réponse GET

```json
{
  "success": true,
  "data": {
    "groups_view": "Archer",
    "groups_edit": "Coach",
    "groups_create": "Coach",
    "groups_delete": "Dirigeant",
    // ... autres permissions
  }
}
```

### Exemple de requête PUT

```json
{
  "permissions": {
    "groups_view": "Archer",
    "groups_edit": "Coach",
    // ... autres permissions
  }
}
```

## Fichiers Créés

```
app/
├── Config/
│   └── PermissionHelper.php          # Helper pour vérifier les permissions
├── Controllers/
│   └── ClubPermissionsController.php # Contrôleur de gestion des permissions
├── Services/
│   └── PermissionService.php         # Service principal de permissions
└── Views/
    └── clubs/
        └── permissions.php            # Interface de configuration

public/assets/
├── css/
│   └── club-permissions.css          # Styles de l'interface
└── js/
    └── club-permissions.js            # JavaScript de l'interface

EXEMPLE_PERMISSIONS.php                # Exemples d'utilisation
PERMISSIONS_README.md                  # Ce fichier
```

## Migration des Contrôleurs Existants

Pour intégrer le système de permissions dans vos contrôleurs existants :

1. Ajouter l'import en haut du fichier :
   ```php
   use App\Config\PermissionHelper;
   use App\Services\PermissionService;
   ```

2. Remplacer les vérifications de rôle existantes par les nouvelles permissions

3. Exemple de migration :
   ```php
   // Avant
   if (!($_SESSION['user']['is_admin'] ?? false)) {
       $_SESSION['error'] = 'Accès refusé';
       header('Location: /dashboard');
       exit;
   }
   
   // Après
   PermissionHelper::requirePermission(
       PermissionService::RESOURCE_GROUPS,
       PermissionService::ACTION_CREATE,
       $_SESSION['user']['clubId'] ?? null
   );
   ```

## Tests

Pour tester le système :

1. Créer des utilisateurs avec différents rôles (Archer, Coach, Dirigeant)
2. Se connecter avec chaque utilisateur
3. Vérifier les permissions dans `/clubs/{id}/permissions`
4. Modifier les permissions et vérifier que les changements sont appliqués
5. Essayer d'accéder à des ressources sans les permissions nécessaires

## Support

Pour toute question ou problème, consultez le fichier `EXEMPLE_PERMISSIONS.php` pour des exemples concrets d'utilisation.
