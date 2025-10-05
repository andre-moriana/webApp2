# Instructions de déploiement

## Problème résolu
La suppression des tirs comptés ne fonctionnait pas car :
1. La méthode getById() ne permettait pas aux admins de récupérer les tirs comptés d'autres utilisateurs
2. Les permissions de suppression n'étaient pas correctement gérées

## Modifications apportées

### 1. routes/scored_training.php
- Ajout de logs de débogage détaillés
- Amélioration de la logique de récupération des tirs comptés pour les admins
- Vérification des permissions avant suppression

### 2. models/ScoredTraining.php
- Modification de la méthode getById() pour accepter un paramètre userId optionnel
- Si userId = null, la méthode récupère le tir compté sans filtre utilisateur (pour les admins)
- Si userId est fourni, la méthode filtre par utilisateur (pour les utilisateurs normaux)

## Instructions de déploiement

1. Connectez-vous au serveur externe (82.67.123.22:25000)
2. Sauvegardez les fichiers actuels :
   - routes/scored_training.php
   - models/ScoredTraining.php

3. Remplacez les fichiers par les versions patchées :
   - scored_training_patch.php → routes/scored_training.php
   - ScoredTraining_patch.php → models/ScoredTraining.php

4. Testez la suppression d'un tir compté depuis l'interface web

## Vérification
Après déploiement, la suppression des tirs comptés devrait fonctionner pour :
- Les administrateurs (peuvent supprimer n'importe quel tir compté)
- Les coaches (peuvent supprimer n'importe quel tir compté)  
- Les utilisateurs normaux (peuvent supprimer uniquement leurs propres tirs comptés)
