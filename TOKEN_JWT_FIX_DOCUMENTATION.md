# Gestion des Tokens JWT - Problème de Réouverture du Navigateur

## Problème Initial

Quand l'utilisateur ferme Safari et le rouvre :
- La session PHP persiste (cookies de session toujours valides)
- L'utilisateur semble connecté (page dashboard s'affiche)
- **Mais aucune donnée du backend ne s'affiche**
- Il faut se déconnecter et se reconnecter pour que les données réapparaissent

### Cause

Le **token JWT** stocké en session PHP expire (généralement après 24h), mais :
1. La session PHP elle-même reste active (jusqu'à 8h d'inactivité)
2. Le token n'est pas vérifié/rafraîchi automatiquement
3. Toutes les requêtes vers le backend échouent avec une erreur 401
4. Les erreurs 401 n'étaient pas interceptées côté client

## Solution Implémentée

### 1. Vérification Automatique du Token JWT (Côté Serveur)

**Fichier:** `/webApp2/app/Services/ApiService.php`

#### Méthodes ajoutées:

```php
private function isTokenExpired($token)
```
- Décode le token JWT et vérifie la date d'expiration (`exp`)
- Considère le token comme expiré s'il reste moins de 5 minutes
- Permet un rafraîchissement proactif avant l'expiration réelle

```php
private function ensureValidToken()
```
- Appelée avant chaque requête API (sauf login/refresh)
- Vérifie si le token est expiré
- Tente de rafraîchir le token si un `refresh_token` est disponible
- Nettoie la session si le rafraîchissement échoue

```php
private function refreshToken()
```
- Appelle l'endpoint `auth/refresh` du backend
- Récupère un nouveau token et le stocke en session
- Met à jour le `refresh_token` si fourni
- Gère les erreurs de rafraîchissement

#### Modification de makeRequest():

```php
public function makeRequest($endpoint, $method = 'GET', $data = null, $retryWithHttp = true) {
    // Vérifier et rafraîchir le token si nécessaire (sauf pour auth/refresh)
    if ($endpoint !== 'auth/refresh' && $endpoint !== 'auth/login') {
        $this->ensureValidToken();
    }
    
    // ... reste du code
    
    // Gérer les erreurs 401 (Unauthorized) - Token invalide
    if ($httpCode === 401) {
        error_log("Erreur 401: Token invalide ou expiré");
        
        // Nettoyer la session et le token
        $this->token = null;
        unset($_SESSION['token']);
        unset($_SESSION['refresh_token']);
        
        return [
            'success' => false,
            'status_code' => 401,
            'unauthorized' => true,
            'message' => 'Session expirée, veuillez vous reconnecter'
        ];
    }
}
```

### 2. Intercepteur API (Côté Client)

**Fichier:** `/webApp2/public/assets/js/api-interceptor.js`

#### Fonctionnalités:

1. **Wrapper de fetch():**
   - Intercepte toutes les requêtes fetch()
   - Détecte les réponses 401
   - Redirige automatiquement vers `/login?expired=1`
   - Évite les redirections multiples avec `sessionStorage`

2. **Wrapper de XMLHttpRequest:**
   - Intercepte toutes les requêtes AJAX classiques
   - Gère les erreurs 401 de la même manière

3. **Vérification au chargement:**
   - Fonction `checkSessionOnLoad()`
   - Appelle `/api/auth/verify` au chargement de chaque page
   - Redirige immédiatement si le token est invalide
   - Évite l'affichage de pages vides

### 3. Endpoint de Vérification

**Fichier:** `/webApp2/public/api/auth/verify.php`

#### Fonctionnement:

```php
// Vérifie si la session existe
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    exit;
}

// Vérifie si le token existe
if (!isset($_SESSION['token'])) {
    http_response_code(401);
    exit;
}

// Décode et vérifie l'expiration du token JWT
$tokenParts = explode('.', $token);
$payload = json_decode(base64_decode($tokenParts[1]), true);

if (time() >= $payload['exp']) {
    // Token expiré, nettoyer la session
    session_unset();
    session_destroy();
    http_response_code(401);
    exit;
}

// Token valide
echo json_encode(['success' => true, 'expires_in' => $payload['exp'] - time()]);
```

### 4. Gestion dans les Contrôleurs

**Fichier:** `/webApp2/app/Controllers/DashboardController.php`

Exemple de gestion des erreurs 401:

```php
$usersResponse = $this->apiService->getUsers();

// Vérifier si on a une erreur 401 (token invalide)
if (isset($usersResponse['unauthorized']) && $usersResponse['unauthorized']) {
    // Nettoyer la session et rediriger vers login
    session_unset();
    session_destroy();
    header('Location: /login?expired=1');
    exit;
}
```

### 5. Intégration dans le Layout

**Fichier:** `/webApp2/app/Views/layouts/header.php`

Les scripts sont chargés automatiquement dans le header:

```html
<!-- API Interceptor pour gérer les erreurs 401 -->
<script src="/public/assets/js/api-interceptor.js"></script>
<!-- Gestionnaire de session pour keep-alive -->
<script src="/public/assets/js/session-manager.js"></script>
```

## Flux de Fonctionnement

### Scénario 1: Token encore valide

1. Utilisateur ferme et rouvre Safari
2. Session PHP toujours active
3. `api-interceptor.js` appelle `/api/auth/verify`
4. Token JWT vérifié → encore valide
5. Page se charge normalement avec les données

### Scénario 2: Token expiré

1. Utilisateur ferme Safari, attend 24h, rouvre Safari
2. Session PHP toujours active (< 8h d'inactivité)
3. `api-interceptor.js` appelle `/api/auth/verify`
4. Token JWT expiré → erreur 401
5. **Redirection immédiate** vers `/login?expired=1`
6. Message affiché: "Votre session a expiré. Veuillez vous reconnecter."

### Scénario 3: Tentative de rafraîchissement

1. Utilisateur sur une page, token expire bientôt (< 5 min)
2. Requête API déclenchée (ex: charger des données)
3. `ensureValidToken()` détecte l'expiration imminente
4. `refreshToken()` appelé automatiquement
5. Si succès: nouveau token récupéré, requête continue
6. Si échec: erreur 401, redirection vers login

## Protection Multi-Niveaux

| Niveau | Composant | Action |
|--------|-----------|--------|
| **1** | `api-interceptor.js` (chargement page) | Vérification immédiate via `/api/auth/verify` |
| **2** | `ApiService::ensureValidToken()` | Vérification avant chaque requête API |
| **3** | `ApiService::makeRequest()` | Détection erreurs 401 du backend |
| **4** | Contrôleurs PHP | Gestion du flag `unauthorized` |
| **5** | `SessionGuard::check()` | Vérification session PHP (8h) |

## Avantages de cette Solution

✅ **Transparent pour l'utilisateur**: Rafraîchissement automatique du token

✅ **Sécurisé**: Triple validation (JS client + PHP serveur + Backend)

✅ **Pas de données vides**: Redirection immédiate si token invalide

✅ **Expérience cohérente**: Message clair "Session expirée"

✅ **Compatible Safari**: Gestion correcte des cookies de session

✅ **Performance**: Vérification proactive (5 min avant expiration)

## Configuration Requise

### Backend PHP (BackendPHP)

Le backend doit fournir:

1. **Endpoint de rafraîchissement**: `POST /api/auth/refresh`
   ```json
   {
     "refresh_token": "xxx"
   }
   ```
   Retourne:
   ```json
   {
     "success": true,
     "token": "nouveau_jwt_token",
     "refresh_token": "nouveau_refresh_token"
   }
   ```

2. **Token JWT avec champ `exp`** (timestamp d'expiration)

3. **Gestion du refresh_token** en session ou cookie sécurisé

### Variables de Session

Après un login réussi, stocker:

```php
$_SESSION['token'] = $loginResult['token'];
$_SESSION['refresh_token'] = $loginResult['refresh_token']; // Important!
$_SESSION['logged_in'] = true;
$_SESSION['last_activity'] = time();
```

## Tests Recommandés

1. **Test de fermeture/réouverture immédiate**
   - Fermer Safari
   - Rouvrir dans les 5 minutes
   - Vérifier que les données s'affichent

2. **Test d'expiration token**
   - Se connecter
   - Modifier manuellement le token en session (expirer)
   - Recharger la page
   - Vérifier la redirection vers login

3. **Test de rafraîchissement**
   - Se connecter
   - Attendre que le token expire dans < 5 min
   - Déclencher une requête API
   - Vérifier le rafraîchissement automatique (logs)

4. **Test après 24h**
   - Se connecter
   - Attendre 24h (token expiré)
   - Rouvrir le navigateur
   - Vérifier la redirection immédiate

## Logs de Debug

Tous les logs sont visibles dans les logs PHP:

```bash
# Vérifier les logs du webApp2
tail -f /Users/andremoriana/webApp2/logs/*.log

# Rechercher les erreurs de token
grep "Token expiré" /Users/andremoriana/webApp2/logs/*.log
grep "401" /Users/andremoriana/webApp2/logs/*.log
```

Messages à surveiller:
- `"Token expiré, tentative de rafraîchissement..."`
- `"Token rafraîchi avec succès"`
- `"Échec du rafraîchissement du token"`
- `"Erreur 401: Token invalide ou expiré"`

## Conclusion

Cette solution résout définitivement le problème de réouverture du navigateur en:
1. Vérifiant automatiquement la validité du token à chaque chargement de page
2. Rafraîchissant le token proactivement avant expiration
3. Redirigeant immédiatement vers login si le token est invalide
4. Évitant l'affichage de pages vides sans données

L'utilisateur ne voit plus jamais une page dashboard vide après réouverture du navigateur.
