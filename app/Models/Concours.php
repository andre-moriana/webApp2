<?php
namespace App\Models;

class Concours
{
    public $id;
    public $nom;
    public $description;
    public $date_debut;
    public $date_fin;
    public $lieu;
    public $type;
    public $statut;
    public $participants = [];
    public $resultats = [];
    public $departs = [];

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
