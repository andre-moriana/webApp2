<?php
class Router {
    private $routes = [];
    private $basePath = "";
    
    public function __construct($basePath = "") {
        $this->basePath = $basePath;
        $this->defineRoutes();
    }
    
    private function defineRoutes() {
        // Routes d'archer (recherche/création depuis licence)
        $this->addRoute("POST", "/archer/search-or-create", "ArcherSearchController@findOrCreateByLicense");
        // Recherche publique pour inscription ciblée (sans auth).
        $this->addRoute("POST", "/archer/search-or-create/public/{concoursId}", "ArcherSearchController@findOrCreateByLicensePublic");
        
        // Routes des concours (protégées)
        // Routes spécifiques AVANT les routes génériques pour éviter les conflits
        $this->addRoute("GET", "/concours", "ConcoursController@index");
        $this->addRoute("GET", "/concours/create", "ConcoursController@create");
        $this->addRoute("POST", "/concours/store", "ConcoursController@store");
        // Route d'inscription avant les autres routes avec {id}
        $this->addRoute("GET", "/concours/{id}/inscription", "ConcoursController@inscription");
        $this->addRoute("POST", "/concours/{id}/inscription", "ConcoursController@storeInscription");
        // Inscription ciblée (publique, sans authentification)
        $this->addRoute("GET", "/inscription-cible/{id}", "ConcoursController@inscriptionCible");
        $this->addRoute("POST", "/inscription-cible/{id}", "ConcoursController@storeInscriptionCible");
        // Confirmation d'inscription par lien email (publique)
        $this->addRoute("GET", "/inscription-confirmer/{token}", "ConcoursController@confirmerInscription");
        // Annulation d'inscription par lien email (publique)
        $this->addRoute("GET", "/inscription-annuler/{token}", "ConcoursController@annulerInscription");
        // Route pour le plan de cible
        $this->addRoute("GET", "/concours/{id}/plan-cible", "ConcoursController@planCible");
        // Route pour le plan de peloton (Campagne/Nature/3D)
        $this->addRoute("GET", "/concours/{id}/plan-peloton", "ConcoursController@planPeloton");
        // Gestion des produits buvette
        $this->addRoute("GET", "/concours/{id}/buvette", "ConcoursController@buvette");
        // Route pour enregistrer le type de blason d'une cible
        $this->addRoute("POST", "/concours/plan-cible-type-blason", "ConcoursController@planCibleTypeBlason");
        // Route pour la mise à jour d'inscription (POST avec X-HTTP-Method-Override ou PUT)
        $this->addRoute("POST", "/concours/{id}/inscription/{inscriptionId}/update", "ConcoursController@updateInscription");
        $this->addRoute("PUT", "/concours/{id}/inscription/{inscriptionId}/update", "ConcoursController@updateInscription");
        // Routes avec {id} après les routes spécifiques
        $this->addRoute("GET", "/concours/show/{id}", "ConcoursController@show");
        $this->addRoute("GET", "/concours/edit/{id}", "ConcoursController@edit");
        $this->addRoute("POST", "/concours/update/{id}", "ConcoursController@update");
        $this->addRoute("GET", "/concours/delete/{id}", "ConcoursController@delete");
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
        $this->addRoute("GET", "/scored-trainings/images-nature", "ScoredTrainingController@getNatureImages");
        
        // Routes des feuilles de marque (protégées)
        $this->addRoute("GET", "/score-sheet", "ScoreSheetController@index");
        $this->addRoute("POST", "/score-sheet/save", "ScoreSheetController@save");
        
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
        
        // Routes d'import d'utilisateurs (protégées - admin seulement)
        $this->addRoute("GET", "/users/import", "UserImportController@index");
        $this->addRoute("POST", "/users/import/process", "UserImportController@process");
        
        // Routes des messages privés (protégées)
        $this->addRoute("GET", "/private-messages", "PrivateMessagesController@index");
        
        // Routes des groupes (protégées)
        $this->addRoute("GET", "/groups", "GroupController@index");
        $this->addRoute("GET", "/groups/create", "GroupController@create");
        $this->addRoute("GET", "/groups/{id}/members", "GroupController@members");
        $this->addRoute("GET", "/groups/{id}/edit", "GroupController@edit");
        $this->addRoute("GET", "/groups/{id}", "GroupController@show");
        $this->addRoute("POST", "/groups", "GroupController@store");
        $this->addRoute("PUT", "/groups/{id}", "GroupController@update");
        $this->addRoute("DELETE", "/groups/{id}", "GroupController@destroy");
        
        // Routes des clubs (protégées - admin seulement pour création/modification/suppression)
        // IMPORTANT: les routes plus spécifiques doivent être définies AVANT les routes paramétrées générales
        $this->addRoute("GET", "/clubs", "ClubController@index");
        $this->addRoute("GET", "/clubs/create", "ClubController@create");
        $this->addRoute("GET", "/clubs/import", "ClubImportController@index");
        $this->addRoute("POST", "/clubs/import/process", "ClubImportController@process");
        $this->addRoute("GET", "/clubs/{id}/permissions", "ClubPermissionsController@edit");
        $this->addRoute("POST", "/clubs/{id}/permissions", "ClubPermissionsController@update");
        $this->addRoute("GET", "/clubs/{id}/edit", "ClubController@edit");
        $this->addRoute("GET", "/clubs/{id}", "ClubController@show");
        $this->addRoute("POST", "/clubs", "ClubController@store");
        $this->addRoute("PUT", "/clubs/{id}", "ClubController@update");
        $this->addRoute("POST", "/clubs/{id}/update", "ClubController@update");
        $this->addRoute("DELETE", "/clubs/{id}", "ClubController@destroy");
        $this->addRoute("POST", "/clubs/{id}/delete", "ClubController@destroy");
        
        // Routes des thèmes (protégées - admin seulement)
        $this->addRoute("GET", "/themes", "ThemeController@index");
        $this->addRoute("GET", "/themes/create", "ThemeController@create");
        $this->addRoute("GET", "/themes/{id}/edit", "ThemeController@edit");
        $this->addRoute("GET", "/themes/{id}", "ThemeController@show");
        $this->addRoute("POST", "/themes", "ThemeController@store");
        $this->addRoute("PUT", "/themes/{id}", "ThemeController@update");
        $this->addRoute("POST", "/themes/{id}/update", "ThemeController@update");
        $this->addRoute("DELETE", "/themes/{id}", "ThemeController@destroy");
        $this->addRoute("POST", "/themes/{id}/delete", "ThemeController@destroy");
        
        // Routes des sujets (topics) (protégées)
        $this->addRoute("GET", "/groups/{groupId}/topics/create", "TopicController@create");
        $this->addRoute("POST", "/groups/{groupId}/topics", "TopicController@store");
        $this->addRoute("GET", "/groups/{groupId}/topics/{topicId}", "TopicController@show");
        
        // Routes API pour les messages privés
        $this->addRoute("GET", "/api/private-messages/conversations", "ApiController@getPrivateConversations");
        $this->addRoute("GET", "/api/private-messages/{userId}/history", "ApiController@getPrivateHistory");
        $this->addRoute("POST", "/api/private-messages/send", "ApiController@sendPrivateMessage");
        $this->addRoute("POST", "/api/private-messages/{userId}/read", "ApiController@markPrivateMessagesAsRead");
        $this->addRoute("DELETE", "/api/private-messages/{userId}/delete", "ApiController@deletePrivateConversation");
        
        // Routes API pour les messages de groupes
        $this->addRoute("GET", "/api/messages/{groupId}/history", "ApiController@getGroupMessages");
        $this->addRoute("POST", "/api/messages/{groupId}/send", "ApiController@sendGroupMessage");
        
        // Routes API pour les formulaires (proxy vers API externe)
        $this->addRoute("GET", "/api/topics/{topicId}/messages", "ApiController@getTopicMessages");
        $this->addRoute("POST", "/api/topics/{topicId}/messages", "ApiController@sendTopicMessage");
        $this->addRoute("GET", "/api/topics/{topicId}/forms", "ApiController@getTopicForms");
        $this->addRoute("GET", "/api/events/{eventId}/forms", "ApiController@getEventForms");
        $this->addRoute("POST", "/api/forms", "ApiController@createForm");
        $this->addRoute("POST", "/api/forms/{formId}/responses", "ApiController@submitFormResponse");
        $this->addRoute("GET", "/api/forms/{formId}/responses", "ApiController@getFormResponses");
        $this->addRoute("DELETE", "/api/forms/{formId}", "ApiController@deleteForm");
        
        // Routes internes pour les groupes (proxy vers API externe)
        $this->addRoute("GET", "/api/users", "ApiController@users");
        $this->addRoute("POST", "/api/users/import-xml", "UserImportController@importSingleXmlUser");
        $this->addRoute("GET", "/users/{id}/avatar", "ApiController@getUserAvatar");
        $this->addRoute("POST", "/groups/{id}/members", "ApiController@addGroupMembers");
        $this->addRoute("DELETE", "/groups/{id}/remove-member/{memberId}", "ApiController@removeGroupMember");
        
        // Route API pour la recherche d'archers
        $this->addRoute("GET", "/api/archers/search", "ApiController@searchArchers");
        $this->addRoute("POST", "/api/archer/search-or-create", "ApiController@searchOrCreateArcherByLicense");
        
        // Routes API pour les groupes (proxy vers API externe)
        $this->addRoute("GET", "/api/users", "ApiController@users");
        $this->addRoute("POST", "/api/groups/create", "ApiController@createGroup");
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
        
        // Routes API pour les concours (proxy vers backend)
        // Routes publiques (sans auth - inscription ciblée)
        $this->addRoute("GET", "/api/concours/{id}/public", "ApiController@proxyConcoursPublic");
        $this->addRoute("GET", "/api/concours/{id}/inscriptions/public", "ApiController@proxyConcoursInscriptionsPublic");
        $this->addRoute("POST", "/api/concours/{id}/inscription/public", "ApiController@proxyConcoursInscriptionPublic");
        $this->addRoute("GET", "/api/concours/distance-recommandee", "ApiController@proxyConcoursDistanceRecommandee");
        $this->addRoute("GET", "/api/concours/blason-recommandee", "ApiController@proxyConcoursBlasonRecommandee");
        $this->addRoute("GET", "/api/concours/{id}/plan-cible", "ApiController@proxyConcoursPlanCible");
        $this->addRoute("POST", "/api/concours/{id}/plan-cible", "ApiController@proxyConcoursPlanCible");
        $this->addRoute("GET", "/api/concours/{id}/plan-cible/{depart}/cibles", "ApiController@proxyConcoursPlanCibleCibles");
        $this->addRoute("GET", "/api/concours/{id}/plan-cible/{depart}/archers-dispo", "ApiController@proxyConcoursPlanCibleArchersDispo");
        $this->addRoute("POST", "/api/concours/{id}/plan-cible/{depart}/liberer", "ApiController@proxyConcoursPlanCibleLiberer");
        $this->addRoute("POST", "/api/concours/{id}/plan-cible/assign", "ApiController@proxyConcoursPlanCibleAssign");
        $this->addRoute("GET", "/api/concours/{id}/plan-peloton", "ApiController@proxyConcoursPlanPeloton");
        $this->addRoute("POST", "/api/concours/{id}/plan-peloton", "ApiController@proxyConcoursPlanPeloton");
        $this->addRoute("GET", "/api/concours/{id}/plan-peloton/{depart}/archers-dispo", "ApiController@proxyConcoursPlanPelotonArchersDispo");
        $this->addRoute("POST", "/api/concours/{id}/plan-peloton/{depart}/liberer", "ApiController@proxyConcoursPlanPelotonLiberer");
        $this->addRoute("POST", "/api/concours/{id}/plan-peloton/assign", "ApiController@proxyConcoursPlanPelotonAssign");
        $this->addRoute("POST", "/api/concours/{id}/inscriptions/send-confirmation-email", "ApiController@proxyConcoursSendConfirmationEmail");
        $this->addRoute("POST", "/api/concours/{id}/inscriptions/send-confirmation-email/public", "ApiController@proxyConcoursSendConfirmationEmailPublic");
        $this->addRoute("GET", "/api/concours/{id}/inscriptions", "ApiController@proxyConcoursInscriptions");
        $this->addRoute("POST", "/api/concours/{id}/inscription", "ApiController@proxyConcoursInscriptionCreate");
        $this->addRoute("GET", "/api/concours/{id}/inscription/{userId}", "ApiController@proxyConcoursInscription");
        $this->addRoute("DELETE", "/api/concours/{id}/inscription/{userId}", "ApiController@proxyConcoursInscription");
        // Route pour la mise à jour d'inscription (POST avec X-HTTP-Method-Override: PUT ou PUT direct)
        $this->addRoute("POST", "/api/concours/{id}/inscription/{inscriptionId}", "ApiController@proxyConcoursInscriptionUpdate");
        $this->addRoute("PUT", "/api/concours/{id}/inscription/{inscriptionId}", "ApiController@proxyConcoursInscriptionUpdate");
        $this->addRoute("PATCH", "/api/concours/{id}/inscription/{inscriptionId}", "ApiController@proxyConcoursInscriptionUpdate");
        // Buvette - produits (admin, auth)
        $this->addRoute("GET", "/api/concours/{id}/buvette/produits", "ApiController@proxyConcoursBuvetteProduits");
        $this->addRoute("POST", "/api/concours/{id}/buvette/produits", "ApiController@proxyConcoursBuvetteProduitsCreate");
        $this->addRoute("PUT", "/api/concours/{id}/buvette/produits/{produitId}", "ApiController@proxyConcoursBuvetteProduitUpdate");
        $this->addRoute("DELETE", "/api/concours/{id}/buvette/produits/{produitId}", "ApiController@proxyConcoursBuvetteProduitDelete");
        // Buvette - produits public (inscription) et réservations (public)
        $this->addRoute("GET", "/api/concours/{id}/buvette/produits/public", "ApiController@proxyConcoursBuvetteProduitsPublic");
        $this->addRoute("POST", "/api/concours/{id}/buvette/reservations", "ApiController@proxyConcoursBuvetteReservations");
        
        // Routes API (protégées)
        $this->addRoute("GET", "/api/documents", "ApiController@documents");
        $this->addRoute("GET", "/api/documents/user/{id}", "ApiController@userDocuments");
        $this->addRoute("POST", "/api/documents/{id}/upload", "ApiController@uploadDocument");
        $this->addRoute("DELETE", "/api/documents/{id}/delete", "ApiController@deleteDocument");
        $this->addRoute("GET", "/api/documents/{id}/download", "ApiController@downloadDocument");
        $this->addRoute("GET", "/api/stats", "ApiController@stats");
        $this->addRoute("GET", "/api/users", "ApiController@users");
        $this->addRoute("GET", "/api/trainings", "ApiController@trainings");
        $this->addRoute("GET", "/api/scored-trainings", "ApiController@getScoredTrainings");
        $this->addRoute("GET", "/api/training/progress", "ApiController@getTrainingProgress");
        $this->addRoute("GET", "/api/training/dashboard/{id}", "ApiController@getTrainingDashboard");
        $this->addRoute("GET", "/api/training/sessions/user/{userId}", "ApiController@getUserTrainingSessions");
        $this->addRoute("GET", "/api/exercises", "ApiController@getExercises");
        $this->addRoute("GET", "/api/exercises/{id}", "ApiController@getExerciseSheet");
        
        // Route de test simple
        $this->addRoute("GET", "/test-messages", "ApiController@testMessages");
        
        // Routes internes pour les messages des groupes (proxy vers API externe)
        // Avec /api/ pour compatibilité mobile
        $this->addRoute("GET", "/api/messages/attachment/{id}", "ApiController@downloadMessageAttachment");
        $this->addRoute("GET", "/api/messages/image/{id}", "ApiController@getMessageImage");
        $this->addRoute("GET", "/api/messages/{id}/history", "ApiController@getGroupMessages");
        $this->addRoute("POST", "/api/messages/{id}/send", "ApiController@sendGroupMessage");
        $this->addRoute("PUT", "/api/messages/{id}", "ApiController@updateMessage");
        $this->addRoute("DELETE", "/api/messages/{id}", "ApiController@deleteMessage");
        
        // Routes sans /api/ pour compatibilité web
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
        $this->addRoute("GET", "/auth/delete-account", "AuthController@deleteAccount");
        $this->addRoute("POST", "/auth/delete-account-request", "AuthController@deleteAccountRequest");
        $this->addRoute("GET", "/auth/validate-deletion/{token}", "AuthController@validateDeletion");
        
        // Route API pour vérifier le token JWT
        $this->addRoute("GET", "/api/auth/verify", "AuthController@verify");
        $this->addRoute("POST", "/api/auth/verify", "AuthController@verify");
        
        // Routes de validation des utilisateurs (protégées - admin seulement)
        $this->addRoute("GET", "/user-validation", "UserValidationController@index");
        $this->addRoute("POST", "/user-validation/approve", "UserValidationController@approve");
        $this->addRoute("POST", "/user-validation/reject", "UserValidationController@reject");
        $this->addRoute("POST", "/user-validation/delete-user", "UserValidationController@deleteUser");
        
        // Routes des signalements (protégées - admin seulement)
        $this->addRoute("GET", "/signalements", "SignalementsController@index");
        $this->addRoute("GET", "/signalements/message/{messageId}", "SignalementsController@getMessage");
        $this->addRoute("GET", "/signalements/{id}", "SignalementsController@show");
        $this->addRoute("POST", "/signalements/{id}/update", "SignalementsController@update");
        $this->addRoute("POST", "/signalements/{id}/delete", "SignalementsController@delete");
        $this->addRoute("DELETE", "/signalements/{id}", "SignalementsController@delete");
        
        // Route de debug (admin seulement)
        $this->addRoute("GET", "/debug/deletion-pending", "DebugController@deletionPending");
        
        // Routes des paramètres utilisateur (protégées)
        $this->addRoute("GET", "/user-settings", "UserSettingsController@index");
        $this->addRoute("POST", "/user-settings/update-profile-image", "UserSettingsController@updateProfileImage");
        $this->addRoute("POST", "/user-settings/change-password", "UserSettingsController@changePassword");
        
        // Routes de protection des données personnelles (publique)
        $this->addRoute("GET", "/privacy", "PrivacyController@index");
        $this->addRoute("GET", "/donnees-personnelles", "PrivacyController@index");
        
        // Routes du formulaire de contact (publiques)
        $this->addRoute("GET", "/contact", "ContactController@index");
        $this->addRoute("POST", "/contact/send", "ContactController@send");
        
        // Route pour maintenir la session active (protégée)
        $this->addRoute("GET", "/keep-alive.php", "KeepAliveController@ping");
        
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
        // Priorité à plan-cible pour éviter le conflit avec /concours/{id}/inscription
        if (strpos($path, '/plan-cible') !== false) {
            $specificity += 25;
        }
        return $specificity;
    }
    
    public function run() {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        
        // Gérer les méthodes HTTP personnalisées via _method
        if ($requestMethod === "POST" && isset($_POST["_method"])) {
            error_log("DEBUG ROUTER: Méthode changée de POST vers " . strtoupper($_POST["_method"]));
            $requestMethod = strtoupper($_POST["_method"]);
        }
        
        error_log("DEBUG ROUTER: Méthode: " . $requestMethod . ", URI: " . $requestUri);
        error_log("DEBUG ROUTER: basePath: '" . $this->basePath . "'");

        // Supprimer le basePath de l'URI
        $originalUri = $requestUri;
        if ($this->basePath && strpos($requestUri, $this->basePath) === 0) {
            $requestUri = substr($requestUri, strlen($this->basePath));
            error_log("DEBUG ROUTER: URI après suppression basePath: '$requestUri' (original: '$originalUri')");
        }
        
        // Normaliser l'URI (enlever les slashes en double)
        $requestUri = preg_replace('#/+#', '/', $requestUri);
        if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
            $requestUri = rtrim($requestUri, '/');
        }
        if ($requestUri === '') {
            $requestUri = '/';
        }
        
        error_log("DEBUG ROUTER: URI normalisée finale: '$requestUri'");
        
        // Tester chaque route
        $routeFound = false;
        foreach ($this->routes as $route) {
            if ($route["method"] !== $requestMethod) {
                continue;
            }
            
            // Utiliser la méthode convertToRegex existante
            $pattern = $this->convertToRegex($route["path"]);
            
            // Log détaillé pour les requêtes vers plan-cible
            if (strpos($requestUri, "/plan-cible") !== false || strpos($route["path"], "/plan-cible") !== false) {
                $testMatch = preg_match($pattern, $requestUri);
                error_log("DEBUG ROUTER PLAN-CIBLE: Test route - method: $requestMethod, path: '" . $route["path"] . "', pattern: '$pattern', URI: '$requestUri', match: " . ($testMatch ? 'OUI' : 'NON'));
                if ($testMatch) {
                    error_log("DEBUG ROUTER PLAN-CIBLE: MATCH TROUVÉ! Handler: " . $route["handler"]);
                }
            }
            
            // Log détaillé pour les requêtes POST vers /concours/store
            if ($requestMethod === "POST" && strpos($requestUri, "/concours") !== false) {
                $matches = preg_match($pattern, $requestUri);
                error_log("DEBUG ROUTER: Test route POST - path: '" . $route["path"] . "', pattern: '$pattern', URI: '$requestUri', match: " . ($matches ? 'OUI' : 'NON'));
                if ($matches) {
                    error_log("DEBUG ROUTER: MATCH TROUVÉ! Handler: " . $route["handler"]);
                }
            }
           
            if (preg_match($pattern, $requestUri, $matches)) {
                error_log("DEBUG ROUTER: Route trouvée - " . $route["method"] . " " . $route["path"] . " -> " . $route["handler"]);
                $routeFound = true;
                
                // Debug: Stocker dans la session pour vérification
                $_SESSION['debug_router_match'] = [
                    'found' => true,
                    'method' => $route["method"],
                    'path' => $route["path"],
                    'handler' => $route["handler"],
                    'requestUri' => $requestUri,
                    'pattern' => $pattern
                ];
                
                // Extraire le contrôleur et la méthode
                list($controller, $method) = explode("@", $route["handler"]);
                
                // Instancier le contrôleur
                require_once "app/Controllers/" . $controller . ".php";
                $controllerInstance = new $controller();
                
                // Appeler la méthode avec les paramètres capturés
                array_shift($matches); // Retirer la correspondance complète
                call_user_func_array([$controllerInstance, $method], $matches);
                return;
             }
        }
        
        // Gérer l'erreur 404
        error_log("DEBUG ROUTER: AUCUNE ROUTE TROUVÉE pour " . $requestMethod . " " . $requestUri);
        error_log("DEBUG ROUTER: Routes disponibles pour " . $requestMethod . ":");
        foreach ($this->routes as $route) {
            if ($route["method"] === $requestMethod) {
                error_log("  - " . $route["path"] . " -> " . $route["handler"]);
            }
        }
        
        // Debug: Stocker dans la session pour vérification
        $_SESSION['debug_router_404'] = [
            'found' => false,
            'method' => $requestMethod,
            'requestUri' => $requestUri,
            'availableRoutes' => []
        ];
        foreach ($this->routes as $route) {
            if ($route["method"] === $requestMethod) {
                $_SESSION['debug_router_404']['availableRoutes'][] = [
                    'path' => $route["path"],
                    'handler' => $route["handler"],
                    'pattern' => $this->convertToRegex($route["path"])
                ];
            }
        }
        
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
        // Ajouter le délimiteur de début et fin
        $regex = "/^" . $pattern . "$/";
        return $regex;
    }
    
    // Méthode de debug pour tester une route
    public function debugRoute($method, $uri) {
        $pattern = $this->convertToRegex($uri);
        foreach ($this->routes as $route) {
            if ($route["method"] === $method) {
                $routePattern = $this->convertToRegex($route["path"]);
                $matches = preg_match($routePattern, $uri);
                if ($matches) {
                    return ['found' => true, 'route' => $route, 'pattern' => $routePattern];
                }
            }
        }
        return ['found' => false, 'tested_uri' => $uri, 'pattern' => $pattern];
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
                // Si c'est une requête AJAX/API, retourner JSON
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false || 
                               strpos($_SERVER['REQUEST_URI'], '/messages/') !== false;
                
                error_log("[Router Auth] Non authentifié - URI: " . $_SERVER['REQUEST_URI'] . " isAjax: " . ($isAjax ? 'yes' : 'no') . " isApi: " . ($isApiRequest ? 'yes' : 'no'));
                
                if ($isAjax || $isApiRequest) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Non authentifié. Veuillez vous reconnecter.'
                    ]);
                    exit;
                }
                
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



