<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <?php if ($success): ?>
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="card-title text-success mb-3">Inscription confirmée</h2>
                        <p class="lead"><?= htmlspecialchars($message) ?></p>
                        <p class="text-muted">Vous pouvez vous connecter avec votre numéro de licence et votre mot de passe.</p>
                        <p class="text-muted">le mot de passe temporaires est générés automatiquement au format : Temp[6 derniers chiffres de l'ID licence]!  Exemple : Temp01234A! </p>
                        <p class="text-muted">Nous vous attendons sur le pas de tir !</p>
                    <?php else: ?>
                        <div class="mb-4">
                            <i class="fas fa-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="card-title text-danger mb-3">Confirmation impossible</h2>
                        <p class="lead"><?= htmlspecialchars($message) ?></p>
                        <p class="text-muted">Si le problème persiste, contactez l'organisateur du concours.</p>
                    <?php endif; ?>
                    <div class="mt-4">
                        <a href="/" class="btn btn-primary">Retour à l'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
