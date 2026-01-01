<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("Non connecté");
}

require_once __DIR__ . '/app/Services/ApiService.php';

$apiService = new ApiService();

echo "<h1>Debug Stats Groupes - Traçage complet</h1>";
echo "<style>body { font-family: monospace; } pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; }</style>";

// === SECTION 1: API Response brute ===
echo "<h2>1. Réponse API /api/groups/list</h2>";
try {
    $groupsResponse = $apiService->get('/api/groups/list');
    echo "<pre>";
    echo "Type de réponse: " . gettype($groupsResponse) . "\n";
    echo "Nombre d'éléments: " . (is_array($groupsResponse) ? count($groupsResponse) : 'N/A') . "\n";
    echo "\nContenu:\n";
    print_r($groupsResponse);
    echo "</pre>";
    
    // === SECTION 2: Premier groupe en détail ===
    echo "<h2>2. Premier groupe en détail</h2>";
    if (!empty($groupsResponse)) {
        $firstGroup = $groupsResponse[0];
        echo "<pre>";
        echo "Clés du groupe:\n";
        print_r(array_keys($firstGroup));
        echo "\nValeur club_id: " . ($firstGroup['club_id'] ?? 'NON DÉFINI') . "\n";
        echo "\nGroupe complet:\n";
        print_r($firstGroup);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>Aucun groupe retourné par l'API</p>";
    }
    
    // === SECTION 3: Construction de groups_by_club (simulation DashboardController) ===
    echo "<h2>3. Construction groups_by_club (logique DashboardController)</h2>";
    $groups_by_club = [];
    
    foreach ($groupsResponse as $group) {
        $clubId = $group['club_id'] ?? null;
        
        echo "<div style='border: 1px solid #ccc; margin: 5px 0; padding: 5px;'>";
        echo "Groupe: <strong>{$group['name']}</strong> | ";
        echo "club_id brut: <code>" . var_export($group['club_id'] ?? 'N/A', true) . "</code> | ";
        echo "clubId calculé: <code>" . var_export($clubId, true) . "</code>";
        
        if (!empty($clubId)) {
            if (!isset($groups_by_club[$clubId])) {
                $groups_by_club[$clubId] = [];
            }
            $groups_by_club[$clubId][] = [
                'id' => $group['id'],
                'name' => $group['name']
            ];
            echo " ➜ <span style='color: green;'>AJOUTÉ à groups_by_club[{$clubId}]</span>";
        } else {
            echo " ➜ <span style='color: red;'>IGNORÉ (pas de club_id)</span>";
        }
        echo "</div>";
    }
    
    // === SECTION 4: Résultat final groups_by_club ===
    echo "<h2>4. Résultat final groups_by_club</h2>";
    echo "<pre>";
    echo "Nombre de clubs: " . count($groups_by_club) . "\n";
    echo "Clés (club IDs): " . implode(', ', array_keys($groups_by_club)) . "\n\n";
    echo "Contenu complet:\n";
    print_r($groups_by_club);
    echo "</pre>";
    
    // === SECTION 5: JSON encode test ===
    echo "<h2>5. Test JSON encode (ce qui sera passé au JavaScript)</h2>";
    $json = json_encode($groups_by_club);
    echo "<pre>";
    echo "JSON valide: " . ($json !== false ? 'OUI' : 'NON') . "\n";
    echo "Taille JSON: " . strlen($json) . " caractères\n";
    echo "JSON: " . htmlspecialchars($json) . "\n";
    echo "</pre>";
    
    // === SECTION 6: Décodage pour vérifier ===
    echo "<h2>6. Test decode JSON (vérification intégrité)</h2>";
    $decoded = json_decode($json, true);
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERREUR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
