# Suppression des Signalements

## 📋 Vue d'ensemble

Ce document décrit l'implémentation de la fonctionnalité de suppression des signalements dans l'application WebApp2.

**Date d'implémentation :** 20/01/2026  
**Statut :** ✅ Fonctionnel

---

## 🏗️ Architecture

### Backend API (BackendPHP)

**Fichier :** `d:\wamp64\www\BackendPHP\routes\reports.php`

#### Route DELETE

```php
DELETE /api/reports/:id
```

**Authentification :** Requise (Admin uniquement)

**Réponse Succès (200) :**
```json
{
  "success": true,
  "message": "Signalement supprimé avec succès"
}
```

**Réponse Erreur (404) :**
```json
{
  "success": false,
  "error": "Signalement non trouvé"
}
```

**Réponse Erreur (500) :**
```json
{
  "success": false,
  "error": "Erreur serveur lors de la suppression du signalement"
}
```

#### Logique de suppression

1. Vérification de l'authentification (Admin)
2. Vérification de l'existence du signalement
3. Suppression en base de données
4. Retour de la réponse JSON

---

### WebApp2 Backend

#### 1. Router

**Fichier :** `d:\GEMENOS\WebApp2\app\Config\Router.php`

Deux routes définies pour supporter POST et DELETE :

```php
$this->addRoute("POST", "/signalements/{id}/delete", "SignalementsController@delete");
$this->addRoute("DELETE", "/signalements/{id}", "SignalementsController@delete");
```

#### 2. Controller

**Fichier :** `d:\GEMENOS\WebApp2\app\Controllers\SignalementsController.php`

**Méthode :** `delete($id)`

**Fonctionnalités :**
- Vérification de session
- Détection des requêtes AJAX
- Appel à l'API backend via `ApiService`
- Gestion des réponses (JSON pour AJAX, redirection pour formulaire)
- Gestion des erreurs et messages flash

**Flux de données :**
```
Frontend → WebApp2 Controller → ApiService → Backend API → Database
                                     ↓
                              Réponse JSON
                                     ↓
                              Frontend (redirect ou JSON)
```

---

### Frontend

#### 1. Vue (HTML)

**Fichier :** `d:\GEMENOS\WebApp2\app\Views\signalements\show.php`

**Bouton de suppression :**
```html
<button type="button" class="btn btn-outline-danger btn-sm" 
        onclick="deleteReport(<?php echo htmlspecialchars($report['id']); ?>)">
    <i class="fas fa-trash me-1"></i>
    Supprimer le signalement
</button>
```

#### 2. JavaScript

**Fichier :** `d:\GEMENOS\WebApp2\public\assets\js\signalement-detail.js`

**Fonction :** `window.deleteReport(reportId)`

**Fonctionnalités :**
- Confirmation utilisateur avec message d'avertissement
- Désactivation du bouton pendant la suppression
- Requête AJAX vers le backend WebApp2
- Feedback visuel (spinner)
- Redirection vers `/signalements` en cas de succès
- Gestion des erreurs avec alert

**Code de la fonction :**
```javascript
window.deleteReport = function(reportId) {
    if (!confirm('⚠️ ATTENTION ⚠️\n\n' +
        'Êtes-vous sûr de vouloir supprimer définitivement ce signalement ?\n\n' +
        'Cette action est irréversible.')) {
        return;
    }
    
    // Désactiver le bouton
    const deleteButton = document.querySelector('[onclick*="deleteReport"]');
    if (deleteButton) {
        deleteButton.disabled = true;
        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Suppression...';
    }
    
    // Requête AJAX
    fetch(`/signalements/${reportId}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Signalement supprimé avec succès');
            window.location.href = '/signalements';
        } else {
            throw new Error(data.error || 'Erreur lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Erreur suppression signalement:', error);
        alert('❌ Erreur lors de la suppression du signalement : ' + error.message);
        
        // Réactiver le bouton
        if (deleteButton) {
            deleteButton.disabled = false;
            deleteButton.innerHTML = '<i class="fas fa-trash me-1"></i> Supprimer le signalement';
        }
    });
};
```

---

## 🔐 Sécurité

### Contrôles d'accès

1. **Backend API :**
   - Authentification JWT requise
   - Middleware Admin vérifie les permissions

2. **WebApp2 :**
   - Session PHP vérifiée via `SessionGuard::check()`
   - Seuls les administrateurs ont accès à cette fonctionnalité

### Validation

- Vérification de l'existence du signalement avant suppression
- Confirmation utilisateur obligatoire (frontend)
- Messages d'erreur descriptifs sans exposer d'informations sensibles

---

## 🎨 Expérience Utilisateur

### Workflow

1. **Page de détail du signalement**
   - L'administrateur consulte le signalement
   - Bouton "Supprimer le signalement" en rouge (danger)

2. **Confirmation**
   - Popup de confirmation avec avertissement
   - Option d'annulation

3. **Suppression**
   - Bouton désactivé avec spinner
   - Texte "Suppression..." affiché

4. **Résultat**
   - **Succès :** Message de confirmation + redirection vers la liste
   - **Échec :** Message d'erreur + bouton réactivé

### Messages

- ✅ **Succès :** "Signalement supprimé avec succès"
- ❌ **Erreur 404 :** "Signalement non trouvé"
- ❌ **Erreur 500 :** "Erreur serveur lors de la suppression du signalement"
- ⚠️ **Confirmation :** "Êtes-vous sûr de vouloir supprimer définitivement ce signalement ? Cette action est irréversible."

---

## 🧪 Tests recommandés

### Tests fonctionnels

1. **Suppression réussie**
   - Créer un signalement de test
   - Le supprimer depuis la page de détail
   - Vérifier la redirection et le message de succès
   - Confirmer la suppression en base de données

2. **Annulation**
   - Cliquer sur "Supprimer"
   - Cliquer sur "Annuler" dans la confirmation
   - Vérifier que le signalement n'est pas supprimé

3. **Signalement inexistant**
   - Tenter de supprimer un signalement avec un ID invalide
   - Vérifier le message d'erreur 404

4. **Permissions**
   - Tester avec un utilisateur non-admin
   - Vérifier le refus d'accès

### Tests de sécurité

1. **Injection SQL**
   - Tester avec des IDs malformés : `1'; DROP TABLE reports; --`
   - Vérifier que la requête préparée protège contre l'injection

2. **Authentification**
   - Tester sans être connecté
   - Vérifier la redirection vers `/login`

3. **CSRF (Cross-Site Request Forgery)**
   - La session PHP et les cookies `same-origin` offrent une protection de base

---

## 📊 Base de données

### Contraintes de clés étrangères

```sql
ALTER TABLE reports
  ADD CONSTRAINT `fk_reports_reporter` 
    FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reports_reported_user` 
    FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) 
    ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_reviewer` 
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) 
    ON DELETE SET NULL;
```

**Comportement lors de la suppression :**
- Si l'utilisateur rapporteur est supprimé → le signalement est supprimé (CASCADE)
- Si l'utilisateur signalé est supprimé → `reported_user_id` devient NULL
- Si le reviewer est supprimé → `reviewed_by` devient NULL

---

## 🚀 Déploiement

### Fichiers modifiés

1. **Backend API :**
   - `d:\wamp64\www\BackendPHP\routes\reports.php` ✅

2. **WebApp2 :**
   - `d:\GEMENOS\WebApp2\app\Config\Router.php` ✅
   - `d:\GEMENOS\WebApp2\app\Controllers\SignalementsController.php` ✅
   - `d:\GEMENOS\WebApp2\app\Views\signalements\show.php` ✅
   - `d:\GEMENOS\WebApp2\public\assets\js\signalement-detail.js` ✅

### Checklist de déploiement

- [ ] Sauvegarder la base de données
- [ ] Uploader les fichiers backend modifiés
- [ ] Uploader les fichiers WebApp2 modifiés
- [ ] Vider le cache navigateur (Ctrl+F5)
- [ ] Tester la suppression avec un signalement de test
- [ ] Vérifier les logs d'erreur PHP

---

## 🐛 Dépannage

### Erreur : "Route non trouvée"

**Cause :** Le router ne trouve pas la route  
**Solution :** Vérifier que les routes sont définies dans `Router.php`

### Erreur : "Non authentifié"

**Cause :** Session expirée ou utilisateur non-admin  
**Solution :** Se reconnecter avec un compte administrateur

### Erreur : "Signalement non trouvé"

**Cause :** Le signalement a déjà été supprimé ou l'ID est invalide  
**Solution :** Retourner à la liste des signalements

### Le bouton ne répond pas

**Cause :** Erreur JavaScript  
**Solution :** Ouvrir la console (F12) et vérifier les erreurs

---

## 📝 Améliorations futures

1. **Confirmation modale Bootstrap**
   - Remplacer `alert()` par une modale Bootstrap plus élégante

2. **Suppression en masse**
   - Permettre de sélectionner plusieurs signalements
   - Bouton "Supprimer la sélection"

3. **Restauration (soft delete)**
   - Ajouter un champ `deleted_at` au lieu de supprimer définitivement
   - Implémenter une corbeille pour restaurer les signalements

4. **Notifications en temps réel**
   - Utiliser WebSockets ou Server-Sent Events
   - Notifier les autres administrateurs de la suppression

5. **Journal d'audit**
   - Enregistrer qui a supprimé quel signalement et quand
   - Table `audit_log` pour traçabilité

---

**Développé le :** 20/01/2026  
**Auteur :** AI Assistant  
**Version :** 1.0
