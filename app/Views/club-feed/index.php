<?php
$facebookDisabled = isset($facebookDisabled) && $facebookDisabled;
$clubName = $clubName ?? 'votre club';
$fbHref = $fbHref ?? '';
$facebookPosts = $facebookPosts ?? [];
$facebookFeedConfigured = isset($facebookFeedConfigured) ? (bool)$facebookFeedConfigured : false;
$facebookGraphError = isset($facebookGraphError) ? (bool)$facebookGraphError : false;
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h4 mb-4">
                <i class="fas fa-newspaper me-2 text-primary"></i>
                Actualités du club
            </h1>
            <?php if ($facebookDisabled): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-2">Les actualités du club ne sont pas affichées sur ce site.</p>
                        <p class="small text-muted mb-4">Vous pouvez suivre <strong><?php echo htmlspecialchars($clubName); ?></strong> sur les réseaux ou via le tableau de bord.</p>
                        <a href="/dashboard" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-1"></i> Tableau de bord
                        </a>
                    </div>
                </div>
            <?php elseif ($fbHref === ''): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-0">
                            Aucune page Facebook n'est configurée pour <strong><?php echo htmlspecialchars($clubName); ?></strong>.
                        </p>
                        <p class="small text-muted mt-2 mb-4">
                            Un administrateur peut renseigner l'URL de la page Facebook du club dans les infos du club.
                        </p>
                        <a href="/dashboard" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-1"></i> Tableau de bord
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-4">
                        <p class="text-muted mb-4 text-center">
                            Dernières actualités de <strong><?php echo htmlspecialchars($clubName); ?></strong> publiées sur Facebook.
                        </p>

                        <?php if (!$facebookFeedConfigured): ?>
                            <div class="alert alert-info text-center">
                                <p class="mb-2">
                                    L'import automatique des publications Facebook n'est pas configuré sur ce serveur.
                                </p>
                                <p class="small mb-3 text-muted">
                                    Veuillez définir <code>FACEBOOK_APP_ID</code> et <code>FACEBOOK_APP_SECRET</code> dans le fichier <code>.env</code>.
                                </p>
                                <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                    <i class="fab fa-facebook me-2"></i> Voir la page Facebook du club
                                </a>
                            </div>
                        <?php elseif ($facebookGraphError && empty($facebookPosts)): ?>
                            <div class="alert alert-warning text-center">
                                <p class="mb-2">
                                    Les publications Facebook du club n'ont pas pu être récupérées automatiquement.
                                </p>
                                <p class="small mb-3 text-muted">
                                    Il se peut que les permissions de l'API Facebook soient limitées pour cette page ou pour cette application.
                                </p>
                                <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                    <i class="fab fa-facebook me-2"></i> Voir la page Facebook du club
                                </a>
                            </div>
                            <!-- Fallback : affichage du plugin de page Facebook -->
                            <div class="mt-4 text-center">
                                <div class="fb-page"
                                     data-tabs="timeline,events"
                                     data-href="<?php echo htmlspecialchars($fbHref); ?>"
                                     data-width="500"
                                     data-hide-cover="false">
                                </div>
                            </div>
                        <?php elseif (empty($facebookPosts)): ?>
                            <div class="alert alert-warning text-center">
                                <p class="mb-2">
                                    Aucune publication Facebook récente n'a pu être trouvée pour ce club.
                                </p>
                                <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                    <i class="fab fa-facebook me-2"></i> Voir la page Facebook du club
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($facebookPosts as $post): ?>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <?php if (!empty($post['full_picture'])): ?>
                                                <a href="<?php echo htmlspecialchars($post['permalink_url'] ?? $fbHref); ?>" target="_blank" rel="noopener noreferrer">
                                                    <img src="<?php echo htmlspecialchars($post['full_picture']); ?>" class="card-img-top" alt="Publication Facebook">
                                                </a>
                                            <?php endif; ?>
                                            <div class="card-body d-flex flex-column">
                                                <?php
                                                $created = '';
                                                if (!empty($post['created_time'])) {
                                                    $dt = date_create($post['created_time']);
                                                    if ($dt) {
                                                        $created = $dt->format('d/m/Y H:i');
                                                    }
                                                }
                                                ?>
                                                <?php if ($created): ?>
                                                    <p class="text-muted small mb-2">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?php echo htmlspecialchars($created); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="card-text mb-3" style="white-space: pre-line;">
                                                    <?php echo htmlspecialchars($post['message'] ?? ''); ?>
                                                </p>
                                                <div class="mt-auto">
                                                    <a href="<?php echo htmlspecialchars($post['permalink_url'] ?? $fbHref); ?>"
                                                       target="_blank"
                                                       rel="noopener noreferrer"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fab fa-facebook me-1"></i> Voir sur Facebook
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
