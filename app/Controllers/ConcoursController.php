<?php
namespace App\Controllers;

use App\Models\Concours;

class ConcoursController
{
    // Liste des concours (récupérés via l'API BackendPHP)
    public function index()
    {
        $concours = $this->fetchConcoursFromApi();
        require __DIR__ . '/../Views/concours/index.php';
    }

    // Affichage du formulaire de création
    public function create()
    {
        require __DIR__ . '/../Views/concours/create.php';
    }

    // Enregistrement d'un nouveau concours
    public function store()
    {
        // Récupérer les données du formulaire
        $data = $_POST;
        $url = 'https://backendphp.example.com/api/concours';
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        // Rediriger vers la liste
        header('Location: /concours');
        exit();
    }

    // Affichage du formulaire d'édition
    public function edit($id)
    {
        $concours = $this->fetchConcoursFromApi($id);
        require __DIR__ . '/../Views/concours/edit.php';
    }

    // Mise à jour d'un concours
    public function update($id)
    {
        $data = $_POST;
        $url = 'https://backendphp.example.com/api/concours/' . $id;
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'PUT',
                'content' => json_encode($data),
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        header('Location: /concours');
        exit();
    }

    // Suppression d'un concours
    public function delete($id)
    {
        $url = 'https://backendphp.example.com/api/concours/' . $id;
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'DELETE',
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        header('Location: /concours');
        exit();
    }

    // Méthode utilitaire pour récupérer les concours via l'API
    private function fetchConcoursFromApi($id = null)
    {
        // Exemple d'appel API (à adapter)
        $url = 'https://backendphp.example.com/api/concours';
        if ($id) $url .= '/' . $id;
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        if ($id) return new Concours($data);
        return array_map(fn($c) => new Concours($c), $data);
    }
}
