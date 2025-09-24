<?php
// Script de test pour vérifier la structure des messages
session_start();

// Simuler une session utilisateur
$_SESSION["user"] = [
    "id" => "123",
    "is_admin" => true
];

// Données de test basées sur votre exemple
$testMessage = [
    "id" => "test123",
    "content" => "",
    "author" => [
        "name" => "Caldora",
        "_id" => "123"  // Même ID que l utilisateur session
    ],
    "attachment" => [
        "url" => "/uploads/687588d6b56af_96a33dfd-f45a-40c4-988e-10c4612c9aec-1_all_982.jpg",
        "originalName" => "96a33dfd-f45a-40c4-988e-10c4612c9aec-1_all_982.jpg",
        "mimeType" => "image/jpeg"
    ],
    "created_at" => "2024-01-15T10:30:00Z"
];

echo "<h2>Test de détection de l auteur</h2>";
echo "<h3>Données du message :</h3>";
echo "<pre>" . json_encode($testMessage, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>ID utilisateur session :</h3>";
echo "<pre>" . $_SESSION["user"]["id"] . "</pre>";

// Test de la logique de détection
$authorId = $testMessage["author_id"] ?? $testMessage["userId"] ?? $testMessage["user_id"] ?? $testMessage["author"]["id"] ?? $testMessage["author"]["_id"] ?? null;
$isCurrentUser = $authorId && $_SESSION["user"]["id"] && $authorId == $_SESSION["user"]["id"];

echo "<h3>Résultat de la détection :</h3>";
echo "<p><strong>authorId détecté :</strong> " . ($authorId ?? "null") . "</p>";
echo "<p><strong>isCurrentUser :</strong> " . ($isCurrentUser ? "true" : "false") . "</p>";

$alignClass = $isCurrentUser ? "justify-content-end" : "justify-content-start";
$messageClass = $isCurrentUser ? "message-sent" : "message-received";

echo "<p><strong>alignClass :</strong> " . $alignClass . "</p>";
echo "<p><strong>messageClass :</strong> " . $messageClass . "</p>";
?>
