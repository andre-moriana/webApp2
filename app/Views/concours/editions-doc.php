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
        /* Logo : taille min. 15mm selon charte FFTA - 60px ≈ 16mm à 96dpi */
        .edition-doc-logo {
            height: 60px;
            max-width: 120px;
            object-fit: contain;
        }
        /* Espace libre autour du logo (charte FFTA) */
        .edition-doc-header-left {
            min-width: 100px;
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
            .no-print { display: none !important; }
            body { font-size: 11pt; }
            .page-break { page-break-after: always; }
            /* En-tête répété via table-header-group : évite la coupure du logo au saut de page */
            .edition-doc-print-table {
                display: table;
                width: 100%;
            }
            .edition-doc-print-thead {
                display: table-header-group;
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
            /* Logo : taille raisonnable, jamais coupé grâce à table-header-group */
            .edition-doc-logo {
                height: 16mm;
                max-width: 32mm;
                object-fit: contain;
            }
            .edition-doc-header-left {
                min-width: 25mm;
                padding: 0 2mm;
            }
            .edition-doc-logo-placeholder {
                font-size: 10pt;
            }
        }
        body { padding: 1rem; }
    </style>
</head>
<body>
    <div class="no-print mb-3">
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
                    <?php include __DIR__ . '/editions/_edition-doc-fin.php'; ?>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
