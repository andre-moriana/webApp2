<?php
// Debug direct de la table group_topics
require_once __DIR__ . '/app/Config/Autoloader.php';
require_once __DIR__ . '/../BackendPHP/config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Group Topics</title></head><body>";
echo "<h1>Debug Table group_topics</h1>";

try {
    $db = Database::getInstance();
    
    // Count total topics
    $sql = "SELECT COUNT(*) as count FROM group_topics";
    $result = $db->fetchOne($sql);
    $totalTopics = $result['count'] ?? 0;
    
    echo "<h2>Total topics dans la table: $totalTopics</h2>";
    
    if ($totalTopics > 0) {
        // Get all topics with group info
        $sql = "SELECT t.*, g.name as group_name 
                FROM group_topics t 
                LEFT JOIN chat_groups g ON t.group_id = g.id 
                ORDER BY t.id";
        $topics = $db->fetchAll($sql);
        
        echo "<h3>Topics trouvés:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Group ID</th><th>Type Group ID</th><th>Group Name</th><th>Created At</th></tr>";
        
        foreach ($topics as $topic) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($topic['id']) . "</td>";
            echo "<td>" . htmlspecialchars($topic['title'] ?? 'N/A') . "</td>";
            echo "<td>" . var_export($topic['group_id'], true) . "</td>";
            echo "<td>" . gettype($topic['group_id']) . "</td>";
            echo "<td>" . htmlspecialchars($topic['group_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($topic['created_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'><strong>La table group_topics est vide!</strong></p>";
        echo "<p>Vérifiez que des sujets ont été créés dans l'application mobile.</p>";
    }
    
    // Get all groups
    echo "<hr><h2>Groups disponibles:</h2>";
    $sql = "SELECT id, name, club_id FROM chat_groups ORDER BY id";
    $groups = $db->fetchAll($sql);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Type ID</th><th>Name</th><th>Club ID</th></tr>";
    foreach ($groups as $group) {
        echo "<tr>";
        echo "<td>" . var_export($group['id'], true) . "</td>";
        echo "<td>" . gettype($group['id']) . "</td>";
        echo "<td>" . htmlspecialchars($group['name']) . "</td>";
        echo "<td>" . htmlspecialchars($group['club_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
