<?php
// Script de debug pour voir les valeurs des utilisateurs
$file = 'app/Controllers/TrainingController.php';
$content = file_get_contents($file);

// Ajouter du debug après la ligne 47
$debugCode = '
        // DEBUG: Afficher les valeurs
        error_log("DEBUG - actualUserId: " . $actualUserId);
        error_log("DEBUG - GET user_id: " . ($_GET["user_id"] ?? "non défini"));
        error_log("DEBUG - isAdmin: " . ($isAdmin ? "true" : "false"));
        error_log("DEBUG - isCoach: " . ($isCoach ? "true" : "false"));
        error_log("DEBUG - selectedUserId avant: " . $selectedUserId);
';

$content = str_replace(
    '        // Récupérer l\'ID de l\'utilisateur sélectionné
        $selectedUserId = $actualUserId; // Utiliser l\'ID du token',
    '        // Récupérer l\'ID de l\'utilisateur sélectionné
        $selectedUserId = $actualUserId; // Utiliser l\'ID du token' . $debugCode,
    $content
);

// Ajouter du debug après la ligne 50
$debugCode2 = '
        error_log("DEBUG - selectedUserId après: " . $selectedUserId);
';

$content = str_replace(
    '        if (($isAdmin || $isCoach) && isset($_GET[\'user_id\']) && !empty($_GET[\'user_id\'])) {
            $selectedUserId = (int)$_GET[\'user_id\'];
        }',
    '        if (($isAdmin || $isCoach) && isset($_GET[\'user_id\']) && !empty($_GET[\'user_id\'])) {
            $selectedUserId = (int)$_GET[\'user_id\'];
        }' . $debugCode2,
    $content
);

file_put_contents($file, $content);
echo "Debug ajouté avec succès!\n";
?>
