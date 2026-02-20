<?php
/**
 * En-tête et pied de page pour les documents d'édition concours
 * En-tête : logo club organisateur (gauche) | titre compétition (centre)
 * Pied de page : informations standard
 */
$clubOrganisateurId = $concours->club_organisateur ?? null;
$clubOrganisateur = $clubOrganisateurId ? ($clubsMap[$clubOrganisateurId] ?? $clubsMap[(string)$clubOrganisateurId] ?? null) : null;
$clubLogoUrl = null;
if ($clubOrganisateur && !empty($clubOrganisateur['logo'])) {
    $logo = $clubOrganisateur['logo'];
    if (strpos($logo, 'http://') === 0 || strpos($logo, 'https://') === 0) {
        $clubLogoUrl = $logo;
    } else {
        $apiBase = $_ENV['API_BASE_URL'] ?? 'https://api.arctraining.fr/api';
        $apiBase = rtrim($apiBase, '/');
        if (substr($apiBase, -4) === '/api') {
            $apiBase = substr($apiBase, 0, -4);
        }
        $clubLogoUrl = $apiBase . (strpos($logo, '/') === 0 ? '' : '/') . $logo;
    }
}
$titreCompetition = htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours');
?>
<!-- En-tête document édition -->
<header class="edition-doc-header">
    <div class="edition-doc-header-inner">
        <div class="edition-doc-header-left">
            <?php if ($clubLogoUrl): ?>
                <img src="<?= htmlspecialchars($clubLogoUrl) ?>" alt="Logo club organisateur" class="edition-doc-logo edition-doc-logo-club">
            <?php else: ?>
                <span class="edition-doc-logo-placeholder text-muted small">Logo club</span>
            <?php endif; ?>
        </div>
        <div class="edition-doc-header-center">
            <h2 class="edition-doc-title"><?= $titreCompetition ?></h2>
        </div>
    </div>
</header>

<!-- Pied de page document édition -->
<footer class="edition-doc-footer">
    <div class="edition-doc-footer-inner">
        <span class="edition-doc-footer-left"><?= htmlspecialchars($docTitle ?? 'Document') ?></span>
        <span class="edition-doc-footer-center">Document généré le <?= date('d/m/Y à H:i') ?></span>
        <span class="edition-doc-footer-right"><?= htmlspecialchars($clubName ?? '') ?></span>
    </div>
</footer>
