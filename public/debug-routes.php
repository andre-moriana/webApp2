<?php
/**
 * Page de debug pour afficher toutes les routes enregistrées
 */

require_once '../app/Config/Autoloader.php';

// Chargement des variables d'environnement
if (file_exists('../.env')) {
    $lines = file('../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once '../app/Config/Router.php';

$router = new Router("");

// Utiliser la réflexion pour accéder aux routes privées
$reflection = new ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$routes = $routesProperty->getValue($router);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Routes</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .post { color: #2196F3; font-weight: bold; }
        .get { color: #4CAF50; font-weight: bold; }
        .delete { color: #f44336; font-weight: bold; }
        .put { color: #FF9800; font-weight: bold; }
        input { padding: 5px; width: 300px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Routes enregistrées (<?php echo count($routes); ?> routes)</h1>
    
    <input type="text" id="search" placeholder="Rechercher une route..." onkeyup="filterRoutes()">
    
    <table id="routesTable">
        <thead>
            <tr>
                <th>Méthode</th>
                <th>Path</th>
                <th>Handler</th>
                <th>Spécificité</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($routes as $route): ?>
                <tr>
                    <td class="<?php echo strtolower($route['method']); ?>">
                        <?php echo $route['method']; ?>
                    </td>
                    <td><?php echo htmlspecialchars($route['path']); ?></td>
                    <td><?php echo htmlspecialchars($route['handler']); ?></td>
                    <td><?php echo $route['specificity'] ?? 0; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <script>
        function filterRoutes() {
            const input = document.getElementById('search');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('routesTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html>
