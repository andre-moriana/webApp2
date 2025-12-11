<?php
require_once __DIR__ . '/../Config/EmailConfig.php';

class EmailService {
    
    /**
     * Envoie un email de contact
     * 
     * @param string $name Nom de l'expéditeur
     * @param string $email Email de l'expéditeur
     * @param string $subject Sujet du message
     * @param string $message Contenu du message
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendContactEmail($name, $email, $subject, $message) {
        try {
            $to = EmailConfig::getContactEmail();
            $fromEmail = EmailConfig::getFromEmail();
            $fromName = EmailConfig::getFromName();
            
            // Validation des données
            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                return [
                    'success' => false,
                    'message' => 'Tous les champs sont requis.'
                ];
            }
            
            // Validation de l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Adresse email invalide.'
                ];
            }
            
            // Construire le message HTML
            $htmlMessage = self::buildHtmlEmail($name, $email, $subject, $message);
            
            // Utiliser SMTP si configuré, sinon utiliser mail()
            if (EmailConfig::useSmtp()) {
                $success = self::sendViaSmtp($to, $fromEmail, $fromName, $name, $email, $subject, $htmlMessage);
            } else {
                $success = self::sendViaMail($to, $fromEmail, $fromName, $name, $email, $subject, $htmlMessage);
            }
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer plus tard.'
                ];
            }
            
        } catch (Exception $e) {
            error_log('Erreur EmailService: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi de votre message: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envoie un email via la fonction mail() de PHP
     */
    private static function sendViaMail($to, $fromEmail, $fromName, $replyName, $replyEmail, $subject, $htmlMessage) {
        $headers = [
            'From' => $fromName . ' <' . $fromEmail . '>',
            'Reply-To' => $replyName . ' <' . $replyEmail . '>',
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        $headersString = '';
        foreach ($headers as $key => $value) {
            $headersString .= $key . ': ' . $value . "\r\n";
        }
        
        return mail($to, $subject, $htmlMessage, $headersString);
    }
    
    /**
     * Lit une réponse SMTP complète (peut être multi-lignes)
     */
    private static function readSmtpResponse($socket) {
        $response = '';
        $lastLine = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            $lastLine = trim($line);
            // Les réponses SMTP se terminent par un espace après le code (ex: "250 OK")
            // Les lignes intermédiaires ont un tiret après le code (ex: "250-")
            if (preg_match('/^\d{3} /', $line)) {
                // Dernière ligne avec code suivi d'un espace
                break;
            }
        }
        return [
            'full' => $response,
            'last' => $lastLine,
            'code' => substr($lastLine, 0, 3)
        ];
    }
    
    /**
     * Envoie un email via SMTP
     */
    private static function sendViaSmtp($to, $fromEmail, $fromName, $replyName, $replyEmail, $subject, $htmlMessage) {
        $smtpHost = EmailConfig::getSmtpHost();
        $smtpPort = EmailConfig::getSmtpPort();
        $smtpUsername = EmailConfig::getSmtpUsername();
        $smtpPassword = EmailConfig::getSmtpPassword();
        $smtpEncryption = EmailConfig::getSmtpEncryption();
        
        // Construire l'hostname avec encryption si nécessaire
        $hostname = $smtpHost;
        if ($smtpEncryption === 'ssl') {
            $hostname = 'ssl://' . $smtpHost;
        }
        
        // Connexion au serveur SMTP
        $socket = @fsockopen($hostname, $smtpPort, $errno, $errstr, 30);
        if (!$socket) {
            error_log("Erreur SMTP: Impossible de se connecter à $hostname:$smtpPort - $errstr ($errno)");
            return false;
        }
        
        // Lire la réponse initiale
        $response = self::readSmtpResponse($socket);
        if ($response['code'] !== '220') {
            error_log("Erreur SMTP: Réponse initiale invalide (code {$response['code']}): {$response['full']}");
            fclose($socket);
            return false;
        }
        
        // Envoyer EHLO
        $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        fputs($socket, "EHLO $serverName\r\n");
        $response = self::readSmtpResponse($socket);
        
        // Si TLS est requis, démarrer TLS
        if ($smtpEncryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = self::readSmtpResponse($socket);
            if ($response['code'] !== '220') {
                error_log("Erreur SMTP: STARTTLS échoué (code {$response['code']}): {$response['full']}");
                fclose($socket);
                return false;
            }
            
            // Activer le chiffrement TLS
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log("Erreur SMTP: Impossible d'activer TLS");
                fclose($socket);
                return false;
            }
            
            // Renvoyer EHLO après TLS
            fputs($socket, "EHLO $serverName\r\n");
            $response = self::readSmtpResponse($socket);
        }
        
        // Authentification si nécessaire
        if (!empty($smtpUsername) && !empty($smtpPassword)) {
            fputs($socket, "AUTH LOGIN\r\n");
            $response = self::readSmtpResponse($socket);
            if ($response['code'] !== '334') {
                error_log("Erreur SMTP: AUTH LOGIN échoué (code {$response['code']}): {$response['full']}");
                fclose($socket);
                return false;
            }
            
            fputs($socket, base64_encode($smtpUsername) . "\r\n");
            $response = self::readSmtpResponse($socket);
            if ($response['code'] !== '334') {
                error_log("Erreur SMTP: Authentification username échouée (code {$response['code']}): {$response['full']}");
                fclose($socket);
                return false;
            }
            
            fputs($socket, base64_encode($smtpPassword) . "\r\n");
            $response = self::readSmtpResponse($socket);
            if ($response['code'] !== '235') {
                error_log("Erreur SMTP: Authentification password échouée (code {$response['code']}): {$response['full']}");
                fclose($socket);
                return false;
            }
        }
        
        // Envoyer MAIL FROM
        fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
        $response = self::readSmtpResponse($socket);
        if ($response['code'] !== '250') {
            error_log("Erreur SMTP: MAIL FROM échoué (code {$response['code']}): {$response['full']}");
            fclose($socket);
            return false;
        }
        
        // Envoyer RCPT TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = self::readSmtpResponse($socket);
        if ($response['code'] !== '250') {
            error_log("Erreur SMTP: RCPT TO échoué (code {$response['code']}): {$response['full']}");
            fclose($socket);
            return false;
        }
        
        // Envoyer DATA
        fputs($socket, "DATA\r\n");
        $response = self::readSmtpResponse($socket);
        if ($response['code'] !== '354') {
            error_log("Erreur SMTP: DATA échoué (code {$response['code']}): {$response['full']}");
            fclose($socket);
            return false;
        }
        
        // Construire le message complet avec tous les en-têtes requis
        $date = date('r'); // Format RFC 2822 pour la date
        $emailMessage = "Date: $date\r\n";
        $emailMessage .= "From: $fromName <$fromEmail>\r\n";
        $emailMessage .= "To: <$to>\r\n";
        $emailMessage .= "Reply-To: $replyName <$replyEmail>\r\n";
        $emailMessage .= "Subject: $subject\r\n";
        $emailMessage .= "MIME-Version: 1.0\r\n";
        $emailMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailMessage .= "Message-ID: <" . time() . "." . uniqid() . "@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";
        $emailMessage .= "\r\n";
        $emailMessage .= $htmlMessage;
        $emailMessage .= "\r\n.\r\n";
        
        // Envoyer le message
        fputs($socket, $emailMessage);
        $response = self::readSmtpResponse($socket);
        if ($response['code'] !== '250') {
            error_log("Erreur SMTP: Envoi du message échoué (code {$response['code']}): {$response['full']}");
            fclose($socket);
            return false;
        }
        
        // Quitter
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    }
    
    /**
     * Construit le message HTML de l'email
     */
    private static function buildHtmlEmail($name, $email, $subject, $message) {
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message de contact</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #198754; color: white; padding: 20px; border-radius: 5px 5px 0 0;">
        <h2 style="margin: 0;">Nouveau message de contact</h2>
    </div>
    <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px;">
        <p><strong>De :</strong> ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ')</p>
        <p><strong>Sujet :</strong> ' . htmlspecialchars($subject) . '</p>
        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
        <div style="background-color: white; padding: 15px; border-radius: 5px; margin-top: 15px;">
            <p style="white-space: pre-wrap;">' . nl2br(htmlspecialchars($message)) . '</p>
        </div>
        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
        <p style="font-size: 12px; color: #666;">
            Ce message a été envoyé depuis le formulaire de contact du Portail Archers de Gémenos.<br>
            Date : ' . date('d/m/Y à H:i') . '
        </p>
    </div>
</body>
</html>';
        
        return $html;
    }
}
