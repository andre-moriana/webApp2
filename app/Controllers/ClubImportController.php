<?php

require_once 'app/Services/ApiService.php';

class ClubImportController {
    private $apiService;
    
    public function __construct() {
        $this->apiService = new ApiService();
    }
    
    public function index() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérification des droits administrateur
        if (!isset($_SESSION['user']['is_admin']) || !(bool)$_SESSION['user']['is_admin']) {
            $_SESSION['error'] = 'Accès refusé. Seuls les administrateurs peuvent importer des clubs.';
            header('Location: /clubs');
            exit;
        }
        
        $title = 'Import de clubs depuis XML - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/clubs/import.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function process() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérification des droits administrateur
        if (!isset($_SESSION['user']['is_admin']) || !(bool)$_SESSION['user']['is_admin']) {
            $_SESSION['error'] = 'Accès refusé. Seuls les administrateurs peuvent importer des clubs.';
            header('Location: /clubs');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /clubs/import');
            exit;
        }
        
        // Augmenter le timeout et la mémoire pour les fichiers volumineux
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');
        
        // Vérifier qu'un fichier a été uploadé
        if (!isset($_FILES['xml_file'])) {
            $_SESSION['error'] = 'Aucun fichier n\'a été sélectionné. Veuillez choisir un fichier XML.';
            header('Location: /clubs/import');
            exit;
        }
        
        $file = $_FILES['xml_file'];
        
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = $this->getUploadErrorMessage($file['error']);
            $_SESSION['error'] = $errorMessage;
            header('Location: /clubs/import');
            exit;
        }
        
        // Vérifier que le fichier n'est pas vide
        if ($file['size'] === 0) {
            $_SESSION['error'] = 'Le fichier est vide. Veuillez sélectionner un fichier XML valide.';
            header('Location: /clubs/import');
            exit;
        }
        
        // Vérifier la taille du fichier (max 200MB)
        $maxSize = 200 * 1024 * 1024; // 200MB
        if ($file['size'] > $maxSize) {
            $_SESSION['error'] = 'Le fichier est trop volumineux. Taille maximale autorisée : 200 MB.';
            header('Location: /clubs/import');
            exit;
        }
        
        // Vérifier les limites PHP
        $phpMaxUpload = $this->parseSize(ini_get('upload_max_filesize'));
        $phpMaxPost = $this->parseSize(ini_get('post_max_size'));
        $phpMaxSize = min($phpMaxUpload, $phpMaxPost);
        
        if ($file['size'] > $phpMaxSize) {
            $maxSizeMB = round($phpMaxSize / (1024 * 1024), 2);
            $_SESSION['error'] = "Le fichier dépasse la limite PHP configurée sur le serveur ({$maxSizeMB} MB). " .
                                "Contactez l'administrateur pour augmenter les paramètres upload_max_filesize et post_max_size dans php.ini.";
            header('Location: /clubs/import');
            exit;
        }
        
        // Vérifier que le fichier est bien un XML
        $fileInfo = pathinfo($file['name']);
        if (!isset($fileInfo['extension']) || strtolower($fileInfo['extension']) !== 'xml') {
            $_SESSION['error'] = 'Le fichier doit être au format XML (.xml).';
            header('Location: /clubs/import');
            exit;
        }
        
        // Vérifier que le fichier temporaire existe
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            $_SESSION['error'] = 'Impossible de lire le fichier uploadé. Veuillez réessayer.';
            header('Location: /clubs/import');
            exit;
        }
        
        // Utiliser XMLReader pour parser et importer le fichier de manière séquentielle
        $results = $this->importClubsFromXMLReader($file['tmp_name']);
        
        // Préparer les messages de résultat
        $successCount = 0;
        $errorCount = 0;
        $totalCount = 0;
        $errors = [];
        
        foreach ($results as $result) {
            $totalCount++;
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
                if (count($errors) < 50) {
                    $errors[] = $result['message'] ?? 'Erreur inconnue';
                }
            }
        }
        
        if ($totalCount === 0) {
            $_SESSION['error'] = 'Aucun club trouvé dans le fichier XML.';
            header('Location: /clubs/import');
            exit;
        }
        
        // Stocker les résultats dans la session
        $_SESSION['import_results'] = [
            'total' => $totalCount,
            'success' => $successCount,
            'errors' => $errorCount,
            'error_messages' => $errors
        ];
        
        if ($errorCount === 0) {
            $_SESSION['success'] = "Import réussi : {$successCount} club(s) importé(s) avec succès.";
        } else {
            $_SESSION['warning'] = "Import partiel : {$successCount} club(s) importé(s), {$errorCount} erreur(s).";
        }
        
        header('Location: /clubs/import');
        exit;
    }
    
    /**
     * Importe les clubs directement depuis le XML (traitement séquentiel)
     */
    private function importClubsFromXMLReader($filePath) {
        $results = [];
        $batchSize = 50;
        $currentBatch = [];
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $results;
        }
        
        // Récupérer la liste des clubs existants une seule fois au début
        $existingClubs = $this->getExistingClubs();
        
        // Activer la gestion des erreurs libxml
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $reader = new XMLReader();
        
        if (!$reader->open($filePath)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            error_log('Erreur lors de l\'ouverture du fichier XML : ' . print_r($errors, true));
            return $results;
        }
        
        $currentEntry = [];
        $currentTag = '';
        $inTableContenu = false;
        
        // Parcourir le XML de manière séquentielle
        while ($reader->read()) {
            switch ($reader->nodeType) {
                case XMLReader::ELEMENT:
                    $currentTag = $reader->localName;
                    
                    // Détecter le début d'un élément TABLE_CONTENU
                    if ($currentTag === 'TABLE_CONTENU') {
                        $inTableContenu = true;
                        $currentEntry = [];
                    }
                    break;
                    
                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    if ($inTableContenu && !empty($currentTag)) {
                        $value = trim($reader->value);
                        $currentEntry[$currentTag] = $value;
                    }
                    break;
                    
                case XMLReader::END_ELEMENT:
                    // Quand on atteint la fin d'un TABLE_CONTENU, traiter l'entrée
                    if ($reader->localName === 'TABLE_CONTENU' && $inTableContenu) {
                        $club = $this->processClubEntry($currentEntry);
                        if ($club !== null) {
                            $currentBatch[] = $club;
                            
                            // Traiter le lot quand il atteint la taille définie
                            if (count($currentBatch) >= $batchSize) {
                                $batchResults = $this->importClubBatch($currentBatch, $existingClubs);
                                $results = array_merge($results, $batchResults);
                                $currentBatch = [];
                                
                                // Libérer la mémoire
                                gc_collect_cycles();
                            }
                        }
                        $currentEntry = [];
                        $inTableContenu = false;
                    }
                    $currentTag = '';
                    break;
            }
        }
        
        // Traiter le dernier lot s'il reste des clubs
        if (!empty($currentBatch)) {
            $batchResults = $this->importClubBatch($currentBatch, $existingClubs);
            $results = array_merge($results, $batchResults);
        }
        
        $reader->close();
        libxml_clear_errors();
        
        return $results;
    }
    
    /**
     * Récupère la liste des clubs existants depuis l'API
     */
    private function getExistingClubs() {
        $clubsMap = [];
        
        try {
            $response = $this->apiService->makeRequest("clubs/list", 'GET');
            if ($response['success'] && isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $club) {
                    $clubId = $club['id'] ?? $club['_id'] ?? null;
                    $clubName = isset($club['name']) ? strtolower(trim($club['name'])) : null;
                    
                    if ($clubId) {
                        $clubsMap['by_id'][$clubId] = $club;
                    }
                    if ($clubName) {
                        $clubsMap['by_name'][$clubName] = $club;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des clubs existants: " . $e->getMessage());
        }
        
        return $clubsMap;
    }
    
    /**
     * Traite une entrée club et retourne les données formatées ou null si l'entrée doit être ignorée
     */
    private function processClubEntry($entry) {
        $agreenum = trim($entry['AGREENUM'] ?? '');
        $nomClub = trim($entry['INntituleclub'] ?? '');
        $localite = trim($entry['Localite'] ?? '');
        
        // Ignorer les entrées sans nom de club
        if (empty($nomClub)) {
            return null;
        }
        
        return [
            'id' => $agreenum, // AGREENUM sera utilisé comme identifiant
            'name' => $nomClub,
            'city' => $localite
        ];
    }
    
    /**
     * Importe un lot de clubs
     */
    private function importClubBatch($clubs, $existingClubs = []) {
        $results = [];
        
        foreach ($clubs as $clubData) {
            try {
                // Vérifier si le club existe déjà (par ID ou nom)
                $existingClub = null;
                
                // Essayer de trouver le club par son ID (AGREENUM)
                if (!empty($clubData['id']) && isset($existingClubs['by_id'][$clubData['id']])) {
                    $existingClub = $existingClubs['by_id'][$clubData['id']];
                }
                
                // Si pas trouvé par ID, essayer par nom
                if (!$existingClub && !empty($clubData['name'])) {
                    $normalizedName = strtolower(trim($clubData['name']));
                    if (isset($existingClubs['by_name'][$normalizedName])) {
                        $existingClub = $existingClubs['by_name'][$normalizedName];
                    }
                }
                
                // Préparer les données pour la création/mise à jour
                $createData = [
                    'name' => $clubData['name']
                ];
                
                // Ajouter la ville si disponible
                if (!empty($clubData['city'])) {
                    $createData['city'] = $clubData['city'];
                }
                
                // Si le club existe déjà, le mettre à jour
                if ($existingClub) {
                    $clubId = $existingClub['id'] ?? $existingClub['_id'] ?? null;
                    if ($clubId) {
                        $response = $this->apiService->makeRequest("clubs/{$clubId}", 'PUT', $createData);
                        
                        if ($response['success']) {
                            $results[] = [
                                'success' => true,
                                'club' => $clubData['name'],
                                'message' => "Club {$clubData['name']} mis à jour avec succès"
                            ];
                        } else {
                            $results[] = [
                                'success' => false,
                                'club' => $clubData['name'],
                                'message' => $response['message'] ?? 'Erreur lors de la mise à jour'
                            ];
                        }
                    } else {
                        $results[] = [
                            'success' => false,
                            'club' => $clubData['name'],
                            'message' => 'Club existant trouvé mais ID invalide'
                        ];
                    }
                } else {
                    // Créer un nouveau club
                    $response = $this->apiService->makeRequest('clubs/create', 'POST', $createData);
                    
                    if ($response['success']) {
                        $results[] = [
                            'success' => true,
                            'club' => $clubData['name'],
                            'message' => "Club {$clubData['name']} créé avec succès"
                        ];
                    } else {
                        $results[] = [
                            'success' => false,
                            'club' => $clubData['name'],
                            'message' => $response['message'] ?? 'Erreur lors de la création'
                        ];
                    }
                }
            } catch (Exception $e) {
                $results[] = [
                    'success' => false,
                    'club' => $clubData['name'] ?? 'Inconnu',
                    'message' => 'Exception : ' . $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                $maxSize = ini_get('upload_max_filesize');
                $maxSizeMB = $this->parseSize($maxSize) / (1024 * 1024);
                return "Le fichier dépasse la taille maximale autorisée par le serveur ({$maxSize} = " . round($maxSizeMB, 2) . " MB). " .
                       "Contactez l'administrateur pour augmenter upload_max_filesize dans php.ini.";
            case UPLOAD_ERR_FORM_SIZE:
                return "Le fichier dépasse la taille maximale autorisée par le formulaire.";
            case UPLOAD_ERR_PARTIAL:
                return "Le fichier n'a été que partiellement uploadé. Veuillez réessayer.";
            case UPLOAD_ERR_NO_FILE:
                return "Aucun fichier n'a été uploadé. Veuillez sélectionner un fichier.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Erreur serveur : répertoire temporaire manquant. Contactez l'administrateur.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Erreur serveur : impossible d'écrire le fichier sur le disque. Contactez l'administrateur.";
            case UPLOAD_ERR_EXTENSION:
                return "L'upload du fichier a été arrêté par une extension PHP. Contactez l'administrateur.";
            default:
                return "Erreur inconnue lors de l'upload (code: {$errorCode}). Veuillez réessayer ou contacter l'administrateur.";
        }
    }
    
    /**
     * Convertit une taille PHP (ex: "100M", "50M") en octets
     */
    private function parseSize($size) {
        $size = trim($size);
        if (empty($size)) {
            return 0;
        }
        
        $last = strtolower($size[strlen($size) - 1]);
        $value = (float)$size;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
                break;
            default:
                break;
        }
        
        return (int)$value;
    }
}

