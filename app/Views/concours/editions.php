<?php
/**
 * Page Éditions - documents à imprimer pour un concours
 */
$concoursId = $concours->id ?? $concours->_id ?? $id ?? null;
$baseUrl = '/concours/' . (int)$concoursId . '/editions';
$mailTargetsArchers = $mailTargetsArchers ?? [];
$mailTargetsClubs = $mailTargetsClubs ?? [];
$mailTargetsComitesRegionaux = $mailTargetsComitesRegionaux ?? [];
$mailTargetsComitesDepartementaux = $mailTargetsComitesDepartementaux ?? [];
$additionalJS = $additionalJS ?? [];
$additionalJS[] = '/public/assets/js/concours-editions.js?v=' . time();
?>
<div class="container-fluid concours-editions">
    <h1 class="mb-4">
        <i class="fas fa-print me-2"></i>Éditions
        <small class="text-muted d-block mt-1"><?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours') ?></small>
    </h1>

    <p class="lead mb-4">Générez les documents à imprimer pour ce concours.</p>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['warning'])): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($_SESSION['warning']) ?></div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

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
                    <button type="button" class="btn btn-outline-success btn-sm btn-edition-mail" data-doc="avis" data-doc-label="Avis de concours">
                        <i class="fas fa-envelope me-1"></i>Diffuser par mail</button>
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
                    <button type="button" class="btn btn-outline-success btn-sm btn-edition-mail" data-doc="liste-participants" data-doc-label="Liste des participants">
                        <i class="fas fa-envelope me-1"></i>Diffuser par mail</button>
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
                    <button type="button" class="btn btn-outline-success btn-sm btn-edition-mail" data-doc="scores" data-doc-label="Scores">
                        <i class="fas fa-envelope me-1"></i>Diffuser par mail</button>
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
                    <button type="button" class="btn btn-outline-success btn-sm btn-edition-mail" data-doc="classement" data-doc-label="Classement">
                        <i class="fas fa-envelope me-1"></i>Diffuser par mail</button>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-coffee text-secondary me-2"></i>Commandes buvette
                    </h5>
                    <p class="card-text text-muted small">Synthèse des commandes buvette, triées par produit.</p>
                    <a href="<?= $baseUrl ?>?doc=commandes-buvette" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>Ouvrir</a>
                    <a href="<?= $baseUrl ?>?doc=commandes-buvette" target="_blank" class="btn btn-primary btn-sm">
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

<div class="modal fade" id="diffusionEditionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="/concours/<?= (int)$concoursId ?>/editions/diffusion-mail">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Diffusion par mail - <span id="diffusionDocLabel">Document</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="doc" id="diffusionDocInput" value="">
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="subject" placeholder="Sujet du mail (optionnel)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="3" placeholder="Message personnalisé (optionnel)"></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <strong>Clubs</strong>
                                    <span>
                                        <button type="button" class="btn btn-link btn-sm p-0 me-2 check-all-group" data-target-group="clubs">Tout cocher</button>
                                        <button type="button" class="btn btn-link btn-sm p-0 uncheck-all-group" data-target-group="clubs">Tout décocher</button>
                                    </span>
                                </div>
                                <div class="card-body p-2" style="max-height: 260px; overflow: auto;">
                                    <?php if (empty($mailTargetsClubs)): ?>
                                        <div class="text-muted small">Aucun club disponible</div>
                                    <?php else: ?>
                                        <?php foreach ($mailTargetsClubs as $club): ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input group-clubs" type="checkbox" name="target_clubs[]" value="<?= htmlspecialchars((string)$club['id']) ?>" id="club_<?= md5((string)$club['id']) ?>">
                                                <label class="form-check-label small" for="club_<?= md5((string)$club['id']) ?>">
                                                    <?= htmlspecialchars((string)$club['label']) ?> (<?= (int)($club['count'] ?? 0) ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <strong>Comités régionaux</strong>
                                    <span>
                                        <button type="button" class="btn btn-link btn-sm p-0 me-2 check-all-group" data-target-group="comites-regionaux">Tout cocher</button>
                                        <button type="button" class="btn btn-link btn-sm p-0 uncheck-all-group" data-target-group="comites-regionaux">Tout décocher</button>
                                    </span>
                                </div>
                                <div class="card-body p-2" style="max-height: 260px; overflow: auto;">
                                    <?php if (empty($mailTargetsComitesRegionaux)): ?>
                                        <div class="text-muted small">Aucun comité régional</div>
                                    <?php else: ?>
                                        <?php foreach ($mailTargetsComitesRegionaux as $comite): ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input group-comites-regionaux" type="checkbox" name="target_comites_regionaux[]" value="<?= htmlspecialchars((string)$comite['id']) ?>" id="comite_reg_<?= md5((string)$comite['id']) ?>">
                                                <label class="form-check-label small" for="comite_reg_<?= md5((string)$comite['id']) ?>">
                                                    <?= htmlspecialchars((string)$comite['label']) ?> (<?= (int)($comite['count'] ?? 0) ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <strong>Comités départementaux</strong>
                                    <span>
                                        <button type="button" class="btn btn-link btn-sm p-0 me-2 check-all-group" data-target-group="comites-departementaux">Tout cocher</button>
                                        <button type="button" class="btn btn-link btn-sm p-0 uncheck-all-group" data-target-group="comites-departementaux">Tout décocher</button>
                                    </span>
                                </div>
                                <div class="card-body p-2" style="max-height: 260px; overflow: auto;">
                                    <?php if (empty($mailTargetsComitesDepartementaux)): ?>
                                        <div class="text-muted small">Aucun comité départemental</div>
                                    <?php else: ?>
                                        <?php foreach ($mailTargetsComitesDepartementaux as $comite): ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input group-comites-departementaux" type="checkbox" name="target_comites_departementaux[]" value="<?= htmlspecialchars((string)$comite['id']) ?>" id="comite_dep_<?= md5((string)$comite['id']) ?>">
                                                <label class="form-check-label small" for="comite_dep_<?= md5((string)$comite['id']) ?>">
                                                    <?= htmlspecialchars((string)$comite['label']) ?> (<?= (int)($comite['count'] ?? 0) ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <strong>Archers</strong>
                                    <span>
                                        <button type="button" class="btn btn-link btn-sm p-0 me-2 check-all-group" data-target-group="archers">Tout cocher</button>
                                        <button type="button" class="btn btn-link btn-sm p-0 uncheck-all-group" data-target-group="archers">Tout décocher</button>
                                    </span>
                                </div>
                                <div class="card-body p-2" style="max-height: 260px; overflow: auto;">
                                    <?php if (empty($mailTargetsArchers)): ?>
                                        <div class="text-muted small">Aucun archer avec email</div>
                                    <?php else: ?>
                                        <?php foreach ($mailTargetsArchers as $archer): ?>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input group-archers" type="checkbox" name="target_archers[]" value="<?= htmlspecialchars((string)$archer['id']) ?>" id="archer_<?= md5((string)$archer['id']) ?>">
                                                <label class="form-check-label small" for="archer_<?= md5((string)$archer['id']) ?>">
                                                    <?= htmlspecialchars((string)$archer['label']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i>Envoyer la diffusion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

