<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <?php if ($success): ?>
                        <div class="mb-4">
                            <i class="fas fa-times-circle text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="card-title text-warning mb-3">Inscription annulée</h2>
                        <p class="lead"><?= htmlspecialchars($message) ?></p>
                        <p class="text-muted">Vous pourrez vous réinscrire si vous le souhaitez.</p>
                    <?php else: ?>
                        <div class="mb-4">
                            <i class="fas fa-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="card-title text-danger mb-3">Annulation impossible</h2>
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
