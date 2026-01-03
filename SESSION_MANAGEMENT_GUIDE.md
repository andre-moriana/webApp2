# Gestion de la session pour les saisies longues

## Problème
L'application web doit :
1. Afficher la page de login quand la session est expirée
2. Ne pas interrompre la session pendant la saisie de tirs comptés ou de feuilles de marque qui peuvent durer plusieurs heures

## Solution implémentée

### 1. Augmentation de la durée de session
Dans [`index.php`](index.php), la durée de vie de la session a été prolongée à 8 heures :
```php
ini_set('session.gc_maxlifetime', 28800); // 8 heures
ini_set('session.cookie_lifetime', 28800); // 8 heures
```

### 2. Endpoint de keep-alive
Un fichier [`/public/keep-alive.php`](public/keep-alive.php) a été créé pour maintenir la session active.
- Il vérifie que l'utilisateur est connecté
- Retourne une réponse JSON avec le statut de la session
- Si la session est expirée, retourne un code 401 avec une demande de redirection

### 3. Gestionnaire de session JavaScript
Le fichier [`/public/assets/js/session-manager.js`](public/assets/js/session-manager.js) gère automatiquement :

#### Détection automatique des pages de saisie longue
Le gestionnaire détecte automatiquement les pages nécessitant un keep-alive :
- `/scored-trainings` - Tirs comptés
- `/score-sheet` - Feuille de marque
- `/trainings` - Entraînements

#### Deux modes de fonctionnement

**Mode Keep-Alive (pages de saisie longue)**
- Envoie une requête toutes les 5 minutes pour maintenir la session active
- Détecte l'activité utilisateur (clics, saisies, scrolls)
- Évite l'expiration de la session pendant la saisie

**Mode Vérification passive (autres pages)**
- Vérifie la session toutes les 30 secondes
- Ne maintient PAS la session active
- Redirige vers login si la session expire

#### Gestion de l'expiration
Quand la session expire :
1. Un message est affiché à l'utilisateur (avec SweetAlert si disponible)
2. Redirection automatique vers `/login`
3. L'URL actuelle peut être sauvegardée pour redirection après reconnexion

## Utilisation

### Automatique
Le gestionnaire de session est chargé automatiquement dans toutes les pages via [`footer.php`](app/Views/layouts/footer.php).
Aucune configuration supplémentaire n'est nécessaire.

### Contrôle manuel
Si vous avez besoin d'activer/désactiver le keep-alive manuellement :

```javascript
// Activer le keep-alive pour une modal de saisie longue
window.sessionManager.enableKeepAlive();

// Désactiver le keep-alive
window.sessionManager.disableKeepAlive();
```

## Configuration

### Modifier l'intervalle de vérification
Dans [`session-manager.js`](public/assets/js/session-manager.js) :
```javascript
new SessionManager({
    checkInterval: 5 * 60 * 1000, // 5 minutes par défaut
    keepAlivePages: [
        '/scored-trainings',
        '/score-sheet',
        '/trainings'
    ]
});
```

### Ajouter d'autres pages de saisie longue
Ajoutez simplement le chemin de la page dans le tableau `keepAlivePages` :
```javascript
keepAlivePages: [
    '/scored-trainings',
    '/score-sheet',
    '/trainings',
    '/ma-nouvelle-page'  // Votre nouvelle page
]
```

## Tests

### Test manuel
1. Connectez-vous à l'application
2. Accédez à une page de saisie longue (ex: `/scored-trainings`)
3. Ouvrez la console du navigateur
4. Vérifiez les logs : `[SessionManager] Page de saisie longue détectée`
5. Attendez 5 minutes, vous devriez voir : `[SessionManager] Session active maintenue`

### Test d'expiration
1. Réduisez `session.gc_maxlifetime` dans [`index.php`](index.php) à 60 secondes pour le test
2. Connectez-vous et accédez à une page normale (ex: `/dashboard`)
3. Attendez 60 secondes
4. Vous devriez voir l'alerte d'expiration et être redirigé vers `/login`

## Fichiers modifiés/créés

### Nouveaux fichiers
- [`/webApp2/public/keep-alive.php`](public/keep-alive.php) - Endpoint de vérification de session
- [`/webApp2/public/assets/js/session-manager.js`](public/assets/js/session-manager.js) - Gestionnaire JavaScript
- [`/webApp2/app/Middleware/SessionGuard.php`](app/Middleware/SessionGuard.php) - Middleware de vérification côté serveur

### Fichiers modifiés
- [`/webApp2/index.php`](index.php) - Augmentation de la durée de session + initialisation last_activity
- [`/webApp2/app/Views/layouts/footer.php`](app/Views/layouts/footer.php) - Inclusion du script session-manager
- [`/webApp2/app/Config/Router.php`](app/Config/Router.php) - Ajout de la route keep-alive
- [`/webApp2/app/Controllers/DashboardController.php`](app/Controllers/DashboardController.php) - Utilisation du SessionGuard
- [`/webApp2/app/Controllers/AuthController.php`](app/Controllers/AuthController.php) - Initialisation du timestamp last_activity
- [`/webApp2/app/Views/auth/login.php`](app/Views/auth/login.php) - Message d'expiration de session
- [`/webApp2/public/assets/js/login.js`](public/assets/js/login.js) - Nettoyage du flag sessionExpired

## Corrections apportées (v2)

### Problème identifié
La redirection vers la page de login ne fonctionnait pas correctement. L'utilisateur restait sur le dashboard sans données au lieu d'être redirigé automatiquement.

### Solutions implémentées

#### 1. Redirection JavaScript immédiate
- **Avant** : Utilisation de SweetAlert avec délai de 3 secondes
- **Après** : Redirection immédiate avec `window.location.replace('/login?expired=1')`
- Protection contre les redirections multiples avec `sessionStorage`

#### 2. Vérification plus fréquente
- **Avant** : Vérification toutes les 30 secondes sur les pages normales
- **Après** : Vérification toutes les **10 secondes** pour détecter rapidement l'expiration

#### 3. Middleware côté serveur (`SessionGuard`)
Nouveau middleware PHP qui vérifie :
- ✅ Présence de la session valide
- ✅ Timestamp de dernière activité (max 8 heures)
- ✅ Existence de l'utilisateur dans la session
- ✅ Redirection automatique vers `/login?expired=1`

#### 4. Tracking de l'activité
- Ajout de `$_SESSION['last_activity']` initialisé au login
- Mise à jour à chaque requête vers keep-alive.php
- Vérification du timeout de 8 heures

#### 5. Message d'expiration
- Ajout d'un message d'avertissement sur la page de login quand `?expired=1`
- Nettoyage du flag `sessionExpired` au chargement de la page de login

## Sécurité

- La session est vérifiée côté serveur à chaque requête de keep-alive
- Le cookie de session utilise les flags de sécurité (httponly, samesite)
- Les requêtes utilisent `credentials: 'same-origin'` pour la sécurité CSRF
- L'endpoint keep-alive nécessite une session valide

## Notes importantes

1. **Durée de session** : La session dure maintenant 8 heures au lieu de la durée par défaut PHP (souvent 24 minutes). Cela permet des saisies très longues sans interruption.

2. **Activité utilisateur** : Le système détecte l'activité utilisateur (clics, saisies, scrolls) mais le keep-alive est automatique même sans activité sur les pages de saisie longue.

3. **Compatibilité** : Le système utilise SweetAlert2 si disponible pour les messages d'alerte, sinon utilise les alertes natives du navigateur.

4. **Performance** : Les vérifications périodiques sont optimisées (5 minutes pour keep-alive, 30 secondes pour vérification passive).

5. **Déconnexion intentionnelle** : Si l'utilisateur clique sur "Déconnexion", le keep-alive est arrêté immédiatement.
