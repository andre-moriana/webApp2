# Portail Web - Archers de Gémenos

Portail web de gestion pour l'application mobile de tir à l'arc des Archers de Gémenos.

##  Fonctionnalités

- **Tableau de bord** : Vue d'ensemble des statistiques et données récentes
- **Gestion des utilisateurs** : CRUD complet des utilisateurs
- **Gestion des groupes** : Administration des groupes d'archers
- **Gestion des exercices** : Création et gestion des fiches d'exercices
- **Suivi des entraînements** : Visualisation des sessions de tir compté
- **Gestion des événements** : Planification et suivi des événements
- **Statistiques avancées** : Graphiques et analyses des performances

##  Architecture

Le portail utilise une architecture MVC (Model-View-Controller) en PHP pur :

```
WebApp2/
 app/
    Controllers/     # Contrôleurs MVC
    Models/          # Modèles de données
    Views/           # Vues (templates)
    Services/        # Services (API, etc.)
    Config/          # Configuration
 public/
    assets/          # CSS, JS, images
 config/              # Fichiers de configuration
 index.php            # Point d'entrée
```

##  Installation

### Prérequis

- PHP 7.4 ou supérieur
- Serveur web (Apache/Nginx)
- Backend API existant (BackendPHP)
- **Aucune dépendance externe requise** (pas de Composer)

### Configuration

1. **Télécharger le projet** dans votre répertoire web :
   ```bash
   # Copier le dossier WebApp2 dans votre répertoire web
   cp -r WebApp2 /var/www/html/
   ```

2. **Configurer l'API Backend** :
   - Créez un fichier `.env` à la racine du projet
   - Ajoutez la configuration suivante :
   ```env
   # URL de l'API backend
   API_BASE_URL=http://82.67.123.22:25000/api
   ```
   Note : Si le fichier `.env` n'existe pas, l'application utilisera l'URL par défaut http://82.67.123.22:25000/api

3. **Configurer le serveur web** :
   
   **Apache** - Le fichier `.htaccess` est déjà inclus :
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

   **Nginx** - Ajouter à votre configuration :
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

##  Utilisation

### Accés au portail

1. Ouvrez votre navigateur
2. Naviguez vers `http://localhost/WebApp2`
3. Le tableau de bord s'affiche avec les données du backend

### Navigation

- **Tableau de bord** (`/`) : Vue d'ensemble
- **Utilisateurs** (`/users`) : Gestion des utilisateurs
- **Groupes** (`/groups`) : Gestion des groupes
- **Exercices** (`/exercises`) : Gestion des exercices
- **Entraînements** (`/trainings`) : Suivi des sessions
- **événements** (`/events`) : Gestion des événements

##  Intégration API

Le portail communique avec le backend existant via des appels API REST :

### Service API

Le `ApiService` gère toutes les communications avec le backend :

```php
$apiService = new ApiService();

// Récupérer les utilisateurs
$users = $apiService->getUsers();

// Créer un utilisateur
$newUser = $apiService->createUser($userData);

// Récupérer les statistiques
$stats = $apiService->getGlobalStats();
```

### Endpoints utilisés

- `GET /api/users` - Liste des utilisateurs
- `GET /api/groups` - Liste des groupes
- `GET /api/exercise-sheets` - Liste des exercices
- `GET /api/scored-trainings` - Liste des entraînements
- `GET /api/events` - Liste des événements
- `GET /api/stats` - Statistiques globales

##  Personnalisation

### Thème

Le portail utilise Bootstrap 5 avec un thème personnalisé. Modifiez `public/assets/css/style.css` pour personnaliser :

- Couleurs principales
- Typographie
- Espacement
- Animations

### Ajout de nouvelles fonctionnalités

1. **Créer un contrôleur** dans `app/Controllers/`
2. **Ajouter les routes** dans `app/Config/Router.php`
3. **Créer les vues** dans `app/Views/`
4. **Ajouter les méthodes API** dans `app/Services/ApiService.php`

##  Dépannage

### Problèmes courants

1. **Erreur 500** : Vérifiez les permissions des fichiers et la configuration PHP
2. **API non accessible** : Vérifiez l'URL dans `.env` et la disponibilité du backend
3. **Styles non chargés** : Vérifiez le chemin vers `public/assets/`
4. **Classes non trouvées** : Vérifiez que l'autoloader fonctionne correctement

### Logs

Les erreurs sont loggées dans les logs PHP standard. Activez le mode debug dans `.env` :

```env
APP_DEBUG=true
```

##  Responsive Design

Le portail est entièrement responsive et s'adapte à tous les écrans :

- **Desktop** : Interface complète avec sidebar
- **Tablet** : Layout adapté avec navigation collapsible
- **Mobile** : Interface optimisée pour les petits écrans

##  Sécurité

- Validation des données côté serveur
- Protection CSRF sur les formulaires
- Échappement des données dans les vues
- Authentification via l'API backend
- Protection des fichiers sensibles (.env, .log)

##  Avantages

- **Aucune dépendance externe** - Fonctionne sans Composer
- **Léger et rapide** - PHP pur sans framework lourd
- **Facile à déployer** - Juste copier les fichiers
- **Compatible** - Fonctionne sur tous les serveurs PHP
- **Maintenable** - Code simple et bien structuré

##  Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit vos changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

##  Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

##  Support

Pour toute question ou problème :

- Créer une issue sur GitHub
- Contacter l'équipe de développement
- Consulter la documentation du backend API

---

**Développé avec  pour les Archers de Gémenos**

## ✅ **Configuration optimisée terminée !**

### **Fichiers créés pour optimiser votre environnement :**

1. **`powershell_functions.ps1`** - Fonctions PowerShell optimisées
2. **`profile_dev.ps1`** - Configuration du profil PowerShell
3. **`config_environment.php`** - Script de configuration automatique
4. **`GUIDE_UTILISATION.md`** - Guide d'utilisation complet

### **Comment utiliser la nouvelle configuration :**

#### **1. Charger la configuration :**
```powershell
. .\profile_dev.ps1
```

#### **2. Commandes rapides disponibles :**
```powershell
<code_block_to_apply_changes_from>
```

#### **3. Méthodes alternatives recommandées :**

**Pour les modifications importantes :**
- Utiliser **VS Code** ou **Cursor** comme éditeur principal
- Modifier directement les fichiers dans l'éditeur
- Utiliser Git pour le versioning

**Pour les tâches simples :**
- Utiliser les commandes PowerShell optimisées
- Utiliser `echo` pour créer des fichiers simples
- Utiliser `Get-Content` pour lire des fichiers

### **Avantages de cette configuration :**

✅ **Encodage UTF-8** correct sans BOM  
✅ **Fonctions PowerShell** optimisées  
✅ **Alias courts** pour les tâches courantes  
✅ **Gestion d'erreurs** améliorée  
✅ **Commandes simples** et fiables  
✅ **Guide d'utilisation** complet  

Maintenant vous pouvez utiliser ces outils pour créer et modifier des fichiers PHP de manière plus fiable dans votre environnement Windows/WAMP !
