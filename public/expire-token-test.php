<?php
/**
 * Script pour expirer manuellement le token JWT pour tester
 * URL: /expire-token-test.php
 */

session_start();

if (!isset($_SESSION['token'])) {
    die('Pas de token en session. Connectez-vous d\'abord.');
}

echo '<h1>Test d\'expiration du token</h1>';

// Afficher le token actuel
echo '<h2>Token actuel :</h2>';
$token = $_SESSION['token'];
$parts = explode('.', $token);
if (count($parts) === 3) {
    $payload = json_decode(base64_decode($parts[1]), true);
    echo '<pre>';
    print_r($payload);
    echo '</pre>';
    echo '<p><strong>Expire le :</strong> ' . date('Y-m-d H:i:s', $payload['exp']) . '</p>';
    echo '<p><strong>Temps restant :</strong> ' . ($payload['exp'] - time()) . ' secondes</p>';
}

// Option 1 : Créer un faux token expiré
if (isset($_GET['fake'])) {
    // Créer un payload avec exp dans le passé
    $fakePayload = [
        'user_id' => $_SESSION['user']['id'] ?? 1,
        'exp' => time() - 3600, // Expiré il y a 1 heure
        'iat' => time() - 7200  // Créé il y a 2 heures
    ];
    
    $fakeToken = 'fake-header.' . base64_encode(json_encode($fakePayload)) . '.fake-signature';
    $_SESSION['token'] = $fakeToken;
    
    echo '<div style="background: #ffebee; padding: 20px; margin: 20px 0; border-left: 4px solid #f44336;">';
    echo '<h3>✅ Token remplacé par un token expiré</h3>';
    echo '<p>Le token a été modifié pour avoir expiré il y a 1 heure.</p>';
    echo '<p><strong>Nouveau token exp :</strong> ' . date('Y-m-d H:i:s', $fakePayload['exp']) . '</p>';
    echo '</div>';
    
    echo '<h3>Maintenant testez :</h3>';
    echo '<ol>';
    echo '<li><a href="/dashboard">Aller sur le Dashboard</a> - devrait vous rediriger vers login</li>';
    echo '<li><a href="/test-simple">Tester l\'API verify</a> - devrait retourner 401</li>';
    echo '</ol>';
    
    exit;
}

// Bouton pour expirer le token
echo '<form method="get">';
echo '<input type="hidden" name="fake" value="1">';
echo '<button type="submit" style="background: #f44336; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
    ⚠️ Expirer le Token Maintenant
</button>';
echo '</form>';

echo '<br><p><a href="/dashboard">Retour au Dashboard</a></p>';
