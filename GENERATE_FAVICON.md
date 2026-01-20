# Génération du Favicon

## 📋 Source

L'icône source provient de l'application mobile :
- **Fichier source :** `d:\GEMENOS\MobileApp2\android\app\src\main\res\mipmap-xxxhdpi\ic_launcher.png`
- **Copie locale :** `d:\GEMENOS\WebApp2\public\assets\images\favicon-source.png`

## 🔧 Générer les favicons

### Option 1 : En ligne (Recommandé)

1. Aller sur **[https://realfavicongenerator.net/](https://realfavicongenerator.net/)**
2. Télécharger `public/assets/images/favicon-source.png`
3. Suivre les instructions pour générer tous les formats
4. Télécharger le package généré
5. Extraire les fichiers dans `public/assets/images/favicon/`

### Option 2 : Avec ImageMagick (Ligne de commande)

Si ImageMagick est installé :

```bash
# Naviguer vers le dossier WebApp2
cd d:\GEMENOS\WebApp2\public\assets\images

# Créer le dossier favicon
mkdir favicon

# Générer favicon.ico (16x16, 32x32, 48x48)
magick favicon-source.png -resize 16x16 favicon/favicon-16.png
magick favicon-source.png -resize 32x32 favicon/favicon-32.png
magick favicon-source.png -resize 48x48 favicon/favicon-48.png
magick favicon/favicon-16.png favicon/favicon-32.png favicon/favicon-48.png favicon/favicon.ico

# Générer les tailles pour Apple Touch Icon
magick favicon-source.png -resize 180x180 favicon/apple-touch-icon.png
magick favicon-source.png -resize 120x120 favicon/apple-touch-icon-120x120.png
magick favicon-source.png -resize 152x152 favicon/apple-touch-icon-152x152.png

# Générer les tailles pour Android Chrome
magick favicon-source.png -resize 192x192 favicon/android-chrome-192x192.png
magick favicon-source.png -resize 512x512 favicon/android-chrome-512x512.png

# Copier le favicon.ico à la racine du public
copy favicon\favicon.ico ..\favicon.ico
```

### Option 3 : Avec Paint.NET ou GIMP (Manuel)

1. Ouvrir `favicon-source.png` dans Paint.NET ou GIMP
2. Redimensionner l'image en 32x32 pixels
3. Enregistrer sous `favicon.ico` dans `public/`
4. Répéter pour les autres tailles si nécessaire

## 📁 Structure des fichiers attendue

```
d:\GEMENOS\WebApp2\public\
├── favicon.ico                          # 16x16, 32x32, 48x48 (multi-taille)
└── assets\
    └── images\
        ├── favicon-source.png           # Source originale (haute résolution)
        └── favicon\
            ├── favicon.ico              # Favicon principal
            ├── favicon-16.png           # 16x16
            ├── favicon-32.png           # 32x32
            ├── favicon-48.png           # 48x48
            ├── apple-touch-icon.png     # 180x180 (iOS)
            ├── apple-touch-icon-120x120.png
            ├── apple-touch-icon-152x152.png
            ├── android-chrome-192x192.png
            ├── android-chrome-512x512.png
            ├── site.webmanifest         # Manifest pour PWA
            └── browserconfig.xml        # Config pour Windows
```

## 🌐 Intégration dans le HTML

Le fichier `app/Views/layouts/header.php` a été mis à jour avec :

```html
<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon/favicon-16.png">

<!-- Apple Touch Icon -->
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicon/apple-touch-icon.png">

<!-- Android Chrome -->
<link rel="icon" type="image/png" sizes="192x192" href="/assets/images/favicon/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/assets/images/favicon/android-chrome-512x512.png">

<!-- Web App Manifest -->
<link rel="manifest" href="/assets/images/favicon/site.webmanifest">

<!-- Theme Color -->
<meta name="theme-color" content="#198754">
```

## 📄 Créer le manifest (site.webmanifest)

Créer le fichier `public/assets/images/favicon/site.webmanifest` :

```json
{
  "name": "Arc Training",
  "short_name": "ArcTraining",
  "description": "Portail de gestion pour les Archers de Gémenos",
  "icons": [
    {
      "src": "/assets/images/favicon/android-chrome-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/assets/images/favicon/android-chrome-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ],
  "theme_color": "#198754",
  "background_color": "#ffffff",
  "display": "standalone",
  "start_url": "/dashboard"
}
```

## ✅ Vérification

Après génération et intégration :

1. **Vider le cache** du navigateur (Ctrl + Shift + Del)
2. **Recharger** la page (Ctrl + F5)
3. **Vérifier** que le favicon s'affiche dans l'onglet du navigateur
4. **Tester** en ajoutant le site aux favoris

## 🎨 Couleur du thème

La couleur principale du thème est **#198754** (vert Bootstrap success).

Cette couleur est utilisée pour :
- Le thème de l'application
- La barre d'adresse sur mobile
- L'arrière-plan de l'écran de démarrage sur mobile

---

**Date :** 20/01/2026  
**Source :** Application mobile React Native  
**Statut :** ⏳ En attente de génération des fichiers
