<?php
$clubName = $clubName ?? 'votre club';
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h4 mb-3">
                <i class="fab fa-facebook me-2 text-primary"></i>
                Debug configuration Facebook
            </h1>

            <div class="alert alert-warning small">
                <strong>Attention :</strong> cette page affiche des informations techniques (mais pas le secret complet).
                À utiliser uniquement par un administrateur pour vérifier la configuration Facebook.
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong>Résumé rapide</strong>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Club courant : <strong><?php echo htmlspecialchars($clubName); ?></strong></li>
                        <li>URL Facebook du club : <code><?php echo htmlspecialchars($facebookUrl ?? '(aucune)'); ?></code></li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong>Logs détaillés</strong>
                    <span class="text-muted small">/facebook-debug</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($logs) && is_array($logs)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                <tr>
                                    <th style="width: 260px;">Clé</th>
                                    <th>Valeur</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($logs as $entry): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($entry['label'] ?? ''); ?></strong></td>
                                        <td>
                                            <pre class="mb-0 small bg-light p-2 rounded" style="white-space: pre-wrap;"><?php
                                                $value = $entry['value'] ?? '';
                                                echo htmlspecialchars(print_r($value, true));
                                            ?></pre>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0 small">Aucun log disponible.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

