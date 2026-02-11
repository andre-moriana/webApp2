<?php

require_once '../app/Controllers/ArcherSearchController.php';

header('Content-Type: application/json');

// Déterminer l'endpoint et la méthode
$path = $_SERVER['PATH_INFO'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Route: POST /api/archer/search-or-create
if ($path === '/archer/search-or-create' && $method === 'POST') {
    $controller = new ArcherSearchController();
    $controller->findOrCreateByLicense();
    exit;
}

// Route inconnue
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
