    </main>
<!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h5>Portail Arc Training</h5>
                    <p class="mb-0">Gestion de l'application mobile de tir à l'arc</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-code me-1"></i>
                        Développé par André Moriana pour les Arc Training
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
<!-- Gestionnaire de session (doit être chargé en premier) -->
    <script src="/public/assets/js/session-manager.js"></script>
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