<?php
// Test de création d'utilisateur
session_start();

// Simuler une session d'administrateur
$_SESSION['logged_in'] = true;
$_SESSION['user'] = [
    'id' => 1,
    'is_admin' => true,
    'username' => 'admin'
];

// Simuler des données POST
$_POST = [
    'firstName' => 'Test',
    'name' => 'Utilisateur',
    'email' => 'test@example.com',
    'username' => 'testuser',
    'password' => 'password123',
    'phone' => '0123456789',
    'gender' => 'M',
    'role' => 'Archer',
    'is_admin' => '0',
    'is_banned' => '0',
    'licenceNumber' => '1234567890',
    'birthDate' => '1990-01-01',
    'ageCategory' => 'Senior',
    'bowType' => 'Classique',
    'arrivalYear' => '2020'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

// Inclure le contrôleur
require_once 'app/Controllers/UserController.php';

echo "Test de création d'utilisateur\n";
echo "==============================\n\n";

// Créer une instance du contrôleur
$controller = new UserController();

// Capturer la sortie
ob_start();
$controller->store();
$output = ob_get_clean();

echo "Résultat du test :\n";
echo $output;
echo "\n\n";

// Vérifier les messages de session
if (isset($_SESSION['success'])) {
    echo "SUCCÈS : " . $_SESSION['success'] . "\n";
}
if (isset($_SESSION['error'])) {
    echo "ERREUR : " . $_SESSION['error'] . "\n";
}
?>
