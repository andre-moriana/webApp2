<?php
/**
 * Ancien endpoint obsolète (générait des codes fictifs sans enregistrement).
 * Redirige vers le formulaire officiel de suppression de compte.
 *
 * Conservé uniquement pour compatibilité avec d'éventuelles URLs externes
 * (ex. fiche Google Play). Peut être supprimé si plus référencé nulle part.
 */
http_response_code(301);
header('Location: /auth/delete-account');
exit;
