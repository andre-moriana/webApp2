<<<<<<< HEAD
<?php
$title = "Détails du club - " . htmlspecialchars($club['name'] ?? 'Club');
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i><?php echo htmlspecialchars($club['name'] ?? 'Club'); ?>
                    </h4>
                    <?php if ($_SESSION['user']['is_admin'] ?? false): ?>
                    <div class="btn-group">
                        <a href="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>/edit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?php echo $club['id'] ?? $club['_id']; ?>)">
                            <i class="fas fa-trash me-1"></i>Supprimer
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Informations générales</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td style="width: 40%;"><strong>Nom :</strong></td>
                                    <td><?php echo htmlspecialchars($club['name'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php if (!empty($club['nameShort'] ?? $club['name_short'] ?? '')): ?>
                                <tr>
                                    <td><strong>Nom court :</strong></td>
                                    <td><?php echo htmlspecialchars($club['nameShort'] ?? $club['name_short']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['description'] ?? '')): ?>
                                <tr>
                                    <td><strong>Description :</strong></td>
                                    <td><?php echo nl2br(htmlspecialchars($club['description'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['address'] ?? '')): ?>
                                <tr>
                                    <td><strong>Adresse :</strong></td>
                                    <td><?php echo htmlspecialchars($club['address']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['city'] ?? '')): ?>
                                <tr>
                                    <td><strong>Ville :</strong></td>
                                    <td><?php echo htmlspecialchars($club['city']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['postalCode'] ?? $club['postal_code'] ?? '')): ?>
                                <tr>
                                    <td><strong>Code postal :</strong></td>
                                    <td><?php echo htmlspecialchars($club['postalCode'] ?? $club['postal_code']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Contact</h6>
                            <table class="table table-sm">
                                <?php if (!empty($club['phone'] ?? '')): ?>
                                <tr>
                                    <td style="width: 40%;"><strong>Téléphone :</strong></td>
                                    <td>
                                        <a href="tel:<?php echo htmlspecialchars($club['phone']); ?>">
                                            <?php echo htmlspecialchars($club['phone']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['email'] ?? '')): ?>
                                <tr>
                                    <td><strong>Email :</strong></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($club['email']); ?>">
                                            <?php echo htmlspecialchars($club['email']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['website'] ?? '')): ?>
                                <tr>
                                    <td><strong>Site web :</strong></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($club['website']); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars($club['website']); ?>
                                            <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php 
                                $president = $club['president'] ?? null;
                                if ($president && isset($president['name'])): 
                                ?>
                                <tr>
                                    <td><strong>Président :</strong></td>
                                    <td><?php echo htmlspecialchars($president['name']); ?></td>
                                </tr>
                                <?php elseif (!empty($club['presidentId'] ?? $club['president_id'] ?? '')): ?>
                                <tr>
                                    <td><strong>Président ID :</strong></td>
                                    <td><?php echo htmlspecialchars($club['presidentId'] ?? $club['president_id']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php 
                                $createdBy = $club['createdBy'] ?? null;
                                if ($createdBy && isset($createdBy['name'])): 
                                ?>
                                <tr>
                                    <td><strong>Créé par :</strong></td>
                                    <td><?php echo htmlspecialchars($createdBy['name']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['createdAt'] ?? $club['created_at'] ?? '')): ?>
                                <tr>
                                    <td><strong>Créé le :</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($club['createdAt'] ?? $club['created_at'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['updatedAt'] ?? $club['updated_at'] ?? '')): ?>
                                <tr>
                                    <td><strong>Modifié le :</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($club['updatedAt'] ?? $club['updated_at'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce club ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="/clubs" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="club_id" id="deleteClubId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(clubId) {
    document.getElementById('deleteClubId').value = clubId;
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = '/clubs/' + clubId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

=======
<?php
$title = "Détails du club - " . htmlspecialchars($club['name'] ?? 'Club');
?>
<?php
$additionalCSS = $additionalCSS ?? [];
$additionalCSS[] = '/public/assets/css/clubs-show.css';
$additionalJS = $additionalJS ?? [];
$additionalJS[] = '/public/assets/js/clubs-show.js';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i><?php echo htmlspecialchars($club['name'] ?? 'Club'); ?>
                    </h4>
                    <div class="btn-group">
                        <?php 
                        // Vérifier si l'utilisateur peut gérer les permissions
                        $user = $_SESSION['user'];
                        $isAdmin = $user['is_admin'] ?? false;
                        $isDirigeant = ($user['role'] ?? '') === 'Dirigeant';
                        $belongsToClub = ($user['clubId'] ?? null) == ($club['id'] ?? $club['_id']);
                        
                        // Afficher le bouton permissions si Dirigeant du club ou Admin
                        if ($isAdmin || ($isDirigeant && $belongsToClub)):
                        ?>
                        <a href="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>/permissions" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-lock me-1"></i>Permissions
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($isAdmin): ?>
                        <a href="/clubs/<?php echo $club['id'] ?? $club['_id']; ?>/edit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?php echo $club['id'] ?? $club['_id']; ?>)">
                            <i class="fas fa-trash me-1"></i>Supprimer
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Informations générales</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td style="width: 40%;"><strong>Nom :</strong></td>
                                    <td><?php echo htmlspecialchars($club['name'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php if (!empty($club['nameShort'] ?? $club['name_short'] ?? '')): ?>
                                <tr>
                                    <td><strong>Nom court :</strong></td>
                                    <td><?php echo htmlspecialchars($club['nameShort'] ?? $club['name_short']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['description'] ?? '')): ?>
                                <tr>
                                    <td><strong>Description :</strong></td>
                                    <td><?php echo nl2br(htmlspecialchars($club['description'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['address'] ?? '')): ?>
                                <tr>
                                    <td><strong>Adresse :</strong></td>
                                    <td><?php echo htmlspecialchars($club['address']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['city'] ?? '')): ?>
                                <tr>
                                    <td><strong>Ville :</strong></td>
                                    <td><?php echo htmlspecialchars($club['city']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['postalCode'] ?? $club['postal_code'] ?? '')): ?>
                                <tr>
                                    <td><strong>Code postal :</strong></td>
                                    <td><?php echo htmlspecialchars($club['postalCode'] ?? $club['postal_code']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Contact</h6>
                            <table class="table table-sm">
                                <?php if (!empty($club['phone'] ?? '')): ?>
                                <tr>
                                    <td style="width: 40%;"><strong>Téléphone :</strong></td>
                                    <td>
                                        <a href="tel:<?php echo htmlspecialchars($club['phone']); ?>">
                                            <?php echo htmlspecialchars($club['phone']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['email'] ?? '')): ?>
                                <tr>
                                    <td><strong>Email :</strong></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($club['email']); ?>">
                                            <?php echo htmlspecialchars($club['email']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['website'] ?? '')): ?>
                                <tr>
                                    <td><strong>Site web :</strong></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($club['website']); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars($club['website']); ?>
                                            <i class="fas fa-external-link-alt ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php 
                                $president = $club['president'] ?? null;
                                if ($president && isset($president['name'])): 
                                ?>
                                <tr>
                                    <td><strong>Président :</strong></td>
                                    <td><?php echo htmlspecialchars($president['name']); ?></td>
                                </tr>
                                <?php elseif (!empty($club['presidentId'] ?? $club['president_id'] ?? '')): ?>
                                <tr>
                                    <td><strong>Président ID :</strong></td>
                                    <td><?php echo htmlspecialchars($club['presidentId'] ?? $club['president_id']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php 
                                $createdBy = $club['createdBy'] ?? null;
                                if ($createdBy && isset($createdBy['name'])): 
                                ?>
                                <tr>
                                    <td><strong>Créé par :</strong></td>
                                    <td><?php echo htmlspecialchars($createdBy['name']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['createdAt'] ?? $club['created_at'] ?? '')): ?>
                                <tr>
                                    <td><strong>Créé le :</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($club['createdAt'] ?? $club['created_at'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($club['updatedAt'] ?? $club['updated_at'] ?? '')): ?>
                                <tr>
                                    <td><strong>Modifié le :</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($club['updatedAt'] ?? $club['updated_at'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce club ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="/clubs" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="club_id" id="deleteClubId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>


>>>>>>> 689251c7d8a7e267feb005c9916a2222adfb1ff4
