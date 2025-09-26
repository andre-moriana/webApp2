<?php

require_once "app/Services/ApiService.php";

class EventController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        $events = [];
        $error = null;

        try {
            // Vérifier si l'utilisateur est admin et utiliser des données de test
            $isAdmin = isset($_SESSION["user"]["is_admin"]) && $_SESSION["user"]["is_admin"] === true;
            $isDemoToken = isset($_SESSION["token"]) && strpos($_SESSION["token"], "demo-token-") === 0;
            
            if ($isAdmin && $isDemoToken) {
                error_log("EventController: Utilisation des données de test");
                $events = $this->getTestEvents();
            } else {
                error_log("EventController: Tentative de récupération des événements via API");
                $response = $this->apiService->getEvents();
                
                if ($response["success"] && isset($response["data"]["events"])) {
                    $events = $response["data"]["events"];
                    error_log("EventController: " . count($events) . " événements reçus de l'API");
                } else {
                    error_log("EventController: Échec de récupération des événements: " . ($response["message"] ?? "Erreur inconnue"));
                    $error = "Impossible de charger les événements: " . ($response["message"] ?? "Erreur inconnue");
                }
            }
        } catch (Exception $e) {
            error_log("EventController: Exception lors de la récupération des événements: " . $e->getMessage());
            $error = "Erreur lors du chargement des événements: " . $e->getMessage();
        }

        // Charger les messages du chat pour le premier événement
        $chatMessages = [];
        if (!empty($events) && isset($events[0]) && isset($events[0]["_id"])) {
            try {
                $firstEventId = $events[0]["_id"];
                $messagesResponse = $this->apiService->getEventMessages($firstEventId);
                if ($messagesResponse["success"] && isset($messagesResponse["data"])) {
                    $chatMessages = $messagesResponse["data"];
                }
            } catch (Exception $e) {
                error_log("EventController: Erreur lors du chargement des messages: " . $e->getMessage());
            }
        }

        // Inclure le header
        include "app/Views/layouts/header.php";
        
        // Inclure la vue
        include "app/Views/events/index.php";
        
        // Inclure le footer
        include "app/Views/layouts/footer.php";
    }
    
    public function create() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        // Inclure le header
        include "app/Views/layouts/header.php";
        
        // Inclure la vue
        include "app/Views/events/create.php";
        
        // Inclure le footer
        include "app/Views/layouts/footer.php";
    }
    
    public function store() {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        $eventData = [
            "name" => $_POST["name"] ?? "",
            "description" => $_POST["description"] ?? "",
            "date" => $_POST["date"] ?? "",
            "time" => $_POST["time"] ?? "",

        ];

        try {
            $response = $this->apiService->createEvent($eventData);
            
            if ($response["success"]) {
                header("Location: /events");
                exit;
            } else {
                $error = "Erreur lors de la création de l'événement: " . ($response["message"] ?? "Erreur inconnue");
                
                // Inclure le header
                include "app/Views/layouts/header.php";
                
                // Inclure la vue avec erreur
                include "app/Views/events/create.php";
                
                // Inclure le footer
                include "app/Views/layouts/footer.php";
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la création de l'événement: " . $e->getMessage();
            
            // Inclure le header
            include "app/Views/layouts/header.php";
            
            // Inclure la vue avec erreur
            include "app/Views/events/create.php";
            
            // Inclure le footer
            include "app/Views/layouts/footer.php";
        }
    }
    
    public function show($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        $event = null;
        $error = null;

        try {
            $response = $this->apiService->getEventDetails($id);
            
            if ($response["success"] && isset($response["data"])) {
                $event = $response["data"];
            } else {
                $error = "Événement non trouvé: " . ($response["message"] ?? "Erreur inconnue");
            }
        } catch (Exception $e) {
            $error = "Erreur lors du chargement de l'événement: " . $e->getMessage();
        }

        // Vérifier l'inscription de l'utilisateur
        $isRegistered = false;
        if ($event) {
            try {
                $registrationResponse = $this->apiService->checkEventRegistration($id);
                if ($registrationResponse["success"] && isset($registrationResponse["data"])) {
                    $isRegistered = $registrationResponse["data"]["is_registered"] ?? false;
                }
            } catch (Exception $e) {
                error_log("EventController: Erreur lors de la vérification de l'inscription: " . $e->getMessage());
            }
        }

        // Charger les messages du chat
        $chatMessages = [];
        if ($event) {
            try {
                $messagesResponse = $this->apiService->getEventMessages($id);
                if ($messagesResponse["success"] && isset($messagesResponse["data"])) {
                    $chatMessages = $messagesResponse["data"];
                }
            } catch (Exception $e) {
                error_log("EventController: Erreur lors du chargement des messages: " . $e->getMessage());
            }
        }

        // Inclure le header
        include "app/Views/layouts/header.php";
        
        // Inclure la vue
        include "app/Views/events/show.php";
        
        // Inclure le footer
        include "app/Views/layouts/footer.php";
    }
    
    public function edit($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        $event = null;
        $error = null;

        try {
            $response = $this->apiService->getEventDetails($id);
            
            if ($response["success"] && isset($response["data"])) {
                $event = $response["data"];
            } else {
                $error = "Événement non trouvé: " . ($response["message"] ?? "Erreur inconnue");
            }
        } catch (Exception $e) {
            $error = "Erreur lors du chargement de l'événement: " . $e->getMessage();
        }

        // Inclure le header
        include "app/Views/layouts/header.php";
        
        // Inclure la vue
        include "app/Views/events/edit.php";
        
        // Inclure le footer
        include "app/Views/layouts/footer.php";
    }
    
    public function update($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        $eventData = [
            "name" => $_POST["name"] ?? "",
            "description" => $_POST["description"] ?? "",
            "date" => $_POST["date"] ?? "",
            "time" => $_POST["time"] ?? "",

        ];

        try {
            $response = $this->apiService->updateEvent($id, $eventData);
            
            if ($response["success"]) {
                header("Location: /events");
                exit;
            } else {
                $error = "Erreur lors de la mise à jour de l'événement: " . ($response["message"] ?? "Erreur inconnue");
                                // Récupérer les détails de l'événement pour pré-remplir le formulaire
                try {
                    $eventResponse = $this->apiService->getEventDetails($id);
                    if ($eventResponse["success"] && isset($eventResponse["data"])) {
                        $event = $eventResponse["data"];
                    } else {
                        $event = $eventData; // Fallback
                    }
                } catch (Exception $e) {
                    $event = $eventData; // Fallback
                }

                
                // Inclure le header
                include "app/Views/layouts/header.php";
                
                // Inclure la vue avec erreur
                include "app/Views/events/edit.php";
                
                // Inclure le footer
                include "app/Views/layouts/footer.php";
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour de l'événement: " . $e->getMessage();
                            // Récupérer les détails de l'événement pour pré-remplir le formulaire
                try {
                    $eventResponse = $this->apiService->getEventDetails($id);
                    if ($eventResponse["success"] && isset($eventResponse["data"])) {
                        $event = $eventResponse["data"];
                    } else {
                        $event = $eventData; // Fallback
                    }
                } catch (Exception $e) {
                    $event = $eventData; // Fallback
                }

            
            // Inclure le header
            include "app/Views/layouts/header.php";
            
            // Inclure la vue avec erreur
            include "app/Views/events/edit.php";
            
            // Inclure le footer
            include "app/Views/layouts/footer.php";
        }
    }
    
    public function destroy($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        try {
            $response = $this->apiService->deleteEvent($id);
            
            if ($response["success"]) {
                header("Location: /events");
                exit;
            } else {
                $error = "Erreur lors de la suppression de l'événement: " . ($response["message"] ?? "Erreur inconnue");
                // Rediriger vers la liste avec un message d'erreur
                header("Location: /events?error=" . urlencode($error));
                exit;
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la suppression de l'événement: " . $e->getMessage();
            header("Location: /events?error=" . urlencode($error));
            exit;
        }
    }
    
    public function register($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        try {
            $response = $this->apiService->registerToEvent($id);
            
            if ($response["success"]) {
                header("Location: /events/" . $id);
                exit;
            } else {
                $error = "Erreur lors de l'inscription: " . ($response["message"] ?? "Erreur inconnue");
                header("Location: /events/" . $id . "?error=" . urlencode($error));
                exit;
            }
        } catch (Exception $e) {
            $error = "Erreur lors de l'inscription: " . $e->getMessage();
            header("Location: /events/" . $id . "?error=" . urlencode($error));
            exit;
        }
    }
    
    public function unregister($id) {
        if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
            header("Location: /login");
            exit;
        }

        try {
            $response = $this->apiService->unregisterFromEvent($id);
            
            if ($response["success"]) {
                header("Location: /events/" . $id);
                exit;
            } else {
                $error = "Erreur lors de la désinscription: " . ($response["message"] ?? "Erreur inconnue");
                header("Location: /events/" . $id . "?error=" . urlencode($error));
                exit;
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la désinscription: " . $e->getMessage();
            header("Location: /events/" . $id . "?error=" . urlencode($error));
            exit;
        }
    }
    
    private function getTestEvents() {
        return [
            [
                "id" => 1,
                "name" => "Compétition départementale",
                "description" => "Compétition de tir à l'arc départementale",
                "date" => "2024-03-15 14:00:00",
                "location" => "Stade de Gémenos",
                "current_participants" => 25,
                "organizer_name" => "Admin Gémenos"
            ],
            [
                "id" => 2,
                "name" => "Entraînement libre",
                "description" => "Séance d'entraînement libre pour tous les niveaux",
                "date" => "2024-03-20 18:00:00",
                "location" => "Salle de tir",
                "current_participants" => 12,
                "organizer_name" => "Jean Dupont"
            ],
            [
                "id" => 3,
                "name" => "Stage technique",
                "description" => "Stage de perfectionnement technique",
                "date" => "2024-03-25 09:00:00",
                "location" => "Salle de tir",
                "current_participants" => 8,
                "organizer_name" => "Marie Martin"
            ]
        ];
    }
}
?>






