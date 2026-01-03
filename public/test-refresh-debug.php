<?php
/**
 * Test simple du rafraîchissement de token
 * URL: /test-refresh-debug.php
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Rafraîchissement Token - Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .box { border: 2px solid #555; padding: 15px; margin: 10px 0; border-radius: 5px; background: #2d2d2d; }
        .success { border-color: #4CAF50; background: #1b5e20; }
        .error { border-color: #f44336; background: #5e1b1b; }
        .info { border-color: #2196F3; background: #1b3a5e; }
        button { padding: 10px 20px; margin: 5px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0b7dda; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        h1 { color: #4CAF50; }
    </style>
</head>
<body>
    <h1>🔍 Test Rafraîchissement Token - Debug</h1>
    
    <div class="box info">
        <h3>État Initial</h3>
        <?php
        echo "<p><strong>Session logged_in:</strong> " . (isset($_SESSION['logged_in']) ? 'OUI' : 'NON') . "</p>";
        echo "<p><strong>Token présent:</strong> " . (isset($_SESSION['token']) ? 'OUI' : 'NON') . "</p>";
        
        if (isset($_SESSION['token'])) {
            $token = $_SESSION['token'];
            $tokenParts = explode('.', $token);
            if (count($tokenParts) === 3) {
                $payload = json_decode(base64_decode($tokenParts[1]), true);
                echo "<p><strong>Token expire le:</strong> " . date('Y-m-d H:i:s', $payload['exp']) . "</p>";
                echo "<p><strong>Temps restant:</strong> " . ($payload['exp'] - time()) . " secondes (" . floor(($payload['exp'] - time()) / 60) . " minutes)</p>";
                echo "<p><strong>Nécessite rafraîchissement (&lt;30min):</strong> " . (($payload['exp'] - time()) < 1800 ? 'OUI' : 'NON') . "</p>";
            }
        }
        ?>
    </div>
    
    <div class="box">
        <h3>Actions</h3>
        <button onclick="testKeepAlive()">Test Keep-Alive</button>
        <button onclick="testRefreshDirect()">Test Refresh Direct (API)</button>
        <button onclick="testExpireToken()">Expirer le Token (30 min avant)</button>
        <button onclick="window.location.reload()">Recharger</button>
    </div>
    
    <div class="box" id="result">
        <h3>Résultats</h3>
        <p>Cliquez sur un bouton pour tester...</p>
    </div>
    
    <script>
        async function testKeepAlive() {
            const result = document.getElementById('result');
            result.innerHTML = '<h3>Test Keep-Alive en cours...</h3>';
            
            try {
                const response = await fetch('/keep-alive.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                result.innerHTML = '<h3>Résultat Keep-Alive</h3>';
                result.innerHTML += `<p><strong>Status:</strong> ${response.status} ${response.statusText}</p>`;
                
                const contentType = response.headers.get('content-type');
                result.innerHTML += `<p><strong>Content-Type:</strong> ${contentType}</p>`;
                
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    result.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    
                    if (data.success) {
                        result.className = 'box success';
                    } else {
                        result.className = 'box error';
                    }
                } else {
                    const text = await response.text();
                    result.innerHTML += '<h4>Réponse (texte brut):</h4>';
                    result.innerHTML += '<pre>' + text.substring(0, 1000) + '</pre>';
                    result.className = 'box error';
                }
            } catch (error) {
                result.innerHTML = '<h3>❌ Erreur</h3>';
                result.innerHTML += '<p>' + error.message + '</p>';
                result.className = 'box error';
            }
        }
        
        async function testRefreshDirect() {
            const result = document.getElementById('result');
            result.innerHTML = '<h3>Test Refresh Direct en cours...</h3>';
            
            try {
                // Récupérer le token actuel depuis la session via une requête
                const tokenResponse = await fetch('/api/auth/verify');
                
                result.innerHTML = '<h3>Résultat Refresh Direct</h3>';
                result.innerHTML += `<p><strong>Status verify:</strong> ${tokenResponse.status}</p>`;
                
                // Maintenant tester refresh
                const refreshResponse = await fetch('/api/auth/refresh', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                result.innerHTML += `<p><strong>Status refresh:</strong> ${refreshResponse.status}</p>`;
                
                const refreshData = await refreshResponse.json();
                result.innerHTML += '<pre>' + JSON.stringify(refreshData, null, 2) + '</pre>';
                
                if (refreshData.success) {
                    result.className = 'box success';
                } else {
                    result.className = 'box error';
                }
            } catch (error) {
                result.innerHTML = '<h3>❌ Erreur</h3>';
                result.innerHTML += '<p>' + error.message + '</p>';
                result.className = 'box error';
            }
        }
        
        async function testExpireToken() {
            const result = document.getElementById('result');
            result.innerHTML = '<h3>Expiration du token en cours...</h3>';
            
            try {
                const response = await fetch('/expire-token-30min.php');
                const data = await response.json();
                
                result.innerHTML = '<h3>Token Expiré</h3>';
                result.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                result.innerHTML += '<p><strong>Le token expire maintenant dans moins de 30 minutes.</strong></p>';
                result.innerHTML += '<p>Cliquez sur "Test Keep-Alive" pour déclencher le rafraîchissement.</p>';
                result.className = 'box success';
            } catch (error) {
                result.innerHTML = '<h3>❌ Erreur</h3>';
                result.innerHTML += '<p>' + error.message + '</p>';
                result.className = 'box error';
            }
        }
    </script>
</body>
</html>
