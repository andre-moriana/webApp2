# Tests des Messages Privés

## Prérequis

1. Le backend PHP doit être démarré et accessible
2. Au moins 2 utilisateurs doivent être créés dans la base de données
3. L'utilisateur doit être connecté à l'application WebApp2

## Plan de tests

### Test 1 : Accès à la page Messages Privés

**Objectif** : Vérifier que le menu Messages est accessible et que la page se charge correctement

**Étapes** :
1. Se connecter à l'application
2. Cliquer sur le menu "Messages" dans la barre de navigation
3. Vérifier que la page se charge sans erreur

**Résultat attendu** :
- La page affiche le titre "Messages Privés"
- Le bouton "Nouvelle conversation" est visible
- La liste des conversations est affichée (vide si aucune conversation)
- La zone de chat affiche "Sélectionnez une conversation pour commencer à échanger des messages"

---

### Test 2 : Démarrer une nouvelle conversation

**Objectif** : Vérifier qu'on peut démarrer une conversation avec un autre utilisateur

**Étapes** :
1. Sur la page Messages Privés, cliquer sur "Nouvelle conversation"
2. Une modal s'ouvre avec la liste des utilisateurs
3. Taper un nom dans la barre de recherche
4. Cliquer sur un utilisateur dans la liste

**Résultat attendu** :
- La modal se ferme
- La zone de chat s'active avec le nom de l'utilisateur dans l'en-tête
- Le formulaire d'envoi de message est visible
- Un message "Aucun message pour le moment" est affiché

---

### Test 3 : Envoyer un message texte

**Objectif** : Vérifier qu'on peut envoyer un message texte simple

**Étapes** :
1. Ouvrir une conversation (Test 2)
2. Taper un message dans la zone de texte (ex: "Bonjour, comment vas-tu ?")
3. Cliquer sur le bouton "Envoyer" ou appuyer sur Ctrl+Enter

**Résultat attendu** :
- Le message apparaît dans la zone de chat
- Le message est aligné à droite avec un fond vert (message envoyé)
- La zone de texte est vidée
- L'horodatage est affiché sous le message

---

### Test 4 : Envoyer un message avec pièce jointe (image)

**Objectif** : Vérifier qu'on peut envoyer une image

**Étapes** :
1. Ouvrir une conversation
2. Cliquer sur le bouton trombone (📎)
3. Sélectionner une image (PNG, JPG, etc.)
4. Vérifier que le nom du fichier apparaît en prévisualisation
5. Optionnellement, ajouter du texte
6. Cliquer sur "Envoyer"

**Résultat attendu** :
- Le message apparaît avec l'image affichée en miniature
- L'image est cliquable et s'ouvre dans un nouvel onglet
- Le texte (si ajouté) est affiché au-dessus de l'image

---

### Test 5 : Envoyer un message avec pièce jointe (document)

**Objectif** : Vérifier qu'on peut envoyer un document (PDF, DOC, etc.)

**Étapes** :
1. Ouvrir une conversation
2. Cliquer sur le bouton trombone (📎)
3. Sélectionner un fichier PDF ou DOC
4. Vérifier que le nom du fichier apparaît en prévisualisation
5. Cliquer sur "Envoyer"

**Résultat attendu** :
- Le message apparaît avec un lien de téléchargement
- Le nom du fichier est affiché
- Le lien fonctionne et télécharge le fichier

---

### Test 6 : Supprimer une pièce jointe avant envoi

**Objectif** : Vérifier qu'on peut annuler l'ajout d'une pièce jointe

**Étapes** :
1. Ouvrir une conversation
2. Cliquer sur le bouton trombone et sélectionner un fichier
3. Cliquer sur le bouton X à côté du nom du fichier dans la prévisualisation

**Résultat attendu** :
- La prévisualisation disparaît
- Le fichier n'est pas envoyé si on clique sur "Envoyer" après

---

### Test 7 : Recevoir un message (avec 2 navigateurs/comptes)

**Objectif** : Vérifier qu'on reçoit les messages envoyés par un autre utilisateur

**Étapes** :
1. Ouvrir 2 navigateurs (ou un navigateur normal + un en navigation privée)
2. Se connecter avec 2 comptes différents
3. Dans le navigateur 1, envoyer un message à l'utilisateur du navigateur 2
4. Attendre 5 secondes (temps du polling)
5. Observer le navigateur 2

**Résultat attendu** :
- Le message apparaît dans la zone de chat du navigateur 2
- Le message est aligné à gauche avec un fond gris (message reçu)
- Le nom de l'expéditeur est affiché au-dessus du message
- Un badge rouge avec le nombre de messages non lus apparaît dans la liste des conversations

---

### Test 8 : Marquage automatique comme lu

**Objectif** : Vérifier que les messages sont marqués comme lus automatiquement

**Étapes** :
1. Avoir des messages non lus (voir Test 7)
2. Cliquer sur la conversation contenant les messages non lus
3. Observer le badge de messages non lus

**Résultat attendu** :
- Le badge disparaît après quelques secondes
- Les messages sont marqués comme lus dans la base de données

---

### Test 9 : Recherche d'utilisateurs

**Objectif** : Vérifier que la recherche d'utilisateurs fonctionne dans la modal

**Étapes** :
1. Cliquer sur "Nouvelle conversation"
2. Dans la barre de recherche, taper une partie d'un nom d'utilisateur
3. Observer la liste des utilisateurs

**Résultat attendu** :
- Seuls les utilisateurs dont le nom contient le texte saisi sont affichés
- La recherche est insensible à la casse
- Si aucun utilisateur ne correspond, la liste est vide

---

### Test 10 : Polling automatique

**Objectif** : Vérifier que les nouveaux messages sont chargés automatiquement

**Étapes** :
1. Ouvrir une conversation
2. Dans un autre navigateur/compte, envoyer un message à l'utilisateur actuel
3. Attendre 5 secondes sans rafraîchir la page

**Résultat attendu** :
- Le nouveau message apparaît automatiquement dans la zone de chat
- Pas besoin de rafraîchir la page manuellement

---

### Test 11 : Gestion des erreurs réseau

**Objectif** : Vérifier que l'application gère correctement les erreurs réseau

**Étapes** :
1. Désactiver le backend PHP (arrêter le serveur)
2. Essayer d'envoyer un message
3. Observer le comportement

**Résultat attendu** :
- Un message d'erreur est affiché (alerte rouge en haut de la page)
- Le message n'est pas envoyé
- L'application ne plante pas

---

### Test 12 : Responsive mobile

**Objectif** : Vérifier que l'interface est utilisable sur mobile

**Étapes** :
1. Ouvrir la page Messages Privés
2. Redimensionner la fenêtre du navigateur à la taille d'un mobile (ou utiliser les DevTools)
3. Tester toutes les fonctionnalités (navigation, envoi de messages, etc.)

**Résultat attendu** :
- L'interface s'adapte correctement à la taille de l'écran
- Les boutons sont cliquables
- Le texte est lisible
- Les colonnes se réorganisent verticalement si nécessaire

---

### Test 13 : Gestion de session expirée

**Objectif** : Vérifier que l'application gère correctement l'expiration de session

**Étapes** :
1. Se connecter à l'application
2. Supprimer les cookies de session (ou attendre l'expiration)
3. Essayer d'envoyer un message

**Résultat attendu** :
- L'utilisateur est redirigé vers la page de connexion
- Un message indique que la session a expiré

---

### Test 14 : Performance avec beaucoup de messages

**Objectif** : Vérifier que l'application reste performante avec beaucoup de messages

**Étapes** :
1. Créer une conversation avec plus de 50 messages (via script ou manuellement)
2. Ouvrir la conversation
3. Scroller dans l'historique
4. Envoyer un nouveau message

**Résultat attendu** :
- Le chargement des messages ne prend pas plus de 2-3 secondes
- Le scroll est fluide
- L'envoi de nouveau message fonctionne normalement

---

## Vérifications supplémentaires

### Logs du serveur

Vérifier dans les logs PHP :
- Pas d'erreur 500
- Les requêtes API sont loguées correctement
- Les tokens JWT sont valides

### Console JavaScript

Vérifier dans la console du navigateur :
- Pas d'erreur JavaScript
- Les requêtes API retournent les bonnes données
- Le polling fonctionne (requêtes toutes les 5 secondes)

### Base de données

Vérifier dans la base de données :
- Les messages sont bien enregistrés
- Les horodatages sont corrects
- Les pièces jointes sont bien liées aux messages
- Le champ `last_read_at` est mis à jour correctement

## Bugs connus et limitations

1. **Polling** : Le polling toutes les 5 secondes peut générer beaucoup de requêtes. Envisager WebSocket pour la production.
2. **Pagination** : Pas de pagination de l'historique pour le moment, peut être lent avec beaucoup de messages.
3. **Notifications** : Pas de notifications push navigateur pour le moment.
4. **Indicateur de saisie** : Pas d'indicateur quand l'autre utilisateur est en train d'écrire.

## Checklist finale

- [ ] Tous les tests passent sans erreur
- [ ] Pas d'erreur dans les logs du serveur
- [ ] Pas d'erreur dans la console JavaScript
- [ ] L'interface est responsive
- [ ] Les messages sont bien enregistrés en base de données
- [ ] Les pièces jointes sont téléchargeables
- [ ] Le polling fonctionne
- [ ] La session expirée est bien gérée
- [ ] Les erreurs réseau sont gérées gracieusement
