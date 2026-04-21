<?php
/**
 * En-tête pour les documents d'édition concours
 * En-tête : logo club organisateur (gauche) | titre compétition (centre)
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

$titreCompetition = htmlspecialchars($concours->titre_competition ?? $concours->nom ?? '');

$toAbsLogoUrl = function ($logoPath) {
    $logoPath = trim((string)$logoPath);
    if ($logoPath === '') {
        return '';
    }
    if (strpos($logoPath, 'http://') === 0 || strpos($logoPath, 'https://') === 0) {
        return $logoPath;
    }
    $apiBase = $_ENV['API_BASE_URL'] ?? 'https://api.arctraining.fr/api';
    $apiBase = rtrim($apiBase, '/');
    if (substr($apiBase, -4) === '/api') {
        $apiBase = substr($apiBase, 0, -4);
    }
    return $apiBase . (strpos($logoPath, '/') === 0 ? '' : '/') . $logoPath;
};

$showHeaderAffiliatedLogos = (($doc ?? '') === 'avis');
$fftaLogoUrl = '';
$comiteLogoUrl = '';
if ($showHeaderAffiliatedLogos) {
    $allClubs = [];
    if (isset($clubs) && is_array($clubs)) {
        $allClubs = $clubs;
    } elseif (isset($clubsMap) && is_array($clubsMap)) {
        foreach ($clubsMap as $clubItem) {
            if (is_array($clubItem)) {
                $allClubs[] = $clubItem;
            }
        }
    }

    $clubOrgCode = trim((string)($clubOrganisateur['nameShort'] ?? $clubOrganisateur['name_short'] ?? ''));
    $clubOrgCodeDigits = preg_replace('/\D/', '', $clubOrgCode);
    $niveauChampionnatRaw = trim((string)($niveauChampionnatName ?? ''));
    $niveauChampionnatNorm = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $niveauChampionnatRaw) ?: $niveauChampionnatRaw);
    $isChampionnatRegional = strpos($niveauChampionnatNorm, 'regional') !== false;
    $isChampionnatDepartemental = strpos($niveauChampionnatNorm, 'departemental') !== false;

    foreach ($allClubs as $clubItem) {
        $clubShortCode = preg_replace('/\D/', '', (string)($clubItem['nameShort'] ?? $clubItem['name_short'] ?? ''));
        if ($clubShortCode === '0000001') {
            $fftaLogoUrl = $toAbsLogoUrl($clubItem['logo'] ?? '');
            if ($fftaLogoUrl !== '') {
                break;
            }
        }
    }

    if (($isChampionnatRegional || $isChampionnatDepartemental) && strlen($clubOrgCodeDigits) >= 4) {
        $prefix = $isChampionnatRegional ? substr($clubOrgCodeDigits, 0, 2) : substr($clubOrgCodeDigits, 0, 4);
        foreach ($allClubs as $clubItem) {
            $candidateCode = preg_replace('/\D/', '', (string)($clubItem['nameShort'] ?? $clubItem['name_short'] ?? ''));
            if ($candidateCode === '' || strpos($candidateCode, $prefix) !== 0) {
                continue;
            }
            $suffix = substr($candidateCode, strlen($prefix));
            if ($suffix === '' || !preg_match('/^0+$/', $suffix)) {
                continue;
            }
            $candidateLogo = $toAbsLogoUrl($clubItem['logo'] ?? '');
            if ($candidateLogo !== '') {
                $comiteLogoUrl = $candidateLogo;
                break;
            }
        }
    }
}
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
            <h2 class="edition-doc-title-center"><?= $titreCompetition ?></h2>
        </div>
        <div class="edition-doc-header-right">
            <?php if ($showHeaderAffiliatedLogos && ($fftaLogoUrl !== '' || $comiteLogoUrl !== '')): ?>
                <?php if ($fftaLogoUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($fftaLogoUrl) ?>" alt="Logo FFTA" class="edition-doc-logo-affiliate">
                <?php endif; ?>
                <?php if ($comiteLogoUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($comiteLogoUrl) ?>" alt="Logo comité" class="edition-doc-logo-affiliate">
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>
