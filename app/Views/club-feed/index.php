<?php
$facebookDisabled = isset($facebookDisabled) && $facebookDisabled;
$clubName = $clubName ?? 'votre club';
$fbHref = $fbHref ?? '';
$facebookPosts = $facebookPosts ?? [];
$facebookFeedConfigured = isset($facebookFeedConfigured) ? (bool)$facebookFeedConfigured : false;
$facebookGraphError = isset($facebookGraphError) ? (bool)$facebookGraphError : false;
$facebookConnected = isset($facebookConnected) ? (bool)$facebookConnected : false;
$canManageClub = isset($canManageClub) ? (bool)$canManageClub : false;
$clubId = $clubId ?? '';
$clubFeedError = $clubFeedError ?? '';
$clubFeedSuccess = $clubFeedSuccess ?? '';

// Nouvelles données (optionnelles) si le contrôleur les fournit.
$events = isset($events) && is_array($events) ? $events : [];
$concoursUpcoming = isset($concoursUpcoming) && is_array($concoursUpcoming) ? $concoursUpcoming : [];
?>
<div class="container-fluid py-4" id="club-news-page"
     data-club-id="<?php echo htmlspecialchars((string)$clubId); ?>"
     data-can-manage="<?php echo $canManageClub ? '1' : '0'; ?>">
    <div class="row g-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h1 class="h4 mb-0">
                    <i class="fas fa-newspaper me-2 text-primary"></i>
                    Actualités
                </h1>
                <div class="text-muted small">
                    Club&nbsp;: <strong><?php echo htmlspecialchars($clubName); ?></strong>
                </div>
            </div>
        </div>

        <div class="col-12">
            <?php if ($clubFeedError !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($clubFeedError); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($clubFeedSuccess !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($clubFeedSuccess); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div id="club-news-alert" class="alert d-none" role="alert"></div>
        </div>

        <div class="col-12 col-xl-8">
            <?php if ($canManageClub): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h2 class="h6 mb-0">
                                <i class="fas fa-pen me-2 text-primary"></i>
                                Publier une actualité
                            </h2>
                            <span class="badge bg-light text-dark border">Dirigeant / Admin</span>
                        </div>

                        <form id="club-news-create-form" class="row g-3" enctype="multipart/form-data">
                            <div class="col-12 col-lg-8">
                                <label class="form-label" for="club-news-title">Titre (optionnel)</label>
                                <input type="text" class="form-control" id="club-news-title" name="title" maxlength="140"
                                       placeholder="Ex : Entraînement spécial samedi">
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label" for="club-news-audience">Audience</label>
                                <select class="form-select" id="club-news-audience" name="audience" required>
                                    <option value="public">Public (tous connectés)</option>
                                    <option value="club" selected>Mon club</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="club-news-content">Contenu</label>
                                <textarea class="form-control" id="club-news-content" name="content" rows="4" required
                                          placeholder="Écris ici l’actualité…"></textarea>
                                <div class="form-text">Le contenu est affiché avec les retours à la ligne.</div>
                            </div>
                            <div class="col-12 col-lg-8">
                                <label class="form-label" for="club-news-attachment">Pièce jointe (optionnel)</label>
                                <input class="form-control" type="file" id="club-news-attachment" name="attachment">
                            </div>
                            <div class="col-12 col-lg-4 d-flex align-items-end justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="club-news-reset-btn">
                                    Réinitialiser
                                </button>
                                <button type="submit" class="btn btn-primary" id="club-news-submit-btn">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" id="club-news-submit-spinner" role="status" aria-hidden="true"></span>
                                    Publier
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="club-news-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="club-news-tab-public" data-bs-toggle="tab"
                                    data-bs-target="#club-news-pane-public" type="button" role="tab">
                                Public
                                <span class="badge bg-light text-dark border ms-1" id="club-news-count-public">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="club-news-tab-club" data-bs-toggle="tab"
                                    data-bs-target="#club-news-pane-club" type="button" role="tab">
                                Mon club
                                <span class="badge bg-light text-dark border ms-1" id="club-news-count-club">0</span>
                            </button>
                        </li>
                        <li class="ms-auto d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-primary" id="club-news-refresh-btn" type="button">
                                <i class="fas fa-sync-alt me-1"></i> Actualiser
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="club-news-tab-content">
                        <div class="tab-pane fade show active" id="club-news-pane-public" role="tabpanel">
                            <div id="club-news-list-public" class="d-grid gap-3"></div>
                            <div id="club-news-empty-public" class="text-center text-muted py-4 d-none">
                                Aucune actualité “Public” pour le moment.
                            </div>
                        </div>
                        <div class="tab-pane fade" id="club-news-pane-club" role="tabpanel">
                            <div id="club-news-list-club" class="d-grid gap-3"></div>
                            <div id="club-news-empty-club" class="text-center text-muted py-4 d-none">
                                Aucune actualité “Mon club” pour le moment.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Évènements
                    </h2>
                    <?php if (!empty($events)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($events as $event): ?>
                                <?php
                                $eventTitle = $event['title'] ?? $event['name'] ?? 'Évènement';
                                $eventDate = $event['date'] ?? $event['startDate'] ?? $event['start_date'] ?? '';
                                $eventUrl = $event['url'] ?? '';
                                ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string)$eventTitle); ?></div>
                                            <?php if ($eventDate !== ''): ?>
                                                <div class="text-muted small"><?php echo htmlspecialchars((string)$eventDate); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (is_string($eventUrl) && $eventUrl !== ''): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($eventUrl); ?>" target="_blank" rel="noopener noreferrer">
                                                Voir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Liste à venir.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">
                        <i class="fas fa-trophy me-2 text-primary"></i>
                        Concours / Avis de concours
                    </h2>
                    <?php if (!empty($concoursUpcoming)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($concoursUpcoming as $c): ?>
                                <?php
                                $cid = $c['id'] ?? $c['_id'] ?? '';
                                $cTitle = $c['title'] ?? $c['name'] ?? 'Concours';
                                $cDate = $c['date'] ?? $c['startDate'] ?? $c['start_date'] ?? '';
                                ?>
                                <div class="list-group-item px-0">
                                    <div class="fw-semibold"><?php echo htmlspecialchars((string)$cTitle); ?></div>
                                    <?php if ($cDate !== ''): ?>
                                        <div class="text-muted small mb-2"><?php echo htmlspecialchars((string)$cDate); ?></div>
                                    <?php endif; ?>
                                    <?php if ($cid !== ''): ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="/concours/show/<?php echo urlencode((string)$cid); ?>">
                                                Voir
                                            </a>
                                            <a class="btn btn-sm btn-outline-secondary" href="/concours/<?php echo urlencode((string)$cid); ?>/editions?doc=avis">
                                                Avis
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Aucun concours à venir pour le moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modale d'édition -->
<div class="modal fade" id="club-news-edit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l’actualité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="club-news-edit-form" class="row g-3">
                    <input type="hidden" id="club-news-edit-id" />
                    <div class="col-12 col-lg-8">
                        <label class="form-label" for="club-news-edit-title">Titre</label>
                        <input type="text" class="form-control" id="club-news-edit-title" maxlength="140">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label" for="club-news-edit-audience">Audience</label>
                        <select class="form-select" id="club-news-edit-audience" required>
                            <option value="public">Public</option>
                            <option value="club">Mon club</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="club-news-edit-content">Contenu</label>
                        <textarea class="form-control" id="club-news-edit-content" rows="5" required></textarea>
                    </div>
                </form>
                <div class="text-muted small">
                    Note : la pièce jointe (si existante) est conservée. (La gestion “remplacer/supprimer” peut être ajoutée ensuite.)
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="club-news-edit-save-btn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="club-news-edit-spinner" role="status" aria-hidden="true"></span>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.clubNewsPage = {
    clubId: <?php echo json_encode((string)$clubId); ?>,
    canManage: <?php echo $canManageClub ? 'true' : 'false'; ?>
};
</script>
<script src="/public/assets/js/club-news.js"></script>
