    </div>
    </main>
<!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h5>Portail Arc Training</h5>
                    <p class="mb-0">Gestion club, compétitions et application mobile de tir à l'arc</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-code me-1"></i>
                        Développé par André Moriana pour Arc Training
                    </p>
                    <small class="text-muted">Version 1.0.0</small>
                    <p class="mt-2 mb-0">
                        <a href="/contact" class="text-light text-decoration-none me-3">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                        <a href="/privacy" class="text-light text-decoration-none">
                            <i class="fas fa-shield-alt me-1"></i>Protection des données personnelles
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jsPDF pour l'export PDF (si nécessaire) -->
    <?php if (isset($additionalJS) && in_array('/public/assets/js/score-sheet.js', $additionalJS)): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <?php endif; ?>
<?php
    if (!isset($skipSessionManager)) {
        $skipSessionManager = false;
        $footerPath = strtolower(rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/', '/')) ?: '/';
        $publicPathPrefixes = [
            '/login', '/contact', '/privacy', '/donnees-personnelles',
            '/auth/register', '/auth/forgot-password', '/auth/reset-password', '/auth/delete-account',
        ];
        foreach ($publicPathPrefixes as $prefix) {
            $normalizedPrefix = rtrim(strtolower($prefix), '/');
            if ($footerPath === $normalizedPrefix || strpos($footerPath, $normalizedPrefix . '/') === 0) {
                $skipSessionManager = true;
                break;
            }
        }
        if (!$skipSessionManager && strpos($footerPath, '/inscription-cible/') === 0) {
            $skipSessionManager = true;
        }
        if (!$skipSessionManager && strpos($footerPath, '/concours/') !== false
            && (strpos($footerPath, '/plan-peloton') !== false || strpos($footerPath, '/plan-cible') !== false)) {
            $skipSessionManager = true;
        }
    }
?>
<?php if (empty($skipSessionManager)): ?>
    <!-- Gestionnaire de session (pages authentifiées uniquement) -->
    <script src="/public/assets/js/session-manager.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
<!-- JavaScript personnalisé -->
    <script src="/public/assets/js/app.js"></script>
<!-- JavaScript spécifique à la page (si défini) -->
    <?php if (isset($additionalJS)): ?>
        <!-- DEBUG: additionalJS défini -->
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- DEBUG: additionalJS non défini -->
    <?php endif; ?>
</body>
</html>