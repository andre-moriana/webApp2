<?php

require_once 'app/Services/ApiService.php';
require_once 'app/Controllers/UserImportController.php';

class ArcherSearchController {
    private $apiService;
    private $userImportController;
    
    public function __construct() {
        $this->apiService = new ApiService();
        $this->userImportController = new UserImportController();
    }
    
    /**
     * Trouve ou crée un archer basé sur son numéro de licence
     * Recherche d'abord dans la BD, puis dans le XML si pas trouvé
     * Retourne l'utilisateur avec son ID
     */
    public function findOrCreateByLicense() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $licenceNumber = trim($input['licence_number'] ?? $input['license_number'] ?? '');
        
        error_log("=== ArcherSearchController::findOrCreateByLicense ===");
        error_log("Licence reçue: " . $licenceNumber);
        
        if (empty($licenceNumber)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Numéro de licence requis']);
            return;
        }
        
        try {
            // Étape 1 : Chercher dans la base de données
            error_log("ÉTAPE 1: Recherche dans la BD pour licence: " . $licenceNumber);
            $bdResult = $this->apiService->makeRequest(
                'users?licence_number=' . urlencode($licenceNumber),
                'GET'
            );
            
            if ($bdResult['success'] && !empty($bdResult['data'])) {
                // Utilisateur trouvé dans la BD
                $users = $bdResult['data'];
                if (is_array($users) && count($users) > 0) {
                    $user = $users[0];
                    $userId = $user['_id'] ?? $user['id'] ?? null;
                    
                    if ($userId) {
                        error_log("✓ Utilisateur trouvé dans BD - ID: " . $userId);
                        echo json_encode([
                            'success' => true,
                            'source' => 'database',
                            'data' => [
                                'user_id' => $userId,
                                'licence_number' => $licenceNumber,
                                'first_name' => $user['first_name'] ?? $user['firstName'] ?? '',
                                'name' => $user['name'] ?? '',
                                'club' => $user['club'] ?? $user['club_name'] ?? '',
                                'age_category' => $user['age_category'] ?? $user['ageCategory'] ?? '',
                                'bow_type' => $user['bow_type'] ?? $user['bowType'] ?? ''
                            ]
                        ]);
                        return;
                    }
                }
            }
            
            // Étape 2 : Chercher dans le XML
            error_log("ÉTAPE 2: Recherche dans le XML pour licence: " . $licenceNumber);
            $xmlPath = __DIR__ . '/../../public/data/users-licences.xml';
            
            if (!file_exists($xmlPath) || !is_readable($xmlPath)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Archer non trouvé (ni en BD ni en XML)']);
                return;
            }
            
            $xmlEntry = $this->findXmlEntryByLicence($xmlPath, $licenceNumber);
            if (!$xmlEntry) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Archer non trouvé dans le système']);
                return;
            }
            
            error_log("✓ Entrée XML trouvée pour licence: " . $licenceNumber);
            
            // Étape 3 : Traiter l'entrée XML
            $userData = $this->processUserEntry($xmlEntry);
            if (!$userData) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Entrée XML invalide']);
                return;
            }
            
            // Étape 4 : Créer l'utilisateur dans la BD
            error_log("ÉTAPE 3: Création de l'utilisateur en BD depuis XML");
            $createData = [
                'first_name' => $userData['first_name'] ?? '',
                'name' => $userData['name'] ?? '',
                'username' => $userData['username'] ?? $userData['licenceNumber'],
                'email' => $userData['email'] ?? ($userData['username'] . '@archers-gemenos.fr'),
                'password' => $userData['password'] ?? 'Temp' . bin2hex(random_bytes(8)),
                'licenceNumber' => $userData['licenceNumber'] ?? $licenceNumber,
                'role' => 'Archer',
                'status' => 'pending',
                'requires_approval' => true,
                'clubId' => $userData['club'] ?? null
            ];
            
            error_log("createData: " . json_encode($createData, JSON_UNESCAPED_UNICODE));
            
            $createResult = $this->apiService->createUser($createData);
            error_log("Résultat createUser: " . json_encode($createResult, JSON_UNESCAPED_UNICODE));
            
            if (!$createResult['success']) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la création du compte']);
                return;
            }
            
            // Extraire l'ID de l'utilisateur créé
            $userId = null;
            if (isset($createResult['data']['user']['_id'])) {
                $userId = $createResult['data']['user']['_id'];
            } elseif (isset($createResult['data']['user']['id'])) {
                $userId = $createResult['data']['user']['id'];
            } elseif (isset($createResult['data']['_id'])) {
                $userId = $createResult['data']['_id'];
            } elseif (isset($createResult['data']['id'])) {
                $userId = $createResult['data']['id'];
            }
            
            if (!$userId) {
                error_log("ERREUR: Impossible d'extraire user_id de la réponse createUser");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erreur: Impossible de récupérer l\'ID utilisateur']);
                return;
            }
            
            error_log("✓ Utilisateur créé en BD - ID: " . $userId);
            
            // Mettre à jour les informations supplémentaires
            if (!empty($userData['birthDate']) || !empty($userData['gender']) || !empty($userData['ageCategory'])) {
                $updateData = [];
                if (!empty($userData['birthDate'])) {
                    $updateData['birthDate'] = $userData['birthDate'];
                }
                if (!empty($userData['gender'])) {
                    $updateData['gender'] = $userData['gender'];
                }
                if (!empty($userData['ageCategory'])) {
                    $updateData['ageCategory'] = $userData['ageCategory'];
                }
                if (!empty($userData['bowType'])) {
                    $updateData['bowType'] = $userData['bowType'];
                }
                
                $this->apiService->updateUser($userId, $updateData);
            }
            
            echo json_encode([
                'success' => true,
                'source' => 'xml_created',
                'data' => [
                    'user_id' => $userId,
                    'licence_number' => $licenceNumber,
                    'first_name' => $userData['first_name'] ?? '',
                    'name' => $userData['name'] ?? '',
                    'club' => $userData['club'] ?? '',
                    'age_category' => $userData['ageCategory'] ?? '',
                    'bow_type' => $userData['bowType'] ?? '',
                    'categorie' => $userData['categorie'] ?? ''
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("ERREUR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Cherche une entrée XML par numéro de licence
     */
    private function findXmlEntryByLicence($xmlPath, $target) {
        $reader = new XMLReader();
        if (!$reader->open($xmlPath)) {
            error_log("Erreur: Impossible d'ouvrir le fichier XML");
            return null;
        }
        
        libxml_clear_errors();
        $currentEntry = [];
        $inTableContenu = false;
        $currentTag = '';
        
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                $tagName = $reader->name;
                if ($tagName === 'TABLE_CONTENU') {
                    $inTableContenu = true;
                    $currentEntry = [];
                } elseif ($inTableContenu && !$reader->isEmptyElement) {
                    $currentTag = $tagName;
                }
            } elseif ($reader->nodeType === XMLReader::TEXT && $inTableContenu && $currentTag) {
                $currentEntry[$currentTag] = $reader->value;
                $currentTag = '';
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->name === 'TABLE_CONTENU' && $inTableContenu) {
                    $entryLicence = $currentEntry['IDLicence'] ?? '';
                    if ($entryLicence !== '' && $entryLicence === $target) {
                        $reader->close();
                        libxml_clear_errors();
                        return $currentEntry;
                    }
                    $currentEntry = [];
                    $inTableContenu = false;
                }
                $currentTag = '';
            }
        }
        
        $reader->close();
        libxml_clear_errors();
        return null;
    }
    
    /**
     * Traite une entrée XML et retourne les données utilisateur
     */
    private function processUserEntry($entry) {
        if (empty($entry)) {
            return null;
        }
        
        $licenceNumber = $entry['IDLicence'] ?? '';
        $nom = $entry['NOM'] ?? '';
        $prenom = $entry['PRENOM'] ?? '';
        
        if (empty($licenceNumber) || empty($nom) || empty($prenom)) {
            return null;
        }
        
        $username = strtolower(substr($prenom, 0, 1) . substr($nom, 0, 1) . substr($licenceNumber, -4));
        $username = preg_replace('/[^a-z0-9_-]/', '', $username);
        
        return [
            'licenceNumber' => $licenceNumber,
            'first_name' => $prenom,
            'name' => $nom,
            'username' => $username,
            'email' => strtolower($prenom . '.' . $nom . '@archers-gemenos.fr'),
            'password' => 'Temp' . bin2hex(random_bytes(8)),
            'club' => $entry['AGREMENTNR'] ?? $entry['club_unique'] ?? null,
            'club_name' => $entry['CIE'] ?? null,
            'categorie' => $entry['CATEGORIE'] ?? null,
            'gender' => $entry['SEXE'] === '1' ? 'M' : ($entry['SEXE'] === '2' ? 'F' : null),
            'birthDate' => $entry['DATENAISSANCE'] ?? null,
            'ageCategory' => $entry['CATEGORIE'] ?? null,
            'bowType' => $entry['TYPARC'] ?? null
        ];
    }
}
