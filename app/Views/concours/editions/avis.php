<?php
/** Avis de concours - document officiel */
?>
<link href="/public/assets/css/concours-avis.css" rel="stylesheet">
<div class="edition-avis">
    <h1 class="text-center mb-4">Avis de concours</h1>

    <table class="table table-bordered">
        <tr>
            <th width="35%">Club organisateur</th>
            <td><?= htmlspecialchars($clubName ?: ($concours->club_name ?? 'Non renseigné')) ?></td>
        </tr>
        <tr>
            <th>Discipline</th>
            <td><?= htmlspecialchars($disciplineName ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Type de compétition</th>
            <td><?= htmlspecialchars($concours->type_competition_name ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Niveau championnat</th>
            <td><?= htmlspecialchars($niveauChampionnatName ?: 'Amical') ?></td>
        </tr>
        <tr>
            <th>Dates</th>
            <td><?= htmlspecialchars($concours->date_debut ?? '') ?> <?= ($concours->date_debut && $concours->date_fin) ? ' - ' : '' ?> <?= htmlspecialchars($concours->date_fin ?? '') ?></td>
        </tr>
    </table>

    <?php

    $departsAvis = isset($departsList) && is_array($departsList) ? $departsList : [];
    $getD = function($d, $key, $default = '') {
        return is_array($d) ? ($d[$key] ?? $default) : ($d->$key ?? $default);
    };
    if (!empty($departsAvis)): ?>
    <div class="mt-4">
        <h4>Liste des départs</h4>
        <table class="table table-bordered table-sm">
            <thead>
                <tr><th>N°</th><th>Date</th><th>Heure de greffe</th><th>Heure du départ</th></tr>
            </thead>
            <tbody>
                <?php foreach ($departsAvis as $d): ?>
                <?php
                $dateDep = $getD($d, 'date_depart', '');
                $heureGreffe = $getD($d, 'heure_greffe', '');
                $heureDepart = $getD($d, 'heure_depart', '');
                $numero = (int)$getD($d, 'numero_depart', 0);
                if ($dateDep && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateDep, $m)) {
                    $dateDep = $m[3] . '/' . $m[2] . '/' . $m[1];
                }
                $heureGreffe = $heureGreffe ? substr((string)$heureGreffe, 0, 5) : '';
                $heureDepart = $heureDepart ? substr((string)$heureDepart, 0, 5) : '';
                ?>
                <tr>
                    <td><?= $numero ?: '—' ?></td>
                    <td><?= htmlspecialchars($dateDep) ?></td>
                    <td><?= htmlspecialchars($heureGreffe) ?></td>
                    <td><?= htmlspecialchars($heureDepart) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php
    $avisInformations = trim(is_object($concours) ? ($concours->informations ?? '') : ($concours['informations'] ?? ''));
    $texteInformationsDefaut = "L'inscription à ce concours est obligatoire. Les inscriptions sont à effectuer auprès du club organisateur ou via le lien d'inscription fourni.";
    ?>
    <div class="mt-4">
        <h4>Informations</h4>
        <?php if ($avisInformations !== ''): ?>
        <div class="informations-avis-concours informations-avis-prewrap"><?= nl2br(htmlspecialchars($avisInformations)) ?></div>
        <?php else: ?>
        <p class="informations-avis-concours informations-avis-default"><?= htmlspecialchars($texteInformationsDefaut) ?></p>
        <?php endif; ?>
    </div>

    <!-- Tarification -->
    <?php
    $tarificationsList = is_object($concours) ? ($concours->tarifications ?? []) : ($concours['tarifications'] ?? []);
    if (is_object($tarificationsList)) {
        $tarificationsList = array_values((array)$tarificationsList);
    }
    $tarificationsList = is_array($tarificationsList) ? $tarificationsList : [];
    $tarifMap = [];
    foreach ($tarificationsList as $t) {
        $t = (array)$t;
        $key = ($t['type_public'] ?? '') . '_' . ($t['type_depart'] ?? '');
        $tarifMap[$key] = $t['prix'] ?? null;
    }
    $formatTarif = function ($value) {
        if ($value === null || $value === '') return 'Non renseigné';
        return number_format((float)$value, 2, ',', ' ') . ' EUR';
    };
    ?>
    <div class="form-group tarification-group">
        <label><h4>Tarification :</h4></label>
        <div class="table-responsive tarification-table-wrap">
            <table class="table table-bordered table-sm tarification-table">
                <colgroup>
                    <col class="tarification-type-col">
                    <col class="tarification-price-col">
                </colgroup>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th class="text-end tarification-nowrap">Tarif</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="tarification-type-cell">Adulte (de U21 a S3) 1er départ</td>
                        <td class="text-end tarification-nowrap"><?= htmlspecialchars($formatTarif($tarifMap['adulte_premier'] ?? null)) ?></td>
                    </tr>
                    <tr>
                        <td class="tarification-type-cell">Enfant (de U11 a U18) 1er départ</td>
                        <td class="text-end tarification-nowrap"><?= htmlspecialchars($formatTarif($tarifMap['enfant_premier'] ?? null)) ?></td>
                    </tr>
                    <tr>
                        <td class="tarification-type-cell">Adulte (de U21 a S3) départ supplémentaire</td>
                        <td class="text-end tarification-nowrap"><?= htmlspecialchars($formatTarif($tarifMap['adulte_supplementaire'] ?? null)) ?></td>
                    </tr>
                    <tr>
                        <td class="tarification-type-cell">Enfant (de U11 a U18) départ supplémentaire</td>
                        <td class="text-end tarification-nowrap"><?= htmlspecialchars($formatTarif($tarifMap['enfant_supplementaire'] ?? null)) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $lienInscription = trim(is_object($concours) ? ($concours->lien_inscription_cible ?? '') : ($concours['lien_inscription_cible'] ?? ''));
    if ($lienInscription !== ''): ?>
    <div class="mt-4 d-flex flex-wrap align-items-start gap-3">
        <div class="edition-avis-qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($lienInscription) ?>" alt="QR Code formulaire d'inscription" class="edition-avis-qr-image">
            <small class="text-muted">Formulaire d'inscription</small>
        </div>
        <div class="edition-avis-lien flex-grow-1">
            <strong>Lien d'inscription :</strong>
            <a href="<?= htmlspecialchars($lienInscription) ?>" target="_blank" rel="noopener noreferrer" class="edition-avis-link-break"><?= htmlspecialchars($lienInscription) ?></a>
        </div>
    </div>
    <?php endif; ?>

    <?php
    $lat = is_object($concours) ? ($concours->lieu_latitude ?? null) : ($concours['lieu_latitude'] ?? null);
    $lng = is_object($concours) ? ($concours->lieu_longitude ?? null) : ($concours['lieu_longitude'] ?? null);
    $lieuAdresse = is_object($concours) ? ($concours->lieu_competition ?? $concours->lieu ?? '') : ($concours['lieu_competition'] ?? $concours['lieu'] ?? '');
    $hasGps = ($lat !== null && $lat !== '' && $lng !== null && $lng !== '');
    if ($hasGps): $lat = (float)$lat; $lng = (float)$lng; ?>
    <div class="mt-4">
        <h4>Plan – S'y rendre</h4>
        <p class="mb-2"><strong>Coordonnées GPS :</strong> <?= htmlspecialchars($lat) ?>, <?= htmlspecialchars($lng) ?></p>
        <?php if (trim((string)$lieuAdresse) !== ''): ?>
        <p class="mb-2"><strong>Lieu :</strong> <?= htmlspecialchars($lieuAdresse) ?></p>
        <?php endif; ?>
        <p class="mb-0">
            <a href="https://www.google.com/maps?q=<?= urlencode($lat . ',' . $lng) ?>" target="_blank" rel="noopener noreferrer" class="me-3">Voir sur Google Maps</a>
            <a href="https://www.openstreetmap.org/?mlat=<?= urlencode($lat) ?>&amp;mlon=<?= urlencode($lng) ?>&amp;zoom=16" target="_blank" rel="noopener noreferrer">Voir sur OpenStreetMap</a>
        </p>
    </div>
    <?php endif; ?>

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

    $clubOrgId = is_object($concours) ? ($concours->club_organisateur ?? null) : ($concours['club_organisateur'] ?? null);
    $clubOrganisateur = null;
    if ($clubOrgId !== null && $clubOrgId !== '') {
        if (isset($clubsMap) && is_array($clubsMap)) {
            $clubOrganisateur = $clubsMap[$clubOrgId] ?? $clubsMap[(string)$clubOrgId] ?? $clubsMap[(int)$clubOrgId] ?? null;
        }
        if (!$clubOrganisateur && is_array($allClubs)) {
            foreach ($allClubs as $clubItem) {
                $clubItemId = $clubItem['id'] ?? $clubItem['_id'] ?? null;
                if ($clubItemId !== null && ((string)$clubItemId === (string)$clubOrgId)) {
                    $clubOrganisateur = $clubItem;
                    break;
                }
            }
        }
    }

    $clubOrganisateurCode = '';
    if (is_array($clubOrganisateur)) {
        $clubOrganisateurCode = trim((string)($clubOrganisateur['nameShort'] ?? $clubOrganisateur['name_short'] ?? ''));
    }
    if ($clubOrganisateurCode === '' && is_string($clubOrgId)) {
        $clubOrganisateurCode = trim($clubOrgId);
    }
    $clubOrganisateurCodeDigits = preg_replace('/\D/', '', $clubOrganisateurCode);

    $niveauChampionnatRaw = trim((string)($niveauChampionnatName ?? ''));
    $niveauChampionnatNorm = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $niveauChampionnatRaw) ?: $niveauChampionnatRaw);
    $isChampionnatRegional = strpos($niveauChampionnatNorm, 'regional') !== false;
    $isChampionnatDepartemental = strpos($niveauChampionnatNorm, 'departemental') !== false;

    $fftaLogoUrl = '';
    foreach ($allClubs as $clubItem) {
        $clubName = strtolower(trim((string)($clubItem['name'] ?? '')));
        $clubShortName = strtolower(trim((string)($clubItem['nameShort'] ?? $clubItem['name_short'] ?? '')));
        if (
            strpos($clubName, 'ffta') !== false ||
            $clubShortName === 'ffta'
        ) {
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
            if ($candidateCode === '') {
                continue;
            }
            if (strpos($candidateCode, $prefix) !== 0) {
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
</div>
