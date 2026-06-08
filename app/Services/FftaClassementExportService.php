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
        $options['inscriptions'] = $options['inscriptions'] ?? [];
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
        return self::attachRangClassementParCategorie($flat, $inscriptions, $resultats, $resultatsByLicence, $disciplineAbv, $options);
    }

    /**
     * Export FFTA scores : tri par nom, prénom puis n° de tir (champs FFTA).
     * Le rang FFTA (champ 22, place qualif.) est le classement général par catégorie et n° de tir.
     *
     * @param array<string, mixed> $options
     * @return list<array{inscription: array, resultat: ?array, rang: int}>
     */
    public static function buildScoresPageRows(array $options): array
    {
        $inscriptions = $options['inscriptions'] ?? [];
        $resultats = $options['resultats'] ?? [];
        $resultatsByLicence = $options['resultatsByLicence'] ?? [];
        $disciplineAbv = $options['disciplineAbv'] ?? null;

        $resolveResultat = self::makeResultatResolver($resultats, $resultatsByLicence);
        $nomPrenomByLicence = self::loadNomPrenomByLicencesForInscriptions($inscriptions);

        $rows = $inscriptions;
        usort($rows, function ($a, $b) use ($nomPrenomByLicence) {
            $nameA = self::resolveNomPrenomFromInscription($a, $nomPrenomByLicence);
            $nameB = self::resolveNomPrenomFromInscription($b, $nomPrenomByLicence);
            $cmp = self::compareAlpha(
                self::normalizeNameForSort($nameA['nom']),
                self::normalizeNameForSort($nameB['nom'])
            );
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = self::compareAlpha(
                self::normalizeNameForSort($nameA['prenom']),
                self::normalizeNameForSort($nameB['prenom'])
            );
            if ($cmp !== 0) {
                return $cmp;
            }
            return self::numeroTirSortKey($a) <=> self::numeroTirSortKey($b);
        });

        $flat = [];
        foreach ($rows as $insc) {
            $flat[] = [
                'inscription' => $insc,
                'resultat' => $resolveResultat($insc),
            ];
        }
        $options['forceRangParDepart'] = true;
        return self::attachRangClassementParCategorie($flat, $inscriptions, $resultats, $resultatsByLicence, $disciplineAbv, $options);
    }

    /**
     * Classement par catégorie et n° de départ (chaque départ a son propre classement) — même rang pour tous les tirs d'une licence sur ce départ.
     * Export scores : voir computeRangGeneralParNumeroTirMap (classement général par n° de tir).
     *
     * @param list<array{inscription: array, resultat: ?array}> $rows
     * @param array<int, array> $inscriptions
     * @param array<string, mixed> $exportOptions
     * @return list<array{inscription: array, resultat: ?array, rangClassement: int}>
     */
    private static function attachRangClassementParCategorie(
        array $rows,
        array $inscriptions,
        array $resultats,
        array $resultatsByLicence,
        ?string $disciplineAbv,
        array $exportOptions
    ): array {
        $forceRangParDepart = !empty($exportOptions['forceRangParDepart']);
        if (!$forceRangParDepart && !self::concoursHasDuelsPrevus($exportOptions)) {
            foreach ($rows as &$row) {
                $row['rangClassement'] = 0;
            }
            unset($row);
            return $rows;
        }

        $categoriesIdToAbv = $exportOptions['categoriesIdToAbv'] ?? [];
        $map = $forceRangParDepart
            ? self::computeRangGeneralParNumeroTirMap($inscriptions, $resultats, $resultatsByLicence, $disciplineAbv, $exportOptions)
            : self::computeRangParCategorieLicenceMap($inscriptions, $resultats, $resultatsByLicence, $disciplineAbv, $exportOptions);
        foreach ($rows as &$row) {
            $insc = $row['inscription'];
            $row['rangClassement'] = $forceRangParDepart
                ? self::resolveRangGeneralParNumeroTir($insc, $map, $categoriesIdToAbv)
                : self::resolveRangClassementParCategorie($insc, $map, $categoriesIdToAbv);
        }
        unset($row);
        return $rows;
    }

    /**
     * Place qualif. (champ 22) : classement général par catégorie et n° de tir (tous départs confondus).
     *
     * @param array<int, array> $inscriptions
     * @param array<string, mixed> $exportOptions
     * @return array<string, int>
     */
    private static function computeRangGeneralParNumeroTirMap(
        array $inscriptions,
        array $resultats,
        array $resultatsByLicence,
        ?string $disciplineAbv,
        array $exportOptions
    ): array {
        $categoriesIdToAbv = $exportOptions['categoriesIdToAbv'] ?? [];
        [$is3D, $isNature] = self::detectDisciplineFlags($resultats, $disciplineAbv);
        $resolveResultat = self::makeResultatResolver($resultats, $resultatsByLicence);

        /** @var array<string, array<string, array{inscription: array, resultat: ?array, score: int}>> $parGroupe */
        $parGroupe = [];
        foreach ($inscriptions as $insc) {
            $lic = self::normalizeLicenceKey((string)($insc['numero_licence'] ?? ''));
            if ($lic === '') {
                continue;
            }
            $groupe = self::groupeClassementParTirKey($insc, $categoriesIdToAbv);
            $r = $resolveResultat($insc);
            $item = [
                'inscription' => $insc,
                'resultat' => $r,
                'score' => $r ? (int)($r['score'] ?? 0) : 0,
            ];
            if (!isset($parGroupe[$groupe][$lic])) {
                $parGroupe[$groupe][$lic] = $item;
                continue;
            }
            if (self::compareScoreItems($item, $parGroupe[$groupe][$lic], $isNature, $is3D) < 0) {
                $parGroupe[$groupe][$lic] = $item;
            }
        }

        /** @var array<string, int> $rangParLicenceGroupe */
        $rangParLicenceGroupe = [];
        foreach ($parGroupe as $groupe => $parLicence) {
            $items = array_values($parLicence);
            usort($items, function ($a, $b) use ($isNature, $is3D) {
                return self::compareScoreItems($a, $b, $isNature, $is3D);
            });
            $rang = 1;
            foreach ($items as $item) {
                $lic = self::normalizeLicenceKey((string)($item['inscription']['numero_licence'] ?? ''));
                if ($lic !== '') {
                    $rangParLicenceGroupe[$lic . '|' . $groupe] = $rang;
                }
                $rang++;
            }
        }

        $map = [];
        foreach ($inscriptions as $insc) {
            $lic = self::normalizeLicenceKey((string)($insc['numero_licence'] ?? ''));
            if ($lic === '') {
                continue;
            }
            $groupe = self::groupeClassementParTirKey($insc, $categoriesIdToAbv);
            $lk = $lic . '|' . $groupe;
            if (isset($rangParLicenceGroupe[$lk])) {
                $map[self::rangParTirMapKey($lic, $insc, $categoriesIdToAbv)] = $rangParLicenceGroupe[$lk];
            }
        }

        return $map;
    }

    /**
     * @param array<string, int> $map
     */
    private static function resolveRangGeneralParNumeroTir(array $insc, array $map, array $categoriesIdToAbv = []): int
    {
        $lic = self::normalizeLicenceKey((string)($insc['numero_licence'] ?? ''));
        if ($lic === '') {
            return 0;
        }
        return $map[self::rangParTirMapKey($lic, $insc, $categoriesIdToAbv)] ?? 0;
    }

    /**
     * Rang par catégorie de classement et n° de départ, clé licence|catégorie|départ.
     *
     * @param array<int, array> $inscriptions
     * @param array<string, mixed> $exportOptions
     * @return array<string, int>
     */
    private static function computeRangParCategorieLicenceMap(
        array $inscriptions,
        array $resultats,
        array $resultatsByLicence,
        ?string $disciplineAbv,
        array $exportOptions
    ): array {
        $typeClassement = $exportOptions['typeClassement'] ?? 'general';
        $clubOrganisateurCode = preg_replace('/\D/', '', (string)($exportOptions['clubOrganisateurCode'] ?? ''));
        $clubsMap = $exportOptions['clubsMap'] ?? [];
        $categoriesIdToAbv = $exportOptions['categoriesIdToAbv'] ?? [];

        [$is3D, $isNature] = self::detectDisciplineFlags($resultats, $disciplineAbv);

        $inscriptionsPourRang = $inscriptions;
        if ($typeClassement === 'regional' && strlen($clubOrganisateurCode) >= 2) {
            $prefixOrg = substr($clubOrganisateurCode, 0, 2);
            $inscriptionsPourRang = array_values(array_filter($inscriptionsPourRang, function ($insc) use ($prefixOrg, $clubsMap) {
                return self::clubCodeMatchesPrefix($insc, $clubsMap, $prefixOrg, 2);
            }));
        } elseif ($typeClassement === 'departemental' && strlen($clubOrganisateurCode) >= 4) {
            $prefixOrg = substr($clubOrganisateurCode, 0, 4);
            $inscriptionsPourRang = array_values(array_filter($inscriptionsPourRang, function ($insc) use ($prefixOrg, $clubsMap) {
                return self::clubCodeMatchesPrefix($insc, $clubsMap, $prefixOrg, 4);
            }));
        }

        $resolveResultat = self::makeResultatResolver($resultats, $resultatsByLicence);

        // Liste de tous les groupes (catégorie × n° départ) présents dans les inscriptions.
        $groupes = [];
        foreach ($inscriptionsPourRang as $insc) {
            $lic = self::normalizeLicenceKey((string)($insc['numero_licence'] ?? ''));
            if ($lic === '') {
                continue;
            }
            $groupes[self::groupeClassementKey($insc, $categoriesIdToAbv)] = true;
        }

        /** @var array<string, int> $rangParLicenceGroupe licence|groupe => rang */
        $rangParLicenceGroupe = [];
        foreach (array_keys($groupes) as $groupe) {
            /** @var array<string, array{inscription: array, resultat: ?array, score: int}> $parLicence */
            $parLicence = [];
            foreach ($inscriptionsPourRang as $insc) {
                if (self::groupeClassementKey($insc, $categoriesIdToAbv) !== $groupe) {
                    continue;
                }
                $lic = self::normalizeLicenceKey((string)($insc['numero_licence'] ?? ''));
                if ($lic === '') {
                    continue;
                }
                $r = $resolveResultat($insc);
                $item = [
                    'inscription' => $insc,
                    'resultat' => $r,
                    'score' => $r ? (int)($r['score'] ?? 0) : 0,
                ];
                if (!isset($parLicence[$lic])) {
                    $parLicence[$lic] = $item;
                    continue;
                }
                if (self::preferInscriptionForRankingOnDepart($item, $parLicence[$lic])) {
                    $parLicence[$lic] = $item;
                }
            }

            $items = array_values($parLicence);
            usort($items, function ($a, $b) use ($isNature, $is3D) {
                return self::compareScoreItems($a, $b, $isNature, $is3D);
            });
            $rang = 1;
            foreach ($items as $item) {
                $lic = self::normalizeLicenceKey((string)($item['inscription']['numero_licence'] ?? ''));
                if ($lic !== '') {
                    $rangParLicenceGroupe[$lic . '|' . $groupe] = $rang;
                }
                $rang++;
            }
        }

        // Appliquer le même rang à toutes les lignes exportées (tous les tirs).
        $map = [];
        foreach ($inscriptions as $insc) {
            $lic = self::normalizeLicenceKey((string)($insc['numero_licence'] ?? ''));
            if ($lic === '') {
                continue;
            }
            $groupe = self::groupeClassementKey($insc, $categoriesIdToAbv);
            $lk = $lic . '|' . $groupe;
            if (isset($rangParLicenceGroupe[$lk])) {
                $map[self::rangParGroupeMapKey($lic, $insc, $categoriesIdToAbv)] = $rangParLicenceGroupe[$lk];
            }
        }

        return $map;
    }

    private static function normalizeLicenceKey(string $licence): string
    {
        $lic = trim($licence);
        if ($lic === '') {
            return '';
        }
        return self::formatLicence($lic) ?: $lic;
    }

    /**
     * @param array<string, string> $categoriesIdToAbv
     */
    private static function categorieClassementKey(array $insc, array $categoriesIdToAbv = []): string
    {
        $cat = trim((string)($insc['abv_categorie_classement'] ?? ''));
        if ($cat === '') {
            $cat = trim((string)($insc['categorie_classement'] ?? ''));
        }
        if ($cat !== '' && is_numeric($cat)) {
            $id = (int)$cat;
            if (isset($categoriesIdToAbv[$id])) {
                $cat = trim((string)$categoriesIdToAbv[$id]);
            } elseif (isset($categoriesIdToAbv[(string)$id])) {
                $cat = trim((string)$categoriesIdToAbv[(string)$id]);
            }
        }
        return $cat !== '' ? $cat : 'Sans catégorie';
    }

    private static function numeroDepartKey(array $insc): string
    {
        $nd = self::resolveNumeroDepart($insc);
        return $nd > 0 ? (string)$nd : '0';
    }

    private static function resolveNumeroDepart(array $insc): int
    {
        foreach (['numero_depart', 'numeroDepart', 'depart'] as $key) {
            if (!isset($insc[$key]) || $insc[$key] === '' || $insc[$key] === null) {
                continue;
            }
            $nd = (int)$insc[$key];
            if ($nd > 0) {
                return $nd;
            }
        }
        return 0;
    }

    private static function isPremierTir(array $insc): bool
    {
        $nt = $insc['numero_tir'] ?? null;
        return $nt === null || $nt === '' || (int)$nt === 1;
    }

    /** Groupe de classement : catégorie + n° départ. */
    private static function groupeClassementKey(array $insc, array $categoriesIdToAbv = []): string
    {
        return self::categorieClassementKey($insc, $categoriesIdToAbv) . '|' . self::numeroDepartKey($insc);
    }

    /** Groupe classement général export scores : catégorie + n° de tir. */
    private static function groupeClassementParTirKey(array $insc, array $categoriesIdToAbv = []): string
    {
        return self::categorieClassementKey($insc, $categoriesIdToAbv) . '|' . self::numeroTirSortKey($insc);
    }

    private static function rangParGroupeMapKey(string $licence, array $insc, array $categoriesIdToAbv = []): string
    {
        return self::normalizeLicenceKey($licence) . '|' . self::groupeClassementKey($insc, $categoriesIdToAbv);
    }

    private static function rangParTirMapKey(string $licence, array $insc, array $categoriesIdToAbv = []): string
    {
        return self::normalizeLicenceKey($licence) . '|' . self::groupeClassementParTirKey($insc, $categoriesIdToAbv);
    }

    /**
     * Meilleur score sur le même départ (tous les tirs), puis n° de tir le plus bas en cas d'égalité.
     *
     * @param array{inscription: array, resultat: ?array, score: int} $candidate
     * @param array{inscription: array, resultat: ?array, score: int} $current
     */
    private static function preferInscriptionForRankingOnDepart(array $candidate, array $current): bool
    {
        $diff = ($candidate['score'] ?? 0) - ($current['score'] ?? 0);
        if ($diff !== 0) {
            return $diff > 0;
        }
        $ntC = $candidate['inscription']['numero_tir'] ?? null;
        $ntCur = $current['inscription']['numero_tir'] ?? null;
        $tirC = ($ntC === null || $ntC === '') ? 1 : (int)$ntC;
        $tirCur = ($ntCur === null || $ntCur === '') ? 1 : (int)$ntCur;
        return $tirC < $tirCur;
    }

    /**
     * @return callable(array): ?array
     */
    private static function makeResultatResolver(array $resultats, array $resultatsByLicence): callable
    {
        $index = $resultatsByLicence;
        foreach ($resultats as $r) {
            if (!is_array($r)) {
                continue;
            }
            $inscId = $r['inscription_id'] ?? $r['inscriptionId'] ?? null;
            if ($inscId !== null && $inscId !== '') {
                $index['id:' . (int)$inscId] = $r;
            }
            $lic = trim((string)($r['numero_licence'] ?? ''));
            if ($lic !== '') {
                $index[$lic] = $r;
                $norm = self::normalizeLicenceKey($lic);
                if ($norm !== '') {
                    $index[$norm] = $r;
                }
            }
        }

        return function (array $insc) use ($index): ?array {
            $inscId = $insc['id'] ?? $insc['_id'] ?? null;
            if ($inscId !== null && $inscId !== '' && isset($index['id:' . (int)$inscId])) {
                return $index['id:' . (int)$inscId];
            }
            $lic = trim((string)($insc['numero_licence'] ?? ''));
            if ($lic !== '' && isset($index[$lic])) {
                return $index[$lic];
            }
            $norm = self::normalizeLicenceKey($lic);
            if ($norm !== '' && isset($index[$norm])) {
                return $index[$norm];
            }
            return null;
        };
    }

    /**
     * @param array<string, int> $map
     */
    private static function resolveRangClassementParCategorie(array $insc, array $map, array $categoriesIdToAbv = []): int
    {
        $lic = self::normalizeLicenceKey((string)($insc['numero_licence'] ?? ''));
        if ($lic === '') {
            return 0;
        }
        return $map[self::rangParGroupeMapKey($lic, $insc, $categoriesIdToAbv)] ?? 0;
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
            return isset($r['nb_11']) || isset($r['nb_10']) || isset($r['nb_8']) || isset($r['nb_5']);
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

        $inscriptionsForLookup = [];
        foreach ($rows as $row) {
            $inscriptionsForLookup[] = $row['inscription'];
        }
        $nomPrenomByLicence = self::loadNomPrenomByLicencesForInscriptions($inscriptionsForLookup);
        $licences = [];
        foreach ($inscriptionsForLookup as $insc) {
            $lic = trim((string)($insc['numero_licence'] ?? ''));
            if ($lic !== '') {
                $licences[] = $lic;
            }
        }
        $licences = array_values(array_unique($licences));
        $sexeByLicence = !empty($licences) ? ArcherSearchController::getSexeByLicences($licences) : [];

        $resultatsForDetect = [];
        foreach ($rows as $row) {
            $r = $row['resultat'] ?? null;
            if (is_array($r) && $r !== []) {
                $resultatsForDetect[] = $r;
            }
        }
        [$is3D] = self::detectDisciplineFlags($resultatsForDetect, $options['disciplineAbv'] ?? null);
        $options['is3D'] = $is3D;

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
        $rangClassement = (int)($row['rangClassement'] ?? 0);
        $clubsMap = $options['clubsMap'] ?? [];
        $categoriesExport = $options['categoriesExport'] ?? [];
        $categoriesAgeIdToAbv = $options['categoriesAgeIdToAbv'] ?? [];
        $concours = $options['concours'] ?? null;
        $disciplineAbv = strtoupper(substr((string)($options['disciplineAbv'] ?? ''), 0, 1));
        $niveauAbv = self::mapNiveauFfta($options['niveauChampionnatAbv'] ?? '');
        $typeChpt = self::resolveTypeChampionnat($options['typeCompetitionName'] ?? '', $concours);
        $isShortDistance = in_array($disciplineAbv, ['S', 'I'], true);

        $abvCat = trim((string)($insc['abv_categorie_classement'] ?? $insc['categorie_classement'] ?? ''));
        $catMeta = $categoriesExport[$abvCat] ?? [];
        $fgFfta = !empty($catMeta['fg_ffta']);

        $licence = trim((string)($insc['numero_licence'] ?? ''));
        $typeLicence = strtoupper(substr(trim((string)($insc['type_licence'] ?? '')), 0, 1));
        if ($typeLicence === 'E' || $licence === '') {
            $licence = self::FFTA_LICENCE_ETRANGER;
        } else {
            $licence = self::formatLicence($licence);
        }

        $nomPrenom = self::resolveNomPrenomFromInscription($insc, $nomPrenomByLicence);
        $nom = $nomPrenom['nom'];
        $prenom = $nomPrenom['prenom'];
        $licRaw = trim((string)($insc['numero_licence'] ?? ''));

        $catageFfta = self::resolveCatageAbv($insc, $categoriesAgeIdToAbv);
        $catClassementFfta = self::formatCategorieClassementFfta($abvCat);

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
        $armeUtilisee = self::resolveArmeUtiliseeFfta($insc, $abvCat);
        $armeClassement = self::mapArmeClassementFftaExport($armeClassement);

        $clubNom = self::resolveClubNomFftaFromInscription($insc, $nomPrenomByLicence);
        $affiliation = self::clubAffiliationCode($insc, $clubsMap);

        $score = $res ? (int)($res['score'] ?? 0) : 0;
        $is3D = !empty($options['is3D']);
        $paille = $res ? self::num2($res['nb_paille'] ?? '') : '';
        if ($is3D) {
            // Tir 3D : champs FFTA 16 = nb 11, 17 = nb 10 (colonnes « dix » / « neuf » du format).
            $dix = $res ? self::num2($res['nb_11'] ?? '') : '';
            $neuf = $res ? self::num2($res['nb_10'] ?? '') : '';
        } else {
            $dix = $res ? self::num2($res['nb_10'] ?? $res['serie1_nb_10'] ?? '') : '';
            $neuf = $res ? self::num2($res['nb_9'] ?? $res['serie1_nb_9'] ?? $res['total_nb_9'] ?? '') : '';
        }

        $champ18 = self::resolveChamp18DistanceOuPiquet($insc);

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
        $fields[6] = $catageFfta;
        $fields[7] = $catClassementFfta;
        $fields[8] = $sexe;
        $fields[9] = substr($armeClassement, 0, 2);
        $fields[10] = '';
        $fields[11] = $clubNom;
        $fields[12] = $affiliation;
        $fields[13] = $score;
        $fields[14] = self::numOrZero($paille);
        $fields[15] = self::numOrZero($dix);
        $fields[16] = self::numOrZero($neuf);
        $fields[17] = self::numOrZero($champ18);
        $fields[18] = self::numOrZero($blason);
        $fields[19] = $dateConcours;
        $fields[20] = $lieu;
        $fields[21] = $rangClassement;
        $fields[22] = self::numOrZero($score1Dist);
        $fields[23] = self::numOrZero($score2Dist);
        $fields[24] = self::numOrZero($score3Dist);
        $fields[25] = self::numOrZero($score4Dist);
        // 26-46 : phases éliminatoires (vides)
        $placeDefinitive = self::inscriptionHasDuel($insc) ? $rangClassement : 0;
        $fields[47] = $placeDefinitive;
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
        $epreuve = strtoupper(substr(trim((string)($options['epreuve'] ?? 'a')), 0, 1));
        if ($epreuve === '' || !preg_match('/^[A-Z]$/', $epreuve)) {
            $epreuve = 'A';
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

    /**
     * @param array<int, array> $inscriptions
     * @return array<string, array{nom: string, prenom: string, cie: string}>
     */
    private static function loadNomPrenomByLicencesForInscriptions(array $inscriptions): array
    {
        $licencesToQuery = [];
        foreach ($inscriptions as $insc) {
            foreach (self::licenceLookupVariants((string)($insc['numero_licence'] ?? '')) as $variant) {
                $licencesToQuery[$variant] = true;
            }
        }
        if ($licencesToQuery === []) {
            return [];
        }

        $fromXml = ArcherSearchController::getNomPrenomByLicences(array_keys($licencesToQuery));
        $lookup = [];
        foreach ($fromXml as $xmlLic => $data) {
            foreach (self::licenceLookupVariants($xmlLic) as $variant) {
                $lookup[$variant] = $data;
            }
        }

        return $lookup;
    }

    /**
     * @return list<string>
     */
    private static function licenceLookupVariants(string $licence): array
    {
        $lic = trim($licence);
        if ($lic === '') {
            return [];
        }

        $variants = [$lic];
        $clean = strtoupper(preg_replace('/\s+/', '', $lic));
        if ($clean !== '' && $clean !== $lic) {
            $variants[] = $clean;
        }

        $formatted = self::formatLicence($lic);
        if ($formatted !== '') {
            $variants[] = $formatted;
        }

        $digits = preg_replace('/\D/', '', $lic);
        if ($digits !== '') {
            $variants[] = str_pad(substr($digits, -7), 7, '0', STR_PAD_LEFT);
            if (strlen($digits) === 7) {
                $variants[] = '0' . $digits;
            }
            if (strlen($digits) === 8 && $digits[0] === '0') {
                $variants[] = substr($digits, 1);
            }
        }

        if (preg_match('/^(\d{7,8})([A-Z])$/', $clean, $m)) {
            $variants[] = str_pad(substr($m[1], -7), 7, '0', STR_PAD_LEFT) . $m[2];
        }

        return array_values(array_unique(array_filter($variants, static function ($v) {
            return trim((string)$v) !== '';
        })));
    }

    /**
     * Nom / prénom tels qu'exportés FFTA (champs NOM et PRENOM) — utilisé pour le tri alphabétique.
     *
     * @param array<string, array{nom: string, prenom: string}> $nomPrenomByLicence
     * @return array{nom: string, prenom: string}
     */
    private static function resolveNomPrenomFromInscription(array $insc, array $nomPrenomByLicence = []): array
    {
        $licRaw = trim((string)($insc['numero_licence'] ?? ''));
        if ($licRaw !== '') {
            foreach (self::licenceLookupVariants($licRaw) as $variant) {
                if (isset($nomPrenomByLicence[$variant])) {
                    return [
                        'nom' => trim((string)$nomPrenomByLicence[$variant]['nom']),
                        'prenom' => trim((string)$nomPrenomByLicence[$variant]['prenom']),
                    ];
                }
            }
        }

        $nom = trim((string)($insc['nom'] ?? $insc['name'] ?? $insc['last_name'] ?? $insc['NOM'] ?? ''));
        $prenom = trim((string)($insc['prenom'] ?? $insc['first_name'] ?? $insc['firstName'] ?? $insc['user_prenom'] ?? $insc['PRENOM'] ?? ''));
        if ($nom !== '' || $prenom !== '') {
            return ['nom' => $nom, 'prenom' => $prenom];
        }

        $display = trim((string)($insc['user_nom'] ?? ''));
        $parts = preg_split('/\s+/', $display, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) >= 2) {
            // user_nom = « Prénom NOM » (format saisie concours)
            return ['nom' => array_pop($parts), 'prenom' => implode(' ', $parts)];
        }
        if (count($parts) === 1) {
            return ['nom' => $parts[0], 'prenom' => ''];
        }

        return ['nom' => '', 'prenom' => ''];
    }

    /**
     * Champ 12 FFTA (nom club tireur) : CIE du fichier licences XML, sinon club de l'inscription.
     *
     * @param array<string, array{nom: string, prenom: string, cie?: string}> $nomPrenomByLicence
     */
    private static function resolveClubNomFftaFromInscription(array $insc, array $nomPrenomByLicence = []): string
    {
        $licRaw = trim((string)($insc['numero_licence'] ?? ''));
        if ($licRaw !== '') {
            foreach (self::licenceLookupVariants($licRaw) as $variant) {
                if (!isset($nomPrenomByLicence[$variant])) {
                    continue;
                }
                $cie = trim((string)($nomPrenomByLicence[$variant]['cie'] ?? ''));
                if ($cie !== '') {
                    return $cie;
                }
            }
        }

        return trim((string)($insc['club_nom'] ?? $insc['club_name'] ?? ''));
    }

    private static function numeroTirSortKey(array $insc): int
    {
        $nt = $insc['numero_tir'] ?? null;
        return ($nt === null || $nt === '') ? 1 : (int)$nt;
    }

    private static function normalizeNameForSort(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($name, 'UTF-8');
        }
        return strtolower($name);
    }

    private static function compareAlpha(string $a, string $b): int
    {
        static $collator = null;
        static $collatorReady = false;
        if (!$collatorReady) {
            $collatorReady = true;
            if (class_exists('Collator')) {
                $c = new \Collator('fr_FR');
                if ($c->getErrorCode() === 0) {
                    $collator = $c;
                }
            }
        }
        if ($collator instanceof \Collator) {
            return $collator->compare($a, $b);
        }
        return strcasecmp($a, $b);
    }

    /**
     * Format FFTA : 7 chiffres (complétés à gauche par des zéros) + 1 lettre de contrôle.
     * Ex. 1234567A → 0123456A. Étrangers : 999999 (sans lettre).
     */
    private static function formatLicence(string $licence): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', trim($licence)));
        if ($clean === '') {
            return '';
        }

        if ($clean === self::FFTA_LICENCE_ETRANGER) {
            return self::FFTA_LICENCE_ETRANGER;
        }

        $letter = '';
        $digits = '';

        if (preg_match('/^(\d{1,8})([A-Z])$/', $clean, $m)) {
            $digits = $m[1];
            $letter = $m[2];
        } elseif (preg_match('/^\d+$/', $clean)) {
            $digits = $clean;
        } else {
            if (preg_match('/([A-Z])$/', $clean)) {
                $letter = substr($clean, -1);
                $clean = substr($clean, 0, -1);
            }
            $digits = preg_replace('/\D/', '', $clean);
        }

        if ($digits === '') {
            return '';
        }

        // 8 chiffres sans lettre : format historique avec zéro de tête (ex. 01234567 → 01234567).
        if ($letter === '' && strlen($digits) === 8 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        } elseif (strlen($digits) > 7) {
            $digits = substr($digits, -7);
        }

        $digits = str_pad($digits, 7, '0', STR_PAD_LEFT);

        return $letter !== '' ? $digits . $letter : $digits;
    }

    /**
     * Champ 18 : piquet (rouge=1, bleu=2, blanc=3) ou distance en mètres (18, 25, 30…).
     */
    private static function resolveChamp18DistanceOuPiquet(array $insc): string
    {
        $piquetRaw = trim((string)($insc['piquet'] ?? ''));
        if ($piquetRaw !== '') {
            $codePiquet = self::mapPiquetCouleurFfta($piquetRaw);
            if ($codePiquet !== '') {
                return $codePiquet;
            }
        }
        if (isset($insc['distance']) && $insc['distance'] !== '' && $insc['distance'] !== null) {
            return $insc['distance'];
        }
        return '';
    }

    /** Rouge=1, bleu=2, blanc=3 (spec FFTA champ 18). */
    private static function mapPiquetCouleurFfta(string $piquet): string
    {
        $p = strtolower(trim($piquet));
        $map = [
            'rouge' => '1',
            'bleu' => '2',
            'blanc' => '3',
            '1' => '1',
            '2' => '2',
            '3' => '3',
        ];
        return $map[$p] ?? '';
    }

    /** Champ 8 : catégorie de classement (2 car. si commence par S, sinon 3). */
    private static function formatCategorieClassementFfta(string $abvCat): string
    {
        $abv = trim($abvCat);
        if ($abv === '') {
            return '';
        }
        $len = (strtoupper($abv[0]) === 'S') ? 2 : 3;
        return substr($abv, 0, $len);
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

    /** Arc nu (BB) : champ 10 (arme de classement) → CL. Champ 50 (arme utilisée) conserve BB. */
    private static function mapArmeClassementFftaExport(string $arme): string
    {
        $arme = strtoupper(substr(trim($arme), 0, 2));
        return $arme === 'BB' ? 'CL' : $arme;
    }

    /**
     * Champ 50 FFTA (arme utilisée sur le pas de tir) — indépendant de l'arme de classement (champ 10).
     */
    private static function resolveArmeUtiliseeFfta(array $insc, string $abvCat): string
    {
        $fromArc = strtoupper(substr(trim((string)($insc['abv_arc'] ?? '')), 0, 2));
        if ($fromArc === 'BB') {
            return 'BB';
        }

        $idarc = (int)($insc['idarc'] ?? $insc['id_arc'] ?? 0);
        if ($idarc === 5) {
            return 'BB';
        }

        $abvCatTrim = trim($abvCat);
        if ($abvCatTrim !== '' && preg_match('/BB$/i', $abvCatTrim)) {
            return 'BB';
        }
        if (strtoupper(self::extractArmeAbv($abvCatTrim, '')) === 'BB') {
            return 'BB';
        }

        $arcLabel = strtoupper(trim((string)($insc['arc'] ?? $insc['arme'] ?? $insc['lb_arc'] ?? '')));
        if ($arcLabel !== '' && (
            str_contains($arcLabel, 'ARC NU')
            || str_contains($arcLabel, 'BAREBOW')
            || preg_match('/\bBB\b/', $arcLabel)
        )) {
            return 'BB';
        }

        if ($fromArc !== '') {
            return $fromArc;
        }

        return '';
    }

    private static function mapNiveauFfta(string $abv): string
    {
        $abv = strtoupper(substr(trim($abv), 0, 1));
        $map = ['N' => 'N', 'R' => 'R', 'D' => 'D', 'C' => 'C', 'I' => 'I', 'S' => 'N', 'E' => 'I', 'M' => 'I', 'O' => 'I'];
        return $map[$abv] ?? ($abv !== '' ? $abv : 'C');
    }

    /**
     * Champ 3 : I (individuel) par défaut, E uniquement si le type de compétition est un tir en équipe.
     */
    private static function resolveTypeChampionnat(string $typeName, $concours = null): string
    {
        $labels = [strtoupper(trim($typeName))];
        if (is_object($concours)) {
            $labels[] = strtoupper(trim((string)($concours->type_competition_text ?? '')));
        } elseif (is_array($concours)) {
            $labels[] = strtoupper(trim((string)($concours['type_competition_text'] ?? '')));
        }
        foreach ($labels as $name) {
            if ($name !== '' && (str_contains($name, 'EQUIPE') || str_contains($name, 'ÉQUIPE'))) {
                return 'E';
            }
        }
        return 'I';
    }

    /**
     * Champ 7 : CATAGE (catégorie d'âge FFTA, ex. U13, S1).
     *
     * @param array<string, string> $categoriesAgeIdToAbv
     */
    private static function resolveCatageAbv(array $insc, array $categoriesAgeIdToAbv): string
    {
        $raw = trim((string)($insc['catage'] ?? ''));
        if ($raw !== '' && isset($categoriesAgeIdToAbv[$raw])) {
            return substr(trim((string)$categoriesAgeIdToAbv[$raw]), 0, 3);
        }
        if ($raw !== '' && is_numeric($raw)) {
            $key = (string)(int)$raw;
            if (isset($categoriesAgeIdToAbv[$key])) {
                return substr(trim((string)$categoriesAgeIdToAbv[$key]), 0, 3);
            }
        }
        if (preg_match('/(U1[1358]|U21|S[123])/i', $raw, $m)) {
            return strtoupper($m[1]);
        }
        return substr($raw, 0, 3);
    }

    /**
     * Duels prévus au concours (option « Duel » à la création/édition du concours).
     * Le champ 22 (place qualif.) n'est calculé que dans ce cas (sinon 0).
     *
     * @param array<string, mixed> $exportOptions
     */
    private static function concoursHasDuelsPrevus(array $exportOptions): bool
    {
        $concours = $exportOptions['concours'] ?? null;
        if ($concours === null) {
            return false;
        }
        $v = is_object($concours) ? ($concours->duel ?? null) : ($concours['duel'] ?? null);
        return $v === 1 || $v === true
            || in_array(strtolower(trim((string)$v)), ['1', 'true', 'oui', 'on'], true);
    }

    /** Champ 48 (place définitives) : renseigné seulement si l'archer est engagé en duel. */
    private static function inscriptionHasDuel(array $insc): bool
    {
        $v = $insc['duel'] ?? null;
        return $v === 1 || $v === true
            || in_array(strtolower(trim((string)$v)), ['1', 'true', 'oui', 'on'], true);
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

    private static function numOrZero($value): string
    {
        if ($value === '' || $value === null) {
            return '0';
        }
        return (string)$value;
    }

    private static function num2($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return (string)max(0, min(99, (int)$value));
    }

    private static function num3($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return (string)max(0, min(999, (int)$value));
    }

    private static function num4($value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        return (string)max(0, min(9999, (int)$value));
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
