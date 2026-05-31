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
            $response = $apiService->makeRequestPublic('contact/send', 'POST', [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
            ]);

            $payload = is_array($response['data'] ?? null) ? $response['data'] : [];
            $apiSuccess = !empty($response['success']) && !empty($payload['success']);

            if ($apiSuccess) {
                $_SESSION['contact_success'] = $payload['message']
                    ?? 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.';
            } else {
                $errorMessage = $payload['message'] ?? $response['message'] ?? 'Erreur lors de l\'envoi de l\'email.';
                error_log('Contact: échec API contact/send — ' . $errorMessage);
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

