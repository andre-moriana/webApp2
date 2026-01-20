# Corrections des Messages Privés - Format de l'API

## Problème identifié

Le backend PHP (BackendPHP) retourne les données directement en JSON, **sans** wrapper avec `{success: true, data: ...}`.

Par exemple :
- `/private-messages/conversations` retourne directement : `[{...}, {...}]`  
  Au lieu de : `{success: true, data: [{...}, {...}]}`

- `/private-messages/private/{userId}/history` retourne directement : `[{...}, {...}]`  
  Au lieu de : `{success: true, data: [{...}, {...}]}`

Cela causait des problèmes car le code WebApp2 s'attendait au format avec `success` et `data`.

## Corrections apportées

### 1. PrivateMessagesController.php

**Méthode `getConversations()`** :
- ✅ Détecte si la réponse est au format standard (`{success, data}`) ou format direct (tableau)
- ✅ Gère les deux formats correctement
- ✅ Ajoute des logs détaillés pour le débogage

### 2. ApiController.php

**Méthode `getPrivateConversations()`** :
- ✅ Retourne directement le tableau si c'est déjà un tableau
- ✅ Gère les erreurs avec `{error: ...}`
- ✅ Ajoute des logs pour le débogage

**Méthode `getPrivateHistory($userId)`** :
- ✅ Retourne directement le tableau de messages
- ✅ Gère les erreurs avec `{error: ...}`
- ✅ Logs de débogage

**Méthode `sendPrivateMessage()`** :
- ✅ Retourne directement l'objet message créé
- ✅ Gère les erreurs avec `{error: ...}`
- ✅ Logs de débogage

**Méthode `markPrivateMessagesAsRead($userId)`** :
- À vérifier selon le format retourné par le backend

## Format attendu du backend

### GET /private-messages/conversations
```json
[
  {
    "other_user": {
      "_id": "userId",
      "name": "Nom Prénom",
      ...
    },
    "last_message": "Contenu du dernier message",
    "last_message_date": "2026-01-20 12:34:56",
    "unread_count": 2
  },
  ...
]
```

### GET /private-messages/private/{userId}/history
```json
[
  {
    "_id": "messageId",
    "content": "Contenu du message",
    "author": {
      "_id": "authorId",
      "name": "Nom Auteur"
    },
    "recipient": "recipientId",
    "createdAt": "2026-01-20 12:34:56",
    "attachment": null ou {...}
  },
  ...
]
```

### POST /private-messages/private/send
```json
{
  "_id": "messageId",
  "content": "Contenu",
  "author": {
    "_id": "authorId",
    "name": "Nom"
  },
  "recipient": "recipientId",
  "createdAt": "2026-01-20 12:34:56",
  "updatedAt": "2026-01-20 12:34:56",
  "attachment": null ou {...}
}
```

### Erreurs
```json
{
  "error": "Message d'erreur"
}
```

## Tests à effectuer

1. **Rafraîchir la page Messages Privés**
   - Vérifier que les logs affichent : "Direct array format, count: X"
   - Vérifier que la liste des conversations s'affiche (même si vide au début)

2. **Créer une nouvelle conversation**
   - Cliquer sur "Nouvelle conversation"
   - Sélectionner un utilisateur
   - Vérifier que le nom s'affiche correctement

3. **Envoyer un message**
   - Envoyer un message texte
   - Vérifier qu'il apparaît dans la zone de chat

4. **Vérifier les logs**
   - Logs PHP : `tail -f /chemin/vers/error.log`
   - Rechercher : "PrivateMessagesController::getConversations()"
   - Rechercher : "ApiController::getPrivateConversations()"

## Commandes de débogage

### Voir les logs en temps réel
```bash
# Linux/Mac
tail -f /var/log/apache2/error.log | grep "PrivateMessages"

# Windows WAMP
# Ouvrir : C:\wamp64\logs\php_error.log
```

### Tester l'API directement
```bash
# Avec curl (remplacer YOUR_JWT_TOKEN)
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost/private-messages/conversations

# Devrait retourner un tableau JSON
```

### Console JavaScript
```javascript
// Dans la console du navigateur (F12)

// Test de récupération des conversations
fetch('/api/private-messages/conversations')
  .then(r => r.json())
  .then(data => console.log('Conversations:', data));
```

## Prochaines étapes

Si la liste des conversations s'affiche maintenant mais est vide :
1. C'est normal si vous n'avez pas encore envoyé de messages
2. Testez en envoyant un message à un autre utilisateur
3. Rafraîchissez la page - la conversation devrait apparaître

Si des erreurs persistent :
1. Consulter `PRIVATE_MESSAGES_DEBUG.md`
2. Utiliser la page de test : `/test-private-messages.php`
3. Vérifier les logs détaillés ajoutés dans le code
