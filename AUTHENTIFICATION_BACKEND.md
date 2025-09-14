# Authentification entièrement gérée par le backend

##  Modifications apportées

### 1. Contrôleur d'authentification (app/Controllers/AuthController.php)
-  Suppression des identifiants codés en dur
-  Authentification entièrement via l'API backend
-  Vérification des droits administrateur via l'API
-  Gestion des erreurs d'authentification
-  Fonctionnalité de réinitialisation de mot de passe
-  Déconnexion via l'API backend

### 2. Service API (app/Services/ApiService.php)
-  Méthodes d'authentification complètes
-  Gestion des tokens via les sessions
-  Headers d'autorisation automatiques
-  Gestion des erreurs d'API

### 3. Interface utilisateur (app/Views/auth/login.php)
-  Suppression des identifiants codés en dur
-  Interface claire pour l'authentification
-  Lien vers la réinitialisation de mot de passe
-  Messages d'erreur informatifs

##  Fonctionnement

1. Connexion : L'utilisateur saisit ses identifiants
2. API Backend : Vérification des identifiants via auth/login
3. Token : Le backend retourne un token JWT
4. Session : Le token est stocké en session
5. Autorisation : Tous les appels API utilisent le token
6. Vérification : Vérification des droits admin via l'API
7. Déconnexion : Invalidation du token via auth/logout

##  Avantages

-  Sécurité : Authentification centralisée sur le backend
-  Cohérence : Même système d'auth pour toutes les applications
-  Flexibilité : Gestion des utilisateurs via l'API
-  Sécurité : Tokens JWT avec expiration
-  Maintenabilité : Code centralisé et réutilisable
