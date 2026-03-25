<?php
/** Commandes buvette - agrégation par produit */
$groupes = $commandesBuvetteGroupes ?? [];
$totalLignes = 0;
$totalArticles = 0;
$totalMontant = 0.0;
foreach ($groupes as $g) {
    $totalLignes += is_array($g['lignes'] ?? null) ? count($g['lignes']) : 0;
    $totalArticles += (int)($g['total_qty'] ?? 0);
    $totalMontant += (float)($g['total_amount'] ?? 0.0);
}
?>

<div class="edition-commandes-buvette">
    <h1 class="text-center mb-4">Commandes buvette</h1>

    <div class="mb-3">
        <strong>Total :</strong>
        <?= (int)$totalArticles ?> article(s)
        <?php if ($totalMontant > 0): ?>
            — <?= number_format($totalMontant, 2, ',', ' ') ?> €
        <?php endif; ?>
    </div>

    <?php if (empty($groupes)): ?>
        <p class="text-muted">Aucune commande buvette.</p>
    <?php else: ?>
        <?php foreach ($groupes as $g): ?>
            <?php
            $libelle = (string)($g['libelle'] ?? '—');
            $unite = trim((string)($g['unite'] ?? ''));
            $prix = $g['prix'] ?? null;
            $qtyTotal = (int)($g['total_qty'] ?? 0);
            $montant = (float)($g['total_amount'] ?? 0.0);
            $lignes = is_array($g['lignes'] ?? null) ? $g['lignes'] : [];
            ?>
            <div class="mt-4">
                <h5 class="mb-2">
                    <?= htmlspecialchars($libelle) ?>
                    <span class="text-muted">
                        (<?= (int)$qtyTotal ?><?= $unite !== '' ? ' ' . htmlspecialchars($unite) : '' ?>)
                        <?php if ($prix !== null && $prix !== ''): ?>
                            — <?= number_format((float)$prix, 2, ',', ' ') ?> € / <?= $unite !== '' ? htmlspecialchars($unite) : 'u.' ?>
                        <?php endif; ?>
                        <?php if ($montant > 0): ?>
                            — Total <?= number_format($montant, 2, ',', ' ') ?> €
                        <?php endif; ?>
                    </span>
                </h5>

                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Inscription</th>
                            <th style="width: 30%;">Quantité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lignes as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($l['inscription'] ?? '—')) ?></td>
                                <td><?= (int)($l['quantite'] ?? 0) ?><?= $unite !== '' ? ' ' . htmlspecialchars($unite) : '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

