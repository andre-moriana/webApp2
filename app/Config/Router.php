<?php
// Debug temporaire
error_log("=== DEBUG ROUTAGE ===");
error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Non défini'));
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'Non défini'));

class Router {
    private $routes = [];
    private $basePath = '';
    
    public function __construct($basePath = '') {
        $this->basePath = $basePath;
        $this->defineRoutes();
    }
    
    private function defineRoutes() {
        // Routes d'authentification
        $this->addRoute('GET', '/login', 'AuthController@login');
        $this->addRoute('POST', '/auth/authenticate', 'AuthController@authenticate');
        $this->addRoute('GET', '/logout', 'AuthController@logout');
        
        // Routes principales (protégées)
        $this->addRoute('GET', '/', 'DashboardController@index');
        $this->addRoute('GET', '/dashboard', 'DashboardController@index');
        
        // Routes des utilisateurs (protégées)
        $this->addRoute('GET', '/users', 'UserController@index');
        $this->addRoute('GET', '/users/create', 'UserController@create');
        $this->addRoute('POST', '/users', 'UserController@store');
        $this->addRoute('GET', '/users/{id}', 'UserController@show');
        $this->addRoute('GET', '/users/{id}/edit', 'UserController@edit');
        $this->addRoute('PUT', '/users/{id}', 'UserController@update');
        $this->addRoute('POST', '/users/{id}/update', 'UserController@update');
        $this->addRoute('DELETE', '/users/{id}', 'UserController@destroy');
        $this->addRoute('POST', '/users/{id}/delete', 'UserController@destroy');
        
        // Routes des groupes (protégées)
        $this->addRoute('GET', '/groups', 'GroupController@index');
        $this->addRoute('GET', '/groups/create', 'GroupController@create');
        $this->addRoute('POST', '/groups', 'GroupController@store');
        $this->addRoute('GET', '/groups/{id}', 'GroupController@show');
        $this->addRoute('GET', '/groups/{id}/edit', 'GroupController@edit');
        $this->addRoute('PUT', '/groups/{id}', 'GroupController@update');
        $this->addRoute('DELETE', '/groups/{id}', 'GroupController@destroy');
        
        // Routes des exercices (protégées)
        $this->addRoute('GET', '/exercises', 'ExerciseController@index');
        $this->addRoute('GET', '/exercises/create', 'ExerciseController@create');
        $this->addRoute('POST', '/exercises', 'ExerciseController@store');
        $this->addRoute('GET', '/exercises/{id}', 'ExerciseController@show');
        $this->addRoute('GET', '/exercises/{id}/edit', 'ExerciseController@edit');
        $this->addRoute('PUT', '/exercises/{id}', 'ExerciseController@update');
        $this->addRoute('DELETE', '/exercises/{id}', 'ExerciseController@destroy');
        
        // Routes des entraînements (protégées)
        $this->addRoute('GET', '/trainings', 'TrainingController@index');
        $this->addRoute('GET', '/trainings/{id}', 'TrainingController@show');
        $this->addRoute('GET', '/trainings/{id}/stats', 'TrainingController@stats');
        
        // Routes des événements (protégées)
        $this->addRoute('GET', '/events', 'EventController@index');
        $this->addRoute('GET', '/events/create', 'EventController@create');
        $this->addRoute('POST', '/events', 'EventController@store');
        $this->addRoute('GET', '/events/{id}', 'EventController@show');
        $this->addRoute('GET', '/events/{id}/edit', 'EventController@edit');
        $this->addRoute('PUT', '/events/{id}', 'EventController@update');
        $this->addRoute('DELETE', '/events/{id}', 'EventController@destroy');
        
        // Routes API (protégées)
        $this->addRoute('GET', '/api/documents', 'ApiController@documents');
        $this->addRoute('GET', '/api/documents/user/{id}', 'ApiController@userDocuments');
        $this->addRoute('POST', '/api/documents/{id}/upload', 'ApiController@uploadDocument');
        $this->addRoute('DELETE', '/api/documents/{id}/delete', 'ApiController@deleteDocument');
        $this->addRoute('GET', '/api/documents/{id}/download', 'ApiController@downloadDocument');
        $this->addRoute('GET', '/api/stats', 'ApiController@stats');
        $this->addRoute('GET', '/api/users', 'ApiController@users');
        $this->addRoute('GET', '/api/trainings', 'ApiController@trainings');
        
        // Routes API pour les messages des groupes
        $this->addRoute('GET', '/api/messages/{id}/history', 'ApiController@getGroupMessages');
        $this->addRoute('POST', '/api/messages/{id}/send', 'ApiController@sendGroupMessage');
    }
    
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Debug temporaire
        error_log("=== DEBUG ROUTAGE ===");
        error_log("REQUEST_URI: " . $requestUri);
        error_log("REQUEST_METHOD: " . $requestMethod);
        error_log("SESSION: " . print_r($_SESSION, true));
        
        // Supprimer le basePath de l'URI
        if ($this->basePath && strpos($requestUri, $this->basePath) === 0) {
            $requestUri = substr($requestUri, strlen($this->basePath));
        }
        
        // Normaliser l'URI
        $requestUri = rtrim($requestUri, '/');
        if (empty($requestUri)) {
            $requestUri = '/';
        }
        
        error_log("URI normalisée: " . $requestUri);
        
        foreach ($this->routes as $route) {
            error_log("Test route: " . $route['method'] . " " . $route['path']);
            if ($route['method'] === $requestMethod) {
                $pattern = $this->convertToRegex($route['path']);
                error_log("Pattern: " . $pattern);
                if (preg_match($pattern, $requestUri, $matches)) {
                    error_log("Route trouvée! Handler: " . $route['handler']);
                    error_log("Matches: " . print_r($matches, true));
                    $this->executeHandler($route['handler'], $matches);
                    return;
                }
            }
        }
        
        error_log("Aucune route trouvée pour: " . $requestUri);
        $this->handle404();
    }
    
    private function convertToRegex($path) {
        // Remplacer {id} par ([^/]+) AVANT d'échapper
        $pattern = str_replace('{id}', '([^/]+)', $path);
        // Échapper seulement les slashes, pas les parenthèses
        $pattern = str_replace('/', '\/', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    private function executeHandler($handler, $matches) {
        list($controllerName, $method) = explode('@', $handler);
        
        // Inclure le contrôleur
        $controllerFile = "app/Controllers/{$controllerName}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            $controller = new $controllerName();
            
            // Vérifier l'authentification pour les routes protégées
            $this->checkAuthentication($controllerName, $method);
            
            // Extraire les paramètres de l'URL
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
        // Routes publiques (pas d'authentification requise)
        $publicRoutes = [
            'AuthController@login',
            'AuthController@authenticate'
        ];
        
        $currentRoute = $controllerName . '@' . $method;
        
        if (!in_array($currentRoute, $publicRoutes)) {
            // Vérifier si l'utilisateur est connecté
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                header('Location: /login');
                exit;
            }
        }
    }
    
    private function handle404() {
        http_response_code(404);
        echo "Page non trouvée";
    }
}
