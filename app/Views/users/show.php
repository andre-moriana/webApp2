<?php
$title = "Détails de l'utilisateur - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Détails de l'utilisateur</h1>
                <div>
                    <a href="/users" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                    </a>
                    <a href="/users/<?php echo $user['id']; ?>/edit" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                </div>
            </div>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Informations personnelles -->
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Informations personnelles
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">ID :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['id']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nom d'utilisateur :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['username'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Prénom :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['firstName'] ?? $user['first_name'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nom :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['name'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Téléphone :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Rôle :</label>
                                        <span class="badge bg-<?php echo ($user['role'] === 'Coach') ? 'primary' : 'secondary'; ?> fs-6">
                                            <?php echo ucfirst($user['role'] ?? 'Archer'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Administrateur :</label>
                                        <span class="badge bg-<?php echo ($user['is_admin'] ?? $user['isAdmin'] ?? false) ? 'danger' : 'secondary'; ?> fs-6">
                                            <?php echo ($user['is_admin'] ?? $user['isAdmin'] ?? false) ? 'Oui' : 'Non'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Validation :</label>
                                        <?php 
                                        $status = $user['status'] ?? 'active';
                                        $statusLabels = [
                                            'pending' => 'En attente de validation',
                                            'active' => 'Validé',
                                            'rejected' => 'Rejeté'
                                        ];
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'active' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $label = $statusLabels[$status] ?? 'Inconnu';
                                        $color = $statusColors[$status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?> fs-6">
                                            <?php echo $label; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Bannissement :</label>
                                        <span class="badge bg-<?php echo ($user['is_banned'] ?? $user['isBanned'] ?? false) ? 'danger' : 'success'; ?> fs-6">
                                            <?php echo ($user['is_banned'] ?? $user['isBanned'] ?? false) ? 'Banni' : 'Actif'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations sportives -->
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bullseye me-2"></i>Informations sportives
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Numéro de licence :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['licenceNumber'] ?? $user['licence_number'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Catégorie d'âge :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['ageCategory'] ?? $user['age_category'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Type d'arc :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['bowType'] ?? $user['bow_type'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Date de naissance :</label>
                                        <p class="form-control-plaintext">
                                            <?php 
                                            if (!empty($user['birthDate'] ?? $user['birth_date'] ?? null)) {
                                                $birthDate = new DateTime($user['birthDate'] ?? $user['birth_date']);
                                                echo $birthDate->format('d/m/Y');
                                            } else {
                                                echo 'Non renseigné';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Genre :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['gender'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Année d'arrivée :</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['arrivalYear'] ?? $user['arrival_year'] ?? 'Non renseigné'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Documents -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Documents
                            </h5>
                            <button class="btn btn-sm btn-primary" onclick="uploadDocument()">
                                <i class="fas fa-upload me-1"></i>Ajouter un document
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="documents-list">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Chargement des documents...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Informations complémentaires -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Informations complémentaires
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Date de création :</label>
                                        <p class="form-control-plaintext">
                                            <?php 
                                            if (!empty($user['createdAt'] ?? $user['created_at'])) {
                                                $createdAt = new DateTime($user['createdAt'] ?? $user['created_at']);
                                                echo $createdAt->format('d/m/Y à H:i');
                                            } else {
                                                echo 'Non renseigné';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Dernière connexion :</label>
                                        <p class="form-control-plaintext">
                                            <?php 
                                            $lastLogin = $user['lastLogin'] ?? $user['last_login'] ?? null;
                                            if (!empty($lastLogin)) {
                                                $lastLoginDate = new DateTime($lastLogin);
                                                echo $lastLoginDate->format('d/m/Y à H:i');
                                            } else {
                                                echo 'Jamais';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Dernière mise à jour :</label>
                                        <p class="form-control-plaintext">
                                            <?php 
                                            if (!empty($user['updatedAt'] ?? $user['updated_at'])) {
                                                $updatedAt = new DateTime($user['updatedAt'] ?? $user['updated_at']);
                                                echo $updatedAt->format('d/m/Y à H:i');
                                            } else {
                                                echo 'Non renseigné';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour upload de document -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="documentName" class="form-label">Nom du document</label>
                        <input type="text" class="form-control" id="documentName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="documentFile" class="form-label">Fichier</label>
                        <input type="file" class="form-control" id="documentFile" name="document" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" required>
                        <div class="form-text">Types autorisés : PDF, DOC, DOCX, TXT, JPG, JPEG, PNG (max 10MB)</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="submitUpload()">Uploader</button>
            </div>
        </div>
    </div>
</div>

<script>
// Charger les documents au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadDocuments();
});

function loadDocuments() {
    fetch(`/api/documents/user/<?php echo $user['id']; ?>`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('documents-list');
            if (data.success && data.documents.length > 0) {
                container.innerHTML = data.documents.map(doc => `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <h6 class="mb-1">${doc.name}</h6>
                            <small class="text-muted">${doc.filename} - ${formatFileSize(doc.file_size)}</small>
                        </div>
                        <div>
                            <a href="/api/documents/${doc.id}/download" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${doc.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="text-center text-muted"><i class="fas fa-file-alt me-2"></i>Aucun document</div>';
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des documents:', error);
            document.getElementById('documents-list').innerHTML = '<div class="text-center text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur lors du chargement</div>';
        });
}

function uploadDocument() {
    const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
    modal.show();
}

function submitUpload() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);
    
    fetch(`/api/documents/<?php echo $user['id']; ?>/upload`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
            form.reset();
            loadDocuments();
            alert('Document uploadé avec succès');
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'upload');
    });
}

function deleteDocument(documentId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce document ?')) {
        fetch(`/api/documents/<?php echo $user['id']; ?>/delete`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({id: documentId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadDocuments();
                alert('Document supprimé avec succès');
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression');
        });
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
