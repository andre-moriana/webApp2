<?php
$facebookUrl = $facebookUrl ?? '';
$clubName = $clubName ?? 'votre club';
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h4 mb-4">
                <i class="fab fa-facebook me-2 text-primary"></i>
                Actualités du club
            </h1>

            <?php if (empty($facebookUrl)): ?>
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
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Les dernières publications de <strong><?php echo htmlspecialchars($clubName); ?></strong> sur Facebook.
                        </p>
                        <div class="fb-feed-wrapper">
                            <div id="fb-root"></div>
                            <div class="fb-page" 
                                 data-href="<?php echo htmlspecialchars($facebookUrl); ?>" 
                                 data-tabs="timeline" 
                                 data-width="500" 
                                 data-height="700" 
                                 data-small-header="false" 
                                 data-adapt-container-width="true" 
                                 data-hide-cover="false" 
                                 data-show-facepile="true">
                            </div>
                            <script async defer crossorigin="anonymous" src="https://connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v18.0"></script>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
