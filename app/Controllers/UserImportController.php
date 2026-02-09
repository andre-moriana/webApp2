<?php

require_once 'app/Config/PermissionHelper.php';
require_once 'app/Services/PermissionService.php';

class UserImportController {
    private $apiService;
    private $ageCategoriesMap;
    
    public function __construct() {
        $this->apiService = new ApiService();
        $this->ageCategoriesMap = null;
    }
    
    public function index() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérification des droits administrateur
        if (!isset($_SESSION['user']['is_admin']) || !(bool)$_SESSION['user']['is_admin']) {
            $_SESSION['error'] = 'Accès refusé. Seuls les administrateurs peuvent importer des utilisateurs.';
            header('Location: /users');
            exit;
        }
        
        $title = 'Import d\'utilisateurs depuis XML - Portail Archers de Gémenos';
        
        include 'app/Views/layouts/header.php';
        include 'app/Views/users/import.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function process() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
        
        // Vérification des droits administrateur
        if (!isset($_SESSION['user']['is_admin']) || !(bool)$_SESSION['user']['is_admin']) {
            $_SESSION['error'] = 'Accès refusé. Seuls les administrateurs peuvent importer des utilisateurs.';
            header('Location: /users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users/import');
            exit;
        }
        
        // Augmenter le timeout et la mémoire pour les fichiers volumineux
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M'); // Augmenter la limite de mémoire
        
        // Vérifier qu'un fichier a été uploadé
        if (!isset($_FILES['xml_file'])) {
            $_SESSION['error'] = 'Aucun fichier n\'a été sélectionné. Veuillez choisir un fichier XML.';
            header('Location: /users/import');
            exit;
        }
        
        $file = $_FILES['xml_file'];
        
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = $this->getUploadErrorMessage($file['error']);
            $_SESSION['error'] = $errorMessage;
            header('Location: /users/import');
            exit;
        }
        
        // Vérifier que le fichier n'est pas vide
        if ($file['size'] === 0) {
            $_SESSION['error'] = 'Le fichier est vide. Veuillez sélectionner un fichier XML valide.';
            header('Location: /users/import');
            exit;
        }
        
        // Vérifier la taille du fichier (max 200MB)
        $maxSize = 200 * 1024 * 1024; // 200MB
        if ($file['size'] > $maxSize) {
            $_SESSION['error'] = 'Le fichier est trop volumineux. Taille maximale autorisée : 200 MB.';
            header('Location: /users/import');
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
            header('Location: /users/import');
            exit;
        }
        
        $clubName = trim($_POST['club_name'] ?? '');
        
        // Vérifier que le fichier est bien un XML
        $fileInfo = pathinfo($file['name']);
        if (!isset($fileInfo['extension']) || strtolower($fileInfo['extension']) !== 'xml') {
            $_SESSION['error'] = 'Le fichier doit être au format XML (.xml).';
            header('Location: /users/import');
            exit;
        }
        
        // Vérifier que le fichier temporaire existe
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            $_SESSION['error'] = 'Impossible de lire le fichier uploadé. Veuillez réessayer.';
            header('Location: /users/import');
            exit;
        }
        
        // Utiliser XMLReader pour parser et importer le fichier de manière séquentielle (évite de charger tout en mémoire)
        $results = $this->importUsersFromXMLReader($file['tmp_name'], $clubName);
        
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
                if (count($errors) < 50) { // Limiter à 50 erreurs pour éviter de surcharger la session
                    $errors[] = $result['message'] ?? 'Erreur inconnue';
                }
            }
        }
        
        if ($totalCount === 0) {
            $_SESSION['error'] = 'Aucun utilisateur trouvé dans le fichier XML.';
            header('Location: /users/import');
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
            $_SESSION['success'] = "Import réussi : {$successCount} utilisateur(s) importé(s) avec succès.";
        } else {
            $_SESSION['warning'] = "Import partiel : {$successCount} utilisateur(s) importé(s), {$errorCount} erreur(s).";
        }
        
        header('Location: /users/import');
        exit;
    }

    public function importSingleXmlUser() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            return;
        }

        try {
            $clubId = $_SESSION['user']['clubId'] ?? null;
            PermissionHelper::requirePermission(
                PermissionService::RESOURCE_USERS_ALL,
                PermissionService::ACTION_VIEW,
                $clubId
            );
        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permissions insuffisantes']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $licence = trim($input['licence_number'] ?? $input['IDLicence'] ?? $input['id_licence'] ?? '');

        if ($licence === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Numero de licence requis']);
            return;
        }

        $xmlPath = __DIR__ . '/../../public/data/users-licences.xml';
        if (!file_exists($xmlPath) || !is_readable($xmlPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Fichier XML introuvable']);
            return;
        }

        $entry = $this->findXmlEntryByLicence($xmlPath, $licence);
        if (!$entry) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Licence non trouvee dans le XML']);
            return;
        }

        $userData = $this->processUserEntry($entry, '');
        if ($userData === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Entree XML invalide']);
            return;
        }

        $results = $this->importUserBatch([$userData]);
        $result = $results[0] ?? null;
        if (!$result || empty($result['success'])) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $result['message'] ?? 'Erreur lors de l\'import']);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'user_id' => $result['user_id'] ?? null,
                'licenceNumber' => $userData['licenceNumber'] ?? null,
                'first_name' => $userData['first_name'] ?? null,
                'name' => $userData['name'] ?? null,
                'club' => $userData['club'] ?? null
            ]
        ]);
    }
    
    /**
     * Extrait les utilisateurs du XML en utilisant XMLReader (lecture séquentielle, économise la mémoire)
     */
    private function extractUsersFromXMLReader($filePath, $selectedClub = '') {
        $users = [];
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $users;
        }
        
        // Activer la gestion des erreurs libxml
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $reader = new XMLReader();
        
        if (!$reader->open($filePath)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            error_log('Erreur lors de l\'ouverture du fichier XML : ' . print_r($errors, true));
            return $users;
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
                        $user = $this->processUserEntry($currentEntry, $selectedClub);
                        if ($user !== null) {
                            $users[] = $user;
                        }
                        $currentEntry = [];
                        $inTableContenu = false;
                        
                        // Libérer la mémoire périodiquement (tous les 100 utilisateurs)
                        if (count($users) % 100 === 0) {
                            gc_collect_cycles();
                        }
                    }
                    $currentTag = '';
                    break;
            }
        }
        
        $reader->close();
        libxml_clear_errors();
        
        return $users;
    }
    
    /**
     * Traite une entrée utilisateur et retourne les données formatées ou null si l'entrée doit être ignorée
     */
    private function processUserEntry($entry, $selectedClub = '') {
        $nom = trim($entry['NOM'] ?? '');
        $prenom = trim($entry['PRENOM'] ?? '');
        $nomComplet = trim($entry['NOMCOMPLET'] ?? '');
        $cie = trim($entry['CIE'] ?? '');
        $clubUnique = trim($entry['club_unique'] ?? $entry['CLUB_UNIQUE'] ?? '');
        $idLicence = trim($entry['IDLicence'] ?? '');
        $dateNaissance = trim($entry['DATENAISSANCE'] ?? '');
        $sexe = trim($entry['SEXE'] ?? '');
        $categorie = trim($entry['CATEGORIE'] ?? '');
        $ageCategory = trim($entry['CATAGE'] ?? '');
        $typeArc = trim($entry['TYPARC'] ?? '');
        $email = trim($entry['EMAIL'] ?? '');
        
        // Si un club a été sélectionné, filtrer par ce club (comparaison insensible à la casse et aux espaces)
        if (!empty($selectedClub)) {
            $normalizedCie = trim(strtoupper($cie));
            $normalizedSelected = trim(strtoupper($selectedClub));
            if ($normalizedCie !== $normalizedSelected) {
                return null;
            }
        }
        
        // Ignorer les entrées sans nom ou prénom
        if (empty($nom) && empty($prenom) && empty($nomComplet)) {
            return null;
        }
        
        // Extraire le nom et prénom depuis NOMCOMPLET si nécessaire
        if (empty($nom) && empty($prenom) && !empty($nomComplet)) {
            $parts = explode(' ', $nomComplet, 2);
            $nom = $parts[0] ?? '';
            $prenom = $parts[1] ?? '';
        }
        
        // Générer un email si absent
        if (empty($email)) {
            $email = $this->generateEmail($nom, $prenom, $idLicence);
        }
        
        // Générer un username
        $username = $this->generateUsername($nom, $prenom, $idLicence);
        
        // Convertir la date de naissance
        $birthDate = $this->parseDate($dateNaissance);
        
        // Convertir le sexe (1 = H/Homme, 2 = F/Femme)
        $gender = ($sexe === '1') ? 'H' : (($sexe === '2') ? 'F' : '');
        
        // Convertir le type d'arc (TYPARC)
        $bowType = $this->convertBowType($typeArc);

        // Convertir la catégorie d'âge (CATAGE)
        $ageCategoryConverted = $this->convertAgeCategory($ageCategory);
        $ageCategoryLabel = $this->getAgeCategoryLabelById($ageCategory);
        if (!empty($ageCategoryLabel)) {
            $ageCategoryConverted = $ageCategoryLabel;
        }

        // Extraire les infos depuis CATEGORIE si nécessaire (ex: CLS2D)
        $categorieParsed = $this->parseCategorieComposite($categorie);
        if ($categorieParsed !== null) {
            if (empty($bowType) && !empty($categorieParsed['bowType'])) {
                $bowType = $categorieParsed['bowType'];
            }
            if (empty($ageCategoryConverted) && !empty($categorieParsed['ageCategory'])) {
                $ageCategoryConverted = $categorieParsed['ageCategory'];
            }
            if (empty($gender) && !empty($categorieParsed['gender'])) {
                $gender = $categorieParsed['gender'];
            }
        }

        $clubCode = !empty($clubUnique) ? $clubUnique : $cie;
        
        return [
            'first_name' => $prenom,
            'name' => $nom,
            'username' => $username,
            'email' => $email,
            'password' => $this->generatePassword($idLicence),
            'licenceNumber' => $idLicence,
            'birthDate' => $birthDate,
            'gender' => $gender,
            'ageCategory' => $ageCategoryConverted ?: $ageCategory,
            'categorie' => $categorie,
            'bowType' => $bowType,
            'club' => $clubCode,
            'role' => 'Archer',
            'status' => 'pending',
            'requires_approval' => true
        ];
    }

    private function parseCategorieComposite($categorie) {
        $value = strtoupper(trim($categorie));
        if ($value === '' || strlen($value) < 4) {
            return null;
        }

        $bowCode = substr($value, 0, 2);
        $genderCode = substr($value, -1);
        $ageCode = substr($value, 2, -1);

        $bowType = $this->convertBowType($bowCode);
        $ageCategory = $this->convertAgeCategory($ageCode);
        $normalizedAge = !empty($ageCategory) ? $ageCategory : $ageCode;

        $gender = '';
        if ($genderCode === 'H') {
            $gender = 'H';
        } elseif ($genderCode === 'D' || $genderCode === 'F') {
            $gender = 'F';
        }

        return [
            'bowType' => $bowType,
            'ageCategory' => $normalizedAge,
            'gender' => $gender
        ];
    }
    
    private function generateEmail($nom, $prenom, $idLicence) {
        // Générer un email basé sur le nom, prénom et ID licence
        $base = strtolower($prenom . '.' . $nom);
        $base = preg_replace('/[^a-z0-9]/', '', $base);
        if (empty($base)) {
            $base = 'user' . substr($idLicence, -6);
        }
        return $base . '@archers-gemenos.fr';
    }
    
    private function generateUsername($nom, $prenom, $idLicence) {
        // Générer un username basé sur le nom, prénom et ID licence
        $base = strtolower($prenom . $nom);
        $base = preg_replace('/[^a-z0-9]/', '', $base);
        if (empty($base)) {
            $base = 'user' . substr($idLicence, -6);
        }
        // S'assurer que le username est unique en ajoutant l'ID licence si nécessaire
        if (!empty($idLicence)) {
            $base .= substr($idLicence, -4);
        }
        return $base;
    }
    
    private function generatePassword($idLicence) {
        // Générer un mot de passe temporaire basé sur l'ID licence
        // L'utilisateur devra le changer à la première connexion
        // S'assurer que l'ID licence a au moins quelques caractères
        $licenceSuffix = !empty($idLicence) ? substr($idLicence, -min(6, strlen($idLicence))) : '000000';
        // Si l'ID licence est trop court, compléter avec des zéros
        $licenceSuffix = str_pad($licenceSuffix, 6, '0', STR_PAD_LEFT);
        return 'Temp' . $licenceSuffix . '!';
    }
    
    private function parseDate($dateString) {
        // Parser la date au format DD/MM/YYYY
        if (empty($dateString)) {
            return null;
        }
        
        $parts = explode('/', $dateString);
        if (count($parts) === 3) {
            $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $year = $parts[2];
            
            // Vérifier que la date est valide
            if (checkdate((int)$month, (int)$day, (int)$year)) {
                return $year . '-' . $month . '-' . $day;
            }
        }
        
        return null;
    }
    
    /**
     * Importe les utilisateurs directement depuis le XML (traitement séquentiel)
     */
    private function importUsersFromXMLReader($filePath, $selectedClub = '') {
        $results = [];
        $batchSize = 50; // Traiter par lots de 50 utilisateurs
        $currentBatch = [];
        $processedCount = 0;
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $results;
        }
        
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
                        $user = $this->processUserEntry($currentEntry, $selectedClub);
                        if ($user !== null) {
                            $currentBatch[] = $user;
                            
                            // Traiter le lot quand il atteint la taille définie
                            if (count($currentBatch) >= $batchSize) {
                                $batchResults = $this->importUserBatch($currentBatch);
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
        
        // Traiter le dernier lot s'il reste des utilisateurs
        if (!empty($currentBatch)) {
            $batchResults = $this->importUserBatch($currentBatch);
            $results = array_merge($results, $batchResults);
        }
        
        $reader->close();
        libxml_clear_errors();
        
        return $results;
    }

    private function findXmlEntryByLicence($filePath, $licence) {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $reader = new XMLReader();
        if (!$reader->open($filePath)) {
            libxml_clear_errors();
            return null;
        }

        $currentEntry = [];
        $currentTag = '';
        $inTableContenu = false;
        $target = trim($licence);

        while ($reader->read()) {
            switch ($reader->nodeType) {
                case XMLReader::ELEMENT:
                    $currentTag = $reader->localName;
                    if ($currentTag === 'TABLE_CONTENU') {
                        $inTableContenu = true;
                        $currentEntry = [];
                    }
                    break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    if ($inTableContenu && $currentTag !== '') {
                        $currentEntry[$currentTag] = trim($reader->value);
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    if ($reader->localName === 'TABLE_CONTENU' && $inTableContenu) {
                        $entryLicence = trim($currentEntry['IDLicence'] ?? '');
                        if ($entryLicence !== '' && $entryLicence === $target) {
                            $reader->close();
                            libxml_clear_errors();
                            return $currentEntry;
                        }
                        $currentEntry = [];
                        $inTableContenu = false;
                    }
                    $currentTag = '';
                    break;
            }
        }

        $reader->close();
        libxml_clear_errors();
        return null;
    }
    
    /**
     * Importe un lot d'utilisateurs
     */
    private function importUserBatch($users) {
        $results = [];
        
        foreach ($users as $userData) {
            try {
                // Créer l'utilisateur de base
                $createData = [
                    'first_name' => $userData['first_name'],
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'role' => $userData['role'] ?? 'Archer',
                    'status' => 'pending',
                    'requires_approval' => true
                ];

                if (!empty($userData['club'])) {
                    $createData['clubId'] = $userData['club'];
                }
                
                $response = $this->apiService->createUser($createData);
                
                if ($response['success']) {
                    // Récupérer l'ID de l'utilisateur créé
                    $userId = null;
                    if (isset($response['data']['user']['_id'])) {
                        $userId = $response['data']['user']['_id'];
                    } elseif (isset($response['data']['user']['id'])) {
                        $userId = $response['data']['user']['id'];
                    } elseif (isset($response['data']['_id'])) {
                        $userId = $response['data']['_id'];
                    } elseif (isset($response['data']['id'])) {
                        $userId = $response['data']['id'];
                    }
                    
                    // Mettre à jour les informations supplémentaires si l'utilisateur a été créé
                    if ($userId) {
                        $updateData = [];
                        
                        // Informations d'identité
                        if (!empty($userData['birthDate'])) {
                            $updateData['birthDate'] = $userData['birthDate'];
                        }
                        if (!empty($userData['gender'])) {
                            $updateData['gender'] = $userData['gender'];
                        }
                        
                        // Informations sportives
                        if (!empty($userData['licenceNumber'])) {
                            $updateData['licenceNumber'] = $userData['licenceNumber'];
                        }
                        if (!empty($userData['ageCategory'])) {
                            $updateData['ageCategory'] = $userData['ageCategory'];
                        }
                        if (!empty($userData['bowType'])) {
                            $updateData['bowType'] = $userData['bowType'];
                        }
                        if (!empty($userData['club'])) {
                            $updateData['clubId'] = $userData['club'];
                        }
                        if (!empty($userData['categorie'])) {
                            // La catégorie peut être stockée dans ageCategory si elle n'est pas déjà remplie
                            if (empty($updateData['ageCategory'])) {
                                $updateData['ageCategory'] = $userData['categorie'];
                            }
                        }
                        
                        // Mettre à jour l'utilisateur avec les informations supplémentaires
                        if (!empty($updateData)) {
                            $updateResponse = $this->apiService->updateUser($userId, $updateData);
                            if (!$updateResponse['success']) {
                                error_log("Erreur lors de la mise à jour de l'utilisateur {$userId}: " . ($updateResponse['message'] ?? 'Erreur inconnue'));
                            }
                        }
                    }
                    
                    $results[] = [
                        'success' => true,
                        'user' => $userData['username'],
                        'user_id' => $userId,
                        'message' => "Utilisateur {$userData['username']} créé avec succès"
                    ];
                } else {
                    $results[] = [
                        'success' => false,
                        'user' => $userData['username'],
                        'message' => $response['message'] ?? 'Erreur lors de la création'
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'success' => false,
                    'user' => $userData['username'],
                    'message' => 'Exception : ' . $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Convertit le code TYPARC en type d'arc lisible
     */
    private function convertBowType($typeArc) {
        $typeArc = trim($typeArc);
        
        if (empty($typeArc)) {
            return '';
        }
        
        // Mapping des codes TYPARC vers les types d'arc
        // Basé sur la table de correspondance fournie
        $mapping = [
            '1' => 'Arc Classique',      // CL
            '2' => 'Arc à poulies',      // CO
            '3' => 'Arc droit',          // AD
            '4' => 'Arc de chasse',      // AC
            '5' => 'Arc Nu',             // BB
            '6' => 'Arc Libre',          // TL
        ];
        
        // Mapping des codes abrégés vers les noms complets
        $codeMapping = [
            'CL' => 'Arc Classique',
            'CO' => 'Arc à poulies',
            'AD' => 'Arc droit',
            'AC' => 'Arc de chasse',
            'BB' => 'Arc Nu',
            'TL' => 'Arc Libre',
        ];
        
        // Si c'est un code numérique, le convertir
        if (isset($mapping[$typeArc])) {
            return $mapping[$typeArc];
        }
        
        // Si c'est un code abrégé, le convertir
        if (isset($codeMapping[strtoupper($typeArc)])) {
            return $codeMapping[strtoupper($typeArc)];
        }
        
        // Si c'est déjà un nom de type d'arc complet, le retourner tel quel
        $validTypes = ['Arc Classique', 'Arc à poulies', 'Arc droit', 'Arc de chasse', 'Arc Nu', 'Arc Libre',
                       'Classique', 'Recurve', 'Compound', 'Barebow', 'Longbow']; // Garder les anciens noms pour compatibilité
        if (in_array($typeArc, $validTypes)) {
            return $typeArc;
        }
        
        // Par défaut, retourner vide
        return '';
    }
    
    /**
     * Convertit le code CATAGE en catégorie d'âge lisible
     */
    private function convertAgeCategory($catage) {
        $catage = trim($catage);
        
        if (empty($catage)) {
            return '';
        }
        
        // Mapping des codes CATAGE vers les catégories d'âge
        // Basé sur la table de correspondance fournie
        $mapping = [
            '0' => 'DECOUVERTE',
            '2' => 'U13 - BENJAMINS',
            '3' => 'U15 - MINIMES',
            '4' => 'U18 - CADETS',
            '5' => 'U21 - JUNIORS',
            '8' => 'U11 - POUSSINS',
            '11' => 'SENIORS1 (S1)',
            '12' => 'SENIORS2 (S2)',
            '13' => 'SENIORS3 (S3)',
            '14' => 'SENIORS1 (T1)',
            '15' => 'SENIORS2 (T2)',
            '16' => 'SENIORS3 (T3)',
            '17' => 'DEBUTANTS',
            // Variantes nationales
            '50' => 'U13 - BENJAMINS (N)',
            '51' => 'U15 - MINIMES (N)',
            '53' => 'U18 - CADETS (N)',
            '54' => 'U21 - JUNIORS (N)',
            '60' => 'SENIORS1 (S1) (N)',
            '61' => 'SENIORS2 (S2) (N)',
            '62' => 'SENIORS3 (S3) (N)',
            '63' => 'SENIORS1 (T1) (N)',
            '64' => 'SENIORS2 (T2) (N)',
            '65' => 'SENIORS3 (T3) (N)',
            // Autres catégories
            '9' => 'W1',
            '10' => 'OPEN',
            '18' => 'FEDERAL',
            '19' => 'CHALLENGE',
            '20' => 'CRITERIUM',
            '21' => 'POTENCE',
            '23' => 'HV1',
            '24' => 'HV2-3',
            '25' => 'HV LIBRE',
            '26' => 'SUPPORT 1',
            '27' => 'OPEN VETERAN',
            '28' => 'OPEN U18',
            '29' => 'CHALLENGE U18',
            '30' => 'SUPPORT 2',
            '31' => 'W1 U18',
            '32' => 'FEDERAL U18',
            '33' => 'FEDERAL VETERAN',
            '34' => 'CRITERIUM U18',
            '35' => 'HV U18',
            '36' => 'FEDERAL NATIONAL',
            '37' => 'OPEN NATIONAL',
            '38' => 'W1 NATIONAL',
        ];
        
        if (isset($mapping[$catage])) {
            return $mapping[$catage];
        }
        
        // Si c'est déjà un nom de catégorie, le retourner tel quel
        if (preg_match('/^(U\d+|SENIORS|DEBUTANTS|DECOUVERTE|W1|OPEN|FEDERAL|CHALLENGE|CRITERIUM|POTENCE|HV|SUPPORT)/i', $catage)) {
            return $catage;
        }
        
        // Par défaut, retourner le code original
        return $catage;
    }

    private function getAgeCategoryLabelById($catage) {
        $catage = trim((string)$catage);
        if ($catage === '' || !ctype_digit($catage)) {
            return '';
        }

        if ($this->ageCategoriesMap === null) {
            $this->ageCategoriesMap = [];
            try {
                $response = $this->apiService->makeRequest('concours/categories-age', 'GET');
                $payload = $this->apiService->unwrapData($response);
                if (is_array($payload) && isset($payload['data']) && isset($payload['success'])) {
                    $payload = $payload['data'];
                }
                if (is_array($payload)) {
                    foreach ($payload as $row) {
                        $id = $row['idcategorie'] ?? null;
                        $label = $row['lb_categorie'] ?? null;
                        if ($id !== null && $label !== null) {
                            $this->ageCategoriesMap[(string)$id] = $label;
                        }
                    }
                }
            } catch (Exception $e) {
                $this->ageCategoriesMap = [];
            }
        }

        return $this->ageCategoriesMap[$catage] ?? '';
    }
    
    public function getClubs() {
        // Cette méthode peut être utilisée pour récupérer la liste des clubs depuis le XML
        // Pour l'instant, on retourne une liste vide ou on peut parser un fichier XML de référence
        return [];
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
                // pas de break, on continue
            case 'm':
                $value *= 1024;
                // pas de break, on continue
            case 'k':
                $value *= 1024;
                break;
            default:
                // Si c'est juste un nombre, on le retourne tel quel (en octets)
                break;
        }
        
        return (int)$value;
    }
}

