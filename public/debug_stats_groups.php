<?php
session_start();

if (!isset($_SESSION['user'])) {
    die("Non connecté");
}

require_once __DIR__ . '/../app/Services/ApiService.php';

$apiService = new ApiService();

echo "<h1>Debug Stats Groupes</h1>";

// Récupérer les groupes
$groupsResponse = $apiService->getGroups();

echo "<h2>1. Réponse API getGroups()</h2>";
echo "<pre>";
print_r($groupsResponse);
echo "</pre>";

if ($groupsResponse['success'] && !empty($groupsResponse['data']['groups'])) {
    $groups = $groupsResponse['data']['groups'];
    
    echo "<h2>2. Premier groupe détaillé</h2>";
    if (!empty($groups[0])) {
        echo "<pre>";
        print_r($groups[0]);
        echo "</pre>";
    }
    
    echo "<h2>3. club_id de chaque groupe</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>club_id</th></tr>";
    foreach ($groups as $group) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($group['id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($group['name'] ?? 'N/A') . "</td>";
        echo "<td style='background: yellow;'><strong>" . htmlspecialchars($group['club_id'] ?? 'NULL') . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Simuler la construction de groups_by_club
    echo "<h2>4. Construction de groups_by_club</h2>";
    $groupsByClub = [];
    foreach ($groups as $group) {
        $clubId = $group['club_id'] ?? '';
        if (!empty($clubId)) {
            if (!isset($groupsByClub[$clubId])) {
                $groupsByClub[$clubId] = [];
            }
            $groupsByClub[$clubId][] = $group;
        }
    }
    echo "<p>Clés dans groupsByClub: <strong>" . implode(', ', array_keys($groupsByClub)) . "</strong></p>";
    echo "<pre>";
    print_r($groupsByClub);
    echo "</pre>";
}
