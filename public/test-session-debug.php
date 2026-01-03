<?php
/**
 * Script de test pour vérifier l'état de la session et du token
 * URL: /test-session-debug.php
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Session & Token</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #4CAF50; }
        .error { border-left-color: #f44336; }
        .warning { border-left-color: #ff9800; }
        .success { border-left-color: #4CAF50; }
        h2 { margin-top: 0; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
        .status.ok { background: #4CAF50; color: white; }
        .status.fail { background: #f44336; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Debug Session & Token JWT</h1>
    <p>Date/Heure: <?php echo date('Y-m-d H:i:s'); ?> (timestamp: <?php echo time(); ?>)</p>
    
    <!-- Session PHP -->
    <div class="section <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'success' : 'error'; ?>">
        <h2>📦 Session PHP</h2>
        <p><strong>Session démarrée:</strong> <span class="status <?php echo session_status() === PHP_SESSION_ACTIVE ? 'ok' : 'fail'; ?>">
            <?php echo session_status() === PHP_SESSION_ACTIVE ? 'OUI' : 'NON'; ?>
        </span></p>
        
        <p><strong>Connecté:</strong> <span class="status <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'ok' : 'fail'; ?>">
            <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'OUI' : 'NON'; ?>
        </span></p>
        
        <?php if (isset($_SESSION['last_activity'])): ?>
        <p><strong>Dernière activité:</strong> 
            <?php 
            $elapsed = time() - $_SESSION['last_activity'];
            $hours = floor($elapsed / 3600);
            $minutes = floor(($elapsed % 3600) / 60);
            echo date('Y-m-d H:i:s', $_SESSION['last_activity']) . " (il y a {$hours}h {$minutes}m)";
            ?>
        </p>
        <?php endif; ?>
        
        <p><strong>Contenu de $_SESSION:</strong></p>
        <pre><?php 
        $sessionCopy = $_SESSION;
        if (isset($sessionCopy['token'])) {
            $sessionCopy['token'] = substr($sessionCopy['token'], 0, 50) . '... (tronqué)';
        }
        print_r($sessionCopy); 
        ?></pre>
    </div>
    
    <!-- Token JWT -->
    <div class="section <?php echo isset($_SESSION['token']) ? 'success' : 'error'; ?>">
        <h2>🔑 Token JWT</h2>
        
        <?php if (isset($_SESSION['token']) && !empty($_SESSION['token'])): ?>
            <?php
            $token = $_SESSION['token'];
            $tokenParts = explode('.', $token);
            $isValid = false;
            $payload = null;
            $error = null;
            
            if (count($tokenParts) === 3) {
                try {
                    $payload = json_decode(base64_decode($tokenParts[1]), true);
                    if ($payload && isset($payload['exp'])) {
                        $isValid = time() < $payload['exp'];
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = "Token mal formé (pas 3 parties)";
            }
            ?>
            
            <p><strong>Token présent:</strong> <span class="status ok">OUI</span></p>
            <p><strong>Format:</strong> <span class="status <?php echo count($tokenParts) === 3 ? 'ok' : 'fail'; ?>">
                <?php echo count($tokenParts); ?> parties (attendu: 3)
            </span></p>
            
            <?php if ($payload): ?>
                <p><strong>Valide:</strong> <span class="status <?php echo $isValid ? 'ok' : 'fail'; ?>">
                    <?php echo $isValid ? 'OUI' : 'NON (EXPIRÉ)'; ?>
                </span></p>
                
                <p><strong>Payload décodé:</strong></p>
                <pre><?php print_r($payload); ?></pre>
                
                <?php if (isset($payload['exp'])): ?>
                    <?php
                    $expiresAt = date('Y-m-d H:i:s', $payload['exp']);
                    $timeLeft = $payload['exp'] - time();
                    $hoursLeft = floor($timeLeft / 3600);
                    $minutesLeft = floor(($timeLeft % 3600) / 60);
                    ?>
                    <p><strong>Expire le:</strong> <?php echo $expiresAt; ?></p>
                    <p><strong>Temps restant:</strong> 
                        <span class="status <?php echo $timeLeft > 0 ? 'ok' : 'fail'; ?>">
                            <?php 
                            if ($timeLeft > 0) {
                                echo "{$hoursLeft}h {$minutesLeft}m";
                            } else {
                                echo "EXPIRÉ depuis " . abs($hoursLeft) . "h " . abs($minutesLeft) . "m";
                            }
                            ?>
                        </span>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($payload['iat'])): ?>
                    <p><strong>Créé le:</strong> <?php echo date('Y-m-d H:i:s', $payload['iat']); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <p class="error"><strong>Erreur:</strong> <?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            
            <details>
                <summary><strong>Token brut (cliquez pour afficher)</strong></summary>
                <pre style="word-break: break-all; white-space: pre-wrap;"><?php echo htmlspecialchars($token); ?></pre>
            </details>
            
        <?php else: ?>
            <p><strong>Token présent:</strong> <span class="status fail">NON</span></p>
            <p class="error">⚠️ Aucun token en session - les requêtes API vont échouer!</p>
        <?php endif; ?>
    </div>
    
    <!-- Test API -->
    <div class="section">
        <h2>🌐 Test API Backend</h2>
        <button onclick="testAPI()">Tester une requête API</button>
        <div id="apiResult" style="margin-top: 10px;"></div>
    </div>
    
    <!-- Actions -->
    <div class="section">
        <h2>🔧 Actions</h2>
        <a href="/dashboard" class="btn">Aller au Dashboard</a>
        <a href="/logout" class="btn" style="background: #f44336;">Se déconnecter</a>
        <button onclick="location.reload()">Recharger cette page</button>
    </div>
    
    <script>
        async function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>⏳ Test en cours...</p>';
            
            try {
                const response = await fetch('/api/auth/verify');
                const data = await response.json();
                
                if (response.ok) {
                    resultDiv.innerHTML = `
                        <div class="section success">
                            <p>✅ <strong>API répond OK</strong></p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="section error">
                            <p>❌ <strong>Erreur ${response.status}</strong></p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="section error">
                        <p>❌ <strong>Erreur réseau</strong></p>
                        <pre>${error.message}</pre>
                    </div>
                `;
            }
        }
    </script>
    
    <style>
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #45a049; }
        button { padding: 10px 20px; margin: 5px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0b7dda; }
    </style>
</body>
</html>
