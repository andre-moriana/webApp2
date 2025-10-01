<?php
$title = "Modifier l'événement - Portail Archers de Gémenos";
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0">Modifier l'événement</h1>
                </div>
                <div class="card-body">
                    <?php if (isset($error) && $error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($event): ?>
                        <form method="POST" action="/events/<?php echo $event['_id']; ?>">
                            <input type="hidden" name="_method" value="PUT">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom de l'événement *</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($event['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="date" name="date" required 
                                       value="<?php echo htmlspecialchars($event['date'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="time" class="form-label">Heure *</label>
                                <input type="time" class="form-control" id="time" name="time" required 
                                       value="<?php echo htmlspecialchars($event['time'] ?? ''); ?>">
                            </div>

                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Mettre à jour
                                </button>
                                <a href="/events" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Événement non trouvé.
                        </div>
                        <a href="/events" class="btn btn-primary">Retour à la liste</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>



