# Filtre dynamique des comités régionaux et départementaux

## 🎯 Fonctionnalité

Dans la page de gestion des clubs (`/clubs`), un filtre intelligent permet de filtrer les comités départementaux en fonction du comité régional sélectionné.

## 📋 Comment ça fonctionne

### Principe de base

Les clubs en France sont organisés hiérarchiquement :
- **Comités régionaux** : identifiés par un `nameShort` se terminant par `00000` (ex: `1300000`)
- **Comités départementaux** : identifiés par un `nameShort` se terminant par `000` mais pas `00000` (ex: `1301000`)
- **Clubs locaux** : autres formats de `nameShort`

### Logique de filtrage

La hiérarchie est basée sur les **2 premiers caractères** du `nameShort` :
- Un comité régional `1300000` regroupe tous les départements commençant par `13` (ex: `1301000`, `1302000`, etc.)
- Un comité régional `0600000` regroupe tous les départements commençant par `06` (ex: `0601000`, `0602000`, etc.)

### Comportement utilisateur

1. **Aucune sélection** : Tous les comités départementaux sont affichés
2. **Sélection d'un comité régional** : 
   - Le select des comités départementaux se met à jour automatiquement
   - Seuls les comités départementaux de cette région sont affichés
   - Si un comité départemental était déjà sélectionné et qu'il n'appartient pas à la nouvelle région, la sélection est réinitialisée
3. **Aucun comité départemental dans la région** : Un message informatif est affiché

## 🔧 Implémentation technique

### Fichiers modifiés

1. **`app/Views/clubs/index.php`**
   - Ajout du script `clubs-table.js`

2. **`public/assets/js/clubs-table.js`**
   - Variable globale `allDepartmentalOptions` pour stocker toutes les options
   - Fonction `updateDepartmentalSelect()` pour filtrer le select départemental
   - Mise à jour de `initClubsTable()` pour initialiser le filtrage

### Code principal

```javascript
// Fonction pour filtrer les comités départementaux selon le comité régional sélectionné
function updateDepartmentalSelect() {
    const filterRegional = document.getElementById('filterRegional');
    const filterDepartmental = document.getElementById('filterDepartmental');
    
    const selectedRegional = filterRegional.value;
    const currentDepartmentalValue = filterDepartmental.value;
    
    // Si aucun comité régional n'est sélectionné, afficher tous
    if (!selectedRegional) {
        // Restaurer toutes les options
        return;
    }
    
    // Extraire les 2 premiers caractères du comité régional
    const regionalPrefix = selectedRegional.substring(0, 2);
    
    // Filtrer les options qui correspondent à ce préfixe
    allDepartmentalOptions.forEach(opt => {
        const optValue = opt.value;
        if (optValue && optValue.substring(0, 2) === regionalPrefix) {
            // Ajouter l'option
        }
    });
}
```

### Événements

- **`change` sur `#filterRegional`** : Déclenche `updateDepartmentalSelect()` puis `filterClubsTable()`
- **`change` sur `#filterDepartmental`** : Déclenche `filterClubsTable()` uniquement

## 🧪 Tests à effectuer

1. ✅ Sélectionner un comité régional → Le select départemental se met à jour
2. ✅ Vérifier que seuls les départements de la région sont affichés
3. ✅ Désélectionner le comité régional → Tous les départements réapparaissent
4. ✅ Sélectionner un département puis changer de région → La sélection départementale se réinitialise si invalide
5. ✅ Sélectionner une région sans départements → Message "Aucun comité départemental dans cette région"
6. ✅ Vérifier que le filtrage de la table fonctionne toujours correctement

## 📊 Exemples

### Exemple 1 : Région Provence-Alpes-Côte d'Azur (13)

- **Comité régional** : `1300000` - PACA
- **Comités départementaux filtrés** :
  - `1301000` - Bouches-du-Rhône
  - `1302000` - Var
  - `1303000` - Alpes-Maritimes
  - etc.

### Exemple 2 : Région Île-de-France (75)

- **Comité régional** : `7500000` - Île-de-France
- **Comités départementaux filtrés** :
  - `7501000` - Paris
  - `7502000` - Seine-et-Marne
  - `7503000` - Yvelines
  - etc.

## 🎨 Amélirations futures possibles

1. **Préchargement intelligent** : Présélectionner automatiquement le comité régional en fonction de la localisation de l'utilisateur
2. **Recherche textuelle** : Ajouter une barre de recherche dans les selects pour trouver rapidement un comité
3. **Hiérarchie visuelle** : Afficher une indentation dans le select départemental pour mieux visualiser la hiérarchie
4. **Badge de comptage** : Afficher le nombre de clubs dans chaque comité
5. **Carte interactive** : Visualiser les comités sur une carte de France

## 📝 Notes

- Le filtrage est purement côté client (JavaScript)
- Aucun appel serveur n'est nécessaire pour le filtrage
- Les options sont sauvegardées au chargement de la page dans `allDepartmentalOptions`
- Le code est compatible avec tous les navigateurs modernes
