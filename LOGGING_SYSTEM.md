# Système de logging centralisé

## 📋 Vue d'ensemble

Le système de logging centralisé permet de gérer tous les logs de manière cohérente dans l'application WebApp2.

## 🔧 Fonctions disponibles

### `window.logDebug(context, message, data)`

Affiche des logs de débogage en environnement de développement uniquement (localhost).

**Paramètres :**
- `context` (string) : Le contexte du log (ex: 'Signalements', 'ApiService', 'Dashboard')
- `message` (string) : Le message descriptif
- `data` (optional) : Données supplémentaires à afficher

**Exemple :**
```javascript
window.logDebug('Signalements', 'Chargement du message', { messageId: 417 });
// Affiche: [2026-01-20T15:30:00.000Z] [Signalements] Chargement du message {messageId: 417}
```

### `window.logError(context, message, error)`

Affiche des logs d'erreur (en production et développement).

**Paramètres :**
- `context` (string) : Le contexte de l'erreur
- `message` (string) : Le message descriptif de l'erreur
- `error` (optional) : L'objet Error ou données supplémentaires

**Exemple :**
```javascript
window.logError('ApiService', 'Erreur de requête', error);
// Affiche: [2026-01-20T15:30:00.000Z] [ERROR] [ApiService] Erreur de requête + error object
```

## 📍 Emplacement

**Fichier :** `d:\GEMENOS\WebApp2\public\assets\js\app.js`

Les fonctions sont définies globalement et disponibles dans tous les fichiers JavaScript chargés après `app.js`.

## 🎯 Avantages

1. **Centralisation** : Un seul point pour gérer tous les logs
2. **Environnement** : Les logs de débogage ne s'affichent qu'en localhost
3. **Format cohérent** : Tous les logs ont le même format avec timestamp et contexte
4. **Facilité de debug** : Chaque log indique clairement d'où il vient
5. **Évolutivité** : Facile d'ajouter des fonctionnalités (envoi vers serveur, etc.)

## 🚫 Ne plus utiliser

❌ **À éviter :**
```javascript
console.log('Message:', data);
console.error('Erreur:', error);
```

✅ **À utiliser :**
```javascript
window.logDebug('Context', 'Message', data);
window.logError('Context', 'Erreur', error);
```

## 📝 Conventions de nommage des contextes

Utilisez des noms de contexte cohérents :

- `'Signalements'` : Tout ce qui concerne les signalements
- `'ApiService'` : Appels API
- `'Dashboard'` : Tableau de bord
- `'Groups'` : Gestion des groupes
- `'Events'` : Gestion des événements
- `'Auth'` : Authentification
- `'Forms'` : Validation de formulaires
- `'Upload'` : Upload de fichiers

## 🔄 Migrations réalisées

Les fichiers suivants ont été migrés vers le nouveau système :

- ✅ `signalement-detail.js` : 8 console.log/error remplacés
- ✅ `signalements.js` : 1 console.log remplacé
- ✅ `app.js` : 1 console.error remplacé

## 🎯 Exemples d'utilisation

### Exemple 1 : Chargement de données

```javascript
function loadData(id) {
    window.logDebug('MyComponent', 'Chargement des données', { id });
    
    fetch(`/api/data/${id}`)
        .then(response => {
            window.logDebug('MyComponent', 'Réponse reçue', { status: response.status });
            return response.json();
        })
        .then(data => {
            window.logDebug('MyComponent', 'Données traitées', data);
        })
        .catch(error => {
            window.logError('MyComponent', 'Erreur chargement', error);
        });
}
```

### Exemple 2 : Validation de formulaire

```javascript
form.addEventListener('submit', function(e) {
    window.logDebug('Form', 'Soumission du formulaire');
    
    if (!validateForm()) {
        window.logError('Form', 'Validation échouée', { 
            errors: getValidationErrors() 
        });
        e.preventDefault();
    }
});
```

### Exemple 3 : Gestion d'état

```javascript
function updateState(newState) {
    window.logDebug('StateManager', 'Mise à jour de l\'état', {
        oldState: currentState,
        newState: newState
    });
    
    currentState = newState;
}
```

## 🚀 Évolutions futures possibles

1. **Niveaux de log** : warning, info, critical
2. **Envoi serveur** : Reporter les erreurs au backend
3. **Filtres** : Activer/désactiver certains contextes
4. **Persistance** : Sauvegarder les logs en localStorage
5. **Export** : Télécharger les logs pour diagnostic
6. **Sentry integration** : Envoi automatique vers Sentry

## 📚 Documentation technique

### Format du timestamp

```javascript
const timestamp = new Date().toISOString();
// Exemple: "2026-01-20T15:30:00.000Z"
```

### Détection de l'environnement

```javascript
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    // Mode développement
}
```

### Structure du log

```
[Timestamp] [Context] Message [Data]
[2026-01-20T15:30:00.000Z] [Signalements] Chargement du message {messageId: 417}

[Timestamp] [ERROR] [Context] Message [Error]
[2026-01-20T15:30:00.000Z] [ERROR] [ApiService] Erreur requête Error: 404 Not Found
```

---

**Version :** 1.0.0  
**Date :** 20/01/2026  
**Auteur :** Assistant  
**Statut :** ✅ Actif en production
