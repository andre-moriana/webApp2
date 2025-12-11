<?php
// Configuration de l'envoi d'emails
// Les valeurs peuvent être surchargées par le fichier .env du BackendPHP
// Modifiez ces valeurs selon vos besoins si vous n'utilisez pas le .env

return [
    // Adresse email de destination pour le formulaire de contact
    'contact_email' => 'andremoriana@gmail.com',
    
    // Adresse email expéditrice (surchargée par SMTP_FROM_EMAIL du .env)
    'from_email' => 'noreply@archers-gemenos.com',
    
    // Nom de l'expéditeur (surchargé par SMTP_FROM_NAME du .env)
    'from_name' => 'Portail Arc Training',
    
    // Configuration SMTP
    // Si use_smtp est true, utilise SMTP au lieu de la fonction mail() de PHP
    // (surchargé par EMAIL_METHOD du .env)
    'use_smtp' => true,
    
    // Serveur SMTP (surchargé par SMTP_HOST du .env)
    // Exemples: smtp.gmail.com, smtp.free.fr, smtp.orange.fr
    'smtp_host' => 'smtp.free.fr',
    
    // Port SMTP (surchargé par SMTP_PORT du .env)
    // 587 pour TLS, 465 pour SSL, 25 pour non sécurisé
    'smtp_port' => 587,
    
    // Nom d'utilisateur SMTP (surchargé par SMTP_USERNAME du .env)
    'smtp_username' => '',
    
    // Mot de passe SMTP (surchargé par SMTP_PASSWORD du .env)
    'smtp_password' => '',
    
    // Type de chiffrement (surchargé par SMTP_SECURE du .env)
    // 'tls' pour TLS, 'ssl' pour SSL, '' pour aucun
    'smtp_encryption' => 'tls'
];

