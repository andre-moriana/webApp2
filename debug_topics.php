<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("Non connecté");
}

require_once __DIR__ . '/app/Services/ApiService.php';

$apiService = new ApiService();

echo "<h1>Debug Topics & Groups</h1>";
echo "<style>body { font-family: monospace; } pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; } .error { color: red; } .success { color: green; }</style>";

// Groupes
echo "<h2>Groupes</h2>";
$groupsResponse = $apiService->makeRequest('groups/list', 'GET');
if ($groupsResponse['success'] && !empty($groupsResponse['data'])) {
    $groups = $groupsResponse['data'];
    echo "<pre>";
    foreach ($groups as $group) {
        $groupId = $group['id'] ?? 'N/A';
        echo "Groupe ID: " . var_export($groupId, true) . " | Type: " . gettype($groupId) . " | Nom: " . ($group['name'] ?? 'N/A') . "\n";
    }
    echo "</pre>";
} else {
    echo "<p class='error'>Erreur récupération groupes</p>";
}

// Topics
echo "<h2>Topics</h2>";
$topicsResponse = $apiService->makeRequest('topics/list', 'GET');
if ($topicsResponse['success'] && !empty($topicsResponse['data'])) {
    $topics = $topicsResponse['data'];
    echo "<pre>";
    foreach ($topics as $topic) {
        $topicGroupId = $topic['group_id'] ?? 'N/A';
        echo "Topic: " . ($topic['title'] ?? 'N/A') . " | group_id: " . var_export($topicGroupId, true) . " | Type: " . gettype($topicGroupId) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p class='error'>Erreur récupération topics</p>";
}

// Test de matching
echo "<h2>Test de Matching</h2>";
if (!empty($groups) && !empty($topics)) {
    $groupsById = [];
    foreach ($groups as $group) {
        $groupId = $group['id'] ?? '';
        $groupsById[$groupId] = [
            'name' => $group['name'] ?? 'N/A',
            'topics' => []
        ];
    }
    
    echo "<p>Clés de groupsById: " . implode(', ', array_map(function($k) { return var_export($k, true) . ' (' . gettype($k) . ')'; }, array_keys($groupsById))) . "</p>";
    
    foreach ($topics as $topic) {
        $topicGroupId = $topic['group_id'] ?? '';
        echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
        echo "Topic: <strong>" . ($topic['title'] ?? 'N/A') . "</strong><br>";
        echo "Cherche group_id: " . var_export($topicGroupId, true) . " (" . gettype($topicGroupId) . ")<br>";
        
        // Test direct
        if (isset($groupsById[$topicGroupId])) {
            echo "<span class='success'>✓ MATCH DIRECT</span><br>";
            $groupsById[$topicGroupId]['topics'][] = $topic;
        } else {
            echo "<span class='error'>✗ PAS DE MATCH DIRECT</span><br>";
            
            // Test avec conversion string
            $stringId = (string)$topicGroupId;
            if (isset($groupsById[$stringId])) {
                echo "<span class='success'>✓ MATCH avec conversion string</span><br>";
            } else {
                echo "<span class='error'>✗ PAS DE MATCH string</span><br>";
            }
            
            // Test avec conversion int
            $intId = (int)$topicGroupId;
            if (isset($groupsById[$intId])) {
                echo "<span class='success'>✓ MATCH avec conversion int</span><br>";
            } else {
                echo "<span class='error'>✗ PAS DE MATCH int</span><br>";
            }
        }
        echo "</div>";
    }
    
    echo "<h3>Résultat Final</h3>";
    echo "<pre>";
    foreach ($groupsById as $id => $data) {
        echo "Groupe: " . $data['name'] . " | Topics: " . count($data['topics']) . "\n";
    }
    echo "</pre>";
}
?>
