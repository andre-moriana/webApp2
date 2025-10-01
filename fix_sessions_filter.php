<?php
// Script pour ajouter un filtrage côté frontend
$file = 'app/Controllers/TrainingController.php';
$content = file_get_contents($file);

// Modifier la méthode getSessionsForExercise pour filtrer côté frontend
$oldMethod = '    private function getSessionsForExercise($exerciseId, $userId = null) {
        try {
            $endpoint = "/training?action=sessions&exercise_id=" . $exerciseId;
            
            // Ajouter l\'user_id si fourni
            if ($userId !== null) {
                $endpoint .= "&user_id=" . $userId;
            }
            $response = $this->apiService->makeRequest($endpoint, \'GET\');
            
            if ($response[\'success\'] && !empty($response[\'data\'])) {
                // Vérifier si c\'est le message de test
                if (isset($response[\'data\'][\'message\']) && $response[\'data\'][\'message\'] === \'Training route working\') {
                    return [];
                }
                
                // Si les données sont dans une structure imbriquée
                if (isset($response[\'data\'][\'success\']) && isset($response[\'data\'][\'data\'])) {
                    return $response[\'data\'][\'data\'];
                }
                
                // Vérifier si c\'est un array de sessions
                if (is_array($response[\'data\']) && !isset($response[\'data\'][\'message\'])) {
                    return $response[\'data\'];
                }
                
                // Si c\'est un objet avec des sessions
                if (isset($response[\'data\'][\'sessions\']) && is_array($response[\'data\'][\'sessions\'])) {
                    return $response[\'data\'][\'sessions\'];
                }
            }
            
            return [];
        } catch (Exception $e) {
            error_log(\'Erreur lors de la récupération des sessions pour l\\\'exercice \' . $exerciseId . \': \' . $e->getMessage());
            return [];
        }
    }';

$newMethod = '    private function getSessionsForExercise($exerciseId, $userId = null) {
        try {
            $endpoint = "/training?action=sessions&exercise_id=" . $exerciseId;
            
            // Ajouter l\'user_id si fourni
            if ($userId !== null) {
                $endpoint .= "&user_id=" . $userId;
            }
            $response = $this->apiService->makeRequest($endpoint, \'GET\');
            
            if ($response[\'success\'] && !empty($response[\'data\'])) {
                // Vérifier si c\'est le message de test
                if (isset($response[\'data\'][\'message\']) && $response[\'data\'][\'message\'] === \'Training route working\') {
                    return [];
                }
                
                $sessions = [];
                
                // Si les données sont dans une structure imbriquée
                if (isset($response[\'data\'][\'success\']) && isset($response[\'data\'][\'data\'])) {
                    $sessions = $response[\'data\'][\'data\'];
                }
                // Vérifier si c\'est un array de sessions
                else if (is_array($response[\'data\']) && !isset($response[\'data\'][\'message\'])) {
                    $sessions = $response[\'data\'];
                }
                // Si c\'est un objet avec des sessions
                else if (isset($response[\'data\'][\'sessions\']) && is_array($response[\'data\'][\'sessions\'])) {
                    $sessions = $response[\'data\'][\'sessions\'];
                }
                
                // FILTRER les sessions par utilisateur côté frontend
                if ($userId !== null && !empty($sessions)) {
                    $filteredSessions = [];
                    foreach ($sessions as $session) {
                        // Vérifier si la session appartient à l\'utilisateur sélectionné
                        if (isset($session[\'user_id\']) && (int)$session[\'user_id\'] === (int)$userId) {
                            $filteredSessions[] = $session;
                        }
                    }
                    return $filteredSessions;
                }
                
                return $sessions;
            }
            
            return [];
        } catch (Exception $e) {
            error_log(\'Erreur lors de la récupération des sessions pour l\\\'exercice \' . $exerciseId . \': \' . $e->getMessage());
            return [];
        }
    }';

$content = str_replace($oldMethod, $newMethod, $content);

file_put_contents($file, $content);
echo "Filtrage côté frontend ajouté!\n";
?>
