<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-layer-group me-2"></i>
            <?php echo htmlspecialchars($group['name'] ?? 'Groupe non trouvé'); ?>
        </h1>
        <div>
            <a href="/groups" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Retour à la liste
            </a>
            <?php if ($group && isset($group['id'])): ?>
            <a href="/groups/<?php echo htmlspecialchars($group['id']); ?>/edit" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>
                Modifier
            </a>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash me-2"></i>
                Supprimer
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif ($group): ?>
        <!-- Informations du groupe -->
        <div class="row">
            <!-- Carte principale -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations détaillées
                        </h6>
                        <span class="badge bg-<?php echo isset($group['status']) && $group['status'] === 'active' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($group['status'] ?? 'inactif'); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="font-weight-bold">Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($group['description'] ?? 'Aucune description')); ?></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="font-weight-bold">Niveau</h5>
                                <p>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst($group['level'] ?? 'Non défini'); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="font-weight-bold">Nombre de membres</h5>
                                <p>
                                    <span class="badge bg-primary">
                                        <?php echo isset($group['memberCount']) ? $group['memberCount'] . ' membres' : '0 membre'; ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5 class="font-weight-bold">Informations complémentaires</h5>
                            <table class="table table-sm">
                                <?php if (isset($group['id'])): ?>
                                <tr>
                                    <th>ID du groupe</th>
                                    <td><?php echo htmlspecialchars($group['id']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($group['createdAt'])): ?>
                                <tr>
                                    <th>Créé le</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($group['createdAt'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($group['updatedAt'])): ?>
                                <tr>
                                    <th>Dernière modification</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($group['updatedAt'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="col-xl-4 col-lg-5">
                <!-- Chat du groupe -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-comments me-2"></i>
                            Chat du groupe
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($chatError): ?>
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($chatError); ?>
                            </div>
                        <?php else: ?>
                            <div class="chat-messages" style="height: 300px; overflow-y: auto;">
                                <?php if (empty($chatMessages)): ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-comments fa-2x mb-2"></i>
                                        <p>Aucun message dans le chat</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($chatMessages as $message): ?>
                                        <div class="chat-message mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="chat-avatar me-2">
                                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                </div>
                                                <div class="chat-content">
                                                    <div class="chat-header">
                                                        <strong class="text-primary">
                                                            <?php echo htmlspecialchars($message['userName'] ?? 'Utilisateur'); ?>
                                                        </strong>
                                                        <small class="text-muted ms-2">
                                                            <?php 
                                                            if (isset($message['timestamp'])) {
                                                                $date = new DateTime($message['timestamp']);
                                                                echo $date->format('d/m/Y H:i');
                                                            }
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <div class="chat-text">
                                                        <?php echo nl2br(htmlspecialchars($message['content'] ?? '')); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Formulaire d'envoi de message -->
                            <div class="chat-input mt-3">
                                <form action="/groups/<?php echo htmlspecialchars($group['id'] ?? ''); ?>/chat" method="POST" class="d-flex">
                                    <input type="text" 
                                           name="message" 
                                           class="form-control me-2" 
                                           placeholder="Votre message..." 
                                           required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>
                            Statistiques
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="display-4 text-primary mb-2">
                                <?php echo $group['memberCount'] ?? 0; ?>
                            </div>
                            <div class="text-muted">Membres actifs</div>
                        </div>

                        <hr>

                        <div class="text-center">
                            <h5 class="font-weight-bold">Répartition par niveau</h5>
                            <div class="mt-3">
                                <span class="badge bg-success me-2">Débutant: <?php echo isset($group['levelStats']['beginner']) ? $group['levelStats']['beginner'] : '0'; ?></span>
                                <span class="badge bg-info me-2">Intermédiaire: <?php echo isset($group['levelStats']['intermediate']) ? $group['levelStats']['intermediate'] : '0'; ?></span>
                                <span class="badge bg-warning">Avancé: <?php echo isset($group['levelStats']['advanced']) ? $group['levelStats']['advanced'] : '0'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bolt me-2"></i>
                            Actions rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-user-plus me-2"></i>
                                Ajouter un membre
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-plus me-2"></i>
                                Planifier un entraînement
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-export me-2"></i>
                                Exporter les données
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmation de suppression -->
<?php if ($group && isset($group['id'])): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer le groupe "<?php echo htmlspecialchars($group['name'] ?? ''); ?>" ?
                Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="/groups/<?php echo htmlspecialchars($group['id']); ?>" method="POST" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>
                        Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?> 