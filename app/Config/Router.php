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
        // Routes d"authentification
        $this->addRoute("GET", "/login", "AuthController@login");
        $this->addRoute("POST", "/auth/authenticate", "AuthController@authenticate");
        $this->addRoute("GET", "/logout", "AuthController@logout");
        
        // Routes principales (protégées)
        $this->addRoute("GET", "/", "DashboardController@index");
        $this->addRoute("GET", "/dashboard", "DashboardController@index");
        
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
        
        // Routes des groupes (protégées) - ORDRE IMPORTANT : plus spécifique en premier
        $this->addRoute("GET", "/groups", "GroupController@index");           // Liste des groupes
        $this->addRoute("GET", "/groups/create", "GroupController@create");
        $this->addRoute("GET", "/groups/{id}/members", "GroupController@members");  // Plus spécifique - DOIT être avant
        $this->addRoute("GET", "/groups/{id}/edit", "GroupController@edit");  // Plus spécifique - DOIT être avant
        $this->addRoute("GET", "/groups/{id}", "GroupController@show");       // Plus générique - DOIT être après
        $this->addRoute("POST", "/groups", "GroupController@store");
        $this->addRoute("PUT", "/groups/{id}", "GroupController@update");
        $this->addRoute("DELETE", "/groups/{id}", "GroupController@destroy");
        
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
        $this->addRoute("POST", "/trainings/update-status", "TrainingController@updateStatus");
        $this->addRoute("GET", "/trainings/{id}", "TrainingController@show");
        $this->addRoute("GET", "/trainings/{id}/stats", "TrainingController@stats");

        // Routes des événements (protégées) - ORDRE IMPORTANT : plus spécifique en premier
        $this->addRoute("GET", "/events", "EventController@index");
        $this->addRoute("GET", "/events/create", "EventController@create");
        $this->addRoute("POST", "/events", "EventController@store");
        $this->addRoute("GET", "/events/{id}/edit", "EventController@edit");  // Plus spécifique - DOIT être avant
        $this->addRoute("POST", "/events/{id}/register", "EventController@register");  // Plus spécifique - DOIT être avant
        $this->addRoute("POST", "/events/{id}/unregister", "EventController@unregister");  // Plus spécifique - DOIT être avant
        $this->addRoute("GET", "/events/{id}", "EventController@show");       // Plus générique - DOIT être après
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
        
        // Routes API pour les messages des groupes
        // Route des pièces jointes en premier (plus spécifique)
        $this->addRoute("GET", "/api/messages/attachment/{id}", "ApiController@downloadMessageAttachment");
        // Routes génériques ensuite
        $this->addRoute("GET", "/api/messages/{id}/history", "ApiController@getGroupMessages");
        $this->addRoute("POST", "/api/messages/{id}/send", "ApiController@sendGroupMessage");
        // Nouvelles routes pour la modification et suppression des messages
        $this->addRoute("PUT", "/api/messages/{id}/update", "ApiController@updateMessage");
        $this->addRoute("DELETE", "/api/messages/{id}/delete", "ApiController@deleteMessage");
    }
    
    public function addRoute($method, $path, $handler) {
        // Insérer les routes plus spécifiques en premier
        $route = [
            "method" => $method,
            "path" => $path,
            "handler" => $handler
        ];
        
        // Si c"est une route avec des paramètres, l"ajouter en premier
        if (strpos($path, "{") !== false) {
            array_unshift($this->routes, $route);
        } else {
            array_unshift($this->routes, $route);
        }
    }
    
    public function run() {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        
        // Gérer les méthodes HTTP personnalisées via _method
        if ($requestMethod === "POST" && isset($_POST["_method"])) {
            $requestMethod = strtoupper($_POST["_method"]);
            error_log("Méthode HTTP personnalisée détectée: " . $requestMethod);
        }
        
        // Debug temporaire - RÉACTIVÉ pour debug
        error_log("=== DEBUG ROUTAGE ===");
        error_log("REQUEST_URI: " . $requestUri);
        error_log("REQUEST_METHOD: " . $requestMethod);
        error_log("BASE_PATH: " . $this->basePath);
        
        // Supprimer le basePath de l'URI
        if ($this->basePath && strpos($requestUri, $this->basePath) === 0) {
            $requestUri = substr($requestUri, strlen($this->basePath));
            error_log("URI après suppression du basePath: " . $requestUri);
        }
        
        // Normaliser l'URI
        $requestUri = rtrim($requestUri, "/");
        if (empty($requestUri)) {
            $requestUri = "/";
        }
        
        error_log("URI normalisée: " . $requestUri);
        
        foreach ($this->routes as $route) {
            error_log("Test route: " . $route["method"] . " " . $route["path"]);
            if ($route["method"] === $requestMethod) {
                $pattern = $this->convertToRegex($route["path"]);
                error_log("Pattern: " . $pattern);
                error_log("Test regex: " . $pattern . " contre " . $requestUri);
                if (preg_match($pattern, $requestUri, $matches)) {
                    error_log("Route trouvée! Handler: " . $route["handler"]);
                    error_log("Matches: " . print_r($matches, true));
                    $this->executeHandler($route["handler"], $matches);
                    return;
                } else {
                    error_log("Pas de match pour cette route");
                }
            }
        }
        
        error_log("Aucune route trouvée pour: " . $requestUri);
        error_log("Routes disponibles: " . print_r($this->routes, true));
        $this->handle404();
    }
    
    private function convertToRegex($path) {
        // Remplacer {id} par ([0-9]+) pour capturer seulement les IDs numériques
        $pattern = preg_replace("/\{([^}]+)\}/", "([0-9]+)", $path);
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


