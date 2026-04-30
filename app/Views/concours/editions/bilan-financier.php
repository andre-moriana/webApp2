<div class="edition-bilan-financier">
    <h2 class="h4 mb-3">Bilan financier</h2>
    <style>
        @media print {
            /* Evite la repetition du total en pied sur chaque page imprimee */
            .edition-bilan-financier .bilan-financier-tfoot {
                display: table-row-group;
            }
        }
    </style>

    <?php
    $rows = isset($bilanFinancierRows) && is_array($bilanFinancierRows) ? $bilanFinancierRows : [];
    $totals = isset($bilanFinancierTotals) && is_array($bilanFinancierTotals) ? $bilanFinancierTotals : [];
    $byClub = isset($bilanFinancierByClub) && is_array($bilanFinancierByClub) ? $bilanFinancierByClub : [];
    $showClubSubtotals = !empty($bilanSousTotauxClub);
    $fmtMoney = function ($value) {
        return number_format((float)$value, 2, ',', ' ') . ' EUR';
    };
    $fmtBool = function ($value) {
        return $value ? 'Oui' : 'Non';
    };
    ?>

    <p class="text-muted small mb-2">
        Calcul base sur les inscriptions confirmees, la tarification du concours et les statuts greffe (present/paye).
    </p>

    <?php if (empty($rows)): ?>
        <p class="text-muted">Aucune donnee disponible pour le bilan financier.</p>
    <?php else: ?>
        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Archer</th>
                        <th>Licence</th>
                        <th>Depart</th>
                        <th>Tarif</th>
                        <th>Type tir</th>
                        <th>Present</th>
                        <th>Paye</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($r['club_nom'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($r['user_nom'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($r['numero_licence'] ?? '')) ?></td>
                            <td><?= (int)($r['numero_depart'] ?? 0) ?></td>
                            <td><?= htmlspecialchars(($r['type_public'] ?? '') === 'enfant' ? 'Enfant' : 'Adulte') ?></td>
                            <td><?php
                                $typeTir = (string)($r['type_depart'] ?? '');
                                if ($typeTir === 'premier') {
                                    echo '1er tir';
                                } elseif ($typeTir === 'deuxieme') {
                                    echo '2eme tir';
                                } else {
                                    echo 'Tir supplementaire';
                                }
                            ?></td>
                            <td><?= htmlspecialchars($fmtBool(!empty($r['present_greffe']))) ?></td>
                            <td><?= htmlspecialchars($fmtBool(!empty($r['paye_greffe']))) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney((float)($r['montant'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bilan-financier-tfoot">
                    <tr class="table-secondary fw-bold">
                        <td colspan="3">Total general</td>
                        <td><?= (int)($totals['inscriptions'] ?? 0) ?></td>
                        <td colspan="2"></td>
                        <td><?= (int)($totals['presentes'] ?? 0) ?></td>
                        <td><?= (int)($totals['payees'] ?? 0) ?></td>
                        <td class="text-end"><?= htmlspecialchars($fmtMoney((float)($totals['montant_total'] ?? 0))) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="border rounded p-2">
                    <div class="small text-muted">Recette theorique (inscriptions)</div>
                    <div class="fw-bold"><?= htmlspecialchars($fmtMoney((float)($totals['montant_total'] ?? 0))) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2">
                    <div class="small text-muted">Recette theorique des presents</div>
                    <div class="fw-bold"><?= htmlspecialchars($fmtMoney((float)($totals['montant_present'] ?? 0))) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2">
                    <div class="small text-muted">Recette encaissee (paye = oui)</div>
                    <div class="fw-bold text-success"><?= htmlspecialchars($fmtMoney((float)($totals['montant_paye'] ?? 0))) ?></div>
                </div>
            </div>
        </div>

        <?php if ($showClubSubtotals): ?>
            <h3 class="h6 mt-3">Sous-totaux par club</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Club</th>
                            <th>Inscriptions</th>
                            <th>Presents</th>
                            <th>Payes</th>
                            <th>Total theorique</th>
                            <th>Total presents</th>
                            <th>Total payes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byClub as $clubName => $clubTotals): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$clubName) ?></td>
                                <td><?= (int)($clubTotals['inscriptions'] ?? 0) ?></td>
                                <td><?= (int)($clubTotals['presentes'] ?? 0) ?></td>
                                <td><?= (int)($clubTotals['payees'] ?? 0) ?></td>
                                <td class="text-end"><?= htmlspecialchars($fmtMoney((float)($clubTotals['montant_total'] ?? 0))) ?></td>
                                <td class="text-end"><?= htmlspecialchars($fmtMoney((float)($clubTotals['montant_present'] ?? 0))) ?></td>
                                <td class="text-end"><?= htmlspecialchars($fmtMoney((float)($clubTotals['montant_paye'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
