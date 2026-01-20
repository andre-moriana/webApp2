# Test Rapide - Suppression de Signalement

## 🎯 Objectif

Vérifier que la suppression d'un signalement fonctionne correctement.

---

## ✅ Prérequis

1. Être connecté en tant qu'**administrateur**
2. Avoir au moins un signalement dans la base de données
3. Les fichiers modifiés doivent être déployés sur le serveur

---

## 🧪 Procédure de test

### Étape 1 : Accéder à la liste des signalements

1. Se connecter à : `https://arctraining.fr/signalements`
2. Vérifier que la liste des signalements s'affiche
3. Noter le nombre total de signalements

### Étape 2 : Accéder au détail d'un signalement

1. Cliquer sur un signalement dans la liste
2. Vérifier que la page de détail s'affiche correctement
3. Noter **l'ID du signalement** (ex: #123)

### Étape 3 : Vérifier le bouton de suppression

1. Localiser le bouton rouge "Supprimer le signalement"
2. Vérifier qu'il est visible et actif
3. Vérifier l'icône de corbeille (🗑️)

### Étape 4 : Tester la confirmation

1. Cliquer sur "Supprimer le signalement"
2. **Vérifier la popup de confirmation :**
   - Texte : "⚠️ ATTENTION ⚠️"
   - "Êtes-vous sûr de vouloir supprimer définitivement ce signalement ?"
   - "Cette action est irréversible."
3. Cliquer sur **Annuler**
4. Vérifier que la page reste sur le détail (pas de suppression)

### Étape 5 : Tester la suppression

1. Cliquer à nouveau sur "Supprimer le signalement"
2. Cliquer sur **OK** dans la confirmation
3. **Observer le comportement attendu :**
   - Le bouton devient désactivé
   - Le texte change en "Suppression..."
   - Un spinner apparaît
4. **Résultat attendu :**
   - Message de succès : "✅ Signalement supprimé avec succès"
   - Redirection automatique vers `/signalements`

### Étape 6 : Vérifier la suppression

1. Sur la page `/signalements`, vérifier que :
   - Le signalement supprimé n'apparaît plus dans la liste
   - Le nombre total de signalements a diminué de 1
2. Tenter d'accéder directement à l'ancien ID :
   - `https://arctraining.fr/signalements/[ID_SUPPRIME]`
   - **Résultat attendu :** Redirection vers `/signalements` (404)

---

## 🐛 Vérification de la console (F12)

### Console JavaScript (Onglet Console)

**En cas de succès :**
```
(aucune erreur)
```

**En cas d'échec :**
```
Erreur suppression signalement: [Message d'erreur]
```

### Réseau (Onglet Network)

1. Filtrer par `signalements`
2. Chercher la requête POST vers `/signalements/[ID]/delete`
3. **Vérifier la réponse :**

**Succès (200) :**
```json
{
  "success": true,
  "message": "Signalement supprimé avec succès"
}
```

**Erreur (404) :**
```json
{
  "success": false,
  "error": "Signalement non trouvé"
}
```

---

## 🔍 Vérification Backend

### Logs PHP

**Fichier :** `d:\wamp64\www\BackendPHP\logs\php_errors.log`

**Rechercher :**
```
DELETE /api/reports/[ID]
```

**Résultat attendu :**
```
[Date] Signalement [ID] supprimé par l'utilisateur [ADMIN_ID]
```

### Base de données

**Requête SQL :**
```sql
SELECT * FROM reports WHERE id = [ID_SUPPRIME];
```

**Résultat attendu :**
```
0 rows returned
```

---

## ❌ Tests d'erreur

### Test 1 : Signalement déjà supprimé

1. Noter l'ID d'un signalement supprimé
2. Tenter d'accéder à `/signalements/[ID_SUPPRIME]`
3. **Résultat attendu :** Redirection vers `/signalements`

### Test 2 : ID invalide

1. Accéder à `/signalements/999999`
2. **Résultat attendu :** Redirection vers `/signalements`

### Test 3 : Utilisateur non-admin

1. Se déconnecter
2. Se connecter avec un compte utilisateur normal
3. Tenter d'accéder à `/signalements`
4. **Résultat attendu :** Erreur 401 ou redirection vers login

### Test 4 : Session expirée

1. Laisser la session expirer (8 heures)
2. Tenter de supprimer un signalement
3. **Résultat attendu :** Redirection vers `/login`

---

## ✅ Checklist de validation

- [ ] Le bouton "Supprimer" est visible et cliquable
- [ ] La confirmation s'affiche correctement
- [ ] L'annulation ne supprime pas le signalement
- [ ] La suppression réussie affiche un message de succès
- [ ] La redirection vers `/signalements` fonctionne
- [ ] Le signalement n'apparaît plus dans la liste
- [ ] La suppression est enregistrée en base de données
- [ ] Les logs PHP ne montrent pas d'erreur
- [ ] La console JavaScript ne montre pas d'erreur
- [ ] Les tests d'erreur fonctionnent correctement

---

## 📊 Résultats attendus

| Test | Attendu | Réel | Statut |
|------|---------|------|--------|
| Affichage du bouton | ✅ Visible | | ⏳ |
| Confirmation | ✅ Popup affichée | | ⏳ |
| Annulation | ✅ Pas de suppression | | ⏳ |
| Suppression | ✅ Succès + redirect | | ⏳ |
| Vérification BDD | ✅ Signalement absent | | ⏳ |
| Test ID invalide | ✅ Redirection | | ⏳ |
| Test non-admin | ✅ Accès refusé | | ⏳ |

---

## 🆘 En cas de problème

### Problème : Bouton ne répond pas

**Solution :**
1. Ouvrir la console (F12)
2. Vérifier les erreurs JavaScript
3. Vérifier que `signalement-detail.js` est chargé
4. Vider le cache (Ctrl+F5)

### Problème : Erreur 404 sur la route

**Solution :**
1. Vérifier que `Router.php` contient les routes de suppression
2. Vérifier que le serveur Apache est redémarré
3. Vérifier le fichier `.htaccess`

### Problème : Erreur 500

**Solution :**
1. Consulter les logs PHP : `BackendPHP/logs/php_errors.log`
2. Vérifier les erreurs de base de données
3. Vérifier que l'utilisateur est admin

### Problème : Message "Route non trouvée"

**Solution :**
1. Vérifier que la route existe dans `routes/reports.php`
2. Vérifier que le backend API est accessible
3. Vérifier les logs du serveur

---

## 📝 Notes de test

**Testeur :**  
**Date :**  
**Environnement :** Production / Dev / Local  
**Navigateur :**  

**Observations :**  
...

**Bugs identifiés :**  
...

**Recommandations :**  
...

---

**Créé le :** 20/01/2026  
**Version :** 1.0
