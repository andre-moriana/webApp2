<?php

class ArcherSearchController {

    /**
     * Le contenu d'une balise peut être réparti sur plusieurs nœuds (TEXT / espaces / CDATA).
     * L'ancien code écrasait après le premier fragment et vidait $currentTag : les valeurs comme TYPARC/SEXE étaient perdues.
     */
    private static function xmlReaderIsTextContent(XMLReader $reader) {
        $t = $reader->nodeType;
        if ($t === XMLReader::TEXT || $t === XMLReader::WHITESPACE) {
            return true;
        }
        if (defined('XMLReader::CDATA') && $t === XMLReader::CDATA) {
            return true;
        }
        if (defined('XMLReader::SIGNIFICANT_WHITESPACE') && $t === XMLReader::SIGNIFICANT_WHITESPACE) {
            return true;
        }
        return false;
    }

    /**
     * Première valeur non vide parmi plusieurs clés possibles (casse / exports XML variables).
     */
    private static function xmlEntryFirstNonEmpty(array $entry, array $keys): string {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $entry)) {
                continue;
            }
            $v = $entry[$key];
            $s = is_string($v) ? trim($v) : trim((string) $v);
            if ($s !== '') {
                return $s;
            }
        }
        return '';
    }
    
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
        
        $xmlPath = __DIR__ . '/../../public/data/licences-users.xml';
        $xmlPath = realpath($xmlPath);
        
        if (!$xmlPath || !file_exists($xmlPath) || !is_readable($xmlPath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Fichier XML non trouvé']);
            return;
        }
        
        $xmlEntry = $this->findXmlEntryByLicence($xmlPath, $licenceNumber);
        if (!$xmlEntry) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Archer non trouvé dans le XML pour la licence: ' . $licenceNumber]);
            return;
        }
        $typRaw = self::xmlEntryFirstNonEmpty($xmlEntry, ['TYPARC', 'typarc', 'Typarc', 'TYP_ARC']);
        $sexeRaw = self::xmlEntryFirstNonEmpty($xmlEntry, ['SEXE', 'sexe', 'Sexe']);
        
        $xmlData = $this->processUserEntry($xmlEntry);
        if (!$xmlData) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            return;
        }
        
        $trimField = function ($v) {
            return is_string($v) ? trim($v) : (string) $v;
        };
        $payload = [
            'success' => true,
            'source' => 'xml',
            'data' => [
                'licence_number' => $licenceNumber,
                'first_name' => $xmlData['first_name'] ?? '',
                'name' => $xmlData['name'] ?? '',
                'club' => $xmlData['club_name'] ?? $xmlData['club'] ?? '',
                'id_club' => $trimField($xmlData['club_unique'] ?? $xmlEntry['club_unique'] ?? ''),
                'age_category' => $xmlData['ageCategory'] ?? '',
                'bow_type' => $trimField($xmlData['bowType'] ?? $typRaw),
                'CATEGORIE' => $trimField($xmlData['categorie'] ?? $xmlEntry['CATEGORIE'] ?? ''),
                'TYPARC' => $trimField($typRaw),
                'CATAGE' => $trimField($xmlEntry['CATAGE'] ?? ''),
                'SEXE' => $trimField($sexeRaw),
                'saison' => $trimField($xmlEntry['ABREV'] ?? ''),
                'type_licence' => $trimField($xmlEntry['type_licence'] ?? ''),
                'creation_renouvellement' => $trimField($xmlEntry['Creation_renouvellement'] ?? ''),
                'certificat_medical' => $trimField($xmlEntry['certificat_medical'] ?? '')
            ]
        ];
        echo json_encode($payload);
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
                    $currentEntry[$currentTag] = '';
                }
            } elseif (self::xmlReaderIsTextContent($reader) && $inTableContenu && $currentTag !== '') {
                $currentEntry[$currentTag] .= $reader->value;
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
     * Retourne les noms d'affichage (Prénom NOM) pour une liste de numéros de licence.
     * Une seule lecture du XML pour toutes les licences demandées.
     * @param string[] $licences Liste de numéros de licence (seront trimés)
     * @return array [licence => 'Prénom NOM'] (licences non trouvées absentes du tableau)
     */
    public static function getDisplayNamesByLicences(array $licences) {
        $wanted = [];
        foreach ($licences as $l) {
            $t = trim((string) $l);
            if ($t !== '') {
                $wanted[$t] = true;
            }
        }
        if (empty($wanted)) {
            return [];
        }
        $xmlPath = __DIR__ . '/../../public/data/licences-users.xml';
        $xmlPath = realpath($xmlPath);
        if (!$xmlPath || !file_exists($xmlPath) || !is_readable($xmlPath)) {
            return [];
        }
        $result = [];
        $reader = new XMLReader();
        if (!$reader->open($xmlPath)) {
            return [];
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
                    $currentEntry[$currentTag] = '';
                }
            } elseif (self::xmlReaderIsTextContent($reader) && $inTableContenu && $currentTag !== '') {
                $currentEntry[$currentTag] .= $reader->value;
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->name === 'TABLE_CONTENU' && $inTableContenu) {
                    $entryLicence = trim($currentEntry['IDLicence'] ?? '');
                    if ($entryLicence !== '' && isset($wanted[$entryLicence])) {
                        $prenom = trim($currentEntry['PRENOM'] ?? '');
                        $nom = trim($currentEntry['NOM'] ?? '');
                        $result[$entryLicence] = trim($prenom . ' ' . $nom);
                        unset($wanted[$entryLicence]);
                        if (empty($wanted)) {
                            $reader->close();
                            libxml_clear_errors();
                            return $result;
                        }
                    }
                    $currentEntry = [];
                    $inTableContenu = false;
                }
                $currentTag = '';
            }
        }
        $reader->close();
        libxml_clear_errors();
        return $result;
    }

    /**
     * Retourne nom et prénom pour une liste de licences (XML FFTA).
     * @param string[] $licences
     * @return array<string, array{nom: string, prenom: string}>
     */
    public static function getNomPrenomByLicences(array $licences) {
        $wanted = [];
        foreach ($licences as $l) {
            $t = trim((string) $l);
            if ($t !== '') {
                $wanted[$t] = true;
            }
        }
        if (empty($wanted)) {
            return [];
        }
        $xmlPath = __DIR__ . '/../../public/data/licences-users.xml';
        $xmlPath = realpath($xmlPath);
        if (!$xmlPath || !file_exists($xmlPath) || !is_readable($xmlPath)) {
            return [];
        }
        $result = [];
        $reader = new XMLReader();
        if (!$reader->open($xmlPath)) {
            return [];
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
                    $currentEntry[$currentTag] = '';
                }
            } elseif (self::xmlReaderIsTextContent($reader) && $inTableContenu && $currentTag !== '') {
                $currentEntry[$currentTag] .= $reader->value;
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->name === 'TABLE_CONTENU' && $inTableContenu) {
                    $entryLicence = trim($currentEntry['IDLicence'] ?? '');
                    if ($entryLicence !== '' && isset($wanted[$entryLicence])) {
                        $result[$entryLicence] = [
                            'nom' => trim($currentEntry['NOM'] ?? ''),
                            'prenom' => trim($currentEntry['PRENOM'] ?? ''),
                        ];
                        unset($wanted[$entryLicence]);
                        if (empty($wanted)) {
                            $reader->close();
                            libxml_clear_errors();
                            return $result;
                        }
                    }
                    $currentEntry = [];
                    $inTableContenu = false;
                }
                $currentTag = '';
            }
        }
        $reader->close();
        libxml_clear_errors();
        return $result;
    }

    /**
     * Retourne le sexe (F/M) pour une liste de numéros de licence depuis le XML.
     * SEXE: 1 = homme (M), 2 = femme (F)
     * @param string[] $licences Liste de numéros de licence
     * @return array [licence => 'F'|'M'|''] (licences non trouvées = '')
     */
    public static function getSexeByLicences(array $licences) {
        $wanted = [];
        foreach ($licences as $l) {
            $t = trim((string) $l);
            if ($t !== '') {
                $wanted[$t] = true;
            }
        }
        if (empty($wanted)) {
            return [];
        }
        $xmlPath = __DIR__ . '/../../public/data/licences-users.xml';
        $xmlPath = realpath($xmlPath);
        if (!$xmlPath || !file_exists($xmlPath) || !is_readable($xmlPath)) {
            return [];
        }
        $result = [];
        $reader = new XMLReader();
        if (!$reader->open($xmlPath)) {
            return [];
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
                    $currentEntry[$currentTag] = '';
                }
            } elseif (self::xmlReaderIsTextContent($reader) && $inTableContenu && $currentTag !== '') {
                $currentEntry[$currentTag] .= $reader->value;
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->name === 'TABLE_CONTENU' && $inTableContenu) {
                    $entryLicence = trim($currentEntry['IDLicence'] ?? '');
                    if ($entryLicence !== '' && isset($wanted[$entryLicence])) {
                        $sexe = trim($currentEntry['SEXE'] ?? '');
                        $result[$entryLicence] = ($sexe === '2') ? 'F' : (($sexe === '1') ? 'M' : '');
                        unset($wanted[$entryLicence]);
                        if (empty($wanted)) {
                            $reader->close();
                            libxml_clear_errors();
                            return $result;
                        }
                    }
                    $currentEntry = [];
                    $inTableContenu = false;
                }
                $currentTag = '';
            }
        }
        $reader->close();
        libxml_clear_errors();
        return $result;
    }

    /**
     * Traite une entrée XML et retourne les données archer
     */
    private function processUserEntry($entry) {
        if (empty($entry)) {
            return null;
        }
        
        $licenceNumber = trim((string) ($entry['IDLicence'] ?? ''));
        $nom = trim((string) ($entry['NOM'] ?? ''));
        $prenom = trim((string) ($entry['PRENOM'] ?? ''));
        
        if ($licenceNumber === '' || $nom === '' || $prenom === '') {
            return null;
        }
        $sexeRaw = trim((string) ($entry['SEXE'] ?? ''));
        
        return [
            'licenceNumber' => $licenceNumber,
            'first_name' => $prenom,
            'name' => $nom,
            'club_name' => trim((string) ($entry['CIE'] ?? '')),
            'club_unique' => trim((string) ($entry['club_unique'] ?? '')),
            'categorie' => trim((string) ($entry['CATEGORIE'] ?? '')),
            'gender' => $sexeRaw === '1' ? 'M' : ($sexeRaw === '2' ? 'F' : ''),
            'birthDate' => trim((string) ($entry['DATENAISSANCE'] ?? '')),
            'ageCategory' => trim((string) ($entry['CATEGORIE'] ?? '')),
            'bowType' => trim((string) ($entry['TYPARC'] ?? ''))
        ];
    }
}
