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
        // TODO: Envoyer les données à l'API BackendPHP
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
        // TODO: Envoyer les modifications à l'API BackendPHP
    }

    // Suppression d'un concours
    public function delete($id)
    {
        // TODO: Appeler l'API BackendPHP pour supprimer
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
