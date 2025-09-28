<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?php echo htmlspecialchars($training['title']); ?></h1>
                    <p class="text-muted mb-0">
                        <?php echo date('d/m/Y à H:i', strtotime($training['start_date'])); ?>
                        <?php if ($training['end_date']): ?>
                        - <?php echo date('H:i', strtotime($training['end_date'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <a href="/trainings" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>

            <!-- Informations générales -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informations de l'entraînement</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Type de tir :</strong> 
                                        <?php if ($training['shooting_type']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($training['shooting_type']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">Non spécifié</span>
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Nombre de volées :</strong> <?php echo $training['total_ends']; ?></p>
                                    <p><strong>Flèches par volée :</strong> <?php echo $training['arrows_per_end']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total flèches :</strong> <?php echo $training['total_arrows']; ?></p>
                                    <p><strong>Score total :</strong> <span class="h5 text-primary"><?php echo $training['total_score']; ?></span></p>
                                    <p><strong>Moyenne par flèche :</strong> <span class="h6 text-success"><?php echo number_format($training['average_score'], 2); ?></span></p>
                                </div>
                            </div>
                            <?php if ($training['notes']): ?>
                            <div class="mt-3">
                                <strong>Notes :</strong>
                                <p class="mt-1"><?php echo nl2br(htmlspecialchars($training['notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Statut</h5>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($training['status'] === 'en_cours'): ?>
                            <div class="text-warning">
                                <i class="fas fa-clock fa-3x mb-2"></i>
                                <h5>En cours</h5>
                                <p class="text-muted">Entraînement en cours d'exécution</p>
                            </div>
                            <?php elseif ($training['status'] === 'terminé'): ?>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-3x mb-2"></i>
                                <h5>Terminé</h5>
                                <p class="text-muted">Entraînement terminé</p>
                            </div>
                            <?php else: ?>
                            <div class="text-secondary">
                                <i class="fas fa-info-circle fa-3x mb-2"></i>
                                <h5><?php echo ucfirst($training['status']); ?></h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détail des volées -->
            <?php if (!empty($training['ends'])): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Détail des volées</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Volée</th>
                                    <th>Scores</th>
                                    <th>Total</th>
                                    <th>Position</th>
                                    <th>Catégorie</th>
                                    <th>Commentaire</th>
                                    <th>GPS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($training['ends'] as $end): ?>
                                <tr>
                                    <td>
                                        <strong>Volée <?php echo $end['end_number']; ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($end['shots'])): ?>
                                        <div class="d-flex gap-1">
                                            <?php foreach ($end['shots'] as $shot): ?>
                                            <span class="badge bg-light text-dark border"><?php echo $shot['score']; ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?php echo $end['total_score']; ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($end['shooting_position']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($end['shooting_position']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($end['target_category']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($end['target_category']); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($end['comment']): ?>
                                        <small><?php echo htmlspecialchars($end['comment']); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($end['latitude'] && $end['longitude']): ?>
                                        <a href="https://www.google.com/maps?q=<?php echo $end['latitude']; ?>,<?php echo $end['longitude']; ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-4">
                    <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune volée enregistrée</h5>
                    <p class="text-muted">Les volées de cet entraînement n'ont pas encore été enregistrées.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
