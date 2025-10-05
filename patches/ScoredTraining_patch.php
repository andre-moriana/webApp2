<?php
// PATCH pour la méthode getById() modifiée
// Remplacer le contenu du fichier models/ScoredTraining.php par ce contenu

<?php

class ScoredTraining {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Obtenir les configurations suggérées selon le type de tir
    public function getShootingTypeConfigurations() {
        return [
            'TAE' => [
                'total_ends' => 12,
                'arrows_per_end' => 6,
                'total_arrows' => 72,
                'description' => '12 volées de 6 flèches'
            ],
            'Salle' => [
                'total_ends' => 20,
                'arrows_per_end' => 3,
                'total_arrows' => 60,
                'description' => '20 volées de 3 flèches'
            ],
            '3D' => [
                'total_ends' => 24,
                'arrows_per_end' => 2,
                'total_arrows' => 48,
                'description' => '24 volées de 2 flèches'
            ],
            'Nature' => [
                'total_ends' => 21,
                'arrows_per_end' => 2,
                'total_arrows' => 42,
                'description' => '21 volées de 2 flèches'
            ],
            'Campagne' => [
                'total_ends' => 24,
                'arrows_per_end' => 3,
                'total_arrows' => 72,
                'description' => '24 volées de 3 flèches'
            ],
            'Libre' => [
                'total_ends' => 0, // Sera défini par l'utilisateur
                'arrows_per_end' => 0, // Sera défini par l'utilisateur
                'total_arrows' => 0, // Sera calculé
                'description' => 'Configuration libre - définie par l\'utilisateur'
            ]
        ];
    }
    
    // Obtenir la configuration suggérée pour un type de tir spécifique
    public function getConfigurationForShootingType($shootingType) {
        $configurations = $this->getShootingTypeConfigurations();
        return isset($configurations[$shootingType]) ? $configurations[$shootingType] : null;
    }
    
    // Créer un nouveau tir compté avec configuration automatique
    public function createWithAutoConfig($userId, $data) {
        // Si un type de tir est spécifié et n'est pas 'Libre', appliquer la configuration automatique
        if (isset($data['shooting_type']) && $data['shooting_type'] !== 'Libre' && !isset($data['total_ends']) && !isset($data['arrows_per_end'])) {
            $config = $this->getConfigurationForShootingType($data['shooting_type']);
            if ($config) {
                $data['total_ends'] = $config['total_ends'];
                $data['arrows_per_end'] = $config['arrows_per_end'];
                $data['total_arrows'] = $config['total_arrows'];
            }
        }
        
        return $this->create($userId, $data);
    }
    
    // Créer un nouveau tir compté
    public function create($userId, $data) {
        error_log('Création tir compté - Données reçues: ' . json_encode($data));
        $sql = "INSERT INTO scored_trainings (user_id, exercise_sheet_id, title, total_ends, arrows_per_end, total_arrows, notes, shooting_type, start_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $totalArrows = $data['total_ends'] * $data['arrows_per_end'];
        
        // Pas de valeur par défaut pour shooting_type
        $shootingType = isset($data['shooting_type']) ? $data['shooting_type'] : null;
        
        $stmt->bind_param("iisiiiss", 
            $userId,
            $data['exercise_sheet_id'],
            $data['title'],
            $data['total_ends'],
            $data['arrows_per_end'],
            $totalArrows,
            $data['notes'],
            $shootingType
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    public function updateNote($trainingId, $note) {
        $sql = "UPDATE scored_trainings SET notes = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $note, $trainingId);
        error_log('updateNote: note=' . $note . ', trainingId=' . $trainingId);
        $result = $stmt->execute();
        error_log('updateNote: result=' . ($result ? 'OK' : 'FAIL') . ', error=' . $stmt->error);
        return $result;
    }    

    // Mettre à jour une volée
    public function updateEnd($endId, $userId, $data) {
        error_log('🎯 === DÉBUT updateEnd ===');
        error_log('🎯 endId: ' . $endId);
        error_log('🎯 userId: ' . $userId);
        error_log('🎯 data: ' . json_encode($data));
        
        // Vérifier que la volée appartient à l'utilisateur
        $sql = "SELECT se.id FROM scored_ends se 
                JOIN scored_trainings st ON se.scored_training_id = st.id 
                WHERE se.id = ? AND st.user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $endId, $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            error_log('❌ Volée non trouvée ou n\'appartient pas à l\'utilisateur');
            return false;
        }
        
        // Mettre à jour la volée
        $sql = "UPDATE scored_ends SET 
                comment = ?, 
                target_category = ?, 
                shooting_position = ?,
                latitude = ?,
                longitude = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $comment = isset($data['comment']) ? $data['comment'] : null;
        $targetCategory = isset($data['target_category']) ? $data['target_category'] : null;
        $shootingPosition = isset($data['shooting_position']) ? $data['shooting_position'] : null;
        $latitude = isset($data['latitude']) ? $data['latitude'] : null;
        $longitude = isset($data['longitude']) ? $data['longitude'] : null;
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssssdd", $comment, $targetCategory, $shootingPosition, $latitude, $longitude, $endId);
        
        $result = $stmt->execute();
        error_log('🎯 === FIN updateEnd - ' . ($result ? 'SUCCÈS' : 'ÉCHEC') . ' ===');
        return $result;
    }
    
    // Obtenir le tableau de bord du tir compté
    public function getDashboard($userId, $exerciseId = null, $shootingType = null) {
        
        $sql = "SELECT 
                    st.*,
                    es.title as exercise_title
                FROM scored_trainings st
                LEFT JOIN exercise_sheets es ON st.exercise_sheet_id = es.id
                WHERE st.user_id = ?";
        
        $params = [$userId];
        $types = "i";
        
        if ($exerciseId) {
            $sql .= " AND st.exercise_sheet_id = ?";
            $params[] = $exerciseId;
            $types .= "i";
        }
        
        if ($shootingType && $shootingType !== 'Tous' && $shootingType !== '') {
            // Vérifier que le type de tir est valide
            $validTypes = ['TAE', 'Salle', '3D', 'Nature', 'Campagne', 'Libre'];
            if (!in_array($shootingType, $validTypes)) {
                return ['current_training' => null, 'recent_trainings' => [], 'training_stats' => []];
            }
            
            $sql .= " AND st.shooting_type = ?";
            $params[] = $shootingType;
            $types .= "s";
        }
        
        $sql .= " ORDER BY st.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['current_training' => null, 'recent_trainings' => [], 'training_stats' => []];
        }
        
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        if (!$result) {
            error_log('❌ Erreur d\'exécution SQL: ' . $stmt->error);
            return ['current_training' => null, 'recent_trainings' => [], 'training_stats' => []];
        }
        
        $result = $stmt->get_result();
        
        $trainings = [];
        $currentTraining = null;
        $recentTrainings = [];
        $stats = [
            'total_trainings' => 0,
            'total_arrows' => 0,
            'total_ends' => 0,
            'total_score' => 0,
            'average_score' => 0,
            'average_arrows_per_training' => 0,
            'average_ends_per_training' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            try {
                $training = $this->formatTraining($row);
                $training['ends'] = $this->getEnds($training['id']);
            
                // Trouver le tir en cours
                if ($training['status'] === 'en_cours') {
                    $currentTraining = $training;
                }
                
                // Ajouter aux tirs récents (tous les tirs)
                $recentTrainings[] = $training;
            
                // Calculer les statistiques
                $stats['total_trainings']++;
                $stats['total_arrows'] += $training['total_arrows'];
                $stats['total_ends'] += $training['total_ends'];
                $stats['total_score'] += $training['total_score'];
            } catch (Exception $e) {
                // Ignorer les erreurs de traitement
            }
        }
        
        // Calculer les moyennes
        if ($stats['total_trainings'] > 0) {
            $stats['average_score'] = round($stats['total_score'] / $stats['total_arrows'], 2);
            $stats['average_arrows_per_training'] = round($stats['total_arrows'] / $stats['total_trainings'], 1);
            $stats['average_ends_per_training'] = round($stats['total_ends'] / $stats['total_trainings'], 1);
        }
        
        return [
            'current_training' => $currentTraining,
            'recent_trainings' => $recentTrainings,
            'training_stats' => $stats
        ];
    }
    
    // Obtenir un tir compté avec ses volées
    public function getById($trainingId, $userId = null) {
        if ($userId === null) {
            // Récupération sans filtre utilisateur (pour les admins)
            $sql = "SELECT 
                        st.*,
                        es.title as exercise_title
                    FROM scored_trainings st
                    LEFT JOIN exercise_sheets es ON st.exercise_sheet_id = es.id
                    WHERE st.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $trainingId);
        } else {
            // Récupération avec filtre utilisateur (pour les utilisateurs normaux)
            $sql = "SELECT 
                        st.*,
                        es.title as exercise_title
                    FROM scored_trainings st
                    LEFT JOIN exercise_sheets es ON st.exercise_sheet_id = es.id
                    WHERE st.id = ? AND st.user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $trainingId, $userId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $training = $this->formatTraining($row);
            $training['ends'] = $this->getEnds($trainingId);
            return $training;
        }
        
        return null;
    }
    
    // Obtenir les volées d'un tir compté
    public function getEnds($trainingId) {
        $sql = "SELECT 
                    se.*,
                    GROUP_CONCAT(ss.score ORDER BY ss.arrow_number) as scores
                FROM scored_ends se
                LEFT JOIN scored_shots ss ON se.id = ss.scored_end_id
                WHERE se.scored_training_id = ?
                GROUP BY se.id
                ORDER BY se.end_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $trainingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ends = [];
        while ($row = $result->fetch_assoc()) {
            $end = [
                'id' => $row['id'],
                'end_number' => $row['end_number'],
                'total_score' => $row['total_score'],
                'comment' => $row['comment'],
                'target_category' => $row['target_category'] ? $row['target_category'] : null,
                'shooting_position' => $row['shooting_position'],
                'latitude' => $row['latitude'] ? (float)$row['latitude'] : null,
                'longitude' => $row['longitude'] ? (float)$row['longitude'] : null,
                'created_at' => $row['created_at'],
                'updated_at' => isset($row['updated_at']) ? $row['updated_at'] : null,
                'shots' => []
            ];
            
            // Récupérer les tirs individuels
            if ($row['scores']) {
                $scores = explode(',', $row['scores']);
                for ($i = 0; $i < count($scores); $i++) {
                    $end['shots'][] = [
                        'id' => $row['id'] . '_' . ($i + 1),
                        'arrow_number' => $i + 1,
                        'score' => (int)$scores[$i],
                        'created_at' => $row['created_at']
                    ];
                }
            }
            
            $ends[] = $end;
        }
        
        return $ends;
    }
    
    // Terminer un tir compté
    public function end($trainingId, $userId, $data) {
        $sql = "UPDATE scored_trainings 
                SET status = 'terminé', end_date = NOW(), notes = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $data['notes'], $trainingId, $userId);
        
        return $stmt->execute();
    }
    
    // Ajouter une volée
    public function addEnd($trainingId, $userId, $endData) {
        // Vérifier que le tir appartient à l'utilisateur
        $sql = "SELECT id FROM scored_trainings WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $trainingId, $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            return false;
        }
        
        // Calculer le score total de la volée
        $totalScore = array_sum($endData['scores']);
        
        // Insérer la volée avec les nouveaux champs incluant GPS
        $sql = "INSERT INTO scored_ends (scored_training_id, end_number, total_score, comment, target_category, shooting_position, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $endNumber = $endData['end_number'];
        $comment = isset($endData['comment']) ? $endData['comment'] : null;
        $targetCategory = (isset($endData['target_category']) && !empty($endData['target_category'])) ? $endData['target_category'] : null;
        $shootingPosition = (isset($endData['shooting_position']) && !empty($endData['shooting_position'])) ? $endData['shooting_position'] : null;
        $latitude = isset($endData['latitude']) ? $endData['latitude'] : null;
        $longitude = isset($endData['longitude']) ? $endData['longitude'] : null;
        
        $stmt->bind_param("iiisssdd", $trainingId, $endNumber, $totalScore, $comment, $targetCategory, $shootingPosition, $latitude, $longitude);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        $endId = $this->db->insert_id;
        
        // Insérer les tirs individuels
        foreach ($endData['scores'] as $arrowNumber => $score) {
            $sql = "INSERT INTO scored_shots (scored_end_id, arrow_number, score) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $arrowNum = $arrowNumber + 1;
            $stmt->bind_param("iii", $endId, $arrowNum, $score);
            $stmt->execute();
        }
        
        // Mettre à jour les statistiques du tir compté
        $this->updateTrainingStats($trainingId);
        
        return true;
    }
    
    // Mettre à jour les statistiques d'un tir compté
    private function updateTrainingStats($trainingId) {
        $sql = "UPDATE scored_trainings st
                SET total_score = (
                    SELECT COALESCE(SUM(se.total_score), 0)
                    FROM scored_ends se
                    WHERE se.scored_training_id = st.id
                ),
                average_score = (
                    SELECT COALESCE(AVG(ss.score), 0)
                    FROM scored_ends se
                    JOIN scored_shots ss ON se.id = ss.scored_end_id
                    WHERE se.scored_training_id = st.id
                )
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $trainingId);
        $stmt->execute();
    }
    
    // Supprimer un tir compté
    public function delete($trainingId, $userId) {
        $sql = "DELETE FROM scored_trainings WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $trainingId, $userId);
        
        return $stmt->execute();
    }
    
    // Supprimer un tir compté (pour les admins/coaches)
    public function deleteByAdmin($trainingId) {
        $sql = "DELETE FROM scored_trainings WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $trainingId);
        
        return $stmt->execute();
    }
    
    // Obtenir les statistiques par type de tir
    public function getStatsByShootingType($userId, $exerciseId = null) {
        $sql = "SELECT 
                    st.shooting_type,
                    COUNT(*) as total_trainings,
                    SUM(st.total_arrows) as total_arrows,
                    SUM(st.total_ends) as total_ends,
                    SUM(st.total_score) as total_score,
                    AVG(st.average_score) as average_score,
                    MAX(st.total_score) as best_training_score,
                    MAX(st.start_date) as last_training_date
                FROM scored_trainings st
                WHERE st.user_id = ?";
        
        $params = [$userId];
        $types = "i";
        
        if ($exerciseId) {
            $sql .= " AND st.exercise_sheet_id = ?";
            $params[] = $exerciseId;
            $types .= "i";
        }
        
        $sql .= " GROUP BY st.shooting_type ORDER BY st.shooting_type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $statsByType = [];
        while ($row = $result->fetch_assoc()) {
            $shootingType = $row['shooting_type'] ?? null;
            $statsByType[$shootingType] = [
                'shooting_type' => $shootingType,
                'total_trainings' => (int)$row['total_trainings'],
                'total_arrows' => (int)$row['total_arrows'],
                'total_ends' => (int)$row['total_ends'],
                'total_score' => (int)$row['total_score'],
                'average_score' => round((float)$row['average_score'], 2),
                'best_training_score' => (int)$row['best_training_score'],
                'last_training_date' => $row['last_training_date'],
                'average_arrows_per_training' => $row['total_trainings'] > 0 ? round($row['total_arrows'] / $row['total_trainings'], 1) : 0,
                'average_ends_per_training' => $row['total_trainings'] > 0 ? round($row['total_ends'] / $row['total_trainings'], 1) : 0,
                'average_score_per_arrow' => $row['total_arrows'] > 0 ? round($row['total_score'] / $row['total_arrows'], 2) : 0
            ];
        }
        
        return $statsByType;
    }
    
    // Obtenir les coordonnées GPS d'un entraînement pour la carte du parcours
    public function getTrainingGPSData($trainingId, $userId) {
        $sql = "SELECT 
                    se.id,
                    se.end_number,
                    se.latitude,
                    se.longitude,
                    se.target_category,
                    se.shooting_position,
                    se.total_score,
                    se.created_at,
                    se.comment,
                    st.shooting_type
                FROM scored_ends se
                JOIN scored_trainings st ON se.scored_training_id = st.id
                WHERE st.id = ? AND st.user_id = ? AND se.latitude IS NOT NULL AND se.longitude IS NOT NULL
                ORDER BY se.end_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $trainingId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $gpsData = [];
        while ($row = $result->fetch_assoc()) {
            $gpsData[] = [
                'id' => $row['id'],
                'end_number' => $row['end_number'],
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'target_category' => $row['target_category'],
                'shooting_position' => $row['shooting_position'],
                'total_score' => $row['total_score'],
                'created_at' => $row['created_at'],
                'comment' => $row['comment'],
                'shooting_type' => $row['shooting_type']
            ];
        }
        
        return $gpsData;
    }
    
    // Formater un tir compté
    private function formatTraining($row) {
        return [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'exercise_sheet_id' => $row['exercise_sheet_id'] ? (int)$row['exercise_sheet_id'] : null,
            'training_session_id' => $row['training_session_id'] ? (int)$row['training_session_id'] : null,
            'title' => $row['title'],
            'total_ends' => (int)$row['total_ends'],
            'arrows_per_end' => (int)$row['arrows_per_end'],
            'total_arrows' => (int)$row['total_arrows'],
            'total_score' => (int)$row['total_score'],
            'average_score' => (float)$row['average_score'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'shooting_type' => $row['shooting_type'] ?? null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'exercise_title' => $row['exercise_title']
        ];
    }
} 