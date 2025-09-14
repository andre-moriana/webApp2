# Architecture du Portail WebApp2

## Principe fondamental
**Le portail WebApp2 utilise UNIQUEMENT les webservices du backend existant.**
Aucune connexion directe à MySQL ou à une base de données.

## Architecture

```
    HTTP/API    
   WebApp2            BackendPHP    
   (Portail)                         (API REST)    
                                                   
 - Controllers                     - MySQL         
 - Views                           - Webservices   
 - Services                        - Authentification
 - Router                                          
                 
```

## Composants du Portail

### 1. Controllers (app/Controllers/)
- **AuthController** : Authentification via API
- **UserController** : Gestion des utilisateurs via API
- **DashboardController** : Tableau de bord
- **GroupController** : Gestion des groupes via API
- **TrainingController** : Gestion des entraînements via API

### 2. Services (app/Services/)
- **ApiService** : Communication avec le backend
- Toutes les données proviennent des webservices

### 3. Views (app/Views/)
- Templates HTML pour l'interface utilisateur
- Aucune logique de base de données

### 4. Configuration
- **.env** : Configuration de l'API backend uniquement
- **Router** : Routage des requêtes
- **Autoloader** : Chargement des classes

## Flux de données

1. **Requête utilisateur**  Controller
2. **Controller**  ApiService
3. **ApiService**  Backend PHP (webservice)
4. **Backend**  MySQL (base de données)
5. **Réponse**  Controller  View  Utilisateur

## Avantages

-  **Séparation des responsabilités** : WebApp2 = Interface, Backend = Données
-  **Réutilisabilité** : L'API peut servir d'autres applications
-  **Sécurité** : Pas d'accès direct à la base de données
-  **Maintenance** : Logique métier centralisée dans le backend
-  **Évolutivité** : Facile d'ajouter de nouvelles fonctionnalités

## Configuration

### Fichier .env
```
API_BASE_URL=http://localhost/BackendPHP/public
JWT_SECRET=your-secret-key-here
```

### Endpoints utilisés
- `GET /users` - Liste des utilisateurs
- `POST /auth/login` - Authentification
- `GET /groups` - Liste des groupes
- `GET /trainings` - Liste des entraînements
- `GET /events` - Liste des événements

## Aucune dépendance MySQL

Le portail WebApp2 ne contient :
-  Pas de modèles de base de données
-  Pas de configuration MySQL
-  Pas de migrations
-  Pas de connexions PDO/MySQLi

Tout passe par l'API du backend existant.
