    </main>
<!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h5>Portail Archers de Gémenos</h5>
                    <p class="mb-0">Gestion de l'application mobile de tir à l'arc</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-code me-1"></i>
                        Développé par André Moriana pour les Archers de Gémenos
                    </p>
                    <small class="text-muted">Version 1.0.0</small>
                </div>
            </div>
        </div>
    </footer>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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