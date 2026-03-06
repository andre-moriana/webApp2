<?php

header('Content-Type: application/json');

// génération d'un code
$confirmation_code = bin2hex(random_bytes(6));

$response = [
    "url" => "https://www.arctraining.fr/deletion-status?code=".$confirmation_code,
    "confirmation_code" => $confirmation_code
];

echo json_encode($response);

?>