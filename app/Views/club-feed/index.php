<?php
$facebookUrl = isset($facebookUrl) ? trim((string)$facebookUrl) : '';
$clubName = $clubName ?? 'votre club';
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h4 mb-4">
                <i class="fab fa-facebook me-2 text-primary"></i>
                Actualités du club
            </h1>

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
            <?php else: ?>
                <?php
                // S'assurer que l'URL Facebook est complète
                $fbHref = $facebookUrl;
                if (strpos($fbHref, 'http') !== 0) {
                    $fbHref = 'https://www.facebook.com/' . ltrim($fbHref, '/');
                }
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Les dernières publications de <strong><?php echo htmlspecialchars($clubName); ?></strong> sur Facebook.
                        </p>
                        <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mb-3">
                            <i class="fab fa-facebook me-1"></i> Voir la page Facebook dans un nouvel onglet
                        </a>
                        <div class="fb-feed-wrapper" style="min-height: 400px;">
                            <?php
                            $iframeSrc = 'https://www.facebook.com/plugins/page.php?' . http_build_query([
                                'href' => $fbHref,
                                'tabs' => 'timeline',
                                'width' => '500',
                                'height' => '700',
                                'small_header' => 'false',
                                'adapt_container_width' => 'true',
                                'hide_cover' => 'false',
                                'show_facepile' => 'true',
                                'locale' => 'fr_FR',
                            ]);
                            ?>
                            <iframe src="<?php echo htmlspecialchars($iframeSrc); ?>"
                                    width="500"
                                    height="700"
                                    style="border:none;overflow:hidden;max-width:100%;"
                                    scrolling="no"
                                    frameborder="0"
                                    allowfullscreen="true"
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                                    title="Page Facebook <?php echo htmlspecialchars($clubName); ?>">
                            </iframe>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
