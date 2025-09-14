<?php
// Corriger GroupController pour utiliser les vraies données API
$groupControllerContent = file_get_contents("app/Controllers/GroupController.php");

// Ajouter l'inclusion d'ApiService au début
$oldStart = '<?php

class GroupController {';

$newStart = '<?php

// Inclure ApiService
require_once __DIR__ . "/../Services/ApiService.php";

class GroupController {';

$correctedContent = str_replace($oldStart, $newStart, $groupControllerContent);

// Modifier la méthode index pour mieux gérer les données API
$oldIndex = '        try {
            // Essayer de récupérer les groupes depuis l\'API
            $response = $this->apiService->getGroups();
            if ($response[\'success\'] && !empty($response[\'data\'][\'groups\'])) {
                $groups = $response[\'data\'][\'groups\'];
            } else {
                // Si l\'API ne fonctionne pas, utiliser des données simulées
                $groups = $this->getSimulatedGroups();
                $error = \'API backend non accessible - Affichage de données simulées\';
            }
        } catch (Exception $e) {
            // En cas d\'erreur, utiliser des données simulées
            $groups = $this->getSimulatedGroups();
            $error = \'Erreur de connexion à l\'API - Affichage de données simulées\';
        }';

$newIndex = '        try {
            // Essayer de récupérer les groupes depuis l\'API
            $response = $this->apiService->getGroups();
            error_log("DEBUG GroupController::index - Réponse API: " . json_encode($response));
            
            if ($response[\'success\'] && !empty($response[\'data\'][\'groups\'])) {
                $groups = $response[\'data\'][\'groups\'];
                error_log("DEBUG GroupController::index - Groupes récupérés: " . count($groups));
            } else {
                // Si l\'API ne fonctionne pas, utiliser des données simulées
                $groups = $this->getSimulatedGroups();
                $error = \'API backend non accessible - Affichage de données simulées\';
                error_log("DEBUG GroupController::index - Utilisation des données simulées");
            }
        } catch (Exception $e) {
            // En cas d\'erreur, utiliser des données simulées
            $groups = $this->getSimulatedGroups();
            $error = \'Erreur de connexion à l\'API - Affichage de données simulées\';
            error_log("DEBUG GroupController::index - Exception: " . $e->getMessage());
        }';

$correctedContent = str_replace($oldIndex, $newIndex, $correctedContent);

file_put_contents("app/Controllers/GroupController.php", $correctedContent);
echo " GroupController corrigé pour utiliser les vraies données API\n";
?>
