<?php
/**
 * Bloc de fin de document d'édition concours
 * Nombre total d'archers, Club organisateur, Arbitre responsable, Liste arbitres, Liste entraîneurs
 */
$arbitres = isset($concours->arbitres) && is_array($concours->arbitres) ? $concours->arbitres : [];
$arbitres = array_map(function($a) { return is_array($a) ? $a : (array)$a; }, $arbitres);

// Arbitre responsable : celui avec responsable=true et rôle Arbitre (Jury_arbitre=2) ou Jury (1)
$arbitreResponsable = null;
foreach ($arbitres as $a) {
    if (!empty($a['responsable']) && in_array((int)($a['Jury_arbitre'] ?? $a['jury_arbitre'] ?? 2), [1, 2])) {
        $arbitreResponsable = $a;
        break;
    }
}
// Si aucun responsable, prendre le premier arbitre (rôle 1 ou 2)
if (!$arbitreResponsable) {
    foreach ($arbitres as $a) {
        if (in_array((int)($a['Jury_arbitre'] ?? $a['jury_arbitre'] ?? 2), [1, 2])) {
            $arbitreResponsable = $a;
            break;
        }
    }
}

// Liste arbitres : Jury_arbitre 1 (Jury) ou 2 (Arbitre)
$listeArbitres = array_filter($arbitres, function($a) {
    return in_array((int)($a['Jury_arbitre'] ?? $a['jury_arbitre'] ?? 2), [1, 2]);
});
// Liste entraîneurs : Jury_arbitre 3
$listeEntraineurs = array_filter($arbitres, function($a) {
    return (int)($a['Jury_arbitre'] ?? $a['jury_arbitre'] ?? 0) === 3;
});

// Civilité selon le sexe : F = Me. (Madame), M = M. (Monsieur)
$getCivilite = function($a) {
    $s = $a['sexe'] ?? $a['gender'] ?? '';
    return ($s === 'F') ? 'Me.' : 'M.';
};
$formatNom = function($a) use ($getCivilite) {
    $nom = $a['nom_display'] ?? trim(($a['first_name'] ?? '') . ' ' . ($a['name'] ?? '')) ?: ($a['IDLicence'] ?? $a['id_licence'] ?? '');
    $prefix = $getCivilite($a);
    return $prefix . ' ' . mb_strtoupper($nom);
};
$formatLigne = function($liste) use ($formatNom) {
    $parts = [];
    foreach ($liste as $a) {
        $lic = trim($a['IDLicence'] ?? $a['id_licence'] ?? '');
        $parts[] = $lic . ' - ' . $formatNom($a);
    }
    return implode(' / ', $parts) . (empty($parts) ? '' : ' /');
};

$clubOrganisateurId = $concours->club_organisateur ?? null;
$clubOrganisateurData = $clubOrganisateurId ? ($clubsMap[$clubOrganisateurId] ?? $clubsMap[(string)$clubOrganisateurId] ?? null) : null;
$clubOrgCode = $clubOrganisateurData ? ($clubOrganisateurData['nameShort'] ?? $clubOrganisateurData['name_short'] ?? '') : '';
$clubNameStr = is_scalar($clubName ?? null) ? (string)($clubName ?? '') : '';
$clubOrgDisplay = ($clubOrgCode ? $clubOrgCode . '  ' : '') . $clubNameStr;
$arbitreRespDisplay = $arbitreResponsable ? $formatNom($arbitreResponsable) : '';
// Pour le classement : nombre après filtres (régional/départemental, et top3 si actif)
if ($doc === 'classement') {
    if (!empty($top3ParCategorie) && isset($byCategorie)) {
        $nbArchers = 0;
        foreach ($byCategorie as $items) { $nbArchers += count($items); }
    } else {
        $nbArchers = isset($inscriptions1erTir) ? count($inscriptions1erTir) : 0;
    }
} else {
    $nbArchers = isset($inscriptions) ? count($inscriptions) : 0;
}
?>
<div class="edition-doc-fin mt-4 pt-4">
    <table class="table table-borderless">
        <?php if ($doc === 'classement'): ?>
            <tr>
                <td colspan="2">
                    <strong>Nombre total d'archers : </strong><span class="mb-0 mt-1"><?= htmlspecialchars($nbArchers) ?></span>
                </td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="mt-3">
        <strong>Liste des arbitres</strong>
        <p class="mb-0 mt-1"><?= htmlspecialchars($formatLigne($listeArbitres)) ?></p>
    </div>

    <div class="mt-3">
        <strong>Liste des entraîneurs</strong>
        <p class="mb-0 mt-1"><?= htmlspecialchars($formatLigne($listeEntraineurs)) ?></p>
    </div>

    <?php if (($doc ?? '') === 'avis'): ?>
    <?php
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

    $clubOrganisateurCodeDigits = preg_replace('/\D/', '', (string)$clubOrgCode);
    $niveauChampionnatRaw = trim((string)($niveauChampionnatName ?? ''));
    $niveauChampionnatNorm = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $niveauChampionnatRaw) ?: $niveauChampionnatRaw);
    $isChampionnatRegional = strpos($niveauChampionnatNorm, 'regional') !== false;
    $isChampionnatDepartemental = strpos($niveauChampionnatNorm, 'departemental') !== false;

    $fftaLogoUrl = '';
    foreach ($allClubs as $clubItem) {
        $clubShortCode = preg_replace('/\D/', '', (string)($clubItem['nameShort'] ?? $clubItem['name_short'] ?? ''));
        if ($clubShortCode === '0000001') {
            $fftaLogoUrl = $toAbsLogoUrl($clubItem['logo'] ?? '');
            if ($fftaLogoUrl !== '') {
                break;
            }
        }
    }

    $comiteLogoUrl = '';
    if (($isChampionnatRegional || $isChampionnatDepartemental) && strlen($clubOrganisateurCodeDigits) >= 4) {
        $prefix = $isChampionnatRegional
            ? substr($clubOrganisateurCodeDigits, 0, 2)
            : substr($clubOrganisateurCodeDigits, 0, 4);
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
    ?>
    <?php if ($fftaLogoUrl !== '' || $comiteLogoUrl !== ''): ?>
    <div class="edition-avis-footer-logos">
        <?php if ($fftaLogoUrl !== ''): ?>
        <img src="<?= htmlspecialchars($fftaLogoUrl) ?>" alt="Logo FFTA" class="edition-avis-footer-logo">
        <?php endif; ?>
        <?php if ($comiteLogoUrl !== ''): ?>
        <img src="<?= htmlspecialchars($comiteLogoUrl) ?>" alt="Logo comité" class="edition-avis-footer-logo">
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
