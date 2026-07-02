<?php
require_once __DIR__ . '/ApiService.php';

/**
 * Façade d'envoi de mail du portail.
 *
 * Tous les envois passent désormais par le service email du BackendPHP
 * (services/EmailService.php) via l'API HTTP, afin d'uniformiser l'envoi de mail
 * entre le backend, l'application mobile et le portail web.
 */
class EmailService {

    /**
     * Formulaire de contact : envoi via l'API backend (POST /api/contact/send).
     *
     * @return array{success: bool, message: string}
     */
    public static function sendContactEmail($name, $email, $subject, $message, $recaptchaToken = '') {
        try {
            $api = new ApiService();
            $response = $api->submitContactForm([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'recaptcha_token' => $recaptchaToken,
            ]);

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
     * Envoie un email générique via le service email du backend (POST /api/email/send).
     *
     * @param ApiService|null $api Instance authentifiée (réutilise le token courant si fournie).
     * @return array{success: bool, message: string}
     */
    public static function sendGenericEmail($to, $subject, $htmlMessage, ApiService $api = null) {
        $to = trim((string)$to);
        $subject = trim((string)$subject);
        $htmlMessage = trim((string)$htmlMessage);

        if ($to === '' || $subject === '' || $htmlMessage === '') {
            return ['success' => false, 'message' => 'Paramètres email incomplets.'];
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Adresse email destinataire invalide.'];
        }

        try {
            $api = $api ?? new ApiService();
            $response = $api->sendEmail($to, $subject, $htmlMessage);
            $payload = is_array($response['data'] ?? null) ? $response['data'] : [];
            $success = (($response['success'] ?? false) === true) && (($payload['success'] ?? false) === true);

            return [
                'success' => $success,
                'message' => $payload['message'] ?? ($success ? 'Email envoyé.' : 'Échec envoi email.'),
            ];
        } catch (Exception $e) {
            error_log('EmailService::sendGenericEmail: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur envoi email: ' . $e->getMessage()];
        }
    }

    /**
     * Envoie le même email à plusieurs destinataires via le service email du backend
     * (POST /api/email/send-bulk).
     *
     * @param ApiService|null $api Instance authentifiée (réutilise le token courant si fournie).
     * @return array{sent: int, failed: int}
     */
    public static function sendGenericEmailBatch(array $recipients, $subject, $htmlMessage, ApiService $api = null) {
        $validRecipients = [];
        foreach ($recipients as $recipient) {
            $to = trim((string)$recipient);
            if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $validRecipients[strtolower($to)] = $to;
            }
        }
        $validRecipients = array_values($validRecipients);

        if (empty($validRecipients)) {
            return ['sent' => 0, 'failed' => 0];
        }

        $subject = trim((string)$subject);
        $htmlMessage = trim((string)$htmlMessage);
        if ($subject === '' || $htmlMessage === '') {
            return ['sent' => 0, 'failed' => count($validRecipients)];
        }

        try {
            $api = $api ?? new ApiService();
            $response = $api->sendBulkEmail($validRecipients, $subject, $htmlMessage);
            $payload = is_array($response['data'] ?? null) ? $response['data'] : [];

            if (isset($payload['sent']) || isset($payload['failed'])) {
                $sent = (int)($payload['sent'] ?? 0);
                $failed = (int)($payload['failed'] ?? (count($validRecipients) - $sent));
                return ['sent' => $sent, 'failed' => max(0, $failed)];
            }

            error_log('EmailService::sendGenericEmailBatch: réponse API invalide - '
                . ($payload['message'] ?? $response['message'] ?? 'inconnue'));
            return ['sent' => 0, 'failed' => count($validRecipients)];
        } catch (Exception $e) {
            error_log('EmailService::sendGenericEmailBatch: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => count($validRecipients)];
        }
    }
}
