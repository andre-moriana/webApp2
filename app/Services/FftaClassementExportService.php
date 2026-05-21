<?php

require_once __DIR__ . '/../Controllers/ArcherSearchController.php';

/**
 * Export classement au format texte FFTA (champs séparés par tabulations).
 * Spécification : BackendPHP/ffta.txt
 */
class FftaClassementExportService
{
    private const FFTA_LICENCE_ETRANGER = '999999';
    private const NB_CHAMPS_LIGNE = 51;

    /**
     * @param array<string, mixed> $options
     */
    public static function buildAndDownload(array $options): void
    {
        $mode = $options['mode'] ?? 'classement';
        $rows = ($mode === 'scores')
            ? self::buildScoresPageRows($options)
            : self::buildClassementRows($options);
        $content = self::buildFileContent($rows, $options);
        $filename = self::buildFilename($options);

        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/plain; charset=Windows-1252');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo self::toWindows1252($content);
        exit;
    }

    /**
     * @param array<string, mixed> $options
     * @return list<array{inscription: array, resultat: ?array, rang: int}>
     */
    public static function buildClassementRows(array $options): array
    {
        $inscriptions = $options['inscriptions'] ?? [];
        $resultats = $options['resultats'] ?? [];
        $resultatsByLicence = $options['resultatsByLicence'] ?? [];
        $typeClassement = $options['typeClassement'] ?? 'general';
        $clubOrganisateurCode = preg_replace('/\D/', '', (string)($options['clubOrganisateurCode'] ?? ''));
        $clubsMap = $options['clubsMap'] ?? [];
        $top3ParCategorie = !empty($options['top3ParCategorie']);
        $disciplineAbv = $options['disciplineAbv'] ?? null;

        [$is3D, $isNature] = self::detectDisciplineFlags($resultats, $disciplineAbv);

        $inscriptions1erTir = array_filter($inscriptions, function ($insc) {
            $nt = $insc['numero_tir'] ?? null;
            return $nt === null || $nt === '' || (int)$nt === 1;
        });

        if ($typeClassement === 'regional' && strlen($clubOrganisateurCode) >= 2) {
            $prefixOrg = substr($clubOrganisateurCode, 0, 2);
            $inscriptions1erTir = array_filter($inscriptions1erTir, function ($insc) use ($prefixOrg, $clubsMap) {
                return self::clubCodeMatchesPrefix($insc, $clubsMap, $prefixOrg, 2);
            });
        } elseif ($typeClassement === 'departemental' && strlen($clubOrganisateurCode) >= 4) {
            $prefixOrg = substr($clubOrganisateurCode, 0, 4);
            $inscriptions1erTir = array_filter($inscriptions1erTir, function ($insc) use ($prefixOrg, $clubsMap) {
                return self::clubCodeMatchesPrefix($insc, $clubsMap, $prefixOrg, 4);
            });
        }

        $byCategorie = [];
        foreach ($inscriptions1erTir as $insc) {
            $cat = trim((string)($insc['categorie_classement'] ?? $insc['abv_categorie_classement'] ?? ''));
            if ($cat === '') {
                $cat = 'Sans catégorie';
            }
            if (!isset($byCategorie[$cat])) {
                $byCategorie[$cat] = [];
            }
            $inscId = $insc['id'] ?? $insc['_id'] ?? null;
            $r = $inscId ? ($resultats[(int)$inscId] ?? null) : null;
            if ($r === null) {
                $lic = trim((string)($insc['numero_licence'] ?? ''));
                $r = ($lic !== '' && isset($resultatsByLicence[$lic])) ? $resultatsByLicence[$lic] : null;
            }
            $byCategorie[$cat][] = [
                'inscription' => $insc,
                'resultat' => $r,
                'score' => $r ? (int)($r['score'] ?? 0) : 0,
            ];
        }

        foreach ($byCategorie as &$items) {
            usort($items, function ($a, $b) use ($isNature, $is3D) {
                return self::compareScoreItems($a, $b, $isNature, $is3D);
            });
            $rang = 1;
            foreach ($items as &$item) {
                $item['rang'] = $rang++;
            }
            unset($item);
        }
        unset($items);

        if ($top3ParCategorie) {
            foreach ($byCategorie as $cat => &$items) {
                $byCategorie[$cat] = array_values(array_filter($items, function ($item) {
                    return ($item['rang'] ?? 0) <= 3;
                }));
            }
            unset($items);
        }

        $flat = [];
        foreach ($byCategorie as $items) {
            foreach ($items as $item) {
                $flat[] = $item;
            }
        }
        return $flat;
    }

    /**
     * Lignes exportées dans le même ordre que la page Scores (filtre départ, tri club/catégorie/départ).
     * Le rang FFTA est calculé par catégorie de classement sur les inscriptions affichées.
     *
     * @param array<string, mixed> $options
     * @return list<array{inscription: array, resultat: ?array, rang: int}>
     */
    public static function buildScoresPageRows(array $options): array
    {
        $inscriptions = $options['inscriptions'] ?? [];
        $resultats = $options['resultats'] ?? [];
        $resultatsByLicence = $options['resultatsByLicence'] ?? [];
        $triScores = $options['triScores'] ?? 'club';
        $disciplineAbv = $options['disciplineAbv'] ?? null;

        [$is3D, $isNature] = self::detectDisciplineFlags($resultats, $disciplineAbv);

        $resolveResultat = function (array $insc) use ($resultats, $resultatsByLicence) {
            $inscId = $insc['id'] ?? $insc['_id'] ?? null;
            $r = $inscId ? ($resultats[(int)$inscId] ?? null) : null;
            if ($r === null) {
                $lic = trim((string)($insc['numero_licence'] ?? ''));
                $r = ($lic !== '' && isset($resultatsByLicence[$lic])) ? $resultatsByLicence[$lic] : null;
            }
            return $r;
        };

        $byCategorie = [];
        foreach ($inscriptions as $insc) {
            $cat = trim((string)($insc['categorie_classement'] ?? $insc['abv_categorie_classement'] ?? ''));
            if ($cat === '') {
                $cat = 'Sans catégorie';
            }
            if (!isset($byCategorie[$cat])) {
                $byCategorie[$cat] = [];
            }
            $r = $resolveResultat($insc);
            $byCategorie[$cat][] = [
                'inscription' => $insc,
                'resultat' => $r,
                'score' => $r ? (int)($r['score'] ?? 0) : 0,
            ];
        }

        foreach ($byCategorie as &$items) {
            usort($items, function ($a, $b) use ($isNature, $is3D) {
                return self::compareScoreItems($a, $b, $isNature, $is3D);
            });
            $rang = 1;
            foreach ($items as &$item) {
                $item['rang'] = $rang++;
            }
            unset($item);
        }
        unset($items);

        $rangByInscId = [];
        foreach ($byCategorie as $items) {
            foreach ($items as $item) {
                $inscId = $item['inscription']['id'] ?? $item['inscription']['_id'] ?? null;
                if ($inscId !== null && $inscId !== '') {
                    $rangByInscId[(int)$inscId] = (int)($item['rang'] ?? 0);
                }
            }
        }

        $groupKey = function (array $insc) use ($triScores): string {
            if ($triScores === 'club') {
                $v = trim($insc['club_nom'] ?? '');
                return $v !== '' ? $v : 'Sans club';
            }
            if ($triScores === 'categorie') {
                $v = trim($insc['categorie_libelle'] ?? $insc['categorie_classement'] ?? $insc['abv_categorie_classement'] ?? '');
                return $v !== '' ? $v : 'Sans catégorie';
            }
            if ($triScores === 'depart') {
                $v = $insc['numero_depart'] ?? null;
                return $v !== null && $v !== '' ? (string)$v : 'Non défini';
            }
            return '—';
        };

        $groups = [];
        foreach ($inscriptions as $insc) {
            $k = $groupKey($insc);
            if (!isset($groups[$k])) {
                $groups[$k] = [];
            }
            $groups[$k][] = $insc;
        }
        if ($triScores === 'depart') {
            ksort($groups, SORT_NATURAL);
        } else {
            ksort($groups, SORT_FLAG_CASE | SORT_NATURAL);
        }

        $flat = [];
        foreach ($groups as $rows) {
            foreach ($rows as $insc) {
                $inscId = $insc['id'] ?? $insc['_id'] ?? null;
                $flat[] = [
                    'inscription' => $insc,
                    'resultat' => $resolveResultat($insc),
                    'rang' => $inscId !== null ? ($rangByInscId[(int)$inscId] ?? 0) : 0,
                ];
            }
        }
        return $flat;
    }

    /**
     * @return array{0: bool, 1: bool} [is3D, isNature]
     */
    private static function detectDisciplineFlags(array $resultats, ?string $disciplineAbv): array
    {
        $hasNatureScores = !empty(array_filter($resultats, function ($r) {
            return isset($r['nb_20_15']) || isset($r['nb_20_10']) || isset($r['nb_15_15']) || isset($r['nb_15_10']);
        }));
        $has3DScores = !empty(array_filter($resultats, function ($r) {
            return isset($r['nb_11']) || isset($r['nb_8']) || isset($r['nb_5']);
        }));
        $is3D = ($disciplineAbv && in_array($disciplineAbv, ['3', '3D'], true)) || $has3DScores;
        $isNature = !$is3D && (($disciplineAbv && in_array($disciplineAbv, ['N', 'C'], true)) || $hasNatureScores);
        return [$is3D, $isNature];
    }

    /**
     * @param array{inscription: array, resultat: ?array, score: int} $a
     * @param array{inscription: array, resultat: ?array, score: int} $b
     */
    private static function compareScoreItems(array $a, array $b, bool $isNature, bool $is3D): int
    {
        $diff = $b['score'] - $a['score'];
        if ($diff !== 0) {
            return $diff;
        }
        $rA = $a['resultat'] ?? [];
        $rB = $b['resultat'] ?? [];
        if ($is3D) {
            foreach (['nb_11', 'nb_10', 'nb_8', 'nb_5'] as $k) {
                $vA = (int)($rA[$k] ?? 0);
                $vB = (int)($rB[$k] ?? 0);
                if ($vA !== $vB) {
                    return $vB - $vA;
                }
            }
            return (int)($rA['nb_0'] ?? 0) - (int)($rB['nb_0'] ?? 0);
        }
        if (!$isNature) {
            return 0;
        }
        foreach (['nb_20_15', 'nb_20_10', 'nb_15_15', 'nb_15_10', 'nb_15', 'nb_10'] as $k) {
            $vA = (int)($rA[$k] ?? 0);
            $vB = (int)($rB[$k] ?? 0);
            if ($vA !== $vB) {
                return $vB - $vA;
            }
        }
        return (int)($rA['nb_0'] ?? 0) - (int)($rB['nb_0'] ?? 0);
    }

    /**
     * @param list<array{inscription: array, resultat: ?array, rang: int}> $rows
     * @param array<string, mixed> $options
     */
    public static function buildFileContent(array $rows, array $options): string
    {
        $lines = [];
        $version = (string)($options['version'] ?? '1.0');
        $lines[] = 'VERSION : ' . "\t" . $version . "\t";

        $arbitres = is_array($options['arbitres'] ?? null) ? $options['arbitres'] : [];
        $rarbitres = [];
        $listeArbitres = [];
        $entraineurs = [];
        foreach ($arbitres as $a) {
            $a = is_array($a) ? $a : (array)$a;
            $lic = self::formatLicence($a['IDLicence'] ?? $a['id_licence'] ?? '');
            if ($lic === '') {
                continue;
            }
            $role = (int)($a['Jury_arbitre'] ?? $a['jury_arbitre'] ?? 2);
            if (!empty($a['responsable']) && in_array($role, [1, 2], true)) {
                $rarbitres[] = $lic;
            } elseif (in_array($role, [1, 2], true)) {
                $listeArbitres[] = $lic;
            } elseif ($role === 3) {
                $entraineurs[] = $lic;
            }
        }
        if (empty($rarbitres) && !empty($listeArbitres)) {
            $rarbitres[] = $listeArbitres[0];
        }
        $lines[] = 'RARBITRES' . "\t" . implode("\t", $rarbitres);
        $lines[] = 'ARBITRES' . "\t" . implode("\t", $listeArbitres);
        $lines[] = 'ENTRAINEURS' . "\t" . implode("\t", $entraineurs);

        $licences = [];
        foreach ($rows as $row) {
            $lic = trim((string)($row['inscription']['numero_licence'] ?? ''));
            if ($lic !== '') {
                $licences[] = $lic;
            }
        }
        $licences = array_values(array_unique($licences));
        $nomPrenomByLicence = !empty($licences) ? ArcherSearchController::getNomPrenomByLicences($licences) : [];
        $sexeByLicence = !empty($licences) ? ArcherSearchController::getSexeByLicences($licences) : [];

        foreach ($rows as $row) {
            $lines[] = self::buildDataLine($row, $options, $nomPrenomByLicence, $sexeByLicence);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * @param array{inscription: array, resultat: ?array, rang: int} $row
     * @param array<string, mixed> $options
     * @param array<string, array{nom: string, prenom: string}> $nomPrenomByLicence
     * @param array<string, string> $sexeByLicence
     */
    private static function buildDataLine(array $row, array $options, array $nomPrenomByLicence, array $sexeByLicence): string
    {
        $insc = $row['inscription'];
        $res = $row['resultat'] ?? [];
        $rang = (int)($row['rang'] ?? 0);
        $clubsMap = $options['clubsMap'] ?? [];
        $categoriesExport = $options['categoriesExport'] ?? [];
        $concours = $options['concours'] ?? null;
        $disciplineAbv = strtoupper(substr((string)($options['disciplineAbv'] ?? ''), 0, 1));
        $niveauAbv = self::mapNiveauFfta($options['niveauChampionnatAbv'] ?? '');
        $typeChpt = self::mapTypeChampionnat($options['typeCompetitionName'] ?? '', $concours);
        $isShortDistance = in_array($disciplineAbv, ['S', 'I'], true);

        $abvCat = trim((string)($insc['abv_categorie_classement'] ?? $insc['categorie_classement'] ?? ''));
        $catMeta = $categoriesExport[$abvCat] ?? [];
        $fgFfta = !empty($catMeta['fg_ffta']);
        $place = $fgFfta ? $rang : 0;

        $licence = trim((string)($insc['numero_licence'] ?? ''));
        $typeLicence = strtoupper(substr(trim((string)($insc['type_licence'] ?? '')), 0, 1));
        if ($typeLicence === 'E' || $licence === '') {
            $licence = self::FFTA_LICENCE_ETRANGER;
        } else {
            $licence = self::formatLicence($licence);
        }

        $nom = '';
        $prenom = '';
        $licRaw = trim((string)($insc['numero_licence'] ?? ''));
        if ($licRaw !== '' && isset($nomPrenomByLicence[$licRaw])) {
            $nom = $nomPrenomByLicence[$licRaw]['nom'];
            $prenom = $nomPrenomByLicence[$licRaw]['prenom'];
        }
        if ($nom === '' && $prenom === '') {
            $parts = preg_split('/\s+/', trim((string)($insc['user_nom'] ?? $insc['nom'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($parts) >= 2) {
                $nom = array_pop($parts);
                $prenom = implode(' ', $parts);
            } elseif (count($parts) === 1) {
                $nom = $parts[0];
            }
        }

        $catExport = trim((string)($catMeta['abv_export_cat'] ?? ''));
        if ($catExport === '') {
            $catExport = self::extractCategorieAge($abvCat, trim((string)($insc['catage'] ?? '')));
        }

        $surclassement = '';
        if (stripos($abvCat, 'DEC') !== false || stripos((string)($catMeta['lb_categorie_classement'] ?? ''), 'PROMOTION') !== false) {
            $surclassement = 'D';
        }

        $sexe = strtoupper(trim((string)($insc['abv_sexe'] ?? '')));
        if ($sexe === 'M') {
            $sexe = 'H';
        }
        if ($sexe === '' && $licRaw !== '' && isset($sexeByLicence[$licRaw])) {
            $s = strtoupper($sexeByLicence[$licRaw]);
            $sexe = ($s === 'M') ? 'H' : $s;
        }
        if ($sexe === '' && isset($insc['sexe'])) {
            $sx = (int)$insc['sexe'];
            $sexe = $sx === 2 ? 'F' : ($sx === 1 ? 'H' : '');
        }
        $sexe = substr($sexe, 0, 1);

        $armeClassement = trim((string)($catMeta['abv_export_arme'] ?? ''));
        if ($armeClassement === '') {
            $armeClassement = self::extractArmeAbv($abvCat, trim((string)($insc['abv_arc'] ?? '')));
        }
        $armeUtilisee = strtoupper(substr(trim((string)($insc['abv_arc'] ?? '')), 0, 2));

        $clubNom = trim((string)($insc['club_nom'] ?? ''));
        $affiliation = self::clubAffiliationCode($insc, $clubsMap);

        $score = $res ? (int)($res['score'] ?? 0) : 0;
        $paille = $res ? self::num2($res['nb_paille'] ?? '') : '';
        $dix = $res ? self::num2($res['nb_10'] ?? $res['serie1_nb_10'] ?? '') : '';
        $neuf = $res ? self::num2($res['nb_9'] ?? $res['serie1_nb_9'] ?? $res['total_nb_9'] ?? '') : '';

        $distance = '';
        $piquet = trim((string)($insc['piquet'] ?? ''));
        if ($piquet !== '' && is_numeric($piquet)) {
            $distance = (string)(int)$piquet;
        } elseif (isset($insc['distance']) && $insc['distance'] !== '' && $insc['distance'] !== null) {
            $distance = self::num2($insc['distance']);
        }

        $blason = '';
        if (isset($insc['blason']) && $insc['blason'] !== '' && $insc['blason'] !== null) {
            $blason = self::num3($insc['blason']);
        }

        $dateConcours = self::formatDateFfta($concours);
        $lieu = self::resolveLieuConcours($concours, $clubsMap);

        $serie1 = $res ? (int)($res['serie1_score'] ?? 0) : 0;
        $serie2 = $res ? (int)($res['serie2_score'] ?? 0) : 0;
        $score1Dist = '';
        $score2Dist = '';
        $score3Dist = '';
        $score4Dist = '';
        if ($isShortDistance) {
            $score3Dist = $serie1 > 0 ? self::num3($serie1) : '';
            $score4Dist = $serie2 > 0 ? self::num3($serie2) : '';
        } else {
            $score1Dist = $serie1 > 0 ? self::num3($serie1) : '';
            $score2Dist = $serie2 > 0 ? self::num3($serie2) : '';
        }

        $numeroTir = trim((string)($insc['numero_tir'] ?? '1'));
        if ($numeroTir === '') {
            $numeroTir = '1';
        }

        $fields = array_fill(0, self::NB_CHAMPS_LIGNE, '');
        $fields[0] = $disciplineAbv;
        $fields[1] = $niveauAbv;
        $fields[2] = $typeChpt;
        $fields[3] = $licence;
        $fields[4] = $nom;
        $fields[5] = $prenom;
        $fields[6] = substr($catExport, 0, 3);
        $fields[7] = substr($surclassement, 0, 3);
        $fields[8] = $sexe;
        $fields[9] = substr($armeClassement, 0, 2);
        $fields[10] = '';
        $fields[11] = $clubNom;
        $fields[12] = $affiliation;
        $fields[13] = self::num4($score);
        $fields[14] = $paille;
        $fields[15] = $dix;
        $fields[16] = $neuf;
        $fields[17] = $distance;
        $fields[18] = $blason;
        $fields[19] = $dateConcours;
        $fields[20] = $lieu;
        $fields[21] = self::num3($place);
        $fields[22] = $score1Dist;
        $fields[23] = $score2Dist;
        $fields[24] = $score3Dist;
        $fields[25] = $score4Dist;
        // 26-46 : phases éliminatoires (vides)
        $fields[47] = self::num3($place);
        $fields[48] = $fgFfta ? '1' : '0';
        $fields[49] = $armeUtilisee;
        $fields[50] = substr($numeroTir, 0, 1);

        return implode("\t", $fields);
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function buildFilename(array $options): string
    {
        $epreuve = strtolower(substr(trim((string)($options['epreuve'] ?? 'a')), 0, 1));
        if ($epreuve === '' || !preg_match('/^[a-z]$/', $epreuve)) {
            $epreuve = 'a';
        }
        $discipline = strtolower(substr((string)($options['disciplineAbv'] ?? 's'), 0, 1));
        $code = preg_replace('/\D/', '', (string)($options['clubOrganisateurCode'] ?? ''));
        $rr = str_pad(substr($code, 0, 2), 2, '0', STR_PAD_LEFT);
        $dd = str_pad(substr($code, 2, 2), 2, '0', STR_PAD_LEFT);
        $ccc = str_pad(substr($code, 4, 3), 3, '0', STR_PAD_LEFT);
        return $epreuve . $discipline . $rr . $dd . $ccc . '.txt';
    }

    private static function clubCodeMatchesPrefix(array $insc, array $clubsMap, string $prefixOrg, int $len): bool
    {
        $idClub = $insc['id_club'] ?? null;
        if ($idClub === null || $idClub === '') {
            return false;
        }
        $club = $clubsMap[$idClub] ?? $clubsMap[(string)$idClub] ?? $clubsMap[(int)$idClub] ?? null;
        $codeClub = $club
            ? preg_replace('/\D/', '', (string)($club['nameShort'] ?? $club['name_short'] ?? ''))
            : (is_string($idClub) && preg_match('/^\d/', $idClub) ? preg_replace('/\D/', '', $idClub) : '');
        return $codeClub !== '' && strlen($codeClub) >= $len && substr($codeClub, 0, $len) === $prefixOrg;
    }

    private static function clubAffiliationCode(array $insc, array $clubsMap): string
    {
        $idClub = $insc['id_club'] ?? null;
        if ($idClub === null || $idClub === '') {
            return '';
        }
        $club = $clubsMap[$idClub] ?? $clubsMap[(string)$idClub] ?? $clubsMap[(int)$idClub] ?? null;
        $code = $club
            ? preg_replace('/\D/', '', (string)($club['nameShort'] ?? $club['name_short'] ?? ''))
            : (is_string($idClub) && preg_match('/^\d/', $idClub) ? preg_replace('/\D/', '', $idClub) : '');
        return str_pad(substr($code, 0, 7), 7, '0', STR_PAD_LEFT);
    }

    private static function formatLicence(string $licence): string
    {
        $digits = preg_replace('/\D/', '', $licence);
        if ($digits === '') {
            return '';
        }
        return str_pad(substr($digits, -7), 7, '0', STR_PAD_LEFT);
    }

    private static function extractCategorieAge(string $abvCat, string $catage): string
    {
        if (preg_match('/(U1[1358]|U21|S[123])/i', $abvCat, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/(U1[1358]|U21|S[123])/i', $catage, $m)) {
            return strtoupper($m[1]);
        }
        return substr($abvCat, 0, 3);
    }

    private static function extractArmeAbv(string $abvCat, string $abvArc): string
    {
        if (preg_match('/(CL|CO|AD|AC|TL|BB)/i', $abvCat, $m)) {
            return strtoupper($m[1]);
        }
        return strtoupper(substr($abvArc, 0, 2));
    }

    private static function mapNiveauFfta(string $abv): string
    {
        $abv = strtoupper(substr(trim($abv), 0, 1));
        $map = ['N' => 'N', 'R' => 'R', 'D' => 'D', 'C' => 'C', 'I' => 'I', 'S' => 'N', 'E' => 'I', 'M' => 'I', 'O' => 'I'];
        return $map[$abv] ?? ($abv !== '' ? $abv : 'C');
    }

    private static function mapTypeChampionnat(string $typeName, $concours): string
    {
        $name = strtoupper($typeName);
        if (str_contains($name, 'EQUIPE') || str_contains($name, 'ÉQUIPE')) {
            return 'E';
        }
        if (is_object($concours)) {
            $div = strtoupper((string)($concours->division_equipe ?? ''));
            if (str_contains($div, 'EQUIPE')) {
                return 'E';
            }
        } elseif (is_array($concours)) {
            $div = strtoupper((string)($concours['division_equipe'] ?? ''));
            if (str_contains($div, 'EQUIPE')) {
                return 'E';
            }
        }
        return 'I';
    }

    private static function formatDateFfta($concours): string
    {
        $raw = '';
        if (is_object($concours)) {
            $raw = (string)($concours->date_debut ?? $concours->date_fin ?? '');
        } elseif (is_array($concours)) {
            $raw = (string)($concours['date_debut'] ?? $concours['date_fin'] ?? '');
        }
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);
        return $ts ? date('d/m/Y', $ts) : '';
    }

    /**
     * Lieu du concours FFTA : ville du club organisateur, sinon lieu du concours.
     *
     * @param object|array|null $concours
     * @param array<string|int, array> $clubsMap
     */
    private static function resolveLieuConcours($concours, array $clubsMap): string
    {
        $clubOrgId = null;
        if (is_object($concours)) {
            $clubOrgId = $concours->club_organisateur ?? null;
        } elseif (is_array($concours)) {
            $clubOrgId = $concours['club_organisateur'] ?? null;
        }
        if ($clubOrgId !== null && $clubOrgId !== '') {
            $club = $clubsMap[$clubOrgId] ?? $clubsMap[(string)$clubOrgId] ?? $clubsMap[(int)$clubOrgId] ?? null;
            if (!$club && is_string($clubOrgId)) {
                $club = $clubsMap[trim((string)$clubOrgId)] ?? null;
            }
            if (is_array($club)) {
                $ville = trim((string)($club['city'] ?? $club['ville'] ?? ''));
                if ($ville !== '') {
                    return $ville;
                }
            }
        }
        if (is_object($concours)) {
            return trim((string)($concours->lieu ?? ''));
        }
        if (is_array($concours)) {
            return trim((string)($concours['lieu'] ?? ''));
        }
        return '';
    }

    private static function num2($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return str_pad((string)max(0, min(99, (int)$value)), 2, '0', STR_PAD_LEFT);
    }

    private static function num3($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return str_pad((string)max(0, min(999, (int)$value)), 3, '0', STR_PAD_LEFT);
    }

    private static function num4($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return str_pad((string)max(0, min(9999, (int)$value)), 4, '0', STR_PAD_LEFT);
    }

    private static function toWindows1252(string $content): string
    {
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($content, 'Windows-1252', 'UTF-8');
            if ($converted !== false) {
                return $converted;
            }
        }
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $content);
            if ($converted !== false) {
                return $converted;
            }
        }
        return $content;
    }
}
