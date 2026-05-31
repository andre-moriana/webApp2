<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Services/EmailService.php';

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
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
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
                'message' => $message,
            ];
            header('Location: /contact');
            exit;
        }
        
        $result = EmailService::sendContactEmail($name, $email, $subject, $message);
        
        if ($result['success']) {
            $_SESSION['contact_success'] = $result['message'];
        } else {
            $_SESSION['contact_error'] = $result['message'];
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
