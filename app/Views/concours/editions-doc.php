<?php
/**
 * Document à imprimer - avec en-tête
 * En-tête : logo club organisateur (gauche) | titre compétition (centre)
 * Fin de document : infos (nb archers, club, arbitres, entraîneurs)
 * $doc = avis | feuilles-marques | liste-participants | scores | classement
 */
$concoursId = $concours->id ?? $concours->_id ?? null;
$departsRaw = is_object($concours) ? ($concours->departs ?? []) : ($concours['departs'] ?? []);
$departsList = array_values(is_array($departsRaw) ? $departsRaw : (array)$departsRaw);
$docTitles = [
    'avis' => 'Avis de concours',
    'feuilles-marques' => 'Feuilles de marques',
    'liste-participants' => 'Liste des participants',
    'scores' => 'Scores',
    'classement' => 'Classement'
];
$docTitle = $docTitles[$doc] ?? 'Document';
$version = defined('APP_VERSION') ? APP_VERSION : ($_ENV['APP_VERSION'] ?? '1.0');
$dateFooter = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($docTitle) ?> - <?= htmlspecialchars($concours->titre_competition ?? $concours->nom ?? 'Concours') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* En-tête - affichage écran (prévisualisation) */
        .edition-doc-header {
            display: block;
            border-bottom: 1px solid #ddd;
            padding: 8px 15px;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }
        .edition-doc-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .edition-doc-header-left, .edition-doc-header-center {
            flex: 1;
            display: flex;
            align-items: center;
        }
        .edition-doc-header-left { justify-content: flex-start; }
        .edition-doc-header-center { justify-content: center; }
        .edition-doc-header-center .edition-doc-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        /* Logo : taille agrandie pour l'en-tête des éditions */
        .edition-doc-logo {
            height: 190px;
            max-width: 380px;
            object-fit: contain;
        }
        /* Espace libre autour du logo */
        .edition-doc-header-left {
            min-width: 150px;
            padding: 0 8px;
        }
        .edition-doc-logo-placeholder { font-size: 10pt; color: #6c757d; }
        /* Table structure pour impression (invisible à l'écran) */
        .edition-doc-print-table {
            width: 100%;
            border-collapse: collapse;
        }
        .edition-doc-print-table td {
            border: none;
        }
        /* Liste des participants : taille de police réduite dans les tableaux */
        .edition-liste-participants table,
        .edition-liste-participants table th,
        .edition-liste-participants table td,
        .edition-classement table,
        .edition-scores table,
        .edition-scores table th,
        .edition-scores table td,
        .edition-classement table th,
        .edition-classement table td {
            font-size: 0.8rem;
        }
        /* Lisibilité : griser 1 ligne sur 2 dans les tableaux (tr + td pour override Bootstrap) */
        .edition-doc-print-tbody table tbody tr:nth-child(even) td,
        .edition-liste-participants table tbody tr:nth-child(even) td,
        .edition-avis table tbody tr:nth-child(even) td,
        .edition-scores table tbody tr:nth-child(even) td,
        .edition-doc-fin table tbody tr:nth-child(even) td {
            background-color: #e9ecef !important;
        }
        .edition-scores-block { page-break-inside: avoid; }
        @media print {
            @page {
                margin: 15mm 15mm 20mm 15mm;
                @bottom-left {
                    content: "<?= $dateFooter ?>";
                    font-size: 9pt;
                    color: #666;
                }
                @bottom-center {
                    content: "Imprimé par Arc Training <?= htmlspecialchars($version) ?>";
                    font-size: 9pt;
                    color: #666;
                }
                @bottom-right {
                    content: "Page " counter(page) " / " counter(pages);
                    font-size: 9pt;
                    color: #666;
                }
            }
            /* Liste des participants : pas de footer imprimé (date, page, etc.) */
            @page edition-liste-participants {
                margin: 15mm 15mm 20mm 15mm;
            }
            body.edition-doc-liste-participants {
                page: edition-liste-participants;
            }
            .no-print { display: none !important; }
            body { font-size: 11pt; }
            .page-break { page-break-after: always; }
            /* En-tête uniquement sur la 1re page (table-row-group = pas de répétition) */
            .edition-doc-print-table {
                display: table;
                width: 100%;
            }
            .edition-doc-print-thead {
                display: table-row-group;
            }
            .edition-doc-print-thead td {
                padding: 0;
                vertical-align: top;
            }
            .edition-doc-print-tbody {
                display: table-row-group;
            }
            .edition-doc-print-tbody > tr > td {
                display: block;
                padding: 0;
            }
            .edition-doc-header {
                display: block !important;
                position: static !important;
                background: #fff;
                border-bottom: 1px solid #ddd;
                padding: 4px 0 8px 0;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .edition-doc-header-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .edition-doc-header-left, .edition-doc-header-center {
                flex: 1;
                display: flex;
                align-items: center;
            }
            .edition-doc-header-left { justify-content: flex-start; }
            .edition-doc-header-center { justify-content: center; }
            .edition-doc-header-center .edition-doc-title {
                margin: 0;
                font-size: 14pt;
                font-weight: 600;
            }
            /* Logo : taille agrandie à l'impression */
            .edition-doc-logo {
                height: 44mm;
                max-width: 88mm;
                object-fit: contain;
            }
            .edition-doc-header-left {
                min-width: 32mm;
                padding: 0 2mm;
            }
            .edition-doc-logo-placeholder {
                font-size: 10pt;
            }
            .edition-liste-participants table,
            .edition-liste-participants table th,
            .edition-liste-participants table td {
                font-size: 9pt;
            }
            .edition-doc-print-tbody table tbody tr:nth-child(even) td,
            .edition-liste-participants table tbody tr:nth-child(even) td,
            .edition-avis table tbody tr:nth-child(even) td,
            .edition-scores table tbody tr:nth-child(even) td {
                background-color: #e9ecef !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        body { padding: 1rem; }
    </style>
</head>
<body<?= ($doc === 'liste-participants') ? ' class="edition-doc-liste-participants"' : '' ?>>
    <div class="no-print mb-3">
        <?php if ($doc === 'liste-participants'): ?>
        <?php
        $baseListeUrl = '/concours/' . (int)$concoursId . '/editions?doc=liste-participants';
        $currentTriListe = $triListeParticipants ?? 'club';
        $currentDepartListe = isset($departFilterListe) && $departFilterListe !== '' && $departFilterListe !== 'tout' && $departFilterListe !== 'all' ? $departFilterListe : 'tout';
        ?>
        <label class="me-2">Départ :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <option value="<?= htmlspecialchars($baseListeUrl . '&tri=' . $currentTriListe . '&depart=tout') ?>"<?= $currentDepartListe === 'tout' ? ' selected' : '' ?>>Tous</option>
            <?php
            $departsListe = isset($departsListListe) && is_array($departsListListe) ? $departsListListe : (isset($departsList) && is_array($departsList) ? $departsList : []);
            foreach ($departsListe as $d):
                $num = (int)($d['numero_depart'] ?? 0);
                if ($num <= 0) continue;
                $url = $baseListeUrl . '&tri=' . $currentTriListe . '&depart=' . $num;
                $sel = $currentDepartListe === (string)$num ? ' selected' : '';
            ?>
            <option value="<?= htmlspecialchars($url) ?>"<?= $sel ?>><?= $num ?></option>
            <?php endforeach; ?>
        </select>
        <label class="me-2">Tri :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <?php
            $triOptions = ['club' => 'Par club', 'depart' => 'Par départ', 'categorie' => 'Par catégorie'];
            foreach ($triOptions as $val => $label):
                $url = $baseListeUrl . '&depart=' . $currentDepartListe . '&tri=' . $val;
                $sel = $currentTriListe === $val ? ' selected' : '';
            ?>
            <option value="<?= htmlspecialchars($url) ?>"<?= $sel ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if ($doc === 'scores'): ?>
        <?php
        $baseScoresUrl = '/concours/' . (int)$concoursId . '/editions?doc=scores';
        $currentTriScores = $triScores ?? 'club';
        $currentDepartScores = isset($departFilterScores) && $departFilterScores !== '' && $departFilterScores !== 'tout' && $departFilterScores !== 'all' ? $departFilterScores : 'tout';
        ?>
        <label class="me-2">Départ :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <option value="<?= htmlspecialchars($baseScoresUrl . '&tri=' . $currentTriScores . '&depart=tout') ?>"<?= $currentDepartScores === 'tout' ? ' selected' : '' ?>>Tous</option>
            <?php
            $departsScores = isset($departsListScores) && is_array($departsListScores) ? $departsListScores : (isset($departsList) && is_array($departsList) ? $departsList : []);
            foreach ($departsScores as $d):
                $num = (int)($d['numero_depart'] ?? 0);
                if ($num <= 0) continue;
                $url = $baseScoresUrl . '&tri=' . $currentTriScores . '&depart=' . $num;
                $sel = $currentDepartScores === (string)$num ? ' selected' : '';
            ?>
            <option value="<?= htmlspecialchars($url) ?>"<?= $sel ?>><?= $num ?></option>
            <?php endforeach; ?>
        </select>
        <label class="me-2">Tri :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <?php
            $triOptionsScores = ['club' => 'Par club', 'categorie' => 'Par catégorie', 'depart' => 'Par départ'];
            foreach ($triOptionsScores as $val => $label):
                $url = $baseScoresUrl . '&depart=' . $currentDepartScores . '&tri=' . $val;
                $sel = $currentTriScores === $val ? ' selected' : '';
            ?>
            <option value="<?= htmlspecialchars($url) ?>"<?= $sel ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if ($doc === 'classement'): ?>
        <label class="me-2">Type de classement :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <?php
            $baseClassementUrl = '/concours/' . (int)$concoursId . '/editions?doc=classement';
            $top3Suffix = (!empty($top3ParCategorie)) ? '&top3=1' : '';
            $types = [
                'general' => 'Général (tous les archers)',
                'regional' => 'Régional (2 premiers chiffres du club de l\'archer = club organisateur)',
                'departemental' => 'Départemental (4 premiers chiffres du club de l\'archer = club organisateur)'
            ];
            foreach ($types as $val => $label):
                $url = $baseClassementUrl . '&type=' . $val . $top3Suffix;
                $sel = ($typeClassement ?? 'general') === $val ? ' selected' : '';
            ?>
            <option value="<?= htmlspecialchars($url) ?>"<?= $sel ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="ms-3 me-2">
            <input type="checkbox" <?= !empty($top3ParCategorie) ? 'checked' : '' ?> onchange="var u='/concours/<?= (int)$concoursId ?>/editions?doc=classement&type=<?= htmlspecialchars($typeClassement ?? 'general') ?>'; if(this.checked) u+='&top3=1'; location.href=u;">
            Top 3 par catégorie
        </label>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print me-1"></i>Imprimer
        </button>
        <a href="/concours/<?= (int)$concoursId ?>/editions" class="btn btn-secondary ms-2">Retour aux éditions</a>
    </div>

    <table class="edition-doc-print-table">
        <thead class="edition-doc-print-thead">
            <tr>
                <td><?php include __DIR__ . '/editions/_header-footer.php'; ?></td>
            </tr>
        </thead>
        <tbody class="edition-doc-print-tbody">
            <tr>
                <td>
<?php
switch ($doc) {
    case 'avis':
        include __DIR__ . '/editions/avis.php';
        break;
    case 'feuilles-marques':
        include __DIR__ . '/editions/feuilles-marques.php';
        break;
    case 'liste-participants':
        include __DIR__ . '/editions/liste-participants.php';
        break;
    case 'scores':
        include __DIR__ . '/editions/scores.php';
        break;
    case 'classement':
        include __DIR__ . '/editions/classement.php';
        break;
    default:
        echo '<p>Document inconnu.</p>';
}
?>
                    <?php if ($doc !== 'liste-participants') include __DIR__ . '/editions/_edition-doc-fin.php'; ?>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
