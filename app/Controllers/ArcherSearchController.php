<?php

class ArcherSearchController {
    
    /**
     * Recherche archer par licence - version publique pour inscription ciblée (sans auth).
     * Valide que le concours existe avant de permettre la recherche.
     */
    public function findOrCreateByLicensePublic($concoursId) {
        header('Content-Type: application/json');
        
        if (empty($concoursId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID concours requis']);
            return;
        }
        
        // Valider que le concours existe (appel API public)
        try {
            $apiService = new ApiService();
            $concoursResponse = $apiService->makeRequestPublic("concours/{$concoursId}/public", 'GET');
            if (!$concoursResponse['success'] || empty($concoursResponse['data'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Concours introuvable']);
                return;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur de validation du concours']);
            return;
        }
        
        $this->doSearchByLicense();
    }
    
    public function findOrCreateByLicense() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            return;
        }
        
        $this->doSearchByLicense();
    }
    
    /**
     * Logique commune de recherche par licence (XML)
     */
    private function doSearchByLicense() {
        $input = json_decode(file_get_contents('php://input'), true);
        $licenceNumber = trim($input['licence_number'] ?? '');
        
        if (empty($licenceNumber)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Numéro de licence requis']);
            return;
        }
        
        $xmlPath = __DIR__ . '/../../public/data/users-licences.xml';
        $xmlPath = realpath($xmlPath);
        
        error_log("ArcherSearchController: Recherche licence '$licenceNumber'");
        
        if (!$xmlPath || !file_exists($xmlPath) || !is_readable($xmlPath)) {
            error_log("ArcherSearchController: Fichier XML non trouvé ou non lisible");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Fichier XML non trouvé']);
            return;
        }
        
        $xmlEntry = $this->findXmlEntryByLicence($xmlPath, $licenceNumber);
        if (!$xmlEntry) {
            error_log("ArcherSearchController: Aucune entrée trouvée pour licence '$licenceNumber'");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Archer non trouvé dans le XML pour la licence: ' . $licenceNumber]);
            return;
        }
        
        $xmlData = $this->processUserEntry($xmlEntry);
        if (!$xmlData) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'source' => 'xml',
            'data' => [
                'licence_number' => $licenceNumber,
                'first_name' => $xmlData['first_name'] ?? '',
                'name' => $xmlData['name'] ?? '',
                'club' => $xmlData['club_name'] ?? $xmlData['club'] ?? '',
                'id_club' => $xmlData['club_unique'] ?? $xmlEntry['club_unique'] ?? '',
                'age_category' => $xmlData['ageCategory'] ?? '',
                'bow_type' => $xmlData['bowType'] ?? '',
                'CATEGORIE' => $xmlData['categorie'] ?? '',
                'TYPARC' => $xmlEntry['TYPARC'] ?? '',
                'CATAGE' => $xmlEntry['CATAGE'] ?? '',
                'SEXE' => $xmlEntry['SEXE'] ?? '',
                'saison' => $xmlEntry['ABREV'] ?? '',
                'type_licence' => $xmlEntry['type_licence'] ?? '',
                'creation_renouvellement' => $xmlEntry['Creation_renouvellement'] ?? '',
                'certificat_medical' => $xmlEntry['certificat_medical'] ?? ''
            ]
        ]);
    }
    
    /**
     * Cherche une entrée XML par numéro de licence
     */
    private function findXmlEntryByLicence($xmlPath, $target) {
        $reader = new XMLReader();
        if (!$reader->open($xmlPath)) {
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
                    $entryLicence = trim($currentEntry['IDLicence'] ?? '');
                    // Comparaison stricte mais aussi avec trim pour éviter les problèmes d'espaces
                    if ($entryLicence !== '' && trim($entryLicence) === trim($target)) {
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
     * Traite une entrée XML et retourne les données archer
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
        
        return [
            'licenceNumber' => $licenceNumber,
            'first_name' => $prenom,
            'name' => $nom,
            'club_name' => $entry['CIE'] ?? '',
            'club_unique' => $entry['club_unique'] ?? '', // ID unique du club pour id_club
            'categorie' => $entry['CATEGORIE'] ?? '',
            'gender' => $entry['SEXE'] === '1' ? 'M' : ($entry['SEXE'] === '2' ? 'F' : ''),
            'birthDate' => $entry['DATENAISSANCE'] ?? '',
            'ageCategory' => $entry['CATEGORIE'] ?? '',
            'bowType' => $entry['TYPARC'] ?? ''
        ];
    }
}
