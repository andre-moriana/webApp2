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
        .edition-doc-header-center { justify-content: center; text-align: center; }
        .edition-doc-header-center .edition-doc-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .edition-doc-header-center .edition-doc-subtitle { text-align: center; }
        /* Logo : taille agrandie pour l'en-tête des éditions */
        .edition-doc-logo {
            height: 190px;
            max-width: 380px;
            object-fit: contain;
        }
        /* Feuilles de marques : en-tête réduit pour tenir sur une page */
        body.edition-doc-feuilles-marques .edition-doc-logo {
            height: 55px;
            max-width: 110px;
        }
        body.edition-doc-feuilles-marques .edition-doc-header .edition-doc-title { font-size: 0.9rem; }
        body.edition-doc-feuilles-marques .edition-doc-header .edition-doc-subtitle { font-size: 0.7rem; }
        body.edition-doc-feuilles-marques .edition-doc-header { padding: 4px 8px 6px; margin-bottom: 0.5rem; }
        body.edition-doc-feuilles-marques .edition-doc-header-left { min-width: 80px; padding: 0 4px; }
        /* Nature : masquer l'en-tête (logo + titre) à l'écran */
        body.edition-doc-feuilles-marques-nature .edition-doc-print-thead { display: none; }
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
        /* Colonnes de largeur identique dans tous les tableaux d'édition */
        .edition-liste-participants table,
        .edition-scores table,
        .edition-classement table {
            table-layout: fixed;
            width: 100%;
        }
        .edition-liste-participants table th,
        .edition-liste-participants table td,
        .edition-scores table th,
        .edition-scores table td,
        .edition-classement table th,
        .edition-classement table td {
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .edition-liste-participants table th:nth-child(1),
        .edition-liste-participants table td:nth-child(1),
        .edition-scores table th:nth-child(1),
        .edition-scores table td:nth-child(1),
        .edition-classement table th:nth-child(1),
        .edition-classement table td:nth-child(1) { width: 4%; min-width: 2rem; }
        .edition-liste-participants table th:nth-child(2),
        .edition-liste-participants table td:nth-child(2),
        .edition-scores table th:nth-child(2),
        .edition-scores table td:nth-child(2),
        .edition-classement table th:nth-child(2),
        .edition-classement table td:nth-child(2) { width: 20%; }
        .edition-liste-participants table th:nth-child(3),
        .edition-liste-participants table td:nth-child(3),
        .edition-scores table th:nth-child(3),
        .edition-scores table td:nth-child(3),
        .edition-classement table th:nth-child(3),
        .edition-classement table td:nth-child(3) { width: 11%; }
        .edition-liste-participants table th:nth-child(4),
        .edition-liste-participants table td:nth-child(4),
        .edition-scores table th:nth-child(4),
        .edition-scores table td:nth-child(4),
        .edition-classement table th:nth-child(4),
        .edition-classement table td:nth-child(4) { width: 15%; }
        .edition-liste-participants table th:nth-child(5),
        .edition-liste-participants table td:nth-child(5),
        .edition-scores table th:nth-child(5),
        .edition-scores table td:nth-child(5),
        .edition-classement table th:nth-child(5),
        .edition-classement table td:nth-child(5) { width: 12%; }
        .edition-liste-participants table th:nth-child(6),
        .edition-liste-participants table td:nth-child(6),
        .edition-scores table th:nth-child(6),
        .edition-scores table td:nth-child(6),
        .edition-classement table th:nth-child(6),
        .edition-classement table td:nth-child(6) { width: 8%; }
        /* Liste des participants : Départ plus étroit, Catégorie plus large */
        .edition-liste-participants table th:nth-child(5),
        .edition-liste-participants table td:nth-child(5) { width: 6%; }
        .edition-liste-participants table th:nth-child(6),
        .edition-liste-participants table td:nth-child(6) { width: 14%; }
        .edition-liste-participants table th:nth-child(7),
        .edition-liste-participants table td:nth-child(7),
        .edition-scores table th:nth-child(7),
        .edition-scores table td:nth-child(7),
        .edition-classement table th:nth-child(7),
        .edition-classement table td:nth-child(7) { width: 8%; }
        .edition-liste-participants table th:nth-child(n+8),
        .edition-liste-participants table td:nth-child(n+8),
        .edition-scores table th:nth-child(n+8),
        .edition-scores table td:nth-child(n+8),
        .edition-classement table th:nth-child(n+8),
        .edition-classement table td:nth-child(n+8) { width: 5%; }
        /* Tableau liste des départs (avis) */
        .edition-avis table.table-sm {
            table-layout: fixed;
            width: 100%;
        }
        .edition-avis table.table-sm th:nth-child(1),
        .edition-avis table.table-sm td:nth-child(1) { width: 8%; }
        .edition-avis table.table-sm th:nth-child(2),
        .edition-avis table.table-sm td:nth-child(2) { width: 25%; }
        .edition-avis table.table-sm th:nth-child(3),
        .edition-avis table.table-sm td:nth-child(3) { width: 25%; }
        .edition-avis table.table-sm th:nth-child(4),
        .edition-avis table.table-sm td:nth-child(4) { width: 25%; }
        /* Lisibilité : griser 1 ligne sur 2 dans les tableaux (tr + td pour override Bootstrap) */
        .edition-doc-print-tbody table tbody tr:nth-child(even) td,
        .edition-liste-participants table tbody tr:nth-child(even) td,
        .edition-avis table tbody tr:nth-child(even) td,
        .edition-scores table tbody tr:nth-child(even) td,
        .edition-doc-fin table tbody tr:nth-child(even) td {
            background-color: #e9ecef !important;
        }
        /* Tableau scores : griser une ligne sur deux (classe sur les lignes paires) */
        .edition-scores .edition-scores-table tbody tr.edition-scores-row-even td {
            background-color: #e9ecef !important;
        }
        .edition-scores-block { page-break-inside: avoid; }
        /* Feuilles de marques Salle : 4 tableaux côte à côte, page paysage, en-tête réduit pour tenir sur une page */
        .edition-feuilles-marques .feuille-marque-salle > p.text-center,
        .edition-feuilles-marques .feuille-marque-salle-landscape > p.text-center {
            font-size: 0.75rem;
            margin-bottom: 0.35rem !important;
        }
        .edition-feuilles-marques .feuille-marque-salle-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem 0.75rem;
            align-items: start;
        }
        .edition-feuilles-marques .feuille-marque-archer-block { page-break-inside: avoid; }
        .edition-feuilles-marques .feuille-marque-table-volees {
            font-size: 0.7rem;
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
        }
        .edition-feuilles-marques .feuille-marque-table-volees th,
        .edition-feuilles-marques .feuille-marque-table-volees td {
            border: 1px solid #333;
            padding: 7px 1px;
            min-height: 1.3em;
            box-sizing: border-box;
            text-align: center;
        }
        .edition-feuilles-marques .feuille-marque-archer-header {
            font-size: 0.65rem;
            padding-bottom: 0.2rem !important;
            margin-bottom: 0.35rem !important;
        }
        .edition-feuilles-marques .feuille-marque-blason { margin-left: 0.35rem; font-weight: 600; font-size: 0.65rem; }
        .edition-feuilles-marques .feuille-marque-signatures { margin-top: 0.25rem; font-size: 0.6rem; }
        .edition-feuilles-marques .feuille-marque-ligne-resume td { text-align: center; font-size: 0.65rem; }
        .edition-feuilles-marques .feuille-marque-ligne-resume-valeurs td { min-height: 7.3em; padding: 20px 1px; }
        /* Nature : 2 archers par page, grille 2 colonnes */
        .edition-feuilles-marques .feuille-marque-nature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem 1rem;
            align-items: start;
        }
        /* Nature : en-tête réduit de moitié (titre page + bloc archer) */
        .edition-feuilles-marques .feuille-marque-nature > p.text-center {
            font-size: 0.65rem;
            margin-bottom: 0.5rem !important;
        }
        .edition-feuilles-marques .feuille-marque-nature .feuille-marque-archer-header {
            font-size: 0.5rem;
            padding-bottom: 0.25rem !important;
            margin-bottom: 0.5rem !important;
        }
        .edition-feuilles-marques .feuille-marque-nature .feuille-marque-blason { font-size: 0.5rem; }
        .edition-feuilles-marques .feuille-marque-nature .feuille-marque-signatures { font-size: 0.55rem; margin-top: 0.25rem; }
        /* Nature : 21 volées, police réduite pour tenir sur la page */
        .edition-feuilles-marques .feuille-marque-table-nature { font-size: 0.65rem; }
        .edition-feuilles-marques .feuille-marque-table-nature th,
        .edition-feuilles-marques .feuille-marque-table-nature td { padding: 2px 3px; min-height: 1.3em; box-sizing: border-box; }
        /* Nature : logo en fond de tableau, transparence 20 % (overlay blanc 80 %) */
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap {
            position: relative;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 35%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature {
            background: transparent !important;
        }
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature thead,
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature tbody,
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature tr,
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature th,
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature td {
            background-color: transparent !important;
            background: transparent !important;
        }
        /* Lignes paires grisées (priorité sur transparent pour voir les bandes) */
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature tbody tr.feuille-marque-row-even td {
            background-color: #e9ecef !important;
            background: #e9ecef !important;
        }
        .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature tr.table-secondary td {
            background-color: rgba(233, 236, 239, 0.85) !important;
        }
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
            /* Feuilles de marques Salle : format paysage */
            @page feuilles-marques-landscape {
                size: landscape;
                margin: 15mm 12mm 20mm 12mm;
            }
            .edition-feuilles-marques .feuille-marque-salle-landscape {
                page: feuilles-marques-landscape;
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
            /* En-tête : répétition sur toutes les pages pour feuilles de marques */
            .edition-doc-print-table {
                display: table;
                width: 100%;
            }
            .edition-doc-print-thead {
                display: table-row-group;
            }
            body.edition-doc-feuilles-marques .edition-doc-print-thead {
                display: table-header-group;
            }
            /* Nature : masquer l'en-tête document (logo + titre) */
            body.edition-doc-feuilles-marques-nature .edition-doc-print-thead {
                display: none !important;
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
            .edition-doc-header-center { justify-content: center; text-align: center; }
            .edition-doc-header-center .edition-doc-title {
                margin: 0;
                font-size: 14pt;
                font-weight: 600;
            }
            .edition-doc-header-center .edition-doc-subtitle { text-align: center; }
            /* Logo : taille agrandie à l'impression */
            .edition-doc-logo {
                height: 44mm;
                max-width: 88mm;
                object-fit: contain;
            }
            /* Feuilles de marques : logo réduit de 50 % à l'impression */
            body.edition-doc-feuilles-marques .edition-doc-logo {
                height: 12mm;
                max-width: 24mm;
            }
            body.edition-doc-feuilles-marques .edition-doc-header {
                padding: 2px 0 4px 0 !important;
                margin-bottom: 0 !important;
            }
            body.edition-doc-feuilles-marques .edition-doc-header-center .edition-doc-title { font-size: 9pt !important; }
            body.edition-doc-feuilles-marques .edition-doc-header-center .edition-doc-subtitle { font-size: 7pt !important; }
            .edition-doc-header-left {
                min-width: 32mm;
                padding: 0 2mm;
            }
            body.edition-doc-feuilles-marques .edition-doc-header-left {
                min-width: 14mm;
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
            .edition-scores table tbody tr:nth-child(even) td,
            .edition-scores .edition-scores-table tbody tr.edition-scores-row-even td {
                background-color: #e9ecef !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            /* Nature : lignes paires grisées à l'impression */
            .edition-feuilles-marques .feuille-marque-table-nature-logo-wrap .feuille-marque-table-nature tbody tr.feuille-marque-row-even td {
                background-color: #e9ecef !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        body { padding: 1rem; }
    </style>
</head>
<body<?php
$bodyClasses = [];
if ($doc === 'liste-participants') $bodyClasses[] = 'edition-doc-liste-participants';
if ($doc === 'feuilles-marques') $bodyClasses[] = 'edition-doc-feuilles-marques';
if ($doc === 'feuilles-marques' && ($disciplineAbv ?? '') === 'N') $bodyClasses[] = 'edition-doc-feuilles-marques-nature';
echo empty($bodyClasses) ? '' : ' class="' . implode(' ', $bodyClasses) . '"';
?>>
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
        <?php if ($doc === 'feuilles-marques'): ?>
        <?php
        $baseFeuillesUrl = '/concours/' . (int)$concoursId . '/editions?doc=feuilles-marques';
        $currentDepartFeuilles = $departFeuilles ?? 'tout';
        $currentSerieFeuilles = $serieFeuilles ?? 'toutes';
        $currentCibleFeuilles = $cibleFeuilles ?? 'toutes';
        $ciblesListFeuilles = $ciblesListFeuilles ?? [];
        $departsListFeuilles = $departsListFeuilles ?? [];
        ?>
        <label class="me-2">Départ :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <option value="<?= htmlspecialchars($baseFeuillesUrl . '&depart=tout&serie=' . rawurlencode($currentSerieFeuilles) . '&cible=' . rawurlencode($currentCibleFeuilles)) ?>"<?= $currentDepartFeuilles === 'tout' ? ' selected' : '' ?>>Tous</option>
            <?php foreach ($departsListFeuilles as $numDepart): ?>
            <option value="<?= htmlspecialchars($baseFeuillesUrl . '&depart=' . (int)$numDepart . '&serie=' . rawurlencode($currentSerieFeuilles) . '&cible=' . rawurlencode($currentCibleFeuilles)) ?>"<?= $currentDepartFeuilles === (string)$numDepart ? ' selected' : '' ?>><?= (int)$numDepart ?></option>
            <?php endforeach; ?>
        </select>
        <label class="me-2">Série :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <option value="<?= htmlspecialchars($baseFeuillesUrl . '&depart=' . rawurlencode($currentDepartFeuilles) . '&cible=' . rawurlencode($currentCibleFeuilles) . '&serie=toutes') ?>"<?= $currentSerieFeuilles === 'toutes' ? ' selected' : '' ?>>TOUTES</option>
            <option value="<?= htmlspecialchars($baseFeuillesUrl . '&depart=' . rawurlencode($currentDepartFeuilles) . '&cible=' . rawurlencode($currentCibleFeuilles) . '&serie=1') ?>"<?= $currentSerieFeuilles === '1' ? ' selected' : '' ?>>1</option>
            <option value="<?= htmlspecialchars($baseFeuillesUrl . '&depart=' . rawurlencode($currentDepartFeuilles) . '&cible=' . rawurlencode($currentCibleFeuilles) . '&serie=2') ?>"<?= $currentSerieFeuilles === '2' ? ' selected' : '' ?>>2</option>
        </select>
        <label class="me-2">Cible :</label>
        <select class="form-select form-select-sm d-inline-block w-auto me-3" onchange="location.href=this.value">
            <option value="<?= htmlspecialchars($baseFeuillesUrl . '&depart=' . rawurlencode($currentDepartFeuilles) . '&serie=' . rawurlencode($currentSerieFeuilles) . '&cible=toutes') ?>"<?= $currentCibleFeuilles === 'toutes' ? ' selected' : '' ?>>TOUTES</option>
            <?php foreach ($ciblesListFeuilles as $numCible): ?>
            <option value="<?= htmlspecialchars($baseFeuillesUrl . '&depart=' . rawurlencode($currentDepartFeuilles) . '&serie=' . rawurlencode($currentSerieFeuilles) . '&cible=' . $numCible) ?>"<?= $currentCibleFeuilles === (string)$numCible ? ' selected' : '' ?>><?= (int)$numCible ?></option>
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
                    <?php if ($doc !== 'liste-participants' && $doc !== 'feuilles-marques') include __DIR__ . '/editions/_edition-doc-fin.php'; ?>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
