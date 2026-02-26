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
                        <div class="fb-feed-wrapper" style="min-height: 500px;">
                            <div id="fb-root"></div>
                            <div class="fb-page" 
                                 data-href="<?php echo htmlspecialchars($fbHref); ?>" 
                                 data-tabs="timeline" 
                                 data-width="500" 
                                 data-height="700" 
                                 data-small-header="false" 
                                 data-adapt-container-width="true" 
                                 data-hide-cover="false" 
                                 data-show-facepile="true">
                                <blockquote cite="<?php echo htmlspecialchars($fbHref); ?>" class="fb-xfbml-parse-ignore">
                                    <a href="<?php echo htmlspecialchars($fbHref); ?>"><?php echo htmlspecialchars($clubName); ?> sur Facebook</a>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    (function(d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) return;
                        js = d.createElement(s); js.id = id;
                        js.src = "https://connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v18.0";
                        js.async = true; js.defer = true;
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));
                    window.fbAsyncInit = function() {
                        if (typeof FB !== 'undefined') {
                            FB.XFBML.parse();
                        }
                    };
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>
