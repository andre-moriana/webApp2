<?php

class DebugSessionController {
    
    public function index() {
        // Ne pas vérifier SessionGuard pour cette page de debug
        // On veut voir l'état réel de la session
        
        // Démarrer la session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Afficher la page de debug directement
        $this->renderDebugPage();
    }
    
    public function simple() {
        // Page de test simple sans vérification de session
        $this->renderSimplePage();
    }
    
    private function renderSimplePage() {
        ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Session - Simple</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
        .box { border: 2px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; background: white; }
        .success { border-color: #4CAF50; background: #f1f8f4; }
        .error { border-color: #f44336; background: #ffebee; }
        .warning { border-color: #ff9800; background: #fff3e0; }
        button { padding: 10px 20px; margin: 5px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        button:hover { background: #0b7dda; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border-radius: 3px; font-size: 12px; }
        .status { font-weight: bold; padding: 5px 10px; border-radius: 3px; display: inline-block; }
        .status.ok { background: #4CAF50; color: white; }
        .status.fail { background: #f44336; color: white; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 20px; }
        .info { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Test Session Simple - arctraining.fr</h1>
    <p>Cette page teste directement l'état de votre session et token JWT.</p>
    
    <div class="info">
        <strong>📍 Serveur:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'Inconnu'; ?><br>
        <strong>⏰ Date/Heure:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
        <strong>🔗 URL:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? '/'; ?>
    </div>
    
    <div class="box">
        <h2>📊 État Session PHP</h2>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p><span class="status ok">✅ CONNECTÉ</span></p>
            <p><strong>Utilisateur:</strong> <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Inconnu'); ?></p>
            <?php if (isset($_SESSION['last_activity'])): ?>
                <p><strong>Dernière activité:</strong> <?php 
                    $elapsed = time() - $_SESSION['last_activity'];
                    $hours = floor($elapsed / 3600);
                    $minutes = floor(($elapsed % 3600) / 60);
                    echo "Il y a {$hours}h {$minutes}m";
                ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p><span class="status fail">❌ NON CONNECTÉ</span></p>
            <p>Vous n'êtes pas connecté ou votre session a expiré.</p>
        <?php endif; ?>
    </div>
    
    <div class="box">
        <h2>🔑 État Token JWT</h2>
        <?php 
        if (isset($_SESSION['token']) && !empty($_SESSION['token'])) {
            $token = $_SESSION['token'];
            $tokenParts = explode('.', $token);
            
            if (count($tokenParts) === 3) {
                try {
                    $payload = json_decode(base64_decode($tokenParts[1]), true);
                    
                    if ($payload && isset($payload['exp'])) {
                        $now = time();
                        $exp = $payload['exp'];
                        $timeLeft = $exp - $now;
                        $isValid = $timeLeft > 0;
                        
                        if ($isValid) {
                            $hoursLeft = floor($timeLeft / 3600);
                            $minutesLeft = floor(($timeLeft % 3600) / 60);
                            echo '<p><span class="status ok">✅ TOKEN VALIDE</span></p>';
                            echo "<p><strong>Expire dans:</strong> {$hoursLeft}h {$minutesLeft}m</p>";
                        } else {
                            $hoursAgo = floor(abs($timeLeft) / 3600);
                            $minutesAgo = floor((abs($timeLeft) % 3600) / 60);
                            echo '<p><span class="status fail">❌ TOKEN EXPIRÉ</span></p>';
                            echo "<p><strong>Expiré depuis:</strong> {$hoursAgo}h {$minutesAgo}m</p>";
                        }
                        
                        echo '<p><strong>Expire le:</strong> ' . date('Y-m-d H:i:s', $exp) . '</p>';
                        if (isset($payload['iat'])) {
                            echo '<p><strong>Créé le:</strong> ' . date('Y-m-d H:i:s', $payload['iat']) . '</p>';
                        }
                    } else {
                        echo '<p><span class="status fail">❌ TOKEN INVALIDE</span></p>';
                        echo '<p>Le token n\'a pas de date d\'expiration.</p>';
                    }
                } catch (Exception $e) {
                    echo '<p><span class="status fail">❌ ERREUR</span></p>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            } else {
                echo '<p><span class="status fail">❌ TOKEN MAL FORMÉ</span></p>';
                echo '<p>Le token ne contient pas 3 parties.</p>';
            }
        } else {
            echo '<p><span class="status fail">❌ AUCUN TOKEN</span></p>';
            echo '<p>Aucun token JWT trouvé en session.</p>';
        }
        ?>
    </div>
    
    <div class="box">
        <h2>📊 Tests Rapides</h2>
        <button onclick="testConsole()">1. Tester la Console</button>
        <button onclick="testSessionAPI()">2. Tester l'API Verify</button>
        <button onclick="window.location.href='/dashboard'">3. Aller au Dashboard</button>
        <div id="testResult" style="margin-top: 15px;"></div>
    </div>
    
    <div id="consoleBox" class="box warning" style="display: none;">
        <h2>⚠️ Console Safari</h2>
        <p>Pour ouvrir la <strong>Console Safari</strong>:</p>
        <ol>
            <li>Si le menu <strong>Développement</strong> n'est pas visible:
                <ul>
                    <li>Menu <strong>Safari</strong> > <strong>Réglages</strong></li>
                    <li>Onglet <strong>Avancées</strong></li>
                    <li>✅ Cocher <strong>"Afficher le menu Développement"</strong></li>
                </ul>
            </li>
            <li>Puis: Menu <strong>Développement</strong> > <strong>Afficher la console JavaScript</strong></li>
            <li>OU raccourci: <strong>Option (⌥) + Command (⌘) + C</strong></li>
        </ol>
    </div>
    
    <div class="box">
        <h2>🔧 Actions</h2>
        <button onclick="window.location.href='/logout'">Se Déconnecter</button>
        <button onclick="window.location.href='/login'">Aller au Login</button>
        <button onclick="location.reload()">Recharger cette Page</button>
    </div>
    
    <script>
        console.log('🔍 Page de test chargée sur arctraining.fr');
        console.log('📍 URL:', window.location.href);
        console.log('⏰ Date:', new Date().toLocaleString());
        
        function testConsole() {
            console.log('✅ TEST CONSOLE: La console fonctionne!');
            console.log('🎯 Informations navigateur:');
            console.log('- User Agent:', navigator.userAgent);
            console.log('- Cookies activés:', navigator.cookieEnabled);
            
            const box = document.getElementById('consoleBox');
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth' });
            
            const result = document.getElementById('testResult');
            result.innerHTML = '<div class="box success"><p>✅ <strong>Console activée!</strong><br>Vérifiez la console Safari pour voir les messages.</p></div>';
        }
        
        async function testSessionAPI() {
            const result = document.getElementById('testResult');
            result.innerHTML = '<p>⏳ Test de /api/auth/verify en cours...</p>';
            
            console.log('🌐 Appel API: /api/auth/verify');
            
            try {
                const startTime = Date.now();
                const response = await fetch('/api/auth/verify');
                const duration = Date.now() - startTime;
                
                console.log('📡 Réponse reçue en', duration, 'ms');
                console.log('📊 Status:', response.status);
                
                const data = await response.json();
                console.log('📄 Données:', data);
                
                if (response.ok) {
                    const expiresIn = data.expires_in || 0;
                    const hours = Math.floor(expiresIn / 3600);
                    const minutes = Math.floor((expiresIn % 3600) / 60);
                    
                    result.innerHTML = `
                        <div class="box success">
                            <h3>✅ Token Valide!</h3>
                            <p>Votre session est active.</p>
                            <p><strong>Expire dans:</strong> ${hours}h ${minutes}m</p>
                            <p><strong>Temps de réponse:</strong> ${duration}ms</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    result.innerHTML = `
                        <div class="box error">
                            <h3>❌ Token Invalide (${response.status})</h3>
                            <p>${data.message || 'Token expiré ou invalide'}</p>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                            <p><strong>⚠️ Vous devez vous reconnecter</strong></p>
                            <button onclick="window.location.href='/logout'">Se Déconnecter</button>
                            <button onclick="window.location.href='/login'">Aller au Login</button>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('❌ Erreur:', error);
                result.innerHTML = `
                    <div class="box error">
                        <h3>❌ Erreur Réseau</h3>
                        <p>${error.message}</p>
                        <p>Vérifiez votre connexion internet.</p>
                    </div>
                `;
            }
        }
        
        // Test automatique au chargement
        console.log('⏱️ Test automatique dans 2 secondes...');
        setTimeout(() => {
            console.log('🚀 Lancement du test automatique');
            testSessionAPI();
        }, 2000);
    </script>
</body>
</html>
        <?php
    }
    
    private function renderDebugPage() {
        // Inclure le contenu de test-session-debug.php
        include __DIR__ . '/../../public/test-session-debug.php';
    }
}
