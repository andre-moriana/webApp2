# Tâches : Standardisation du numéro de licence

**Objectif :** Uniformiser l'utilisation de `numero_licence` dans tout le projet (remplacer `licenceNumber`, `licence_number`).

---

## 1. BackendPHP (API)

### 1.1 Modèle User (`models/User.php`)
- [ ] Ligne 34-36 : Ajouter `numero_licence` comme alias accepté à l'insertion (en plus de `licenceNumber` et `licence_number`)
- [ ] Vérifier que les méthodes `findById`, `getByUsername`, etc. retournent le champ avec le nom `numero_licence` (ou `licence_number` si la colonne BDD s'appelle ainsi)

### 1.2 Routes / Contrôleurs d'authentification
- [ ] Vérifier le format de la réponse login (`data.user`)
- [ ] S'assurer que la réponse contient `numero_licence` dans l'objet utilisateur retourné

### 1.3 Base de données
- [ ] Vérifier le nom de la colonne dans la table `users` (licence_number ou numero_licence)
- [ ] Si nécessaire, ajouter un alias dans les requêtes SELECT pour retourner `numero_licence`

---

## 2. WebApp2 - AuthController

**Fichier :** `app/Controllers/AuthController.php`

### 2.1 Connexion via API (ligne 33)
- [ ] Si `$result['user']` est stocké tel quel : vérifier que l'API renvoie `numero_licence`
- [ ] Sinon : normaliser la session après réception pour inclure `numero_licence`

### 2.2 Méthode authenticate() - construction manuelle de la session (ligne 146)
- [ ] Remplacer : `'licenceNumber' => $userData['licenceNumber'] ?? $userData['licence_number'] ?? ''`
- [ ] Par : `'numero_licence' => $userData['numero_licence'] ?? $userData['licence_number'] ?? $userData['licenceNumber'] ?? ''`
- [ ] Supprimer la clé `licenceNumber` de la session (ou garder en double pendant la phase de transition)

---

## 3. WebApp2 - Vues PHP

### 3.1 Vue concours show
**Fichier :** `app/Views/concours/show.php` (ligne 269)

- [ ] Remplacer : `$_SESSION['user']['licenceNumber'] ?? $_SESSION['user']['licence_number'] ?? $_SESSION['user']['numero_licence'] ?? ''`
- [ ] Par : `$_SESSION['user']['numero_licence'] ?? ''`

### 3.2 Autres vues
- [ ] Rechercher toute utilisation de `licenceNumber` ou `licence_number` dans les vues PHP
- [ ] Remplacer par `numero_licence`

---

## 4. WebApp2 - ApiService

**Fichier :** `app/Services/ApiService.php`

### 4.1 Ligne 449 (sportData)
- [ ] Remplacer `$userData['licenceNumber']` par `$userData['numero_licence'] ?? $userData['licenceNumber']`

### 4.2 Lignes 1105-1106 (registerData)
- [ ] Remplacer `licenceNumber` par `numero_licence` dans les données d'inscription envoyées à l'API
- [ ] Vérifier que l'API accepte ce champ

---

## 5. WebApp2 - JavaScript

### 5.1 concours-inscription.js
- [ ] Ligne 48, 102, 133, 386, 440, 569, 695, 2593, 3160, 3286, 3656, 3670, 3683 : uniformiser les fallbacks pour privilégier `numero_licence`
- [ ] Format cible : `archer.numero_licence ?? archer.licence_number ?? archer.licenceNumber ?? archer.IDLicence`

### 5.2 concours-inscription-simple.js
- [ ] Lignes 259, 312, 352, 962, 1259 : utiliser `numero_licence` pour les inscriptions
- [ ] Adapter les appels API si nécessaire

### 5.3 plan-cible.js
- [ ] Vérifier la cohérence (déjà en `numero_licence` pour les plans)
- [ ] Lignes 200, 204, 252, 272 : confirmer que les données reçues utilisent `numero_licence`

### 5.4 plan-peloton.js
- [ ] Lignes 90, 93, 134 : vérifier la cohérence avec `numero_licence`

---

## 6. Ordre d'exécution recommandé

1. **BackendPHP** : s'assurer que l'API renvoie `numero_licence` dans les réponses (ou au minimum `licence_number`)
2. **AuthController** : normaliser la session pour stocker `numero_licence`
3. **Vues PHP** : utiliser uniquement `$_SESSION['user']['numero_licence']`
4. **ApiService** : adapter les envois/réceptions
5. **JavaScript** : mettre à jour les lectures/écritures

---

## 7. Phase de transition (optionnel)

Pour une migration sans rupture, garder temporairement les deux clés dans la session :

```php
// Dans AuthController - phase de transition
'numero_licence' => $userData['numero_licence'] ?? $userData['licence_number'] ?? $userData['licenceNumber'] ?? '',
'licenceNumber' => $userData['numero_licence'] ?? $userData['licence_number'] ?? $userData['licenceNumber'] ?? '', // à supprimer après migration
```

Puis supprimer `licenceNumber` une fois tout le code migré.

---

## 8. Vérification finale

- [ ] Tous les utilisateurs connectés ont `numero_licence` dans leur session
- [ ] Les inscriptions concours utilisent `numero_licence` partout
- [ ] Les plans de cible et peloton utilisent `numero_licence`
- [ ] Aucune référence à `licenceNumber` ou `licence_number` dans le code PHP (sauf compatibilité API backend)
- [ ] Tests : connexion, inscription concours, modification/suppression de sa propre inscription

---

*Document créé le 18/02/2025*
