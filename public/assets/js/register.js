document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const registerForm = document.getElementById('registerForm');
    const confirmPasswordField = document.getElementById('confirm_password');

    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Form validation
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = passwordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            // Check if passwords match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                confirmPasswordField.focus();
                return false;
            }
            
            // Check password strength
            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères');
                passwordField.focus();
                return false;
            }
            
            // Check if username is valid
            const username = document.getElementById('username').value;
            if (username.length < 3) {
                e.preventDefault();
                alert('Le nom d\'utilisateur doit contenir au moins 3 caractères');
                document.getElementById('username').focus();
                return false;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
            submitButton.disabled = true;
            
            // Re-enable button after 5 seconds (fallback)
            setTimeout(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 5000);
        });
    }

    // Real-time password confirmation
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            const password = passwordField.value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }

    // Auto-generate username from first and last name
    const firstNameField = document.getElementById('first_name');
    const lastNameField = document.getElementById('last_name');
    const usernameField = document.getElementById('username');

    if (firstNameField && lastNameField && usernameField) {
        function generateUsername() {
            const firstName = firstNameField.value.toLowerCase().trim();
            const lastName = lastNameField.value.toLowerCase().trim();
            
            if (firstName && lastName) {
                // Remove accents and special characters
                const cleanFirstName = firstName.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                const cleanLastName = lastName.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                
                // Generate username (first letter of first name + last name)
                const username = cleanFirstName.charAt(0) + cleanLastName;
                usernameField.value = username;
            }
        }

        firstNameField.addEventListener('blur', generateUsername);
        lastNameField.addEventListener('blur', generateUsername);
    }
});
