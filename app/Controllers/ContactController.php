<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Services/ApiService.php';

class ContactController {
    
    public function index() {
        $title = 'Contact - Portail Arc Training';
        $pageTitle = $title;
        $skipSessionManager = true;
        
        include 'app/Views/layouts/header-public.php';
        include 'app/Views/contact/index.php';
        include 'app/Views/layouts/footer.php';
    }
    
    public function send() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /contact');
            exit;
        }
        
        // Récupérer les données du formulaire
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validation côté serveur
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Le nom est requis.';
        }
        
        if (empty($email)) {
            $errors[] = 'L\'adresse email est requise.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email n\'est pas valide.';
        }
        
        if (empty($subject)) {
            $errors[] = 'Le sujet est requis.';
        }
        
        if (empty($message)) {
            $errors[] = 'Le message est requis.';
        }
        
        if (!empty($errors)) {
            $_SESSION['contact_errors'] = $errors;
            $_SESSION['contact_data'] = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message
            ];
            header('Location: /contact');
            exit;
        }
        
        try {
            $apiService = new ApiService();
            $response = $apiService->submitContactForm([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
            ]);

            $payload = is_array($response['data'] ?? null) ? $response['data'] : [];
            $httpCode = (int)($response['status_code'] ?? 0);
            $apiSuccess = $httpCode >= 200 && $httpCode < 300 && !empty($payload['success']);

            if ($apiSuccess) {
                $_SESSION['contact_success'] = $payload['message']
                    ?? 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.';
            } else {
                $apiDetail = $payload['message'] ?? $payload['error'] ?? null;
                if ($httpCode === 0 || $response['data'] === null) {
                    $curlErr = $response['curl_error'] ?? '';
                    $triedUrl = $response['url'] ?? (ApiService::resolveContactSendUrl() ?: (ApiService::resolveApiBaseUrl() . '/contact/send'));
                    if ($curlErr !== '') {
                        $errorMessage = 'Impossible de joindre l\'API (' . $triedUrl . ') : ' . $curlErr;
                    } elseif ($httpCode === 500) {
                        $errorMessage = 'L\'envoi email a échoué (HTTP 500).';
                        $raw = trim((string)($response['raw_response'] ?? ''));
                        if ($raw !== '') {
                            error_log('Contact: réponse brute API — ' . substr($raw, 0, 300));
                            $decoded = json_decode($raw, true);
                            if (is_array($decoded) && !empty($decoded['message'])) {
                                $errorMessage = (string)$decoded['message'];
                            }
                        } else {
                            $errorMessage .= ' Redéployez public/contact-send.php sur l\'API (réponse vide).';
                        }
                    } else {
                        $errorMessage = 'Impossible de joindre l\'API (' . $triedUrl . '). Vérifiez API_BASE_URL dans le .env du portail.';
                    }
                } elseif ($httpCode === 404) {
                    $errorMessage = 'Route contact absente sur l\'API. Déployez routes/contact.php sur api.arctraining.fr.';
                } elseif ($httpCode === 500) {
                    $errorMessage = $apiDetail
                        ?? 'L\'API a reçu le message mais l\'envoi email a échoué. Vérifiez CONTACT_EMAIL et SMTP dans le .env du backend.';
                } else {
                    $errorMessage = $apiDetail ?? 'Erreur lors de l\'envoi de l\'email.';
                }
                error_log('Contact: échec envoi (HTTP ' . $httpCode . ', URL ' . ($response['url'] ?? 'n/a') . ') — ' . $errorMessage);
                $_SESSION['contact_error'] = $errorMessage;
                $_SESSION['contact_data'] = [
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                ];
            }
        } catch (Exception $e) {
            error_log('Contact: exception API — ' . $e->getMessage());
            $_SESSION['contact_error'] = 'Impossible de contacter le serveur. Veuillez réessayer plus tard.';
            $_SESSION['contact_data'] = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
            ];
        }

        header('Location: /contact');
        exit;
    }
}

