<?php
// Configuration de l'envoi d'emails
// Modifiez ces valeurs selon vos besoins

return [
    // Adresse email de destination pour le formulaire de contact
    'contact_email' => 'andremoriana@gmail.com',
    
    // Adresse email expéditrice
    'from_email' => 'noreply@archers-gemenos.com',
    
    // Nom de l'expéditeur
    'from_name' => 'Portail Arc Training',
    
    // Configuration SMTP
    // Si use_smtp est true, utilise SMTP au lieu de la fonction mail() de PHP
    'use_smtp' => true,
    
    // Serveur SMTP (ex: smtp.gmail.com, smtp.free.fr, smtp.orange.fr)
    'smtp_host' => 'smtp.gmail.com',
    
    // Port SMTP (587 pour TLS, 465 pour SSL, 25 pour non sécurisé)
    'smtp_port' => 587,
    
    // Nom d'utilisateur SMTP (généralement votre adresse email)
    // Pour Gmail: votre adresse Gmail complète (ex: votre-email@gmail.com)
    'smtp_username' => '',
    
    // Mot de passe SMTP
    // Pour Gmail: vous DEVEZ utiliser un "Mot de passe d'application"
    // Créez-en un ici: https://myaccount.google.com/apppasswords
    // (Activez d'abord la validation en 2 étapes si nécessaire)
    'smtp_password' => '',
    
    // Type de chiffrement ('tls', 'ssl' ou '' pour aucun)
    'smtp_encryption' => 'tls'
];

