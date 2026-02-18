<?php
/**
 * Document à imprimer - page standalone (sans header/footer)
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
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; }
            .page-break { page-break-after: always; }
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
</body>
</html>
