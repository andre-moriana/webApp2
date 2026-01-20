# Instructions de rechargement

## 🔄 Rechargement des fichiers JavaScript

Les fichiers JavaScript ont été mis à jour avec le nouveau système de logging centralisé. Pour que les changements prennent effet, vous devez **forcer le rechargement** de la page.

### ✅ Comment recharger correctement

#### Sur Windows / Linux
```
Ctrl + F5
```
ou
```
Ctrl + Shift + R
```

#### Sur Mac
```
Cmd + Shift + R
```

### 🎯 Vérifier que le rechargement a fonctionné

Après avoir rechargé la page, ouvrez la console (F12) et vous devriez voir des logs au format :

```
[2026-01-20T...] [Signalements] Page de détail chargée
```

Au lieu de simplement :
```
Page de détail du signalement chargée
```

### 📍 Pour la page actuelle (/signalements/20)

1. Appuyez sur **Ctrl + F5** pour recharger
2. Cliquez sur "Voir le message"
3. Vérifiez dans la console (F12) que vous voyez :
   ```
   [Timestamp] [Signalements] Chargement du message {messageId: 417}
   [Timestamp] [Signalements] URL de la requête {apiUrl: "..."}
   [Timestamp] [Signalements] Réponse complète {...}
   ```

### ⚠️ Si les logs ne changent pas

1. **Vider le cache complètement** :
   - Chrome : Ctrl + Shift + Del → Cocher "Images et fichiers en cache" → Effacer
   - Firefox : Ctrl + Shift + Del → Cocher "Cache" → Effacer
   - Edge : Ctrl + Shift + Del → Cocher "Images et fichiers mis en cache" → Effacer

2. **Désactiver le cache pendant le développement** :
   - Ouvrir DevTools (F12)
   - Onglet "Network"
   - Cocher "Disable cache"
   - Laisser DevTools ouvert

3. **Mode navigation privée** :
   - Ouvrir une fenêtre en navigation privée
   - Se connecter à nouveau
   - Tester

### 🔍 Fichiers JavaScript mis à jour

Les fichiers suivants ont été modifiés :

1. **`app.js`** - Système de logging centralisé (logDebug, logError)
2. **`signalement-detail.js`** - Utilise le nouveau système avec fallback
3. **`signalements.js`** - Utilise le nouveau système avec fallback

### 📊 Logs attendus après rechargement

#### En localhost (développement)
Tous les logs de débogage s'affichent :
```
[2026-01-20T15:30:00.000Z] [Signalements] Chargement du message {messageId: 417}
[2026-01-20T15:30:00.000Z] [Signalements] URL de la requête {apiUrl: "/signalements/message/417"}
[2026-01-20T15:30:00.000Z] [Signalements] Réponse complète {data: {...}, ...}
[2026-01-20T15:30:00.000Z] [Signalements] Structure du message {id: 417, ...}
[2026-01-20T15:30:00.000Z] [Signalements] Nom auteur utilisé {authorName: "..."}
```

#### En production (arctraining.fr)
Seuls les logs d'erreur s'affichent :
```
[2026-01-20T15:30:00.000Z] [ERROR] [Signalements] Erreur chargement message {...}
```

### ⚙️ Système de fallback

Si le fichier `app.js` ne se charge pas correctement, un système de fallback est en place qui utilise `console.log` directement avec un format similaire :

```
[Signalements] Chargement du message {messageId: 417}
```

(Sans le timestamp, mais avec le contexte)

### 🚀 Test rapide

Pour tester rapidement que tout fonctionne :

1. Recharger avec **Ctrl + F5**
2. Ouvrir console (F12)
3. Taper dans la console :
   ```javascript
   window.logDebug('Test', 'Message de test', {data: 'test'})
   ```
4. Vous devriez voir :
   ```
   [2026-01-20T...] [Test] Message de test {data: "test"}
   ```

Si vous voyez ce résultat, le système fonctionne ! ✅

---

**Date :** 20/01/2026  
**Version :** 1.0.0  
**Statut :** ✅ Prêt à tester
