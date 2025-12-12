
<?xml version="1.0" encoding="UTF-8"?>
<WINDEV_TABLE>
    <TABLE_CONTENU>
        <NOM>Dupont</NOM>
        <PRENOM>Jean</PRENOM>
        <NOMCOMPLET>Jean Dupont</NOMCOMPLET>
        <IDLicence>123456789</IDLicence>
        <CIE>130000</CIE>
        <DATENAISSANCE>15/01/1990</DATENAISSANCE>
        <SEXE>1</SEXE>
        <CATEGORIE>CLS1D</CATEGORIE>
        <CATAGE>11</CATAGE>
        <TYPARC>1</TYPARC>
        <EMAIL>jean.dupont@example.com</EMAIL>
    </TABLE_CONTENU>
    <TABLE_CONTENU>
        <NOM>Martin</NOM>
        <PRENOM>Marie</PRENOM>
        <IDLicence>987654321</IDLicence>
        <EMAIL>marie.martin@example.com</EMAIL>
    </TABLE_CONTENU>
</WINDEV_TABLE>
```

## Champs disponibles (format WINDEV_TABLE)

- **IDLicence** (requis) : Numéro de licence unique de l'utilisateur (utilisé pour la recherche)
- **NOM** : Nom de famille
- **PRENOM** : Prénom
- **NOMCOMPLET** : Nom complet (utilisé si NOM et PRENOM sont vides)
- **EMAIL** : Adresse email
- **CIE** : Code du club
- **DATENAISSANCE** : Date de naissance au format DD/MM/YYYY
- **SEXE** : Genre (1 = Homme/H, 2 = Femme/F)
- **CATEGORIE** : Catégorie (ex: CLS1D)
- **CATAGE** : Code de catégorie d'âge (converti automatiquement)
- **TYPARC** : Code de type d'arc (1 = Arc Classique, 2 = Arc à poulies, etc.)

## Conversion automatique

Le système convertit automatiquement :
- **SEXE** : 1 → H, 2 → F
- **DATENAISSANCE** : DD/MM/YYYY → YYYY-MM-DD
- **CATAGE** : Code numérique → Nom de catégorie (ex: 11 → SENIORS1 (S1))
- **TYPARC** : Code numérique → Type d'arc (ex: 1 → Arc Classique)

## Notes importantes

1. Le fichier XML est mis en cache côté client pour améliorer les performances
2. Le cache est automatiquement utilisé lors des recherches suivantes
3. Pour forcer le rechargement, videz le cache du navigateur
4. Le fichier doit être valide XML (syntaxe correcte)
5. L'encodage doit être UTF-8 pour gérer les caractères accentués

