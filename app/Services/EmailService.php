<?php
require_once __DIR__ . '/../Config/EmailConfig.php';

class EmailService {
    
    /**
     * Formulaire de contact : envoi via l'API backend (comme send-confirmation-email des concours).
     *
     * @return array{success: bool, message: string}
     */
    public static function sendContactEmail($name, $email, $subject, $message) {
        try {
            require_once __DIR__ . '/ApiService.php';
            $api = new ApiService();
            $response = $api->submitContactForm(compact('name', 'email', 'subject', 'message'));

            $payload = is_array($response['data'] ?? null) ? $response['data'] : [];
            $httpCode = (int)($response['status_code'] ?? 0);

            if ($httpCode >= 200 && $httpCode < 300 && !empty($payload['success'])) {
                return [
                    'success' => true,
                    'message' => $payload['message']
                        ?? 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.',
                ];
            }

            $errorMessage = $payload['message'] ?? $payload['error'] ?? null;
            if ($httpCode === 0 || $response['data'] === null) {
                $curlErr = $response['curl_error'] ?? '';
                $url = $response['url'] ?? ApiService::resolveApiBaseUrl() . '/contact/send';
                $errorMessage = 'Impossible de joindre l\'API (' . $url . ')';
                if ($curlErr !== '') {
                    $errorMessage .= ' : ' . $curlErr;
                }
            } elseif ($errorMessage === null) {
                $errorMessage = 'Erreur lors de l\'envoi de l\'email (HTTP ' . $httpCode . ').';
            }

            error_log('Contact API: ' . $errorMessage);
            return ['success' => false, 'message' => $errorMessage];
        } catch (Exception $e) {
            error_log('Contact API exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Impossible de contacter le serveur. Veuillez réessayer plus tard.',
            ];
        }
    }
    
    /**
     * Génère un Message-ID unique conforme aux normes RFC 5322
     * 
     * @param string $domain Domaine à utiliser pour le Message-ID
     * @return string Message-ID au format <unique-id@domain>
     */
    private static function generateMessageId($domain = null) {
        if ($domain === null) {
            // Extraire le domaine de l'adresse email ou utiliser le serveur
            $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
            $domain = $serverName;
        }
        
        // Générer un identifiant unique avec plus d'entropie
        $uniqueId = uniqid('', true) . '.' . bin2hex(random_bytes(8));
        $timestamp = microtime(true);
        
        return '<' . sprintf('%.0f', $timestamp * 1000000) . '.' . $uniqueId . '@' . $domain . '>';
    }
    
    /**
     * Génère une date au format RFC 2822
     * 
     * @return string Date formatée selon RFC 2822
     */
    private static function generateDate() {
        return date('r'); // Format RFC 2822 (ex: "Mon, 15 Jan 2024 14:30:00 +0100")
    }
    
    /**
     * Extrait le domaine à utiliser pour le Message-ID
     * 
     * @param string $fromEmail Adresse email de l'expéditeur
     * @return string Domaine à utiliser
     */
    private static function extractDomain($fromEmail) {
        $domain = $_SERVER['SERVER_NAME'] ?? 'localhost';
        if (strpos($fromEmail, '@') !== false) {
            $domain = substr(strrchr($fromEmail, '@'), 1);
        }
        return $domain;
    }
    
    private static function hasSmtpCredentials(): bool {
        return EmailConfig::getSmtpHost() !== ''
            && EmailConfig::getSmtpHost() !== 'localhost'
            && EmailConfig::getSmtpUsername() !== ''
            && EmailConfig::getSmtpPassword() !== '';
    }

    private static function buildContactFailureMessage(string $detail = ''): string {
        $base = 'Impossible d\'envoyer votre message pour le moment.';
        $debug = (($_ENV['APP_DEBUG'] ?? '0') === '1' || ($_ENV['APP_DEBUG'] ?? '') === 'true'
            || (getenv('APP_DEBUG') ?: '') === 'true');

        if ($detail !== '' && $debug) {
            return $base . ' Détail : ' . $detail;
        }
        if (!self::hasSmtpCredentials()) {
            return $base . ' Le portail doit disposer des mêmes réglages SMTP que l\'API (EMAIL_METHOD=smtp, SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD, CONTACT_EMAIL dans le .env du portail ou .env du backend sur le même serveur).';
        }
        if ($detail !== '') {
            error_log('Contact (message utilisateur masqué): ' . $detail);
        }
        return $base . ' Vérifiez le mot de passe SMTP dans le .env (guillemets si le mot de passe contient ! ou #).';
    }

    /**
     * Envoi via PHPMailer (vendor du backend si disponible).
     */
    private static function sendViaBackendMailer(
        $to,
        $senderName,
        $senderEmail,
        $subject,
        $htmlMessage,
        &$lastError = ''
    ): bool {
        if (!self::hasSmtpCredentials()) {
            $lastError = 'SMTP non configuré (SMTP_HOST / SMTP_USERNAME).';
            return false;
        }

        $autoload = self::resolvePhpmailerAutoload();
        if ($autoload === null) {
            $lastError = 'PHPMailer introuvable sur le serveur du portail.';
            return false;
        }

        require_once $autoload;

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $lastError = 'PHPMailer introuvable après chargement de l\'autoload.';
            return false;
        }

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = EmailConfig::getSmtpHost();
            $mail->SMTPAuth = true;
            $mail->Username = EmailConfig::getSmtpUsername();
            $mail->Password = EmailConfig::getSmtpPassword();
            $mail->Port = EmailConfig::getSmtpPort();
            $mail->CharSet = 'UTF-8';
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            $mail->Timeout = 30;

            $encryption = EmailConfig::getSmtpEncryption();
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === '') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $fromEmail = EmailConfig::getFromEmail();
            $fromName = EmailConfig::getFromName();
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                $fromEmail = 'noreply@arctraining.fr';
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($senderEmail, $senderName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlMessage;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlMessage));

            $mail->send();
            return true;
        } catch (PHPMailer\PHPMailer\Exception $e) {
            $lastError = $e->getMessage();
            if (isset($mail) && !empty($mail->ErrorInfo)) {
                $lastError .= ' — ' . $mail->ErrorInfo;
            }
            error_log('Contact PHPMailer: ' . $lastError);

            if (EmailConfig::getSmtpEncryption() === 'tls' && (int)EmailConfig::getSmtpPort() === 587) {
                error_log('Contact PHPMailer: repli port 465 / SSL');
                return self::sendViaPhpmailerSslFallback(
                    $to,
                    $senderName,
                    $senderEmail,
                    $subject,
                    $htmlMessage,
                    $fromEmail ?? 'noreply@arctraining.fr',
                    $fromName ?? 'Portail Arc Training',
                    $lastError
                );
            }
            return false;
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            error_log('Contact PHPMailer: ' . $lastError);
            return false;
        }
    }

    private static function sendViaPhpmailerSslFallback(
        $to,
        $senderName,
        $senderEmail,
        $subject,
        $htmlMessage,
        $fromEmail,
        $fromName,
        &$lastError
    ): bool {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = EmailConfig::getSmtpHost();
            $mail->SMTPAuth = true;
            $mail->Username = EmailConfig::getSmtpUsername();
            $mail->Password = EmailConfig::getSmtpPassword();
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            $mail->Timeout = 30;
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($senderEmail, $senderName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlMessage;
            $mail->send();
            return true;
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            return false;
        }
    }

    private static function resolvePhpmailerAutoload(): ?string {
        $candidates = [];
        $backendPath = EmailConfig::resolveBackendPath();
        if ($backendPath !== null) {
            $candidates[] = $backendPath . '/vendor/autoload.php';
        }
        $candidates[] = dirname(__DIR__, 2) . '/../BackendPHP/vendor/autoload.php';
        $candidates[] = '/home/www/BackendPHP/vendor/autoload.php';

        foreach ($candidates as $autoload) {
            if (is_readable($autoload)) {
                return $autoload;
            }
        }
        return null;
    }

    /**
     * SMTP socket avec repli port 465 / SSL si le port 587 échoue.
     */
    private static function sendViaSmtpWithFallback(
        $to,
        $fromEmail,
        $fromName,
        $replyName,
        $replyEmail,
        $subject,
        $htmlMessage,
        &$lastError = ''
    ): bool {
        if (self::sendViaSmtp($to, $fromEmail, $fromName, $replyName, $replyEmail, $subject, $htmlMessage)) {
            return true;
        }

        $encryption = EmailConfig::getSmtpEncryption();
        $port = (int)EmailConfig::getSmtpPort();
        if ($encryption === 'tls' && $port === 587) {
            error_log('Contact SMTP socket: échec 587/TLS, tentative 465/SSL');
            if (self::sendViaSmtp($to, $fromEmail, $fromName, $replyName, $replyEmail, $subject, $htmlMessage, 465, 'ssl')) {
                return true;
            }
        }

        $lastError = 'Connexion SMTP refusée ou identifiants incorrects.';
        return false;
    }

    /**
     * Envoie un email via la fonction mail() de PHP
     */
    private static function sendViaMail($to, $fromEmail, $fromName, $replyName, $replyEmail, $subject, $htmlMessage) {
        $domain = self::extractDomain($fromEmail);
        
        $headers = [
            'Date' => self::generateDate(),
            'From' => $fromName . ' <' . $fromEmail . '>',
            'To' => '<' . $to . '>',
            'Reply-To' => $replyName . ' <' . $replyEmail . '>',
            'Message-ID' => self::generateMessageId($domain),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Mailer' => 'PHP/' . phpversion(),
            'X-Priority' => '3'
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
    private static function sendViaSmtp(
        $to,
        $fromEmail,
        $fromName,
        $replyName,
        $replyEmail,
        $subject,
        $htmlMessage,
        $portOverride = null,
        $encryptionOverride = null
    ) {
        $smtpHost = EmailConfig::getSmtpHost();
        $smtpPort = $portOverride ?? EmailConfig::getSmtpPort();
        $smtpUsername = EmailConfig::getSmtpUsername();
        $smtpPassword = EmailConfig::getSmtpPassword();
        $smtpEncryption = $encryptionOverride ?? EmailConfig::getSmtpEncryption();
        
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
        $domain = self::extractDomain($fromEmail);
        $date = self::generateDate();
        $messageId = self::generateMessageId($domain);
        
        $emailMessage = "Date: $date\r\n";
        $emailMessage .= "From: $fromName <$fromEmail>\r\n";
        $emailMessage .= "To: <$to>\r\n";
        $emailMessage .= "Reply-To: $replyName <$replyEmail>\r\n";
        $emailMessage .= "Subject: $subject\r\n";
        $emailMessage .= "Message-ID: $messageId\r\n";
        $emailMessage .= "MIME-Version: 1.0\r\n";
        $emailMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailMessage .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $emailMessage .= "X-Priority: 3\r\n";
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
            Ce message a été envoyé depuis le formulaire de contact du Portail Arc Training.<br>
            Date : ' . date('d/m/Y à H:i') . '
        </p>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Envoie un email de demande de suppression de compte à l'utilisateur
     * 
     * @param string $userEmail Email de l'utilisateur
     * @param string $token Token de validation
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendAccountDeletionRequestToUser($userEmail, $token) {
        try {
            $fromEmail = EmailConfig::getFromEmail();
            $fromName = EmailConfig::getFromName();
            
            $subject = "Confirmation de votre demande de suppression de compte";
            
            // Construire l'URL de validation
            $validationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                . "://" . $_SERVER['HTTP_HOST'] 
                . "/auth/validate-deletion/" . $token;
            
            $htmlMessage = self::buildAccountDeletionUserEmail($validationUrl);
            
            // Utiliser SMTP si configuré, sinon utiliser mail()
            if (EmailConfig::useSmtp()) {
                $success = self::sendViaSmtp($userEmail, $fromEmail, $fromName, $fromName, $fromEmail, $subject, $htmlMessage);
            } else {
                $success = self::sendViaMail($userEmail, $fromEmail, $fromName, $fromName, $fromEmail, $subject, $htmlMessage);
            }
            
            return [
                'success' => $success,
                'message' => $success ? 'Email envoyé avec succès' : 'Erreur lors de l\'envoi de l\'email'
            ];
            
        } catch (Exception $e) {
            error_log('Erreur EmailService (suppression user): ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envoie un email de notification à l'administrateur
     * 
     * @param string $userEmail Email de l'utilisateur qui demande la suppression
     * @param string $reason Raison de la suppression
     * @param string $token Token de validation
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendAccountDeletionNotificationToAdmin($userEmail, $reason, $token) {
        try {
            $adminEmail = EmailConfig::getContactEmail();
            $fromEmail = EmailConfig::getFromEmail();
            $fromName = EmailConfig::getFromName();
            
            $subject = "Nouvelle demande de suppression de compte";
            
            // Construire l'URL de validation
            $validationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                . "://" . $_SERVER['HTTP_HOST'] 
                . "/auth/validate-deletion/" . $token;
            
            $htmlMessage = self::buildAccountDeletionAdminEmail($userEmail, $reason, $validationUrl);
            
            // Utiliser SMTP si configuré, sinon utiliser mail()
            if (EmailConfig::useSmtp()) {
                $success = self::sendViaSmtp($adminEmail, $fromEmail, $fromName, $fromName, $fromEmail, $subject, $htmlMessage);
            } else {
                $success = self::sendViaMail($adminEmail, $fromEmail, $fromName, $fromName, $fromEmail, $subject, $htmlMessage);
            }
            
            return [
                'success' => $success,
                'message' => $success ? 'Email envoyé avec succès' : 'Erreur lors de l\'envoi de l\'email'
            ];
            
        } catch (Exception $e) {
            error_log('Erreur EmailService (suppression admin): ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Construit le message HTML pour l'utilisateur
     */
    private static function buildAccountDeletionUserEmail($validationUrl) {
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de suppression de compte</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #dc3545; color: white; padding: 20px; border-radius: 5px 5px 0 0;">
        <h2 style="margin: 0;">⚠️ Demande de suppression de compte</h2>
    </div>
    <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px;">
        <p>Bonjour,</p>
        
        <p>Nous avons bien reçu votre demande de suppression de compte sur le Portail Arc Training.</p>
        
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>⚠️ Attention :</strong> Cette action est irréversible et entraînera la suppression définitive de toutes vos données personnelles, entraînements et statistiques.</p>
        </div>
        
        <p><strong>Pour confirmer la suppression de votre compte, cliquez sur le lien ci-dessous :</strong></p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . htmlspecialchars($validationUrl) . '" 
               style="background-color: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                Confirmer la suppression
            </a>
        </div>
        
        <p style="font-size: 14px; color: #666;">
            Si vous n\'avez pas demandé cette suppression, ignorez simplement cet email. Votre compte restera actif.
        </p>
        
        <p style="font-size: 14px; color: #666;">
            Ce lien est valide pour 48 heures.
        </p>
        
        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #666;">
            Conformément au RGPD, votre demande sera traitée sous 30 jours maximum.<br>
            Pour toute question, contactez-nous via le formulaire de contact du portail.<br>
            Date : ' . date('d/m/Y à H:i') . '
        </p>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Construit le message HTML pour l'administrateur
     */
    private static function buildAccountDeletionAdminEmail($userEmail, $reason, $validationUrl) {
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande de suppression de compte</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #0d6efd; color: white; padding: 20px; border-radius: 5px 5px 0 0;">
        <h2 style="margin: 0;">📋 Nouvelle demande de suppression</h2>
    </div>
    <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px;">
        <p>Bonjour Administrateur,</p>
        
        <p>Une nouvelle demande de suppression de compte a été enregistrée.</p>
        
        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #dee2e6;">
            <p><strong>Email de l\'utilisateur :</strong> ' . htmlspecialchars($userEmail) . '</p>
            <p><strong>Date de la demande :</strong> ' . date('d/m/Y à H:i') . '</p>
            ' . (!empty($reason) ? '<p><strong>Raison :</strong></p><p style="background-color: #f8f9fa; padding: 10px; border-radius: 3px; white-space: pre-wrap;">' . nl2br(htmlspecialchars($reason)) . '</p>' : '<p><em>Aucune raison fournie</em></p>') . '
        </div>
        
        <p><strong>Action requise :</strong></p>
        <p>L\'utilisateur doit confirmer sa demande via le lien qui lui a été envoyé. Une fois confirmée, vous pourrez procéder à la suppression définitive du compte depuis le panneau d\'administration.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . htmlspecialchars($validationUrl) . '" 
               style="background-color: #0d6efd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                Voir la demande
            </a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #666;">
            Rappel RGPD : Cette demande doit être traitée sous 30 jours maximum.<br>
            Ce message a été généré automatiquement par le Portail Arc Training.
        </p>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Envoie un email générique (diffusion, notifications internes, etc.)
     *
     * @param string $to Destinataire
     * @param string $subject Sujet
     * @param string $htmlMessage Contenu HTML
     * @param string|null $replyToEmail Email de réponse (fallback: from)
     * @param string|null $replyToName Nom de réponse (fallback: from)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function sendGenericEmail($to, $subject, $htmlMessage, $replyToEmail = null, $replyToName = null) {
        try {
            $to = trim((string)$to);
            $subject = trim((string)$subject);
            $htmlMessage = trim((string)$htmlMessage);

            if ($to === '' || $subject === '' || $htmlMessage === '') {
                return [
                    'success' => false,
                    'message' => 'Paramètres email incomplets.'
                ];
            }

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Adresse email destinataire invalide.'
                ];
            }

            $fromEmail = EmailConfig::getFromEmail();
            $fromName = EmailConfig::getFromName();
            $replyEmail = $replyToEmail ?: $fromEmail;
            $replyName = $replyToName ?: $fromName;

            if (EmailConfig::useSmtp()) {
                $success = self::sendViaSmtp($to, $fromEmail, $fromName, $replyName, $replyEmail, $subject, $htmlMessage);
            } else {
                $success = self::sendViaMail($to, $fromEmail, $fromName, $replyName, $replyEmail, $subject, $htmlMessage);
            }

            return [
                'success' => (bool)$success,
                'message' => $success ? 'Email envoyé.' : 'Échec envoi email.'
            ];
        } catch (Exception $e) {
            error_log('Erreur EmailService::sendGenericEmail: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur envoi email: ' . $e->getMessage()
            ];
        }
    }
}
