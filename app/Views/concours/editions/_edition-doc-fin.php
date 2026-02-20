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
        <tr>
            <th class="align-top" style="width: 35%;">Nombre total d'archers</th>
            <td><?= (int)$nbArchers ?></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr>
            <th class="align-top">Club Organisateur</th>
            <th class="align-top">Arbitre Responsable</th>
        </tr>
        <tr>
            <td><?= htmlspecialchars($clubOrgDisplay) ?></td>
            <td><?= htmlspecialchars($arbitreRespDisplay) ?></td>
        </tr>
    </table>

    <div class="mt-3">
        <strong>Liste des arbitres</strong>
        <p class="mb-0 mt-1"><?= htmlspecialchars($formatLigne($listeArbitres)) ?></p>
    </div>

    <div class="mt-3">
        <strong>Liste des entraîneurs</strong>
        <p class="mb-0 mt-1"><?= htmlspecialchars($formatLigne($listeEntraineurs)) ?></p>
    </div>
</div>
