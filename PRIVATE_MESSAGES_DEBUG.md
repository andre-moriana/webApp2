# Guide de débogage des Messages Privés

## Problèmes connus et solutions

### Problème 1 : "Utilisateur inconnu ()" dans la conversation

**Symptômes** :
- Quand on clique sur un utilisateur dans la modal, le nom affiche "Utilisateur inconnu ()"
- L'ID utilisateur est vide

**Causes possibles** :
1. Les données utilisateurs ne sont pas correctement formatées par l'API
2. Les attributs `data-user-id` et `data-user-name` sont vides dans le HTML
3. Le JavaScript ne récupère pas correctement les données

**Solutions** :
1. **Vérifier les logs PHP** :
   - Ouvrir les logs PHP (généralement dans `/var/log/apache2/error.log` ou `/var/log/php-fpm/error.log`)
   - Rechercher les lignes commençant par `PrivateMessagesController::getAllUsers()`
   - Vérifier que les utilisateurs ont bien un ID et un nom

2. **Vérifier dans le navigateur** :
   - Ouvrir la page Messages Privés
   - Cliquer sur "Nouvelle conversation"
   - Ouvrir l'inspecteur d'éléments (F12)
   - Dans l'onglet Elements/Inspecteur, regarder le HTML de la modal
   - Vérifier que chaque élément `.user-item` a bien les attributs :
     ```html
     <a ... data-user-id="XXXXX" data-user-name="Prénom Nom">
     ```

3. **Vérifier dans la console JavaScript** :
   - Ouvrir la console (F12 > Console)
   - Rechercher les logs "User in modal: ID=..."
   - Vérifier que les IDs et noms sont présents

**Corrections apportées** :
- ✅ Ajout de debug dans le contrôleur pour voir les données des utilisateurs
- ✅ Amélioration de la construction du nom d'utilisateur (support de plusieurs formats)
- ✅ Filtrage des utilisateurs sans ID valide
- ✅ Ajout de validation dans le JavaScript avant d'ouvrir la conversation

---

### Problème 2 : Erreur 404 sur `/api/private-messages//history`

**Symptômes** :
- URL avec double slash : `/api/private-messages//history`
- Erreur 404 dans la console

**Cause** :
- L'ID utilisateur est vide ou undefined lors de l'appel à l'API

**Solution** :
1. **Vérifier que l'utilisateur a bien un ID** :
   - Dans la console, taper : `document.querySelectorAll('.user-item')`
   - Inspecter les éléments et vérifier les `dataset.userId`

2. **Vérifier les logs** :
   - Console JavaScript : "loadMessages: ID utilisateur invalide"
   - Console JavaScript : "loadMessages: Chargement des messages pour userId: XXXXX"

**Corrections apportées** :
- ✅ Validation de l'userId avant l'appel API
- ✅ Logs détaillés dans le JavaScript
- ✅ Message d'erreur clair si l'ID est invalide

---

### Problème 3 : Pas d'utilisateurs dans la modal

**Symptômes** :
- La modal affiche "Aucun utilisateur disponible"
- Mais des utilisateurs existent dans la base de données

**Causes possibles** :
1. Tous les utilisateurs sont filtrés (inactifs, ou l'utilisateur actuel)
2. L'API ne retourne pas les utilisateurs correctement
3. Erreur dans la récupération des utilisateurs

**Solution** :
1. **Vérifier les logs PHP** :
   - Rechercher "PrivateMessagesController::getAllUsers()"
   - Vérifier "Total users: X"
   - Vérifier "Filtered users: Y"
   - Si Y = 0, regarder les logs "User filtered out: ..."

2. **Vérifier l'API** :
   - Dans le navigateur, aller sur : `/api/users` (si accessible)
   - Ou tester avec Postman/curl :
     ```bash
     curl -H "Authorization: Bearer YOUR_TOKEN" http://votre-api/users
     ```

3. **Vérifier les statuts des utilisateurs** :
   - Dans la base de données MySQL, vérifier la table `users`
   - S'assurer que les utilisateurs ont `status = 'active'`

**Corrections apportées** :
- ✅ Debug détaillé du filtrage des utilisateurs
- ✅ Logs pour voir pourquoi les utilisateurs sont filtrés
- ✅ Vérification que l'ID n'est pas vide avant de filtrer

---

## Checklist de débogage

### Étape 1 : Vérifier les données utilisateurs

- [ ] Ouvrir la page Messages Privés
- [ ] Ouvrir les logs PHP (`tail -f /var/log/apache2/error.log`)
- [ ] Ouvrir la console JavaScript (F12)
- [ ] Cliquer sur "Nouvelle conversation"
- [ ] Vérifier dans les logs :
  - "Total users: X" (devrait être > 0)
  - "Premier utilisateur: {...}" (devrait contenir id, firstName, lastName, etc.)
  - "Filtered users: Y" (devrait être > 0)

### Étape 2 : Vérifier le HTML généré

- [ ] Cliquer sur "Nouvelle conversation"
- [ ] Inspecter un élément `.user-item` dans la modal
- [ ] Vérifier que `data-user-id` n'est pas vide
- [ ] Vérifier que `data-user-name` contient un nom valide

### Étape 3 : Vérifier le JavaScript

- [ ] Dans la console, vérifier les logs :
  - "User in modal: ID=..., Name=..."
  - "Click sur user-item: ..."
  - "loadMessages: Chargement des messages pour userId: ..."
- [ ] Si erreur, noter le message d'erreur exact

### Étape 4 : Vérifier l'API Backend

- [ ] Tester l'endpoint `/api/users` pour voir si les utilisateurs sont retournés
- [ ] Tester l'endpoint `/api/private-messages/conversations`
- [ ] Vérifier que le token JWT est valide (pas expiré)

---

## Commandes utiles

### Voir les logs PHP en temps réel
```bash
# Linux/Mac
tail -f /var/log/apache2/error.log

# Windows avec WAMP
# Ouvrir : C:\wamp64\logs\php_error.log
```

### Tester l'API avec curl
```bash
# Récupérer les utilisateurs
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost/api/users

# Récupérer les conversations
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost/api/private-messages/conversations
```

### Déboguer dans le navigateur
```javascript
// Dans la console JavaScript

// Voir tous les utilisateurs dans la modal
document.querySelectorAll('.user-item').forEach(item => {
    console.log({
        id: item.dataset.userId,
        name: item.dataset.userName
    });
});

// Voir l'utilisateur actuel
console.log({
    currentUserId: window.currentUserId,
    currentUserName: window.currentUserName
});
```

---

## Solutions rapides

### Si aucun utilisateur n'apparaît :
1. Vérifier dans la base de données que `status = 'active'`
2. Vérifier qu'il y a au moins 2 utilisateurs (l'actuel est filtré)
3. Vérifier que l'API Backend est accessible

### Si l'ID utilisateur est vide :
1. Vérifier le format des données dans l'API (id vs _id)
2. Vérifier que les utilisateurs ont bien un champ `id` ou `_id`
3. Vérifier les logs pour voir la structure des données

### Si erreur 404 :
1. Vérifier que les routes sont bien configurées dans `Router.php`
2. Vérifier que l'URL ne contient pas de double slash
3. Vérifier que l'ID utilisateur est bien passé dans l'URL

---

## Contact et support

Si le problème persiste après avoir suivi ce guide :

1. **Collecter les informations** :
   - Logs PHP (lignes pertinentes)
   - Logs JavaScript (console)
   - Capture d'écran de l'erreur
   - HTML d'un élément `.user-item` (inspecteur)

2. **Vérifier la base de données** :
   - Structure de la table `users`
   - Exemple d'un utilisateur (sans les données sensibles)

3. **Vérifier la configuration** :
   - Fichier `.env` (API_BASE_URL)
   - Version PHP
   - Version des dépendances
