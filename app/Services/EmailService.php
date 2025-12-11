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
            
            // Préparer les headers
            $headers = [
                'From' => $fromName . ' <' . $fromEmail . '>',
                'Reply-To' => $name . ' <' . $email . '>',
                'X-Mailer' => 'PHP/' . phpversion(),
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/html; charset=UTF-8'
            ];
            
            // Construire le message HTML
            $htmlMessage = self::buildHtmlEmail($name, $email, $subject, $message);
            
            // Convertir les headers en chaîne
            $headersString = '';
            foreach ($headers as $key => $value) {
                $headersString .= $key . ': ' . $value . "\r\n";
            }
            
            // Envoyer l'email
            $success = mail($to, $subject, $htmlMessage, $headersString);
            
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
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi de votre message.'
            ];
        }
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

