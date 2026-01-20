# Architecture de la fonctionnalité Signalements

## 🏗️ Architecture correcte

### Flux de données

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│             │         │              │         │             │
│  Frontend   │────────▶│   WebApp2    │────────▶│  Backend    │
│  (Browser)  │  AJAX   │   Backend    │   API   │    PHP      │
│             │         │              │         │             │
└─────────────┘         └──────────────┘         └─────────────┘
     JS                   PHP Controller            PHP API
```

### ✅ Architecture actuelle (CORRECTE)

1. **Frontend (JavaScript)**
   - Fichier : `public/assets/js/signalement-detail.js`
   - Action : Appel AJAX vers `/signalements/message/{id}`
   - Auth : Session PHP (credentials: 'same-origin')

2. **Backend WebApp2 (PHP)**
   - Fichier : `app/Controllers/SignalementsController.php`
   - Méthode : `getMessage($messageId)`
   - Action : Utilise `ApiService->makeRequest()`
   - Auth : SessionGuard vérifie la session

3. **Backend API PHP**
   - Fichier : `routes/message.php`
   - Route : `GET /messages/get/{id}`
   - Action : Récupère le message depuis la DB
   - Auth : Token JWT vérifié par `AuthMiddleware`

## 🔄 Flux complet d'une requête

### 1. Utilisateur clique sur "Voir le message"

```javascript
// signalement-detail.js
window.loadMessage(messageId)
```

### 2. JavaScript appelle WebApp2

```javascript
fetch('/signalements/message/123', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json'
    },
    credentials: 'same-origin' // Envoie les cookies de session
})
```

### 3. Router WebApp2 route la requête

```php
// Router.php
GET /signalements/message/{messageId} → SignalementsController@getMessage
```

### 4. Contrôleur vérifie la session

```php
// SignalementsController.php
SessionGuard::check(); // Vérifie que l'utilisateur est connecté
```

### 5. Contrôleur appelle l'API Backend

```php
// SignalementsController.php
$response = $this->apiService->makeRequest('messages/get/' . $messageId, 'GET');
```

### 6. ApiService fait la requête HTTP

```php
// ApiService.php
- Ajoute le token JWT dans les headers
- Appelle https://api.arctraining.fr/messages/get/123
- Retourne la réponse
```

### 7. Backend API retourne le message

```php
// routes/message.php
- AuthMiddleware vérifie le token JWT
- Récupère le message depuis la DB
- Retourne JSON avec le message
```

### 8. WebApp2 retourne au Frontend

```php
echo json_encode($response); // Proxie la réponse de l'API
```

### 9. JavaScript affiche le message

```javascript
messageContent.innerHTML = `<div class="card">...</div>`;
```

## 🔐 Sécurité

### Couche 1 : Frontend → WebApp2
- **Méthode** : Session PHP
- **Vérification** : `SessionGuard::check()`
- **Cookie** : Session HTTP-only

### Couche 2 : WebApp2 → Backend API
- **Méthode** : Token JWT
- **Vérification** : `AuthMiddleware::requireAuth()`
- **Header** : `Authorization: Bearer {token}`

## 📁 Fichiers impliqués

### Frontend
```
d:\GEMENOS\WebApp2\
├── public/assets/js/
│   └── signalement-detail.js     ← Appel AJAX
└── app/Views/signalements/
    └── show.php                   ← Modal pour afficher le message
```

### Backend WebApp2
```
d:\GEMENOS\WebApp2\
├── app/Controllers/
│   └── SignalementsController.php ← Méthode getMessage()
├── app/Config/
│   └── Router.php                 ← Route /signalements/message/{id}
└── app/Services/
    └── ApiService.php             ← Appel vers l'API Backend
```

### Backend API PHP
```
d:\wamp64\www\BackendPHP\
├── routes/
│   └── message.php                ← Route GET /messages/get/{id}
└── models/
    └── Message.php                ← Récupération depuis DB
```

## ✨ Avantages de cette architecture

1. **Séparation des responsabilités**
   - Frontend : Affichage et UX
   - WebApp2 : Orchestration et sécurité session
   - Backend : Logique métier et données

2. **Sécurité renforcée**
   - Double authentification (Session + JWT)
   - Pas de token JWT exposé au JavaScript
   - CORS géré au niveau serveur

3. **Maintenabilité**
   - Changements d'API transparents pour le frontend
   - Centralisation de la logique d'appel API
   - Logs à chaque niveau

4. **Flexibilité**
   - Possibilité de cacher les réponses
   - Transformation des données si nécessaire
   - Gestion centralisée des erreurs

## 🚫 Pourquoi NE PAS appeler directement l'API Backend

### ❌ Approche incorrecte (évitée)

```javascript
// NE PAS FAIRE CELA
fetch('https://api.arctraining.fr/messages/get/123', {
    headers: {
        'Authorization': `Bearer ${token}` // Token exposé au JS
    }
})
```

**Problèmes :**
1. Token JWT exposé dans le JavaScript (sécurité)
2. Gestion CORS complexe
3. Pas de centralisation des appels API
4. Difficile de déboguer
5. Couplage fort entre frontend et API
6. Violation du principe de séparation

### ✅ Approche correcte (implémentée)

```javascript
// BON : Appel vers WebApp2
fetch('/signalements/message/123', {
    credentials: 'same-origin' // Session PHP
})
```

**Avantages :**
1. Sécurité renforcée (session + JWT)
2. Pas de problème CORS
3. Centralisation via ApiService
4. Logs à chaque niveau
5. Découplage frontend/API
6. Architecture propre

## 📊 Schéma de sécurité

```
┌──────────────────────────────────────────────────────────┐
│                                                          │
│  Browser (JavaScript)                                    │
│  - Pas de token JWT                                      │
│  - Utilise les cookies de session                        │
│                                                          │
└────────────────┬─────────────────────────────────────────┘
                 │ Session Cookie
                 ▼
┌──────────────────────────────────────────────────────────┐
│                                                          │
│  WebApp2 Backend (PHP)                                   │
│  - Vérifie la session (SessionGuard)                     │
│  - Stocke le token JWT en session                        │
│  - Ajoute le token aux requêtes API                      │
│                                                          │
└────────────────┬─────────────────────────────────────────┘
                 │ JWT Token (dans les headers)
                 ▼
┌──────────────────────────────────────────────────────────┐
│                                                          │
│  Backend API PHP                                         │
│  - Vérifie le token JWT (AuthMiddleware)                │
│  - Retourne les données                                  │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

## 🎯 Points clés à retenir

1. **Le JavaScript ne doit JAMAIS appeler directement l'API Backend**
2. **Toutes les requêtes passent par WebApp2**
3. **WebApp2 utilise ApiService pour communiquer avec l'API**
4. **La session PHP gère l'authentification frontend**
5. **Le JWT gère l'authentification backend**

---

**Date de création :** 20/01/2026  
**Version :** 1.2.0  
**Statut :** ✅ Architecture validée et implémentée
