<?php
// Test des URLs de pièces jointes
$backendUrl = "http://82.67.123.22:25000";

// Test avec différentes structures d URLs
$testUrls = [
    "/uploads/image.jpg",
    "uploads/image.jpg", 
    "http://82.67.123.22:25000/uploads/image.jpg",
    "https://example.com/image.jpg"
];

echo "<h2>Test des URLs de pièces jointes</h2>";

foreach ($testUrls as $url) {
    echo "<h3>URL originale: " . htmlspecialchars($url) . "</h3>";
    
    if ($url && !str_starts_with($url, "http")) {
        $correctedUrl = $backendUrl . "/" . ltrim($url, "/");
        echo "<p>URL corrigée: " . htmlspecialchars($correctedUrl) . "</p>";
    } else {
        echo "<p>URL déjà complète: " . htmlspecialchars($url) . "</p>";
    }
    echo "<hr>";
}
?>
