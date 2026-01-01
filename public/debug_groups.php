<?php
// Script de debug pour vérifier les données des groupes
session_start();

require_once __DIR__ . '/../app/Services/ApiService.php';

$apiService = new ApiService();

echo "<h1>Debug - Groupes et Clubs</h1>";

// Récupérer les groupes
echo "<h2>Groupes depuis l'API</h2>";
$groupsResponse = $apiService->getGroups();

if ($groupsResponse['success']) {
    echo "<p>Nombre de groupes : " . count($groupsResponse['data']['groups']) . "</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>club_id</th><th>clubId</th><th>Admin ID</th></tr>";
    
    foreach ($groupsResponse['data']['groups'] as $group) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($group['id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($group['name'] ?? 'N/A') . "</td>";
        echo "<td style='background: yellow;'><strong>" . htmlspecialchars($group['club_id'] ?? 'NULL') . "</strong></td>";
        echo "<td style='background: lightblue;'><strong>" . htmlspecialchars($group['clubId'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . htmlspecialchars($group['admin_id'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Erreur : " . htmlspecialchars($groupsResponse['message'] ?? 'Inconnue') . "</p>";
}

// Récupérer les clubs
echo "<h2>Clubs depuis l'API</h2>";
$clubsResponse = $apiService->getClubs();

if ($clubsResponse['success']) {
    echo "<p>Nombre de clubs : " . count($clubsResponse['data']['clubs']) . "</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>MongoDB ID</th><th>Nameshort (utilisé dans dashboard)</th><th>Nom</th></tr>";
    
    foreach ($clubsResponse['data']['clubs'] as $club) {
        $nameshort = $club['nameshort'] ?? $club['nameShort'] ?? 'N/A';
        $mongoId = $club['id'] ?? $club['_id'] ?? 'N/A';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($mongoId) . "</td>";
        echo "<td style='background: lightgreen;'><strong>" . htmlspecialchars($nameshort) . "</strong></td>";
        echo "<td>" . htmlspecialchars($club['name'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Erreur : " . htmlspecialchars($clubsResponse['message'] ?? 'Inconnue') . "</p>";
}

// Vérifier la correspondance
echo "<h2>Analyse de correspondance</h2>";
if ($groupsResponse['success'] && $clubsResponse['success']) {
    $clubNameshorts = [];
    foreach ($clubsResponse['data']['clubs'] as $club) {
        $nameshort = $club['nameshort'] ?? $club['nameShort'] ?? '';
        if ($nameshort) {
            $clubNameshorts[] = $nameshort;
        }
    }
    
    echo "<h3>Groupes avec club_id NON CORRESPONDANT à un nameshort :</h3>";
    echo "<ul>";
    $mismatchCount = 0;
    foreach ($groupsResponse['data']['groups'] as $group) {
        $clubId = $group['club_id'] ?? $group['clubId'] ?? '';
        if ($clubId && !in_array($clubId, $clubNameshorts)) {
            $mismatchCount++;
            echo "<li style='color: red;'><strong>" . htmlspecialchars($group['name']) . "</strong> a club_id=<strong>" . htmlspecialchars($clubId) . "</strong> (pas dans la liste des nameshort)</li>";
        }
    }
    if ($mismatchCount === 0) {
        echo "<li style='color: green;'>✓ Tous les groupes ont un club_id valide correspondant à un nameshort</li>";
    } else {
        echo "<li><strong style='color: red;'>PROBLÈME : $mismatchCount groupe(s) ont un club_id qui ne correspond pas à un nameshort de club !</strong></li>";
    }
    echo "</ul>";
    
    echo "<h3>Nameshort des clubs disponibles :</h3>";
    echo "<ul>";
    foreach ($clubNameshorts as $ns) {
        echo "<li>" . htmlspecialchars($ns) . "</li>";
    }
    echo "</ul>";
}
