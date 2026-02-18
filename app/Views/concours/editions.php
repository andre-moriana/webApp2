<?php
/**
 * Page Éditions - documents à imprimer pour un concours
 */
$concoursId = $concours->id ?? $concours->_id ?? $id ?? null;
$baseUrl = '/concours/' . (int)$concoursId . '/editions';
?>
<div class="container-fluid concours-editions">
    <h1 class="mb-4">
        <i class="fas fa-print me-2"></i>Éditions
        <small class="text-muted d-block mt-1"><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours') ?></small>
    </h1>

    <p class="lead mb-4">Générez les documents à imprimer pour ce concours.</p>

    <div class="row g-3">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-bullhorn text-primary me-2"></i>Avis de concours
                    </h5>
                    <p class="card-text text-muted small">Document officiel annonçant le concours (dates, lieu, discipline, inscriptions).</p>
                    <a href="<?= $baseUrl ?>?doc=avis" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Ouvrir</a>
                    <a href="<?= $baseUrl ?>?doc=avis" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Imprimer</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-clipboard-list text-success me-2"></i>Feuilles de marques
                    </h5>
                    <p class="card-text text-muted small">Feuilles pour noter les scores par cible ou par peloton.</p>
                    <a href="<?= $baseUrl ?>?doc=feuilles-marques" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Ouvrir</a>
                    <a href="<?= $baseUrl ?>?doc=feuilles-marques" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Imprimer</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-users text-info me-2"></i>Liste des participants
                    </h5>
                    <p class="card-text text-muted small">Liste des archers inscrits et confirmés par club et départ.</p>
                    <a href="<?= $baseUrl ?>?doc=liste-participants" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Ouvrir</a>
                    <a href="<?= $baseUrl ?>?doc=liste-participants" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Imprimer</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chart-line text-warning me-2"></i>Scores
                    </h5>
                    <p class="card-text text-muted small">Tableau des scores par participant.</p>
                    <a href="<?= $baseUrl ?>?doc=scores" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Ouvrir</a>
                    <a href="<?= $baseUrl ?>?doc=scores" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Imprimer</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-trophy text-warning me-2"></i>Classement
                    </h5>
                    <p class="card-text text-muted small">Classement final des participants.</p>
                    <a href="<?= $baseUrl ?>?doc=classement" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Ouvrir</a>
                    <a href="<?= $baseUrl ?>?doc=classement" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Imprimer</a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="/concours/show/<?= (int)$concoursId ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour au concours
        </a>
    </div>
</div>
