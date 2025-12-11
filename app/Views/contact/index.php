<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-envelope me-2"></i>Formulaire de contact
                    </h1>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['contact_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['contact_success']); unset($_SESSION['contact_success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['contact_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['contact_error']); unset($_SESSION['contact_error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['contact_errors']) && !empty($_SESSION['contact_errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Erreurs :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($_SESSION['contact_errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['contact_errors']); ?>
                    <?php endif; ?>
                    
                    <p class="mb-4">
                        Utilisez ce formulaire pour nous contacter. Nous vous répondrons dans les plus brefs délais.
                    </p>
                    
                    <form method="POST" action="/contact/send" id="contactForm" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-user me-2"></i>Nom <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="name" 
                                name="name" 
                                value="<?php echo htmlspecialchars($_SESSION['contact_data']['name'] ?? ''); ?>"
                                required
                                autocomplete="name"
                            >
                            <div class="invalid-feedback">
                                Veuillez saisir votre nom.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Adresse email <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($_SESSION['contact_data']['email'] ?? ''); ?>"
                                required
                                autocomplete="email"
                            >
                            <div class="invalid-feedback">
                                Veuillez saisir une adresse email valide.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">
                                <i class="fas fa-tag me-2"></i>Sujet <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="subject" 
                                name="subject" 
                                value="<?php echo htmlspecialchars($_SESSION['contact_data']['subject'] ?? ''); ?>"
                                required
                            >
                            <div class="invalid-feedback">
                                Veuillez saisir un sujet.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">
                                <i class="fas fa-comment me-2"></i>Message <span class="text-danger">*</span>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="message" 
                                name="message" 
                                rows="6" 
                                required
                            ><?php echo htmlspecialchars($_SESSION['contact_data']['message'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">
                                Veuillez saisir votre message.
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                            </button>
                            <a href="/privacy" class="btn btn-outline-secondary">
                                <i class="fas fa-shield-alt me-2"></i>Données personnelles
                            </a>
                        </div>
                    </form>
                    
                    <?php unset($_SESSION['contact_data']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation Bootstrap
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var form = document.getElementById('contactForm');
        form.addEventListener('submit', function(event) {
            if (form.checkValidity() === false) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }, false);
})();
</script>

