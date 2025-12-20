<?php
$title = "Paramètres utilisateur - Portail Archers de Gémenos";
include __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-cog me-2"></i>Paramètres utilisateur
                </h1>
                <a href="/dashboard" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                </a>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Photo de profil -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-circle me-2"></i>Photo de profil
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="profile-image-container">
                            <?php 
                            $profileImage = $user['profileImage'] ?? $user['profile_image'] ?? null;
                            if (!empty($profileImage)): 
                                // Construire l'URL complète vers le backend
                                $imageUrl = 'http://82.67.123.22:25000' . $profileImage;
                            ?>
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                     alt="Photo de profil" 
                                     class="profile-image" 
                                     id="currentProfileImage"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="profile-image-placeholder" id="currentProfileImage" style="display: none;">
                                    <i class="fas fa-user fa-3x"></i>
                                </div>
                            <?php else: 
                                $displayName = $user['firstName'] ?? $user['name'] ?? 'U';
                            ?>
                                <div class="profile-image-placeholder" id="currentProfileImage">
                                    <i class="fas fa-user fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted mt-2">Photo de profil actuelle</p>
                    </div>
                    
                    <form id="profileImageForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profileImage" class="form-label">Nouvelle photo de profil</label>
                            <input type="file" class="form-control" id="profileImage" name="profileImage" 
                                   accept="image/jpeg,image/png,image/gif,image/webp" required>
                            <!-- Version: 2025-10-08-19:40 -->
                            <div class="form-text">
                                Formats acceptés: JPEG, PNG, GIF, WebP. Taille maximum: 1MB
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="updateProfileImageBtn">
                            <i class="fas fa-upload me-2"></i>Mettre à jour la photo
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Changement de mot de passe -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lock me-2"></i>Changer le mot de passe
                    </h5>
                </div>
                <div class="card-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">
                                Le mot de passe doit contenir au moins 6 caractères
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning" id="changePasswordBtn">
                            <i class="fas fa-key me-2"></i>Changer le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    

    <!-- Informations utilisateur -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informations du compte
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Nom complet:</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                    // Priorité: name puis last_name, puis username en dernier recours
                                    $fullName = $user['name'] ?? '';
                                    if (empty($fullName)) {
                                        $fullName = $user['username'] ?? 'Non défini';
                                    }
                                    echo htmlspecialchars($fullName); 
                                    ?>
                                </dd>
                                
                                <dt class="col-sm-4">Prénom:</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                    // Priorité: firstName puis first_name puis firstname
                                    $firstName = $user['firstName'] ?? $user['first_name'] ?? $user['firstname'] ?? 'Non défini';
                                    echo htmlspecialchars($firstName); 
                                    ?>
                                </dd>
                                
                                <dt class="col-sm-4">Email:</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                    // Utiliser l'email de la base de données
                                    $email = $user['email'] ?? 'Non défini';
                                    echo htmlspecialchars($email); 
                                    ?>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Nom d'utilisateur:</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                    // Utiliser le username de la base de données
                                    $username = $user['username'] ?? 'Non défini';
                                    echo htmlspecialchars($username); 
                                    ?>
                                </dd>
                                
                                <dt class="col-sm-4">Rôle:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-<?php echo ($user['role'] === 'admin' || ($user['is_admin'] ?? $user['isAdmin'] ?? false)) ? 'danger' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['role'] ?? 'user'); ?>
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-4">Statut:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-<?php echo ($user['status'] ?? 'active') === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'actif'); ?>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS personnalisé -->
<style>
.profile-image-container {
    position: relative;
    display: inline-block;
}

.profile-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #dee2e6;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.profile-image-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #dee2e6;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
</style>

<!-- JavaScript pour la gestion des formulaires -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de photo de profil
    const profileImageForm = document.getElementById('profileImageForm');
    const updateProfileImageBtn = document.getElementById('updateProfileImageBtn');
    
    profileImageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        updateProfileImageBtn.disabled = true;
        updateProfileImageBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mise à jour...';
        
        try {
            const response = await fetch('/user-settings/update-profile-image', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Mettre à jour l'image affichée
                const currentImage = document.getElementById('currentProfileImage');
                if (currentImage.tagName === 'IMG') {
                    currentImage.src = result.image_url;
                } else {
                    // Remplacer le placeholder par l'image
                    currentImage.outerHTML = `<img src="${result.image_url}" alt="Photo de profil" class="profile-image" id="currentProfileImage">`;
                }
                
                showAlert('success', result.message);
                this.reset();
            } else {
                showAlert('danger', result.message);
            }
        } catch (error) {
            console.error('Erreur:', error);
            showAlert('danger', 'Erreur lors de la mise à jour de la photo');
        } finally {
            updateProfileImageBtn.disabled = false;
            updateProfileImageBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Mettre à jour la photo';
        }
    });
    
    // Gestion du formulaire de changement de mot de passe
    const changePasswordForm = document.getElementById('changePasswordForm');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    
    changePasswordForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password'),
            confirm_password: formData.get('confirm_password')
        };
        
        changePasswordBtn.disabled = true;
        changePasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changement...';
        
        try {
            const response = await fetch('/user-settings/change-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                this.reset();
            } else {
                showAlert('danger', result.message);
            }
        } catch (error) {
            console.error('Erreur:', error);
            showAlert('danger', 'Erreur lors du changement de mot de passe');
        } finally {
            changePasswordBtn.disabled = false;
            changePasswordBtn.innerHTML = '<i class="fas fa-key me-2"></i>Changer le mot de passe';
        }
    });
    
    // Fonction pour afficher les alertes
    function showAlert(type, message) {
        const alertContainer = document.querySelector('.container-fluid .row:first-child .col-12');
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertContainer.appendChild(alertDiv);
        
        // Auto-supprimer après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
