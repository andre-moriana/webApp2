<?php
/**
 * Ancienne page de statut obsolète (codes non liés à la base de données).
 * Redirige vers le formulaire officiel de suppression de compte.
 */
http_response_code(301);
header('Location: /auth/delete-account');
exit;
