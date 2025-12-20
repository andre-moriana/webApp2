<?php
// Configuration de l'envoi d'emails
// Copiez ce fichier vers email.config.php et modifiez les valeurs selon vos besoins

return [
    // Adresse email de destination pour le formulaire de contact
    'contact_email' => 'nom@domaine.com',
    
    // Adresse email expéditrice
    'from_email' => 'noreply@domaine.com',
    
    // Nom de l'expéditeur
    'from_name' => 'Portail Arc Training',
    
    // Configuration SMTP
    // Si use_smtp est true, utilise SMTP au lieu de la fonction mail() de PHP
    'use_smtp' => true,
    
    // Serveur SMTP
    // Gmail: smtp.gmail.com
    // Free: smtp.free.fr
    // Orange: smtp.orange.fr
    // Outlook/Hotmail: smtp-mail.outlook.com
    'smtp_host' => 'smtp.domaine.com',
    
    // Port SMTP
    // 587 pour TLS (recommandé)
    // 465 pour SSL
    // 25 pour non sécurisé (non recommandé)
    'smtp_port' => 587,
    
    // Nom d'utilisateur SMTP (généralement votre adresse email)
    // Pour Gmail: votre adresse Gmail complète
    'smtp_username' => 'votre-email@gmail.com',
    
    // Mot de passe SMTP
    // Pour Gmail: vous devez utiliser un "Mot de passe d'application"
    // (pas votre mot de passe Gmail normal)
    // Créez-en un dans: https://myaccount.google.com/apppasswords
    'smtp_password' => 'votre-mot-de-passe-application',
    
    // Type de chiffrement
    // 'tls' pour TLS (recommandé, port 587)
    // 'ssl' pour SSL (port 465)
    // '' pour aucun chiffrement (port 25, non recommandé)
    'smtp_encryption' => 'tls'
];

