<?php
$title = "Portail Archers de Gémenos - Participants de l'événement";
?>
<!-- Inclusion des styles -->
<link rel="stylesheet" href="/public/assets/css/events.css">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Participants de l'événement</h1>
                <a href="/events/<?php echo $event["_id"] ?? "null"; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>Retour à l'événement
                </a>
            </div>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($event): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($event["name"] ?? "Nom inconnu"); ?></h5>
                        <small class="text-muted">
                            <?php 
                            $membersCount = count($event["members"] ?? []);
                            echo $membersCount . " participant" . ($membersCount > 1 ? 's' : '');
                            ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($event["members"])): ?>
                            <div class="list-group">
                                <?php foreach ($event["members"] as $member): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($member["name"] ?? "U", 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($member["name"] ?? "Nom inconnu"); ?></h6>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($member["_id"] ?? "N/A"); ?></small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success">Inscrit</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun participant</h5>
                                <p class="text-muted">Personne ne s'est encore inscrit à cet événement.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Événement non trouvé
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
