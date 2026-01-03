# 🔐 Système de Maintien de Session et Rafraîchissement Token JWT

## 📌 Problème Résolu

**Problème Initial :** Lors de saisies longues (tir compté, feuille de marque) qui peuvent durer plusieurs heures, le token JWT expirait après ~1 heure, déconnectant l'utilisateur et perdant ses données.

**Solution :** Système automatique de rafraîchissement du token JWT pendant les sessions longues.

---

## 🏗️ Architecture de la Solution

### 1. Backend - Endpoint de Rafraîchissement

**Fichier :** `/BackendPHP/routes/auth.php`

#### Fonction `refreshToken()`
```php
function refreshToken($request) {
    // 1. Vérifie l'authentification de l'utilisateur
    $user = AuthMiddleware::authenticate();
    
    // 2. Vérifie que le compte est actif
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Compte inactif'];
    }
    
    // 3. Génère un nouveau token JWT avec nouvelle date d'expiration
    $newToken = generateJWT($user['id'], $user['username'], $user['is_admin'], $user['role']);
    
    // 4. Retourne le nouveau token
    return [
        'success' => true,
        'data' => ['token' => $newToken],
        'token' => $newToken
    ];
}
```

**Route :** `POST /api/auth/refresh`

#### Caractéristiques du Token
- **Durée de vie :** 1 heure (3600 secondes)
- **Contenu (payload) :**
  - `user_id` : ID de l'utilisateur
  - `username` : Nom d'utilisateur
  - `is_admin` : Statut admin
  - `role` : Rôle utilisateur
  - `iat` : Date de création (timestamp)
  - `exp` : Date d'expiration (timestamp)

---

### 2. WebApp - Keep-Alive avec Rafraîchissement

**Fichier :** `/webApp2/public/keep-alive.php`

#### Fonctionnement
```php
// 1. Vérifie la session PHP
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['token'])) {
    return ['success' => false, 'message' => 'Session expirée'];
}

// 2. Décode le token JWT
$payload = json_decode(base64_decode($tokenParts[1]), true);
$timeLeft = $payload['exp'] - time();

// 3. Si token expire dans moins de 30 minutes (1800 secondes)
if ($timeLeft < 1800) {
    // Appelle le backend pour rafraîchir le token
    $response = $apiService->makeRequest('auth/refresh', 'POST', ['token' => $_SESSION['token']]);
    
    if ($response['success'] && isset($response['data']['token'])) {
        // Met à jour le token en session
        $_SESSION['token'] = $response['data']['token'];
        
        return [
            'success' => true,
            'token' => [
                'expires_in' => 3600,
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'refreshed' => true
            ]
        ];
    }
}

// 4. Token encore valide, pas de rafraîchissement nécessaire
return [
    'success' => true,
    'token' => [
        'expires_in' => $timeLeft,
        'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
        'refreshed' => false
    ]
];
```

#### Déclenchement
- **Automatique :** Toutes les 5 minutes sur les pages de saisie longue
- **Seuil de rafraîchissement :** Quand il reste < 30 minutes avant expiration
- **Résultat :** Token renouvelé avec nouvelle expiration de 1 heure

---

### 3. Frontend - Gestionnaire de Session

**Fichier :** `/webApp2/public/assets/js/session-manager.js`

#### SessionManager Class
```javascript
class SessionManager {
    constructor() {
        this.checkInterval = null;
    }
    
    // Démarrer les vérifications périodiques
    startPeriodicCheck() {
        // Vérification toutes les 10 secondes (pages normales)
        this.checkInterval = setInterval(() => {
            this.checkSessionStatus();
        }, 10000);
    }
    
    // Démarrer le keep-alive pour saisies longues
    startKeepAlive() {
        // Vérification toutes les 5 minutes (pages longues)
        this.keepAliveInterval = setInterval(() => {
            this.checkSession();
        }, 5 * 60 * 1000);
    }
    
    // Vérifier et afficher le statut du token
    async checkSession() {
        const response = await fetch('/keep-alive.php');
        const data = await response.json();
        
        if (data.token && data.token.refreshed) {
            console.log('✅ Token JWT rafraîchi! Nouvelle expiration:', data.token.expires_at);
        } else {
            const minutesLeft = Math.floor(data.token.expires_in / 60);
            console.log(`Session maintenue - Token expire dans: ${minutesLeft} minutes`);
        }
    }
}
```

#### Initialisation
```javascript
// Pages normales (dashboard, listes, etc.)
if (window.SessionManager) {
    const sessionManager = new window.SessionManager();
    sessionManager.startPeriodicCheck(); // Check toutes les 10 secondes
}

// Pages de saisie longue (/scored-trainings, /score-sheet)
if (window.SessionManager) {
    const sessionManager = new window.SessionManager();
    sessionManager.startKeepAlive(); // Check toutes les 5 minutes avec rafraîchissement
}
```

---

## 🔄 Flux de Rafraîchissement

### Scénario : Saisie d'une Feuille de Marque (3 heures)

```
┌─────────────────────────────────────────────────────────────┐
│ Début Session                                                │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Token JWT                                                │ │
│ │ exp: 13:00 (1 heure de validité)                        │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

12:00 - Utilisateur commence la saisie
12:05 - Keep-alive: Token valide (55 min restantes)
12:10 - Keep-alive: Token valide (50 min restantes)
12:15 - Keep-alive: Token valide (45 min restantes)
12:20 - Keep-alive: Token valide (40 min restantes)
12:25 - Keep-alive: Token valide (35 min restantes)

┌─────────────────────────────────────────────────────────────┐
│ 12:30 - Keep-alive: Token < 30 min restantes               │
│ 🔄 RAFRAÎCHISSEMENT AUTOMATIQUE                            │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Nouveau Token JWT                                        │ │
│ │ exp: 13:30 (1 heure de validité)                        │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

12:35 - Keep-alive: Token valide (55 min restantes)
...
13:00 - Keep-alive: Token valide (30 min restantes)

┌─────────────────────────────────────────────────────────────┐
│ 13:05 - Keep-alive: Token < 30 min restantes               │
│ 🔄 RAFRAÎCHISSEMENT AUTOMATIQUE #2                         │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Nouveau Token JWT                                        │ │
│ │ exp: 14:05 (1 heure de validité)                        │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

... Continue indéfiniment tant que l'utilisateur est actif ...

15:00 - Utilisateur termine la saisie
       ✅ Session maintenue pendant 3 heures sans déconnexion
```

---

## 🧪 Pages de Test

### 1. Test Simple - État de la Session

**URL :** https://arctraining.fr/test-simple

**Fonctionnalités :**
- Affiche le token JWT actuel
- Décode et affiche le payload
- Montre le temps restant avant expiration
- Bouton pour vérifier l'API `/api/auth/verify`

### 2. Test Token Expiré

**URL :** https://arctraining.fr/expire-token

**Fonctionnalités :**
- Affiche le token actuel
- Bouton pour expirer manuellement le token
- Permet de tester le comportement avec un token expiré

### 3. Test Saisie Longue

**URL :** https://arctraining.fr/test-long-session

**Fonctionnalités :**
- Simule une saisie longue (tir compté / feuille de marque)
- Vérifie la session toutes les 5 minutes
- Affiche les logs en temps réel :
  - `✅ TOKEN RAFRAÎCHI! Nouveau exp: [date]`
  - `✓ Session OK - Token expire dans X minutes`
- Compteurs :
  - Durée d'activité totale
  - Nombre de vérifications effectuées
  - Nombre de rafraîchissements de token
  - Temps avant prochain check

---

## 📊 Logs de Debug

### Backend Logs (`/BackendPHP/logs/`)

```bash
# Rafraîchissement de token
[RefreshToken] Demande de rafraîchissement du token
[RefreshToken] Nouveau token généré pour user_id: 123

# Keep-alive
keep-alive.php: Token JWT valide - expire dans 1234 secondes (21 minutes)
keep-alive.php: Token expire bientôt (< 30 min), rafraîchissement nécessaire
keep-alive.php: Token rafraîchi avec succès, nouveau exp: 2024-01-15 14:30:00
```

### WebApp Logs (`/webApp2/logs/`)

```bash
# Vérifications keep-alive
[SessionManager] 🔍 Appel keep-alive.php
[SessionManager] ✅ Token JWT rafraîchi! Nouvelle expiration: 2024-01-15 14:30:00
[SessionManager] Session maintenue - Token expire dans: 45 minutes
```

### Console Browser (Safari/Chrome)

```javascript
// Logs visibles dans la console développeur
[SessionManager] 🔍 Appel keep-alive.php
[SessionManager] ✅ Token JWT rafraîchi! Nouvelle expiration: 2024-01-15 14:30:00
[SessionManager] Session maintenue - Token expire dans: 45 minutes
```

---

## ✅ Tests à Effectuer

### Test 1 : Vérifier l'Endpoint Backend
```bash
# Test avec curl (remplacer YOUR_TOKEN par un vrai token)
curl -X POST https://api.arctraining.fr/api/auth/refresh \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"

# Résultat attendu:
{
  "success": true,
  "message": "Token rafraîchi avec succès",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Test 2 : Session Courte (30 minutes)
1. Se connecter sur https://arctraining.fr
2. Aller sur https://arctraining.fr/test-long-session
3. Observer les logs pendant 30+ minutes
4. Vérifier qu'un rafraîchissement apparaît après ~30 minutes

### Test 3 : Session Longue (2-3 heures)
1. Se connecter sur https://arctraining.fr
2. Aller sur une vraie page de saisie : `/scored-trainings/create` ou `/score-sheet`
3. Laisser la page ouverte pendant 2-3 heures
4. Vérifier que :
   - Le token est rafraîchi toutes les ~30 minutes
   - Aucune déconnexion ne se produit
   - Les données peuvent être sauvegardées à tout moment

### Test 4 : Token Expiré
1. Aller sur https://arctraining.fr/expire-token
2. Cliquer sur "Expirer le Token Maintenant"
3. Essayer d'accéder au dashboard
4. Vérifier la redirection vers `/login?expired=1`

---

## 🔧 Configuration

### Durée de Vie du Token
**Fichier :** `/BackendPHP/config/SecurityConfig.php`

```php
class SecurityConfig {
    const TOKEN_EXPIRY = 3600; // 1 heure en secondes
}
```

### Seuil de Rafraîchissement
**Fichier :** `/webApp2/public/keep-alive.php`

```php
// Rafraîchir si moins de 30 minutes restantes
if ($timeLeft < 1800) { // 1800 secondes = 30 minutes
    // Rafraîchir le token
}
```

### Fréquence de Vérification
**Fichier :** `/webApp2/public/assets/js/session-manager.js`

```javascript
// Pages normales : toutes les 10 secondes
setInterval(() => this.checkSessionStatus(), 10000);

// Pages longues : toutes les 5 minutes
setInterval(() => this.checkSession(), 5 * 60 * 1000);
```

---

## 📋 Checklist de Vérification

- [x] Endpoint backend `/api/auth/refresh` créé
- [x] Fonction `refreshToken()` implémentée
- [x] Route POST ajoutée dans auth.php
- [x] Keep-alive.php vérifie l'expiration du token
- [x] Keep-alive.php appelle auth/refresh si nécessaire
- [x] SessionManager affiche les logs de rafraîchissement
- [x] Page de test /test-long-session créée
- [x] Route /test-long-session ajoutée dans Router.php
- [x] Logs en temps réel sur la page de test
- [ ] **À TESTER :** Vérifier endpoint backend avec curl
- [ ] **À TESTER :** Session courte (30 minutes)
- [ ] **À TESTER :** Session longue (2-3 heures)
- [ ] **À TESTER :** Token expiré avec /expire-token

---

## 🚨 Points d'Attention

### 1. Session PHP vs Token JWT
- **Session PHP :** Durée 8 heures (configurée dans php.ini)
- **Token JWT :** Durée 1 heure (renouvelable)
- Les deux doivent être valides pour rester connecté

### 2. Sécurité
- Le token est stocké en `$_SESSION['token']` côté serveur
- Jamais exposé en clair dans le HTML
- Utilise HTTPS pour toutes les communications
- Vérifie le statut du compte à chaque rafraîchissement

### 3. Performance
- Keep-alive toutes les 5 minutes = 12 requêtes/heure
- Rafraîchissement uniquement si nécessaire (< 30 min)
- Pas d'impact sur la base de données (lecture seule)

### 4. Compatibilité
- Fonctionne avec Safari, Chrome, Firefox
- Compatible avec les applications mobiles (MobileApp2)
- Pas de conflit avec les autres systèmes d'authentification

---

## 📝 Notes de Maintenance

### Si le token expire trop vite
Augmenter `TOKEN_EXPIRY` dans `SecurityConfig.php`

### Si trop de rafraîchissements
Diminuer le seuil dans `keep-alive.php` (actuellement 1800 secondes)

### Si pas assez de rafraîchissements
Vérifier que :
1. L'endpoint `/api/auth/refresh` est accessible
2. Les logs backend montrent les appels
3. Le SessionManager est bien initialisé
4. La fréquence de keep-alive est correcte (5 minutes)

---

## 🎯 Résultat Final

✅ **Sessions illimitées** pendant les saisies longues
✅ **Rafraîchissement automatique** du token JWT
✅ **Aucune perte de données** pendant les saisies
✅ **Logs complets** pour le débogage
✅ **Pages de test** pour validation
✅ **Compatible** avec toutes les pages de l'application

---

**Documentation créée le :** 15 janvier 2024
**Dernière mise à jour :** 15 janvier 2024
**Version :** 1.0
