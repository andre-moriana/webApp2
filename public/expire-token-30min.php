<?php
/**
 * Script pour définir le token avec une expiration dans 25 minutes
 * Permet de tester le rafraîchissement automatique
 */

session_start();

if (!isset($_SESSION['token'])) {
    die(json_encode(['success' => false, 'message' => 'Pas de token en session']));
}

// Décoder le token actuel
$token = $_SESSION['token'];
$tokenParts = explode('.', $token);

if (count($tokenParts) !== 3) {
    die(json_encode(['success' => false, 'message' => 'Token mal formé']));
}

// Décoder le payload
$payload = json_decode(base64_decode($tokenParts[1]), true);

// Modifier l'expiration pour dans 25 minutes (moins de 30, donc déclenchera le refresh)
$newExp = time() + (25 * 60); // 25 minutes
$payload['exp'] = $newExp;

// Recréer le token (attention: ceci invalide la signature, mais pour les tests ça suffit)
$newPayload = base64_encode(json_encode($payload));
$fakeToken = $tokenParts[0] . '.' . $newPayload . '.' . $tokenParts[2];

$_SESSION['token'] = $fakeToken;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Token modifié pour expirer dans 25 minutes',
    'old_exp' => date('Y-m-d H:i:s', $payload['exp']),
    'new_exp' => date('Y-m-d H:i:s', $newExp),
    'time_left' => ($newExp - time()) . ' secondes',
    'will_refresh' => (($newExp - time()) < 1800) ? 'OUI' : 'NON'
]);
