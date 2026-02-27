<?php
$facebookDisabled = isset($facebookDisabled) && $facebookDisabled;
$clubName = $clubName ?? 'votre club';
$fbHref = $fbHref ?? '';
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h4 mb-4">
                <i class="fab fa-facebook me-2 text-primary"></i>
                Actualités du club
            </h1>

            <?php if ($facebookDisabled): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fab fa-facebook fa-4x text-muted mb-3"></i>
                        <p class="text-muted mb-2">Le fil d’actualités Facebook n’est pas affiché sur ce site.</p>
                        <p class="small text-muted mb-4">Vous pouvez suivre les actualités de <strong><?php echo htmlspecialchars($clubName); ?></strong> directement sur Facebook.</p>
                        <?php if ($fbHref !== ''): ?>
                            <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary me-2">
                                <i class="fab fa-facebook me-1"></i> Page Facebook du club
                            </a>
                        <?php endif; ?>
                        <?php
                        $user = $_SESSION['user'] ?? [];
                        $isAdmin = !empty($user['is_admin']);
                        $role = $user['role'] ?? '';
                        $isArcher = (stripos($role, 'archer') !== false || strtolower($role) === 'user');
                        if ($isAdmin || !$isArcher): ?>
                            <a href="/dashboard" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt me-1"></i> Tableau de bord
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else:
                $facebookUrl = isset($facebookUrl) ? trim((string)$facebookUrl) : '';
                $posts = $posts ?? [];
                $facebookConnected = $facebookConnected ?? false;
                $feedError = $feedError ?? '';
                $showFacebookPagePlugin = $showFacebookPagePlugin ?? false;
                $facebookAppId = $facebookAppId ?? '';
                if ($facebookUrl !== '' && $fbHref === '') {
                    $fbHref = (strpos($facebookUrl, 'http') === 0) ? $facebookUrl : 'https://www.facebook.com/' . ltrim($facebookUrl, '/');
                }
                $flashError = $_SESSION['club_feed_error'] ?? '';
                $flashSuccess = $_SESSION['club_feed_success'] ?? '';
                if ($flashError !== '') unset($_SESSION['club_feed_error']);
                if ($flashSuccess !== '') unset($_SESSION['club_feed_success']);
            ?>
            <?php if ($flashError): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flashError); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flashSuccess); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($showFacebookPagePlugin) && $facebookAppId !== '' && $fbHref !== ''): ?>
                <?php
                $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
                $currentDomain = preg_replace('/:\d+$/', '', $currentDomain);
                $domainOk = (strtolower($currentDomain) === 'arctraining.fr');
                ?>
                <?php if (!$domainOk): ?>
                <div class="alert alert-warning mb-3" role="alert">
                    <strong>Domaine actuel :</strong> <code><?php echo htmlspecialchars($currentDomain ?: 'inconnu'); ?></code>. Ajoutez-le dans <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com</a> → app « Feed page club » → Paramètres → Basique → <strong>Domaines de l'app</strong>. En local (<code>localhost</code>), le widget est souvent bloqué.
                </div>
                <?php else: ?>
                <p class="text-muted small mb-2">Si le fil ne s'affiche pas ci-dessous, testez en <strong>navigation privée</strong> ou désactivez temporairement les bloqueurs de publicité (Facebook charge un iframe).</p>
                <?php endif; ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <p class="text-muted small mb-3">Fil de la page Facebook de <strong><?php echo htmlspecialchars($clubName); ?></strong> (widget officiel Facebook).</p>
                        <div id="fb-root"></div>
                        <script>
                        window.fbAsyncInit = function() {
                            FB.init({
                                appId: '<?php echo htmlspecialchars($facebookAppId); ?>',
                                xfbml: true,
                                version: 'v18.0'
                            });
                            FB.Event.subscribe('xfbml.render', function() {
                                var el = document.getElementById('fb-page-wrap');
                                if (el) el.style.minHeight = '0';
                            });
                            var wrap = document.getElementById('fb-page-wrap');
                            if (wrap) FB.XFBML.parse(wrap);
                        };
                        </script>
                        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v18.0&appId=<?php echo htmlspecialchars($facebookAppId); ?>"></script>
                        <div id="fb-page-wrap" style="min-height: 400px; width: 100%; max-width: 500px;">
                            <div class="fb-page" data-href="<?php echo htmlspecialchars($fbHref); ?>" data-tabs="timeline" data-width="500" data-height="500" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"></div>
                        </div>
                        <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="fab fa-facebook me-1"></i> Voir la page sur Facebook
                        </a>
                    </div>
                </div>
            <?php elseif (!empty($showFacebookPagePlugin) && $fbHref !== '' && $facebookAppId === ''): ?>
                <div class="alert alert-info mb-4">
                    <strong>Widget Facebook non configuré.</strong> Pour afficher le fil de la page ici, ajoutez <code>FACEBOOK_APP_ID=1640559626974623</code> (ou l’ID de votre app) dans le fichier <code>.env</code> à la racine de WebApp2. Voir <strong>FACEBOOK-FIL-CLUB-CONFIG.md</strong>.
                </div>
            <?php endif; ?>

            <?php if ($facebookUrl === ''): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fab fa-facebook fa-4x text-muted mb-3"></i>
                        <p class="text-muted mb-0">
                            Aucune page Facebook n'est configurée pour <strong><?php echo htmlspecialchars($clubName); ?></strong>.
                        </p>
                        <p class="small text-muted mt-2">
                            Cette page affiche les actualités du club auquel votre compte est rattaché. Pour voir un fil Facebook ici, un dirigeant ou administrateur doit&nbsp;:
                        </p>
                        <ol class="small text-muted text-start mt-2 mb-3" style="max-width: 400px; margin-left: auto; margin-right: auto;">
                            <li>Aller dans <strong>Club</strong> puis modifier les infos du club <strong><?php echo htmlspecialchars($clubName); ?></strong>.</li>
                            <li>Renseigner l'URL de la page Facebook du club (ex. https://www.facebook.com/ArchersDeGemenos).</li>
                            <li>Revenir sur cette page et cliquer sur «&nbsp;Connecter la page Facebook&nbsp;» pour afficher les publications.</li>
                        </ol>
                        <a href="/dashboard" class="btn btn-outline-primary mt-3">
                            <i class="fas fa-tachometer-alt me-1"></i> Aller au tableau de bord
                        </a>
                    </div>
                </div>
            <?php elseif ($facebookConnected && empty($posts)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <i class="fab fa-facebook fa-3x text-primary mb-3"></i>
                        <?php if ($feedError !== ''): ?>
                            <p class="text-warning mb-2">Le fil n'a pas pu être chargé.</p>
                            <p class="small text-muted mb-3"><?php echo htmlspecialchars($feedError); ?></p>
                            <div class="small text-muted mb-3 text-start" style="max-width: 600px; margin-left: auto; margin-right: auto;">
                                <p class="mb-2"><strong>Procédure recommandée :</strong></p>
                                <ol class="mb-2 ps-3">
                                    <li class="mb-1">Cliquez sur <strong>«&nbsp;Déconnecter la page Facebook&nbsp;»</strong> ci‑dessous.</li>
                                    <li class="mb-1">Sur <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com</a>, ouvrez votre app.</li>
                                    <li class="mb-1"><strong>Ajouter la permission via les Cas d’usage :</strong>
                                        <ul class="mt-1 mb-0">
                                            <li>Dans le <strong>menu de gauche</strong> du tableau de bord, cliquez sur <strong>«&nbsp;Cas d’usage&nbsp;»</strong> (ou <em>Use cases</em>).</li>
                                            <li>Si besoin, ajoutez le cas d’usage <strong>«&nbsp;Gérer tout sur votre Page&nbsp;»</strong> (<em>Manage everything on your Page</em>).</li>
                                            <li>Cliquez sur ce cas d’usage, puis ajoutez la permission optionnelle <strong>pages_read_engagement</strong> (bouton <strong>Ajouter</strong> / <em>Add</em> à côté de la permission).</li>
                                            <li>Sinon, cherchez <strong>«&nbsp;Facebook Login&nbsp;»</strong> dans le menu gauche → <strong>Paramètres</strong> → onglet <strong>Autorisations et fonctionnalités</strong> pour les anciennes apps.</li>
                                        </ul>
                                    </li>
                                    <li class="mb-1"><strong>En mode Développement :</strong> le compte qui connecte la page doit être dans <strong>Rôles &gt; Rôles de l’application</strong> (vous l’avez déjà).</li>
                                    <li class="mb-1"><strong>En mode Live :</strong> soumettre une demande d’<strong>App Review</strong> pour «&nbsp;Pages Read Engagement&nbsp;» ou «&nbsp;Page Public Content Access&nbsp;».</li>
                                    <li class="mb-1"><strong>Révoquez l'app</strong> dans Facebook pour forcer la redemande des autorisations : <a href="https://www.facebook.com/settings?tab=applications" target="_blank" rel="noopener">Paramètres → Applications, sites web et intégrations</a>, retirez l'app de la liste.
                                    </li>
                                    <li class="mb-1">Revenez ici : <strong>«&nbsp;Déconnecter&nbsp;»</strong> puis <strong>«&nbsp;Connecter la page Facebook&nbsp;»</strong>.</li>
                                </ol>
                                <p class="mb-0 small"><strong>Important :</strong> En mode Développement, seul un compte <strong>Administrateur, Développeur ou Testeur</strong> de l’app peut connecter une page.</p>
                                <p class="mt-2 mb-0 small text-muted"><strong>Rappel :</strong> Les identifiants Facebook se configurent uniquement dans le .env de WebApp2 (pas dans l'API).</p>
                                <p class="mt-2 mb-0 small">Un guide pas à pas (avec les noms de menus que vous voyez) est dans le fichier <strong>FACEBOOK-FIL-CLUB-CONFIG.md</strong> à la racine du projet WebApp2.</p>
                            </div>
                            <a href="/club-feed/disconnect" class="btn btn-outline-danger me-2"><i class="fas fa-unlink me-1"></i>Déconnecter la page Facebook</a>
                            <a href="/club-feed/connect" class="btn btn-primary">Connecter la page Facebook</a>
                        <?php else: ?>
                            <p class="text-muted mb-3">Page connectée. Aucune publication récente pour le moment.</p>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
                            <i class="fab fa-facebook me-1"></i> Voir la page Facebook
                        </a>
                    </div>
                </div>
            <?php elseif (!empty($posts)): ?>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <p class="text-muted mb-0 small">
                        Dernières publications de <strong><?php echo htmlspecialchars($clubName); ?></strong> sur Facebook.
                    </p>
                    <a href="<?php echo htmlspecialchars($fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-facebook me-1"></i> Voir la page Facebook
                    </a>
                </div>
                <div class="row g-3">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if (!empty($post['full_picture'])): ?>
                                    <a href="<?php echo htmlspecialchars($post['permalink_url'] ?? $fbHref); ?>" target="_blank" rel="noopener noreferrer" class="d-block">
                                        <img src="<?php echo htmlspecialchars($post['full_picture']); ?>" class="card-img-top" alt="" style="object-fit: cover; height: 200px;">
                                    </a>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <?php if (!empty($post['message'])): ?>
                                        <p class="card-text flex-grow-1"><?php echo nl2br(htmlspecialchars(mb_substr($post['message'], 0, 300) . (mb_strlen($post['message']) > 300 ? '…' : ''))); ?></p>
                                    <?php endif; ?>
                                    <div class="mt-auto pt-2 d-flex align-items-center justify-content-between">
                                        <span class="text-muted small">
                                            <?php
                                            if (!empty($post['created_time'])) {
                                                $dt = new DateTime($post['created_time']);
                                                echo $dt->format('d/m/Y à H:i');
                                            }
                                            ?>
                                        </span>
                                        <a href="<?php echo htmlspecialchars($post['permalink_url'] ?? $fbHref); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                                            Voir sur Facebook
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fab fa-facebook fa-4x text-primary mb-3"></i>
                        <h2 class="h5 mb-3">Fil Facebook de <?php echo htmlspecialchars($clubName); ?></h2>
                        <p class="text-muted mb-4">
                            Pour afficher les publications directement sur cette page, connectez une fois la page Facebook du club. Un administrateur de la page doit cliquer ci-dessous et autoriser l'accès.
                        </p>
                        <a href="/club-feed/connect" class="btn btn-primary btn-lg">
                            <i class="fab fa-facebook me-2"></i> Connecter la page Facebook
                        </a>
                        <p class="text-muted small mt-3 mb-0">
                            Après connexion, les posts s'afficheront ici sans quitter le site.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
