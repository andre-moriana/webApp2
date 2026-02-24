<?php
/** Avis de concours - document officiel */
?>
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
            <td><?= htmlspecialchars($typeCompetitionName ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Niveau championnat</th>
            <td><?= htmlspecialchars($niveauChampionnatName ?: 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Lieu</th>
            <td><?= htmlspecialchars($concours->lieu_competition ?? $concours->lieu ?? 'Non renseigné') ?></td>
        </tr>
        <tr>
            <th>Dates</th>
            <td><?= htmlspecialchars($concours->date_debut ?? '') ?> <?= ($concours->date_debut && $concours->date_fin) ? ' - ' : '' ?> <?= htmlspecialchars($concours->date_fin ?? '') ?></td>
        </tr>
    </table>

    <?php
    $avisInformations = trim(is_object($concours) ? ($concours->informations ?? '') : ($concours['informations'] ?? ''));
    $texteInformationsDefaut = "L'inscription à ce concours est obligatoire. Les inscriptions sont à effectuer auprès du club organisateur ou via le lien d'inscription fourni.";
    ?>
    <div class="mt-4">
        <h4>Informations</h4>
        <?php if ($avisInformations !== ''): ?>
        <div class="informations-avis-concours" style="white-space: pre-wrap; line-height: 1.35;"><?= nl2br(htmlspecialchars($avisInformations)) ?></div>
        <?php else: ?>
        <p class="informations-avis-concours" style="line-height: 1.35;"><?= htmlspecialchars($texteInformationsDefaut) ?></p>
        <?php endif; ?>
    </div>

    <?php
    $lienInscription = trim(is_object($concours) ? ($concours->lien_inscription_cible ?? '') : ($concours['lien_inscription_cible'] ?? ''));
    if ($lienInscription !== ''): ?>
    <div class="mt-4 d-flex flex-wrap align-items-start gap-3">
        <div class="edition-avis-qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($lienInscription) ?>" alt="QR Code formulaire d'inscription" style="display: block; width: 120px; height: 120px;">
            <small class="text-muted">Formulaire d'inscription</small>
        </div>
        <div class="edition-avis-lien flex-grow-1">
            <strong>Lien d'inscription :</strong>
            <a href="<?= htmlspecialchars($lienInscription) ?>" target="_blank" rel="noopener noreferrer" style="word-break: break-all;"><?= htmlspecialchars($lienInscription) ?></a>
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
</div>
