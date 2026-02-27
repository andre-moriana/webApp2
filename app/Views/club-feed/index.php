<?php
$facebookDisabled = isset($facebookDisabled) && $facebookDisabled;
$clubName = $clubName ?? 'votre club';
$fbHref = $fbHref ?? '';
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
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-4">
                            Suivez les actualités de <strong><?php echo htmlspecialchars($clubName); ?></strong> sur Facebook.
                        </p>
                        <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg">
                            <i class="fab fa-facebook me-2"></i> Voir la page Facebook du club
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
