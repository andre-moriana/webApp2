# Guide de gestion des utilisateurs administrateurs

## Problème identifié
Seul l'utilisateur `admin` avec le mot de passe `admin123` peut actuellement se connecter au portail d'administration.

## Utilisateurs administrateurs dans la base de données
- **User** (username: admin, email: test@example.com) -  Fonctionne
- **Moriana** (username: janus, email: andre.moriana@free.fr) -  Mot de passe inconnu

## Solutions recommandées

### Option 1: Réinitialisation des mots de passe via l'API backend
1. Accéder à l'interface d'administration du backend
2. Réinitialiser le mot de passe pour l'utilisateur `janus` (Moriana)
3. Communiquer le nouveau mot de passe à l'utilisateur

### Option 2: Création d'un mot de passe par défaut temporaire
1. Modifier temporairement le mot de passe de `janus` dans la base de données
2. Utiliser un mot de passe simple comme `admin123` ou `janus123`
3. Demander à l'utilisateur de le changer lors de la première connexion

### Option 3: Utilisation de l'API pour créer de nouveaux comptes admin
1. Utiliser l'API pour créer de nouveaux comptes administrateurs
2. Distribuer les identifiants aux utilisateurs concernés

## Test de connexion
Pour tester la connexion d'un utilisateur admin :
1. Aller sur la page de connexion
2. Saisir le nom d'utilisateur ou l'email
3. Saisir le mot de passe
4. Vérifier que l'accès aux groupes fonctionne

## Identifiants actuellement fonctionnels
- **Nom d'utilisateur :** admin
- **Mot de passe :** admin123
- **Email :** test@example.com

## Prochaines étapes
1. Contacter l'administrateur de la base de données
2. Réinitialiser les mots de passe des autres utilisateurs admin
3. Tester la connexion avec tous les utilisateurs admin
4. Communiquer les identifiants aux utilisateurs concernés
