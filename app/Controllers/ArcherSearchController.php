<?php

class ArcherSearchController {
    
    public function findOrCreateByLicense() {
        // Démarrer la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non authentifié']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $licenceNumber = trim($input['licence_number'] ?? '');
        
        if (empty($licenceNumber)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Numéro de licence requis']);
            return;
        }
        
        // Chercher dans le XML uniquement
        $xmlPath = __DIR__ . '/../../public/data/users-licences.xml';
        
        // Normaliser le chemin
        $xmlPath = realpath($xmlPath);
        
        error_log("ArcherSearchController: Recherche licence '$licenceNumber'");
        error_log("ArcherSearchController: Chemin XML: '$xmlPath'");
        
        if (!$xmlPath || !file_exists($xmlPath) || !is_readable($xmlPath)) {
            error_log("ArcherSearchController: Fichier XML non trouvé ou non lisible");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Fichier XML non trouvé: ' . ($xmlPath ?: __DIR__ . '/../../public/data/users-licences.xml')]);
            return;
        }
        
        $xmlEntry = $this->findXmlEntryByLicence($xmlPath, $licenceNumber);
        if (!$xmlEntry) {
            error_log("ArcherSearchController: Aucune entrée trouvée pour licence '$licenceNumber'");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Archer non trouvé dans le XML pour la licence: ' . $licenceNumber]);
            return;
        }
        
        error_log("ArcherSearchController: Entrée trouvée pour licence '$licenceNumber'");
        
        // Traiter l'entrée XML
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
                'club' => $xmlData['club_name'] ?? '',
                'age_category' => $xmlData['ageCategory'] ?? '',
                'bow_type' => $xmlData['bowType'] ?? '',
                // Données pour le pré-remplissage (une seule clé par donnée)
                'CATEGORIE' => $xmlData['categorie'] ?? '',
                'TYPARC' => $xmlData['bowType'] ?? '',
                'SEXE' => $xmlEntry['SEXE'] ?? '',
                'saison' => $xmlEntry['ABREV'] ?? '',
                'type_licence' => $xmlEntry['type_licence'] ?? '',
                'creation_renouvellement' => $xmlEntry['Creation_renouvellement'] ?? '',
                'certificat_medical' => $xmlEntry['CERTIFICAT'] ?? ''
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
            'categorie' => $entry['CATEGORIE'] ?? '',
            'gender' => $entry['SEXE'] === '1' ? 'M' : ($entry['SEXE'] === '2' ? 'F' : ''),
            'birthDate' => $entry['DATENAISSANCE'] ?? '',
            'ageCategory' => $entry['CATEGORIE'] ?? '',
            'bowType' => $entry['TYPARC'] ?? ''
        ];
    }
}
