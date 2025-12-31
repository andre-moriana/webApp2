<?php

class DebugController {
    
    public function deletionPending() {
        // Vérifier que l'utilisateur est admin
        if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
            die("Accès refusé - Admin seulement");
        }

        require_once __DIR__ . '/../Services/ApiService.php';

        echo "<html><head><title>Debug Deletion Pending</title><style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ddd; }
        .section h2 { margin-top: 0; color: #333; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        </style></head><body>";

        echo "<h1>🔍 Diagnostic - Utilisateurs en attente de suppression</h1>";

        // 1. Vérifier la session
        echo "<div class='section'>";
        echo "<h2>1. Informations de session</h2>";
        echo "<pre>";
        echo "Logged in: " . (isset($_SESSION['logged_in']) ? '✅ YES' : '❌ NO') . "\n";
        echo "Token présent: " . (isset($_SESSION['token']) ? '✅ YES' : '❌ NO') . "\n";
        if (isset($_SESSION['token'])) {
            echo "Token (20 premiers chars): " . substr($_SESSION['token'], 0, 20) . "...\n";
        }
        echo "\nUser info:\n";
        echo json_encode($_SESSION['user'] ?? 'NO USER', JSON_PRETTY_PRINT);
        echo "</pre>";
        echo "</div>";

        // 2. Tester la connexion à l'API
        echo "<div class='section'>";
        echo "<h2>2. Test de connexion à l'API</h2>";
        try {
            $apiService = new ApiService();
            
            // Tester l'endpoint deletion-pending
            echo "<h3>Appel GET /users/deletion-pending</h3>";
            $result = $apiService->getDeletionPendingUsers();
            
            echo "<pre>";
            echo "Success: " . ($result['success'] ? '<span class="success">✅ TRUE</span>' : '<span class="error">❌ FALSE</span>') . "\n";
            echo "Status Code: " . ($result['status_code'] ?? 'N/A') . "\n";
            echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
            echo "\nRéponse complète:\n";
            echo json_encode($result, JSON_PRETTY_PRINT);
            echo "</pre>";
            
            if (isset($result['data'])) {
                echo "<h3>Données reçues</h3>";
                echo "<pre>";
                if (is_array($result['data'])) {
                    echo "Type: Array\n";
                    echo "Nombre d'éléments: " . count($result['data']) . "\n\n";
                    
                    // Vérifier si data contient data (double imbrication)
                    if (isset($result['data']['data'])) {
                        echo "⚠️ Double imbrication détectée (data.data)\n";
                        echo "Nombre dans data.data: " . count($result['data']['data']) . "\n\n";
                        
                        if (!empty($result['data']['data'])) {
                            echo "Premier utilisateur dans data.data:\n";
                            echo json_encode($result['data']['data'][0], JSON_PRETTY_PRINT);
                        }
                    } else {
                        if (!empty($result['data'])) {
                            echo "Premier élément:\n";
                            echo json_encode($result['data'][0] ?? 'Tableau vide', JSON_PRETTY_PRINT);
                        } else {
                            echo "⚠️ Tableau vide";
                        }
                    }
                } else {
                    echo "Type: " . gettype($result['data']) . "\n";
                    echo json_encode($result['data'], JSON_PRETTY_PRINT);
                }
                echo "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<pre class='error'>";
            echo "❌ Exception: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString();
            echo "</pre>";
        }
        echo "</div>";

        // 3. Test direct avec cURL vers le backend
        echo "<div class='section'>";
        echo "<h2>3. Test direct cURL vers le backend</h2>";

        // Lire l'URL de l'API depuis .env
        $baseUrl = "https://api.arctraining.fr/api";
        if (file_exists('.env')) {
            $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, "API_BASE_URL") === 0) {
                    list($key, $value) = explode("=", $line, 2);
                    $baseUrl = trim($value);
                    break;
                }
            }
        }

        $url = rtrim($baseUrl, '/') . '/users/deletion-pending';
        echo "<pre>";
        echo "URL: " . $url . "\n";
        echo "Token: " . (isset($_SESSION['token']) ? substr($_SESSION['token'], 0, 20) . "..." : "NONE") . "\n\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $headers = ['Accept: application/json'];
        if (isset($_SESSION['token'])) {
            $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        echo "HTTP Code: " . $httpCode . "\n";
        if ($curlError) {
            echo "cURL Error: " . $curlError . "\n";
        }
        echo "\nRéponse brute:\n";
        echo htmlspecialchars($response);
        echo "\n\nRéponse décodée:\n";
        $decoded = json_decode($response, true);
        echo json_encode($decoded, JSON_PRETTY_PRINT);
        echo "</pre>";
        echo "</div>";

        echo "</body></html>";
    }
}
