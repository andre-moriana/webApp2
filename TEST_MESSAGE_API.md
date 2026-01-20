# Test de l'API Message

## 🔍 Diagnostic des erreurs

Si vous rencontrez l'erreur "Erreur lors du chargement du message", voici comment diagnostiquer :

### 1. Vérifier la console du navigateur

Ouvrez les DevTools (F12) et vérifiez :

```
Console > Messages
- "Chargement du message: {id}"
- "Token trouvé, longueur: {nombre}"
- "URL API: https://api.arctraining.fr/messages/get/{id}"
```

### 2. Vérifier la session

Dans la console du navigateur :

```javascript
// Vérifier que la requête utilise les cookies de session
// La session doit être active (vérifié automatiquement par WebApp2)
```

**Attendu :** Session active (vérifié par SessionGuard)  
**Problème si :** Redirection vers `/login`

### 3. Vérifier la requête réseau

DevTools > Onglet Network :

1. Filtrer par "get"
2. Cliquer sur la requête `get/{id}`
3. Vérifier :
   - **Status** : Devrait être 200
   - **Headers > Authorization** : `Bearer {token}`
   - **Response** : Contenu JSON

### 4. Tester la route WebApp2

**Note :** La route WebApp2 nécessite une session active. Il est plus simple de tester via le navigateur.

Si vous voulez tester avec curl, vous devez d'abord vous connecter et récupérer le cookie de session :

```bash
# 1. Se connecter pour obtenir le cookie de session
curl -c cookies.txt -X POST "https://arctraining.fr/auth/authenticate" \
  -d "username=admin&password=motdepasse"

# 2. Tester la route avec le cookie
curl -b cookies.txt -X GET "https://arctraining.fr/signalements/message/123"
```

**Réponse attendue :**
```json
{
  "success": true,
  "message": {
    "id": 123,
    "content": "...",
    "author": {...}
  }
}
```

### 5. Erreurs courantes

#### Erreur : Redirection vers `/login`
**Cause :** Session expirée ou non authentifié  
**Solution :**
1. Vérifier que vous êtes bien connecté
2. Se reconnecter si nécessaire
3. Vérifier les logs : SessionGuard vérifie l'authentification

#### Erreur : "401 Unauthorized" ou "403 Forbidden"
**Cause :** Pas les droits administrateur ou session invalide  
**Solution :**
1. Se déconnecter et se reconnecter
2. Vérifier que l'utilisateur a les droits admin
3. Vérifier les logs PHP : `d:/wamp64/www/BackendPHP/logs/php_errors.log`

#### Erreur : "404 Not Found"
**Cause :** La route n'existe pas ou le message n'existe pas  
**Solution :**
1. Vérifier que le message_id existe dans la base de données
2. Vérifier les logs backend

#### Erreur : "Network Error"
**Cause :** Problème de connexion ou CORS  
**Solution :**
1. Vérifier que le backend est accessible
2. Tester avec curl
3. Vérifier les CORS dans le .htaccess

### 6. Vérifier les logs backend

```bash
# Logs PHP
tail -f d:/wamp64/www/BackendPHP/logs/php_errors.log

# Chercher les erreurs liées aux messages
grep "MESSAGE ROUTER" d:/wamp64/www/BackendPHP/logs/php_errors.log
```

### 7. Vérifier que la route est bien configurée

Dans `d:\wamp64\www\BackendPHP\routes\message.php`, chercher :

```php
elseif (preg_match('/^\/get\/(\d+)$/', $path, $matches) && $method === 'GET')
```

### 8. Tester avec un message existant

SQL pour trouver des messages :

```sql
SELECT id, content, author_id 
FROM messages 
ORDER BY created_at DESC 
LIMIT 10;
```

Utiliser l'un de ces IDs pour tester.

## 🛠️ Corrections appliquées

### Version 1.2.0 - Architecture correcte (WebApp2 → API Backend)

✅ **Changements effectués :**

**Architecture :**
- ✅ JavaScript → WebApp2 Backend → API Backend PHP
- ❌ JavaScript → API Backend PHP directement (INCORRECT)

1. **URL principale** : 
   - ❌ V1.0 : `https://arctraining.fr/api/messages/get/{id}` (incorrect)
   - ❌ V1.1 : `https://api.arctraining.fr/messages/get/{id}` (appel direct API)
   - ✅ V1.2 : `/signalements/message/{id}` (passe par WebApp2)

2. **URL images** :
   - ❌ Avant : `https://api.arctraining.fr/messages/image/{filename}`
   - ✅ Après : `/messages/image/{messageId}` (passe par WebApp2)

3. **URL pièces jointes** :
   - ❌ Avant : `https://api.arctraining.fr/messages/attachment/{filename}`
   - ✅ Après : `/messages/attachment/{messageId}` (passe par WebApp2)

4. **Authentification** :
   - ❌ Avant : Token JWT dans meta-tag
   - ✅ Après : Session PHP (credentials: 'same-origin')

5. **Débogage amélioré** :
   - Logs console détaillés
   - Messages d'erreur plus explicites

### Fichiers modifiés

1. `d:\GEMENOS\WebApp2\public\assets\js\signalement-detail.js`
   - Appel vers `/signalements/message/{id}` au lieu de l'API directe
   - Utilisation de `credentials: 'same-origin'` au lieu de token JWT
   
2. `d:\GEMENOS\WebApp2\app\Controllers\SignalementsController.php`
   - Nouvelle méthode `getMessage($messageId)`
   - Utilise `ApiService` pour appeler le backend
   
3. `d:\GEMENOS\WebApp2\app\Config\Router.php`
   - Nouvelle route `GET /signalements/message/{messageId}`
   
4. `d:\GEMENOS\WebApp2\app\Views\layouts\header.php`
   - Suppression de la meta-tag `api-token` (plus nécessaire)

## 📝 Checklist avant utilisation

- [ ] La table `reports` existe dans la base de données
- [ ] Il existe des signalements avec `message_id` non null
- [ ] Les messages référencés existent dans la table `messages`
- [ ] L'utilisateur est connecté avec un token valide
- [ ] Le backend est accessible sur `https://api.arctraining.fr`

## 🎯 Test manuel

1. Se connecter à `/login`
2. Aller sur `/signalements`
3. Cliquer sur un signalement qui a un `message_id`
4. Cliquer sur "Voir le message"
5. Ouvrir la console (F12)
6. Observer les logs
7. Le message devrait s'afficher dans la modal

## 📞 Si le problème persiste

1. **Copier les logs de la console** (F12 > Console)
2. **Vérifier les erreurs réseau** (F12 > Network)
3. **Vérifier les logs PHP** : 
   ```bash
   tail -n 50 d:/wamp64/www/BackendPHP/logs/php_errors.log
   ```
4. **Fournir** :
   - Le message d'erreur exact
   - L'URL testée
   - Le status HTTP reçu
   - Les logs console

---

**Dernière mise à jour :** 20/01/2026 - Version 1.1.1  
**Fichier :** `d:\GEMENOS\WebApp2\public\assets\js\signalement-detail.js`
