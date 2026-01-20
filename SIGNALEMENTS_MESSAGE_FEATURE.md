# Fonctionnalité "Voir le Message" - Signalements

## 📋 Vue d'ensemble

Cette fonctionnalité permet aux administrateurs de visualiser le contenu complet d'un message signalé directement depuis la page de détail d'un signalement, sans quitter l'interface de gestion.

## ✅ Modifications effectuées

### 1. Backend - Route API (`d:\wamp64\www\BackendPHP\routes\message.php`)

**Nouvelle route ajoutée :**
```php
GET /api/messages/get/{id}
```

**Fonctionnalités :**
- Récupération d'un message spécifique par son ID
- Authentification obligatoire (token JWT)
- Retourne toutes les informations du message :
  - Contenu
  - Auteur (ID et nom)
  - Date de création et modification
  - Pièces jointes (nom, type MIME, taille)
  - Contexte (groupe, événement, topic)

**Sécurité :**
- Vérification de l'authentification via `AuthMiddleware::requireAuth()`
- Retour d'erreur 404 si le message n'existe pas
- Retour d'erreur 500 en cas de problème serveur

### 2. Frontend - Vue de détail (`d:\GEMENOS\WebApp2\app\Views\signalements\show.php`)

**Modifications :**

1. **Bouton "Voir le message"** mis à jour :
```php
<button type="button" class="btn btn-outline-info btn-sm" 
        data-bs-toggle="modal" data-bs-target="#messageModal"
        onclick="loadMessage(<?php echo htmlspecialchars($report['message_id']); ?>)">
    <i class="fas fa-comment me-1"></i>
    Voir le message
</button>
```

2. **Modal Bootstrap ajoutée** :
```html
<div class="modal fade" id="messageModal">
    <!-- Affichage du message avec loader pendant le chargement -->
</div>
```

**Caractéristiques de la modal :**
- Affichage responsive (modal-lg)
- Loader pendant le chargement
- Gestion des erreurs
- Bouton de fermeture

### 3. JavaScript - Chargement dynamique (`d:\GEMENOS\WebApp2\public\assets\js\signalement-detail.js`)

**Nouvelle fonction globale :**
```javascript
window.loadMessage = function(messageId) { ... }
```

**Fonctionnalités :**
- Appel AJAX vers l'API backend
- Récupération du token depuis une meta-tag
- Affichage du loader pendant le chargement
- Formatage du contenu :
  - Nom de l'auteur
  - Date formatée en français
  - Contenu avec retours à la ligne préservés
  - Affichage des images en ligne
  - Liens de téléchargement pour les autres fichiers
- Gestion des erreurs avec messages explicites
- Échappement HTML pour la sécurité (XSS)

**Fonction utilitaire :**
```javascript
function escapeHtml(text) { ... }
```
Protection contre les attaques XSS en échappant les caractères HTML.

### 4. Header - Token API (`d:\GEMENOS\WebApp2\app\Views\layouts\header.php`)

**Meta-tag ajoutée :**
```html
<meta name="api-token" content="<?php echo $_SESSION['token'] ?? ''; ?>">
```

**Utilité :**
- Stocke le token JWT dans une meta-tag accessible en JavaScript
- Permet aux requêtes AJAX d'être authentifiées
- Sécurisé car le token est déjà en session

## 🎯 Utilisation

### Pour les administrateurs

1. **Accéder au détail d'un signalement** ayant un `message_id`
2. **Cliquer sur "Voir le message"** dans la section Actions
3. **Consulter le message** dans la modal :
   - Lire le contenu complet
   - Voir qui a posté le message
   - Vérifier la date de publication
   - Visualiser les pièces jointes
4. **Fermer la modal** et prendre les mesures appropriées

### Exemple de flux

```
1. Signalement reçu → Statut "En attente"
2. Admin clique sur "Voir détails"
3. Admin clique sur "Voir le message"
4. Modal s'ouvre → Message chargé via AJAX
5. Admin examine le contenu
6. Admin détermine si le signalement est fondé
7. Admin met à jour le statut et ajoute des notes
8. Signalement traité → Statut "Résolu" ou "Rejeté"
```

## 🔒 Sécurité

### Backend
- ✅ Authentification requise sur toutes les routes
- ✅ Vérification de l'existence du message
- ✅ Gestion des erreurs sans exposition de données sensibles
- ✅ Logs des erreurs pour le débogage

### Frontend
- ✅ Échappement HTML de tout contenu utilisateur
- ✅ Token stocké dans une meta-tag (pas dans le code JavaScript)
- ✅ Gestion des erreurs réseau
- ✅ Validation des réponses API

## 📊 Format de données

### Requête
```
GET https://arctraining.fr/api/messages/get/123
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json
```

### Réponse réussie
```json
{
  "success": true,
  "message": {
    "id": 123,
    "_id": 123,
    "content": "Ceci est un exemple de message",
    "author": {
      "id": 456,
      "name": "Jean Dupont"
    },
    "group_id": 789,
    "event_id": null,
    "topic_id": null,
    "attachment": {
      "filename": "abc123.jpg",
      "originalName": "photo.jpg",
      "mimeType": "image/jpeg",
      "size": 102400,
      "path": "/uploads/messages/abc123.jpg"
    },
    "created_at": "2026-01-20 10:30:00",
    "updated_at": "2026-01-20 10:30:00"
  }
}
```

### Réponse d'erreur
```json
{
  "error": "Message non trouvé"
}
```

## 🎨 Interface utilisateur

### Affichage du message

```
┌──────────────────────────────────────────┐
│ 👤 Jean Dupont     🕐 20/01/2026 10:30  │
├──────────────────────────────────────────┤
│                                          │
│ Ceci est un exemple de message           │
│ avec plusieurs lignes                     │
│                                          │
│ [Image affichée si présente]             │
│ ou                                       │
│ [📎 Télécharger fichier.pdf]            │
│                                          │
├──────────────────────────────────────────┤
│ ℹ️ Message ID: #123                      │
└──────────────────────────────────────────┘
```

### États de chargement

1. **Chargement** : Spinner avec texte "Chargement du message..."
2. **Succès** : Message affiché dans une card Bootstrap
3. **Erreur** : Alert danger avec message d'erreur

## 🧪 Test

### Scénarios de test

1. **Message simple (texte seulement)**
   - ✅ Affichage du contenu
   - ✅ Nom de l'auteur
   - ✅ Date formatée

2. **Message avec image**
   - ✅ Image affichée en ligne
   - ✅ Responsive (max-height: 400px)
   - ✅ Arrondi des coins

3. **Message avec fichier**
   - ✅ Lien de téléchargement
   - ✅ Nom original du fichier

4. **Erreurs**
   - ✅ Message introuvable (404)
   - ✅ Erreur réseau
   - ✅ Token invalide

### Commandes de test

```bash
# Test de la route API (avec curl)
curl -X GET "https://arctraining.fr/api/messages/get/123" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"

# Vérifier les logs
tail -f d:/wamp64/www/BackendPHP/logs/php_errors.log
```

## 📝 Notes techniques

### Compatibilité
- Bootstrap 5.3+ (pour la modal)
- Fetch API (ES6+)
- Navigateurs modernes

### Performance
- Chargement asynchrone (pas de blocage de l'UI)
- Cache navigateur pour les images
- Requête unique par message

### Limitations actuelles
- Pas de pagination pour les longs messages
- Pas de contexte (messages avant/après)
- Pas de possibilité de modération directe depuis la modal

## 🔄 Améliorations possibles

- [ ] Afficher le contexte du message (messages avant/après)
- [ ] Permettre de modérer directement depuis la modal
- [ ] Ajouter un lien vers le groupe/événement/topic
- [ ] Historique des modifications du message
- [ ] Bouton pour copier le contenu
- [ ] Export du message en PDF

## 📞 Support

Pour toute question ou problème, consulter :
- Documentation principale : `SIGNALEMENTS_README.md`
- Logs backend : `d:/wamp64/www/BackendPHP/logs/php_errors.log`
- Console navigateur : DevTools > Console

---

**Date de création** : 20/01/2026  
**Version** : 1.0  
**Statut** : ✅ Implémenté et testé
