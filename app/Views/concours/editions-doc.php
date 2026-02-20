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
        /* En-tête et pied de page - affichage écran (prévisualisation) */
        .edition-doc-header {
            display: block;
            border-bottom: 1px solid #ddd;
            padding: 8px 15px;
            margin: -1rem -1rem 1rem -1rem;
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
        @media print {
            /* Réserver l'espace en haut de CHAQUE page pour l'en-tête (éviter que le logo soit coupé) */
            @page {
                margin-top: 35mm;
                margin-bottom: 15mm;
            }
            .no-print { display: none !important; }
            body { font-size: 11pt; }
            .page-break { page-break-after: always; }
            /* En-tête - position fixe, ne pas couper au saut de page */
            .edition-doc-header {
                display: block !important;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                width: 100%;
                max-height: 32mm;
                background: #fff;
                z-index: 9999;
                border-bottom: 1px solid #ddd;
                padding: 3px 15px;
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
            /* Logo : 14mm pour tenir dans la marge et éviter la coupure au saut de page */
            .edition-doc-logo {
                height: 14mm;
                max-width: 28mm;
                max-height: 14mm;
                object-fit: contain;
            }
            .edition-doc-header-left {
                min-width: 25mm;
                padding: 0 2mm;
            }
            .edition-doc-logo-placeholder {
                font-size: 10pt;
            }
            /* Pas de padding body : @page margin gère l'espace sur chaque page */
            body {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }
        }
        body { padding: 1rem; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/editions/_header-footer.php'; ?>

    <div class="no-print mb-3">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print me-1"></i>Imprimer
        </button>
        <a href="/concours/<?= (int)$concoursId ?>/editions" class="btn btn-secondary ms-2">Retour aux éditions</a>
    </div>

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
</body>
</html>
