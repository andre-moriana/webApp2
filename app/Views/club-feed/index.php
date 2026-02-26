<?php
$facebookUrl = isset($facebookUrl) ? trim((string)$facebookUrl) : '';
$clubName = $clubName ?? 'votre club';
$posts = $posts ?? [];
$facebookConnected = $facebookConnected ?? false;
$feedError = $feedError ?? '';
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
                            Aucune page Facebook n'est configurée pour <strong><?php echo htmlspecialchars($clubName); ?></strong>.
                        </p>
                        <p class="small text-muted mt-2">
                            Cette page affiche les actualités du club auquel votre compte est rattaché. Pour voir un fil Facebook ici, un dirigeant ou administrateur doit&nbsp;:
                        </p>
                        <ol class="small text-muted text-start mt-2 mb-3" style="max-width: 400px; margin-left: auto; margin-right: auto;">
                            <li>Aller dans <strong>Club</strong> puis modifier les infos du club <strong><?php echo htmlspecialchars($clubName); ?></strong>.</li>
                            <li>Renseigner l'URL de la page Facebook du club (ex. https://www.facebook.com/ArchersDeGemenos).</li>
                            <li>Revenir sur cette page et cliquer sur «&nbsp;Connecter la page Facebook&nbsp;» pour afficher les publications.</li>
                        </ol>
                        <a href="/dashboard" class="btn btn-outline-primary mt-3">
                            <i class="fas fa-tachometer-alt me-1"></i> Aller au tableau de bord
                        </a>
                    </div>
                </div>
            <?php elseif ($facebookConnected && empty($posts)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <i class="fab fa-facebook fa-3x text-primary mb-3"></i>
                        <?php if ($feedError !== ''): ?>
                            <p class="text-warning mb-2">Le fil n'a pas pu être chargé.</p>
                            <p class="small text-muted mb-3"><?php echo htmlspecialchars($feedError); ?></p>
                            <div class="small text-muted mb-3 text-start" style="max-width: 560px; margin-left: auto; margin-right: auto;">
                                <p class="mb-2"><strong>Procédure recommandée :</strong></p>
                                <ol class="mb-2 ps-3">
                                    <li class="mb-1">Cliquez sur <strong>«&nbsp;Déconnecter la page Facebook&nbsp;»</strong> ci‑dessous.</li>
                                    <li class="mb-1">Sur <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com</a>, ouvrez votre app.</li>
                                    <li class="mb-1">Allez dans <strong>Paramètres &gt; Utilisation de l’app &gt; Autorisations et fonctionnalités</strong> et ajoutez <strong>pages_read_engagement</strong> (et <strong>pages_show_list</strong> si besoin).</li>
                                    <li class="mb-1"><strong>En mode Développement :</strong> le compte qui connecte la page doit être <strong>Administrateur, Développeur ou Testeur</strong> de l’app (<strong>Rôles &gt; Rôles de l’application</strong>).</li>
                                    <li class="mb-1"><strong>En mode Live :</strong> soumettre une demande d’<strong>App Review</strong> pour «&nbsp;Pages Read Engagement&nbsp;» ou «&nbsp;Page Public Content Access&nbsp;».</li>
                                    <li class="mb-1">Revenez ici, cliquez sur <strong>«&nbsp;Déconnecter&nbsp;»</strong> puis sur <strong>«&nbsp;Connecter la page Facebook&nbsp;»</strong> pour réautoriser.</li>
                                </ol>
                                <p class="mb-0 small"><strong>Important :</strong> En mode Développement, le compte Facebook qui clique sur « Connecter » doit figurer dans <strong>Rôles &gt; Rôles de l’application</strong> (Administrateur, Développeur ou Testeur).</p>
                            </div>
                            <a href="/club-feed/disconnect" class="btn btn-outline-danger me-2"><i class="fas fa-unlink me-1"></i>Déconnecter la page Facebook</a>
                            <a href="/club-feed/connect" class="btn btn-primary">Connecter la page Facebook</a>
                        <?php else: ?>
                            <p class="text-muted mb-3">Page connectée. Aucune publication récente pour le moment.</p>
                        <?php endif; ?>
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
