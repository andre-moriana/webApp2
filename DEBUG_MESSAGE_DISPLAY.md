# Guide de débogage - Affichage des messages

## 🐛 Erreur : "Cannot read properties of undefined (reading 'name')"

### Cause

Cette erreur se produit lorsque la structure de données retournée par l'API ne correspond pas à celle attendue par le JavaScript.

### Corrections appliquées

#### 1. Backend - Modèle Message (`Message.php`)

**Problème :** Le champ `u.name` peut être NULL dans la base de données

**Solution :**
```php
// ❌ AVANT
SELECT m.*, u.name as author_name ...
FROM messages m 
INNER JOIN users u ON m.author_id = u.id

// ✅ APRÈS
SELECT m.*, 
       COALESCE(u.name, u.username, 'Utilisateur inconnu') as author_name ...
FROM messages m 
LEFT JOIN users u ON m.author_id = u.id
```

**Avantages :**
- `COALESCE` : Utilise `name`, sinon `username`, sinon 'Utilisateur inconnu'
- `LEFT JOIN` : Retourne le message même si l'utilisateur n'existe plus

#### 2. Frontend - JavaScript (`signalement-detail.js`)

**Problème :** Le code supposait que `message.author.name` existe toujours

**Solution :**
```javascript
// ❌ AVANT
const authorName = message.author.name;

// ✅ APRÈS
let authorName = 'Auteur inconnu';
if (message.author && message.author.name) {
    authorName = message.author.name;
} else if (message.author_name) {
    authorName = message.author_name;
}
```

**Avantages :**
- Gère plusieurs formats de données
- Valeur par défaut si aucune donnée disponible
- Pas d'erreur JavaScript

#### 3. Logging amélioré

**Ajouts :**
- Logs détaillés dans le contrôleur WebApp2
- Logs de structure dans le JavaScript
- Affichage de la structure complète de la réponse

## 🔍 Comment déboguer

### 1. Ouvrir la console du navigateur (F12)

Vérifier les logs :
```
Réponse complète: {success: true, message: {...}}
Structure du message: {id: 417, content: 'présent', author: {...}, ...}
Nom auteur utilisé: John Doe
```

### 2. Vérifier les logs PHP

```bash
tail -f d:/wamp64/www/BackendPHP/logs/php_errors.log
```

Chercher :
```
SignalementsController::getMessage - Réponse API: {...}
SignalementsController::getMessage - Author: {"id":123,"name":"John"}
```

### 3. Vérifier la base de données

```sql
-- Vérifier un message spécifique
SELECT m.id, 
       m.content,
       m.author_id,
       u.name as author_name,
       u.username as author_username
FROM messages m
LEFT JOIN users u ON m.author_id = u.id
WHERE m.id = 417;
```

**Vérifier :**
- Le message existe ?
- `author_id` est valide ?
- `author_name` ou `author_username` est renseigné ?

## 📊 Structure des données

### Réponse API attendue

```json
{
  "success": true,
  "message": {
    "id": 417,
    "content": "Contenu du message",
    "author": {
      "id": 123,
      "name": "John Doe"
    },
    "created_at": "2026-01-20 10:30:00",
    "attachment": null
  }
}
```

### Cas gérés

1. **Auteur normal**
   ```json
   "author": {"id": 123, "name": "John Doe"}
   ```

2. **Auteur sans nom (utilise username)**
   ```json
   "author": {"id": 123, "name": "john_doe"}
   ```
   Backend utilise `COALESCE(u.name, u.username)`

3. **Auteur supprimé**
   ```json
   "author": {"id": null, "name": "Utilisateur inconnu"}
   ```
   Backend utilise `LEFT JOIN`

4. **Format alternatif**
   ```json
   "author_name": "John Doe"
   ```
   JavaScript vérifie aussi ce format

## ⚠️ Erreurs courantes

### Erreur : "Message non trouvé"

**Cause :** Le message n'existe pas dans la DB

**Solution :**
1. Vérifier que le `message_id` dans `reports` est correct
2. Vérifier que le message n'a pas été supprimé

### Erreur : "author is null"

**Cause :** L'utilisateur auteur a été supprimé

**Solution :** ✅ Déjà géré avec `LEFT JOIN` et `COALESCE`

### Erreur : "content is undefined"

**Cause :** Le champ `content` est NULL

**Solution :** JavaScript utilise maintenant un fallback :
```javascript
${escapeHtml(message.content || 'Contenu non disponible')}
```

## 🧪 Tests

### Test 1 : Message normal

```sql
-- Message avec auteur ayant un nom
SELECT m.id, COALESCE(u.name, u.username) as author
FROM messages m
LEFT JOIN users u ON m.author_id = u.id
WHERE u.name IS NOT NULL
LIMIT 1;
```

### Test 2 : Message avec auteur sans nom

```sql
-- Message avec auteur sans nom (utilise username)
SELECT m.id, COALESCE(u.name, u.username) as author
FROM messages m
LEFT JOIN users u ON m.author_id = u.id
WHERE u.name IS NULL AND u.username IS NOT NULL
LIMIT 1;
```

### Test 3 : Message avec auteur supprimé

```sql
-- Message dont l'auteur n'existe plus
SELECT m.id, COALESCE(u.name, u.username, 'Utilisateur inconnu') as author
FROM messages m
LEFT JOIN users u ON m.author_id = u.id
WHERE u.id IS NULL
LIMIT 1;
```

## ✅ Checklist de vérification

Avant de signaler un bug :

- [ ] Console navigateur : Vérifier la structure de `data.message`
- [ ] Logs PHP : Vérifier la réponse de l'API
- [ ] Base de données : Vérifier que le message et l'auteur existent
- [ ] Network tab : Vérifier que la requête retourne 200 OK
- [ ] Vérifier que le JavaScript ne contient pas de fautes de frappe

## 🔧 Solutions de contournement temporaires

Si le problème persiste :

1. **Recharger la page** (Ctrl+F5)
2. **Vider le cache** du navigateur
3. **Vérifier la session** : Se déconnecter/reconnecter
4. **Vérifier les permissions** : L'utilisateur est-il admin ?

---

**Version :** 1.2.1  
**Date :** 20/01/2026  
**Statut :** ✅ Corrections appliquées
