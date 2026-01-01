<?php
// Script pour exécuter la migration de club_id
session_start();

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    die("<h1>Accès refusé</h1><p>Vous devez être administrateur pour accéder à cette page.</p>");
}

// Charger la configuration de la base de données
require_once __DIR__ . '/../../Developement/BackendPHP/config/database.php';

echo "<h1>Migration - Ajout de club_id à chat_groups</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h2>Connexion à la base de données : OK</h2>";
    
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM chat_groups LIKE 'club_id'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "<p style='color: orange;'>⚠️ La colonne club_id existe déjà dans chat_groups.</p>";
    } else {
        echo "<p style='color: blue;'>➡️ Ajout de la colonne club_id...</p>";
        
        // Ajouter la colonne
        $pdo->exec("ALTER TABLE `chat_groups` ADD COLUMN `club_id` VARCHAR(50) NULL DEFAULT NULL AFTER `is_private`");
        echo "<p style='color: green;'>✓ Colonne club_id ajoutée avec succès</p>";
        
        // Ajouter l'index
        $pdo->exec("ALTER TABLE `chat_groups` ADD INDEX `idx_club_id` (`club_id`)");
        echo "<p style='color: green;'>✓ Index créé sur club_id</p>";
    }
    
    // Mettre à jour les groupes existants
    echo "<p style='color: blue;'>➡️ Mise à jour des groupes existants...</p>";
    $stmt = $pdo->exec("
        UPDATE `chat_groups` cg
        INNER JOIN `users` u ON cg.admin_id = u.id
        SET cg.club_id = u.club_id
        WHERE cg.club_id IS NULL AND u.club_id IS NOT NULL
    ");
    echo "<p style='color: green;'>✓ $stmt groupe(s) mis à jour</p>";
    
    // Afficher les résultats
    echo "<h2>Vérification des données</h2>";
    $stmt = $pdo->query("
        SELECT g.id, g.name, g.club_id, u.club_id as user_club_id, u.name as admin_name
        FROM chat_groups g
        INNER JOIN users u ON g.admin_id = u.id
        LIMIT 10
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nom groupe</th><th>club_id (groupe)</th><th>club_id (user)</th><th>Admin</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td style='background: lightgreen;'><strong>" . htmlspecialchars($row['club_id'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['user_club_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 style='color: green;'>✓ Migration terminée avec succès !</h2>";
    echo "<p><a href='/dashboard'>Retour au dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
