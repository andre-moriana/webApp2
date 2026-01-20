<!-- Page de détail d'un signalement -->
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-flag me-2"></i>
                Détails du Signalement #<?php echo htmlspecialchars($report['id']); ?>
            </h1>
            <a href="/signalements" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Retour à la liste
            </a>
        </div>
    </div>
</div>

<!-- Messages de succès/erreur -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row">
    <!-- Informations du signalement -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-info-circle me-2"></i>
                    Informations du signalement
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>ID du signalement:</strong><br>
                        #<?php echo htmlspecialchars($report['id']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Date de création:</strong><br>
                        <?php 
                        if (!empty($report['created_at'])) {
                            $date = new DateTime($report['created_at']);
                            echo $date->format('d/m/Y à H:i');
                        }
                        ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Statut actuel:</strong><br>
                        <?php
                        $statusClass = '';
                        $statusLabel = '';
                        switch ($report['status']) {
                            case 'pending':
                                $statusClass = 'badge-danger';
                                $statusLabel = 'En attente';
                                break;
                            case 'reviewed':
                                $statusClass = 'badge-warning';
                                $statusLabel = 'En cours';
                                break;
                            case 'resolved':
                                $statusClass = 'badge-success';
                                $statusLabel = 'Résolu';
                                break;
                            case 'dismissed':
                                $statusClass = 'badge-secondary';
                                $statusLabel = 'Rejeté';
                                break;
                            default:
                                $statusClass = 'badge-secondary';
                                $statusLabel = $report['status'];
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?> fs-6">
                            <?php echo $statusLabel; ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Type de contenu:</strong><br>
                        <?php 
                        $contentTypes = [
                            'message' => 'Message',
                            'topic' => 'Sujet',
                            'comment' => 'Commentaire',
                            'user' => 'Utilisateur',
                            'other' => 'Autre'
                        ];
                        echo $contentTypes[$report['content_type']] ?? $report['content_type'] ?? 'Message';
                        ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Raison du signalement:</strong><br>
                        <?php
                        $reasonLabels = [
                            'harassment' => 'Harcèlement',
                            'spam' => 'Spam',
                            'inappropriate_content' => 'Contenu inapproprié',
                            'violence' => 'Violence',
                            'hate_speech' => 'Discours de haine',
                            'fake_news' => 'Fausse information',
                            'other' => 'Autre'
                        ];
                        $reasonLabel = $reasonLabels[$report['reason']] ?? $report['reason'];
                        ?>
                        <span class="badge badge-info fs-6">
                            <?php echo htmlspecialchars($reasonLabel); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Description:</strong><br>
                        <div class="alert alert-light mt-2">
                            <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Signalé par:</strong><br>
                        <?php echo htmlspecialchars($report['reporter_username'] ?? 'Inconnu'); ?>
                        <?php if (!empty($report['reporter_email'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($report['reporter_email']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Utilisateur signalé:</strong><br>
                        <?php 
                        $reportedUsername = $report['reported_username'] ?? $report['reported_user_name'] ?? 'N/A';
                        echo htmlspecialchars($reportedUsername);
                        ?>
                        <?php if (!empty($report['reported_user_id'])): ?>
                            <br><small class="text-muted">ID: <?php echo htmlspecialchars($report['reported_user_id']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($report['message_id'])): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>ID du message concerné:</strong><br>
                            #<?php echo htmlspecialchars($report['message_id']); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($report['reviewed_at']) || !empty($report['reviewer_username'])): ?>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <?php if (!empty($report['reviewer_username'])): ?>
                                <strong>Traité par:</strong><br>
                                <?php echo htmlspecialchars($report['reviewer_username']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if (!empty($report['reviewed_at'])): ?>
                                <strong>Date de traitement:</strong><br>
                                <?php 
                                $reviewedDate = new DateTime($report['reviewed_at']);
                                echo $reviewedDate->format('d/m/Y à H:i');
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($report['admin_notes'])): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Notes de l'administrateur:</strong><br>
                            <div class="alert alert-secondary mt-2">
                                <?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-warning text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-tools me-2"></i>
                    Actions
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/signalements/<?php echo htmlspecialchars($report['id']); ?>/update">
                    <div class="mb-3">
                        <label for="status" class="form-label">
                            <strong>Changer le statut:</strong>
                        </label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pending" <?php echo $report['status'] === 'pending' ? 'selected' : ''; ?>>
                                En attente
                            </option>
                            <option value="reviewed" <?php echo $report['status'] === 'reviewed' ? 'selected' : ''; ?>>
                                En cours de traitement
                            </option>
                            <option value="resolved" <?php echo $report['status'] === 'resolved' ? 'selected' : ''; ?>>
                                Résolu
                            </option>
                            <option value="dismissed" <?php echo $report['status'] === 'dismissed' ? 'selected' : ''; ?>>
                                Rejeté
                            </option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">
                            <strong>Notes administrateur:</strong>
                        </label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="5" 
                                  placeholder="Ajoutez des notes sur les actions prises..."><?php echo htmlspecialchars($report['admin_notes'] ?? ''); ?></textarea>
                        <small class="text-muted">
                            Ces notes sont privées et visibles uniquement par les administrateurs.
                        </small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <?php if (!empty($report['reported_user_id'])): ?>
                        <a href="https://arctraining.fr/users/<?php echo htmlspecialchars($report['reported_user_id']); ?>" 
                           class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-user me-1"></i>
                            Voir le profil signalé
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($report['message_id'])): ?>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="alert('Fonctionnalité en cours de développement')">
                            <i class="fas fa-comment me-1"></i>
                            Voir le message
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce signalement ?')) alert('Fonctionnalité en cours de développement')">
                        <i class="fas fa-trash me-1"></i>
                        Supprimer le signalement
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Aide rapide -->
        <div class="card shadow">
            <div class="card-header py-3 bg-info text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-question-circle me-2"></i>
                    Aide rapide
                </h6>
            </div>
            <div class="card-body">
                <h6>Statuts disponibles:</h6>
                <ul class="small">
                    <li><strong>En attente:</strong> Signalement non traité</li>
                    <li><strong>En cours:</strong> Signalement en cours d'examen</li>
                    <li><strong>Résolu:</strong> Action corrective prise</li>
                    <li><strong>Rejeté:</strong> Signalement non fondé</li>
                </ul>
                
                <h6 class="mt-3">Actions recommandées:</h6>
                <ul class="small">
                    <li>Vérifier le contenu signalé</li>
                    <li>Contacter les utilisateurs concernés</li>
                    <li>Prendre des mesures si nécessaire</li>
                    <li>Documenter la décision</li>
                </ul>
            </div>
        </div>
    </div>
</div>
