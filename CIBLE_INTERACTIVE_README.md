# Fonctionnalité de Cible Interactive

## Description
Cette fonctionnalité permet aux utilisateurs de saisir les scores des tirs comptés de deux manières :
1. **Mode Tableau** : Saisie traditionnelle via des listes déroulantes
2. **Mode Cible Interactive** : Saisie en cliquant directement sur une représentation visuelle de la cible

## Fonctionnalités

### Mode Cible Interactive
- **Cible visuelle** : Représentation SVG d'une cible FITA standard avec zones colorées
- **Sélection par drag & drop** : 
  - Clic et glisser pour placer une flèche avec précision
  - Zoom automatique autour du pointeur pendant le drag
  - Affichage en temps réel du score pendant le mouvement
  - Confirmation du score au relâchement de la souris
- **Calcul automatique du score** : Le score est calculé automatiquement selon la position
- **Mode zoom** : Possibilité de zoomer sur la cible pour une sélection plus précise
- **Gestion des flèches** : 
  - Placement séquentiel des flèches
  - Suppression individuelle par clic sur la flèche
  - Réinitialisation complète
- **Affichage des scores** : Liste des scores sélectionnés avec possibilité de suppression
- **Interface intuitive** : Indicateur de score en temps réel avec instructions

### Zones de score (Blason FITA standard)
- **Zone 10 (centre jaune)** : 10 points (rayon ≤ 5)
- **Zone 9 (jaune)** : 9 points (rayon ≤ 15)
- **Zone 8 (rouge)** : 8 points (rayon ≤ 25)
- **Zone 7 (rouge)** : 7 points (rayon ≤ 35)
- **Zone 6 (bleu)** : 6 points (rayon ≤ 45)
- **Zone 5 (bleu)** : 5 points (rayon ≤ 55)
- **Zone 4 (noir)** : 4 points (rayon ≤ 65)
- **Zone 3 (noir)** : 3 points (rayon ≤ 75)
- **Zone 2 (blanc)** : 2 points (rayon ≤ 85)
- **Zone 1 (blanc)** : 1 point (rayon ≤ 95)
- **Manqué** : 0 points (rayon > 95)

*Basé sur le standard FITA officiel comme référencé sur [Hava Archerie](https://www.hava-archerie.fr/Files/132996/Img/02/blasons-fita-40-z.jpg)*

## Utilisation

1. Ouvrir la modal d'ajout de volée
2. Sélectionner le mode "Cible interactive"
3. **Placer les flèches** :
   - Cliquer et maintenir sur la cible
   - Glisser pour ajuster la position (zoom automatique autour du pointeur)
   - Voir le score en temps réel dans l'indicateur
   - Relâcher pour confirmer le placement
4. Utiliser le bouton "Zoom" pour une vue agrandie de la cible
5. Utiliser "Réinitialiser" pour effacer toutes les flèches
6. Cliquer sur une flèche individuelle pour la supprimer
7. Enregistrer la volée normalement

## Fichiers modifiés

- `app/Views/scored-trainings/show.php` : Interface utilisateur
- `public/assets/css/scored-trainings.css` : Styles pour la cible interactive
- `public/assets/js/scored-training-show.js` : Logique JavaScript

## Compatibilité

- Compatible avec tous les types de tir (TAE, Salle, 3D, Nature, Campagne, Libre)
- Responsive design pour mobile et desktop
- Fonctionne avec les navigateurs modernes supportant SVG
