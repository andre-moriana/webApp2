<?php
$facebookUrl = isset($facebookUrl) ? trim((string)$facebookUrl) : '';
$clubName = $clubName ?? 'votre club';
$posts = $posts ?? [];
$facebookConnected = $facebookConnected ?? false;
$fbHref = $fbHref ?? '';
if ($facebookUrl !== '' && $fbHref === '') {
    $fbHref = (strpos($facebookUrl, 'http') === 0) ? $facebookUrl : 'https://www.facebook.com/' . ltrim($facebookUrl, '/');
}
$flashError = $_SESSION['club_feed_error'] ?? '';
$flashSuccess = $_SESSION['club_feed_success'] ?? '';
if ($flashError !== '') unset($_SESSION['club_feed_error']);
if ($flashSuccess !== '') unset($_SESSION['club_feed_success']);
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h4 mb-4">
                <i class="fab fa-facebook me-2 text-primary"></i>
                Actualités du club
            </h1>

            <?php if ($flashError): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flashError); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flashSuccess); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <?php if ($facebookUrl === ''): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fab fa-facebook fa-4x text-muted mb-3"></i>
                        <p class="text-muted mb-0">
                            Aucune page Facebook n'est configurée pour <?php echo htmlspecialchars($clubName); ?>.
                        </p>
                        <p class="small text-muted mt-2">
                            Votre dirigeant ou administrateur peut ajouter l'URL de la page Facebook dans les informations du club.
                        </p>
                        <a href="/dashboard" class="btn btn-outline-primary mt-3">
                            <i class="fas fa-tachometer-alt me-1"></i> Aller au tableau de bord
                        </a>
                    </div>
                </div>
            <?php elseif ($facebookConnected && empty($posts)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <i class="fab fa-facebook fa-3x text-primary mb-3"></i>
                        <p class="text-muted mb-3">Page connectée. Aucune publication récente pour le moment.</p>
                        <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
                            <i class="fab fa-facebook me-1"></i> Voir la page Facebook
                        </a>
                    </div>
                </div>
            <?php elseif (!empty($posts)): ?>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <p class="text-muted mb-0 small">
                        Dernières publications de <strong><?php echo htmlspecialchars($clubName); ?></strong> sur Facebook.
                    </p>
                    <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-facebook me-1"></i> Voir la page Facebook
                    </a>
                </div>
                <div class="row g-3">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if (!empty($post['full_picture'])): ?>
                                    <a href="<?php echo htmlspecialchars($post['permalink_url'] ?? $fbHref); ?>" target="_blank" rel="noopener noreferrer" class="d-block">
                                        <img src="<?php echo htmlspecialchars($post['full_picture']); ?>" class="card-img-top" alt="" style="object-fit: cover; height: 200px;">
                                    </a>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <?php if (!empty($post['message'])): ?>
                                        <p class="card-text flex-grow-1"><?php echo nl2br(htmlspecialchars(mb_substr($post['message'], 0, 300) . (mb_strlen($post['message']) > 300 ? '…' : ''))); ?></p>
                                    <?php endif; ?>
                                    <div class="mt-auto pt-2 d-flex align-items-center justify-content-between">
                                        <span class="text-muted small">
                                            <?php
                                            if (!empty($post['created_time'])) {
                                                $dt = new DateTime($post['created_time']);
                                                echo $dt->format('d/m/Y à H:i');
                                            }
                                            ?>
                                        </span>
                                        <a href="<?php echo htmlspecialchars($post['permalink_url'] ?? $fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                            Voir sur Facebook
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fab fa-facebook fa-4x text-primary mb-3"></i>
                        <h2 class="h5 mb-3">Fil Facebook de <?php echo htmlspecialchars($clubName); ?></h2>
                        <p class="text-muted mb-4">
                            Pour afficher les publications directement sur cette page, connectez une fois la page Facebook du club. Un administrateur de la page doit cliquer ci-dessous et autoriser l'accès.
                        </p>
                        <a href="/club-feed/connect" class="btn btn-primary btn-lg">
                            <i class="fab fa-facebook me-2"></i> Connecter la page Facebook
                        </a>
                        <p class="text-muted small mt-3 mb-0">
                            Après connexion, les posts s'afficheront ici sans quitter le site.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
