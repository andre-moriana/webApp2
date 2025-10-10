<?php
// Debug temporaire
//error_log("=== DEBUG ROUTAGE ===");
//error_log("REQUEST_URI: " . ($_SERVER["REQUEST_URI"] ?? "Non défini"));
//error_log("REQUEST_METHOD: " . ($_SERVER["REQUEST_METHOD"] ?? "Non défini"));

class Router {
    private $routes = [];
    private $basePath = "";
    
    public function __construct($basePath = "") {
        $this->basePath = $basePath;
        $this->defineRoutes();
    }
    
    private function defineRoutes() {
        // Routes d'authentification
        $this->addRoute("GET", "/login", "AuthController@login");
        $this->addRoute("POST", "/auth/authenticate", "AuthController@authenticate");
        $this->addRoute("GET", "/logout", "AuthController@logout");
        
        // Routes principales (protégées)
        $this->addRoute("GET", "/", "DashboardController@index");
        $this->addRoute("GET", "/dashboard", "DashboardController@index");
        
        // Routes des exercices (protégées)
        $this->addRoute("GET", "/exercises", "ExerciseController@index");
        $this->addRoute("GET", "/exercises/create", "ExerciseController@create");
        $this->addRoute("POST", "/exercises", "ExerciseController@store");
        $this->addRoute("GET", "/exercises/{id}", "ExerciseController@show");
        $this->addRoute("GET", "/exercises/{id}/edit", "ExerciseController@edit");
        $this->addRoute("PUT", "/exercises/{id}", "ExerciseController@update");
        $this->addRoute("DELETE", "/exercises/{id}", "ExerciseController@destroy");
        
        // Routes des entraînements (protégées)
        $this->addRoute("GET", "/trainings", "TrainingController@index");
        $this->addRoute("POST", "/trainings/update-progression", "TrainingController@updateProgression");
        $this->addRoute("POST", "/trainings/update-notes", "TrainingController@updateNotes");
        $this->addRoute("POST", "/trainings/save-session", "TrainingController@saveSession");
        $this->addRoute("POST", "/trainings/delete-session", "TrainingController@deleteSession");
        $this->addRoute("POST", "/trainings/update-status", "TrainingController@updateStatus");
        $this->addRoute("GET", "/trainings/{id}", "TrainingController@show");
        $this->addRoute("GET", "/trainings/{id}/stats", "TrainingController@stats");
        
        // Routes des tirs comptés (protégées)
        $this->addRoute("GET", "/scored-trainings", "ScoredTrainingController@index");
        $this->addRoute("GET", "/scored-trainings/create", "ScoredTrainingController@create");
        $this->addRoute("POST", "/scored-trainings", "ScoredTrainingController@store");
        $this->addRoute("POST", "/scored-trainings/delete/{id}", "ScoredTrainingController@delete");
        $this->addRoute("GET", "/scored-trainings/{id}", "ScoredTrainingController@show");
        $this->addRoute("POST", "/scored-trainings/{id}/end", "ScoredTrainingController@endTraining");
        $this->addRoute("POST", "/scored-trainings/{id}/ends", "ScoredTrainingController@addEnd");
        $this->addRoute("DELETE", "/scored-trainings/{id}", "ScoredTrainingController@delete");
        
        // Routes des utilisateurs (protégées)
        $this->addRoute("GET", "/users", "UserController@index");
        $this->addRoute("GET", "/users/create", "UserController@create");
        $this->addRoute("POST", "/users", "UserController@store");
        $this->addRoute("GET", "/users/{id}", "UserController@show");
        $this->addRoute("GET", "/users/{id}/edit", "UserController@edit");
        $this->addRoute("PUT", "/users/{id}", "UserController@update");
        $this->addRoute("POST", "/users/{id}/update", "UserController@update");
        $this->addRoute("DELETE", "/users/{id}", "UserController@destroy");
        $this->addRoute("POST", "/users/{id}/delete", "UserController@destroy");
        
        // Routes des groupes (protégées)
        $this->addRoute("GET", "/groups", "GroupController@index");
        $this->addRoute("GET", "/groups/create", "GroupController@create");
        $this->addRoute("GET", "/groups/{id}/members", "GroupController@members");
        $this->addRoute("GET", "/groups/{id}/edit", "GroupController@edit");
        $this->addRoute("GET", "/groups/{id}", "GroupController@show");
        $this->addRoute("POST", "/groups", "GroupController@store");
        $this->addRoute("PUT", "/groups/{id}", "GroupController@update");
        $this->addRoute("DELETE", "/groups/{id}", "GroupController@destroy");
        
        // Routes internes pour les groupes (proxy vers API externe)
        $this->addRoute("GET", "/users", "ApiController@users");
        $this->addRoute("POST", "/groups/{id}/members", "ApiController@addGroupMembers");
        $this->addRoute("DELETE", "/groups/{id}/remove-member/{memberId}", "ApiController@removeGroupMember");
        
        // Routes API pour les groupes (proxy vers API externe)
        $this->addRoute("GET", "/api/users", "ApiController@users");
        $this->addRoute("POST", "/api/groups/{id}/members", "ApiController@addGroupMembers");
        $this->addRoute("DELETE", "/api/groups/{id}/remove-member/{memberId}", "ApiController@removeGroupMember");
        
        // Routes des événements (protégées)
        $this->addRoute("GET", "/events", "EventController@index");
        $this->addRoute("GET", "/events/create", "EventController@create");
        $this->addRoute("POST", "/events", "EventController@store");
        $this->addRoute("GET", "/events/{id}/edit", "EventController@edit");
        $this->addRoute("GET", "/events/{id}/participants", "EventController@participants");
        $this->addRoute("POST", "/events/{id}/register", "EventController@register");
        $this->addRoute("POST", "/events/{id}/unregister", "EventController@unregister");
        
        // Routes des messages d'événements (AVANT les routes générales)
        $this->addRoute("GET", "/events/{id}/messages", "ApiController@getEventMessages");
        $this->addRoute("POST", "/events/{id}/messages", "ApiController@sendEventMessage");
        $this->addRoute("PUT", "/events/messages/{id}", "ApiController@updateEventMessage");
        $this->addRoute("DELETE", "/events/messages/{id}", "ApiController@deleteEventMessage");
        $this->addRoute("GET", "/events/{id}/data", "ApiController@getEvent");
        $this->addRoute("DELETE", "/events/{id}/delete", "ApiController@deleteEvent");
        $this->addRoute("POST", "/events/{id}/join", "ApiController@joinEvent");
        $this->addRoute("POST", "/events/{id}/leave", "ApiController@leaveEvent");
        
        // Routes générales des événements (APRÈS les routes spécifiques)
        $this->addRoute("GET", "/events/{id}", "EventController@show");
        $this->addRoute("PUT", "/events/{id}", "EventController@update");
        $this->addRoute("DELETE", "/events/{id}", "EventController@destroy");
        
        // Routes API (protégées)
        $this->addRoute("GET", "/api/documents", "ApiController@documents");
        $this->addRoute("GET", "/api/documents/user/{id}", "ApiController@userDocuments");
        $this->addRoute("POST", "/api/documents/{id}/upload", "ApiController@uploadDocument");
        $this->addRoute("DELETE", "/api/documents/{id}/delete", "ApiController@deleteDocument");
        $this->addRoute("GET", "/api/documents/{id}/download", "ApiController@downloadDocument");
        $this->addRoute("GET", "/api/stats", "ApiController@stats");
        $this->addRoute("GET", "/api/users", "ApiController@users");
        $this->addRoute("GET", "/api/trainings", "ApiController@trainings");
        
        // Route de test simple
        $this->addRoute("GET", "/test-messages", "ApiController@testMessages");
        
        // Routes internes pour les messages des groupes (proxy vers API externe)
        $this->addRoute("GET", "/messages/attachment/{id}", "ApiController@downloadMessageAttachment");
        $this->addRoute("GET", "/messages/image/{id}", "ApiController@getMessageImage");
        $this->addRoute("GET", "/messages/{id}/history", "ApiController@getGroupMessages");
        $this->addRoute("POST", "/messages/{id}/send", "ApiController@sendGroupMessage");
        $this->addRoute("PUT", "/messages/{id}", "ApiController@updateMessage");
        $this->addRoute("DELETE", "/messages/{id}", "ApiController@deleteMessage");
        
        
        // Routes d'authentification
        $this->addRoute("GET", "/login", "AuthController@login");
        $this->addRoute("POST", "/auth/authenticate", "AuthController@authenticate");
        $this->addRoute("GET", "/logout", "AuthController@logout");
        $this->addRoute("GET", "/auth/reset-password", "AuthController@resetPassword");
        $this->addRoute("POST", "/auth/update-password", "AuthController@updatePassword");
        $this->addRoute("GET", "/auth/register", "AuthController@register");
        $this->addRoute("POST", "/auth/create-user", "AuthController@createUser");
        
        // Routes de validation des utilisateurs (protégées - admin seulement)
        $this->addRoute("GET", "/user-validation", "UserValidationController@index");
        $this->addRoute("POST", "/user-validation/approve", "UserValidationController@approve");
        $this->addRoute("POST", "/user-validation/reject", "UserValidationController@reject");
        
        // Routes des paramètres utilisateur (protégées)
        $this->addRoute("GET", "/user-settings", "UserSettingsController@index");
        $this->addRoute("POST", "/user-settings/update-profile-image", "UserSettingsController@updateProfileImage");
        $this->addRoute("POST", "/user-settings/change-password", "UserSettingsController@changePassword");
        
    }
    
    public function addRoute($method, $path, $handler) {
        // Insérer les routes plus spécifiques en premier
        $route = [
            "method" => $method,
            "path" => $path,
            "handler" => $handler,
            // Calculer la spécificité de la route
            "specificity" => $this->calculateRouteSpecificity($path)
        ];
        
        // Ajouter la route au tableau
        $this->routes[] = $route;
        
        // Trier les routes par spécificité (plus spécifique en premier)
        usort($this->routes, function($a, $b) {
            return $b['specificity'] - $a['specificity'];
        });
        
   //     error_log("Route ajoutée: " . $method . " " . $path . " -> " . $handler . " (spécificité: " . $route['specificity'] . ")");
    }
    
    private function calculateRouteSpecificity($path) {
        $specificity = 0;
        
        // Les routes avec paramètres sont beaucoup moins spécifiques
        if (strpos($path, '{') !== false) {
            $specificity -= 50;
        }
        
        // Plus il y a de segments, plus la route est spécifique
        $segments = explode('/', trim($path, '/'));
        $specificity += count($segments) * 10;
        
        // Les routes avec des segments fixes sont plus spécifiques
        foreach ($segments as $segment) {
            if (strpos($segment, '{') === false) {
                $specificity += 5;
            }
        }
        
        // Les routes API sont plus spécifiques
        if (strpos($path, '/api/') === 0) {
            $specificity += 20;
        }
        
        // Les routes avec des actions spécifiques sont plus spécifiques
        if (strpos($path, '/create') !== false || 
            strpos($path, '/edit') !== false ||
            strpos($path, '/delete') !== false ||
            strpos($path, '/update') !== false) {
            $specificity += 15;
        }
        
//        error_log("Spécificité calculée pour " . $path . ": " . $specificity);
        return $specificity;
    }
    
    public function run() {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        
        // Gérer les méthodes HTTP personnalisées via _method
        if ($requestMethod === "POST" && isset($_POST["_method"])) {
            $requestMethod = strtoupper($_POST["_method"]);
            error_log("Méthode HTTP personnalisée détectée: " . $requestMethod);
        }
        
        // Debug temporaire - DÉSACTIVÉ
//        error_log("=== DEBUG ROUTAGE ===");
//        error_log("REQUEST_URI: " . $requestUri);
//        error_log("REQUEST_METHOD: " . $requestMethod);
//        error_log("BASE_PATH: " . $this->basePath);
        
        // Supprimer le basePath de l'URI
        if ($this->basePath && strpos($requestUri, $this->basePath) === 0) {
            $requestUri = substr($requestUri, strlen($this->basePath));
        }
        
//        error_log("URI normalisée: " . $requestUri);
//        error_log("Nombre total de routes: " . count($this->routes));
//        error_log("Routes disponibles dans l'ordre de test:");
//        foreach ($this->routes as $index => $route) {
//            error_log(($index + 1) . ". " . $route["method"] . " " . $route["path"] . " -> " . $route["handler"]);
//        }
        
        // Tester chaque route
        foreach ($this->routes as $route) {
            if ($route["method"] !== $requestMethod) {
                continue;
            }
            
//            error_log("Test route: " . $route["method"] . " " . $route["path"]);
            
            // Utiliser la méthode convertToRegex existante
            $pattern = $this->convertToRegex($route["path"]);
            
//            error_log("DEBUG Router - Pattern: " . $pattern);
//            error_log("DEBUG Router - Test regex: " . $pattern . " contre " . $requestUri);
            
            if (preg_match($pattern, $requestUri, $matches)) {
//                error_log("DEBUG Router - Route trouvée! Handler: " . $route["handler"]);
//                error_log("DEBUG Router - Matches: " . print_r($matches, true));
                
                // Extraire le contrôleur et la méthode
                list($controller, $method) = explode("@", $route["handler"]);
                
                // Instancier le contrôleur
                require_once "app/Controllers/" . $controller . ".php";
                $controllerInstance = new $controller();
                
                // Appeler la méthode avec les paramètres capturés
                array_shift($matches); // Retirer la correspondance complète
                call_user_func_array([$controllerInstance, $method], $matches);
                return;
            } else {
//                error_log("Pas de match pour cette route");
            }
        }
        
        // Si aucune route ne correspond
//        error_log("Aucune route trouvée pour: " . $requestUri);
//        error_log("Routes disponibles: " . print_r($this->routes, true));
        
        // Gérer l'erreur 404
        header("HTTP/1.0 404 Not Found");
        include "app/Views/layouts/header.php";
        include "app/Views/errors/404.php";
        include "app/Views/layouts/footer.php";
    }
    
    private function convertToRegex($path) {
        // Remplacer {id} par ([^/]+) pour capturer les IDs (numériques ou chaînes)
        $pattern = preg_replace("/\{([^}]+)\}/", "([^/]+)", $path);
        // Échapper seulement les slashes, pas les parenthèses
        $pattern = str_replace("/", "\/", $pattern);
        //error_log("Conversion route: " . $path . " -> " . $pattern);
        return "/^" . $pattern . "$/";
    }
    
    private function executeHandler($handler, $matches) {
        list($controllerName, $method) = explode("@", $handler);
        
        // Inclure le contrôleur
        $controllerFile = "app/Controllers/{$controllerName}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            $controller = new $controllerName();
            
            // Vérifier l"authentification pour les routes protégées
            $this->checkAuthentication($controllerName, $method);
            
            // Extraire les paramètres de l"URL
            $params = array_slice($matches, 1);
            
            // Appeler la méthode avec les paramètres
            if (method_exists($controller, $method)) {
                call_user_func_array([$controller, $method], $params);
            } else {
                $this->handle404();
            }
        } else {
            $this->handle404();
        }
    }
    
    private function checkAuthentication($controllerName, $method) {
        // Routes publiques (pas d"authentification requise)
        $publicRoutes = [
            "AuthController@login",
            "AuthController@authenticate"
        ];
        
        $currentRoute = $controllerName . "@" . $method;
        
        if (!in_array($currentRoute, $publicRoutes)) {
            // Vérifier si l"utilisateur est connecté
            if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
                header("Location: /login");
                exit;
            }
        }
    }
    
    private function handle404() {
        http_response_code(404);
        echo "Page non trouvée";
    }
}



