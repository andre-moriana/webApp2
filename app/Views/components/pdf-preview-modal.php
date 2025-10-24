<!-- Modal d'aperçu PDF -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" role="dialog" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfPreviewModalLabel">
                    <i class="fas fa-file-pdf text-danger"></i> Aperçu du document
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div id="pdfLoadingSpinner" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement du document...</p>
                </div>
                <div id="pdfError" class="text-center py-5" style="display: none;">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5>Impossible de charger le document</h5>
                    <p class="text-muted">Le fichier PDF ne peut pas être affiché dans le navigateur.</p>
                    <a id="pdfDownloadLink" href="#" class="btn btn-primary" download>
                        <i class="fas fa-download"></i> Télécharger le fichier
                    </a>
                </div>
                <iframe id="pdfViewer" 
                        src="" 
                        width="100%" 
                        height="600px" 
                        style="border: none; display: none;"
                        onload="hidePdfLoading()"
                        onerror="showPdfError()">
                </iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePdfModal()">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <a id="pdfDownloadBtn" href="#" class="btn btn-primary" download>
                    <i class="fas fa-download"></i> Télécharger
                </a>
                <button type="button" class="btn btn-info" onclick="openPdfInNewTab()">
                    <i class="fas fa-external-link-alt"></i> Ouvrir dans un nouvel onglet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Fonctions globales pour l'aperçu PDF
window.showPdfPreview = function(pdfUrl, fileName) {
    // Fonction helper pour sélectionner des éléments
    function $(selector) {
        return document.querySelector(selector);
    }
    
    function $$(selector) {
        return document.querySelectorAll(selector);
    }
    
    // Afficher la modale
    const modal = document.getElementById('pdfPreviewModal');
    if (modal) {
        // Utiliser Bootstrap modal si disponible, sinon afficher directement
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            modal.style.display = 'block';
            modal.classList.add('show');
        }
    }
    
    // Réinitialiser l'état
    const loadingSpinner = document.getElementById('pdfLoadingSpinner');
    const errorDiv = document.getElementById('pdfError');
    const viewer = document.getElementById('pdfViewer');
    
    if (loadingSpinner) loadingSpinner.style.display = 'block';
    if (errorDiv) errorDiv.style.display = 'none';
    if (viewer) viewer.style.display = 'none';
    
    // Mettre à jour les liens de téléchargement
    const downloadLink = document.getElementById('pdfDownloadLink');
    const downloadBtn = document.getElementById('pdfDownloadBtn');
    
    if (downloadLink) {
        downloadLink.href = pdfUrl;
        downloadLink.download = fileName;
    }
    if (downloadBtn) {
        downloadBtn.href = pdfUrl;
        downloadBtn.download = fileName;
    }
    
    // Mettre à jour le titre
    const title = document.getElementById('pdfPreviewModalLabel');
    if (title) {
        title.innerHTML = '<i class="fas fa-file-pdf text-danger"></i> ' + fileName;
    }
    
    // Charger le PDF
    if (viewer) {
        viewer.src = pdfUrl;
    }
};

window.hidePdfLoading = function() {
    const loadingSpinner = document.getElementById('pdfLoadingSpinner');
    const viewer = document.getElementById('pdfViewer');
    
    if (loadingSpinner) loadingSpinner.style.display = 'none';
    if (viewer) viewer.style.display = 'block';
};

window.showPdfError = function() {
    const loadingSpinner = document.getElementById('pdfLoadingSpinner');
    const errorDiv = document.getElementById('pdfError');
    
    if (loadingSpinner) loadingSpinner.style.display = 'none';
    if (errorDiv) errorDiv.style.display = 'block';
};

window.openPdfInNewTab = function() {
    const viewer = document.getElementById('pdfViewer');
    if (viewer && viewer.src) {
        window.open(viewer.src, '_blank');
    }
};

// Fonction pour fermer la modale
window.closePdfModal = function() {
    const modal = document.getElementById('pdfPreviewModal');
    if (modal) {
        // Nettoyer l'iframe
        const viewer = document.getElementById('pdfViewer');
        if (viewer) {
            viewer.src = '';
            viewer.style.display = 'none';
        }
        
        // Réinitialiser l'état
        const loadingSpinner = document.getElementById('pdfLoadingSpinner');
        const errorDiv = document.getElementById('pdfError');
        
        if (loadingSpinner) loadingSpinner.style.display = 'none';
        if (errorDiv) errorDiv.style.display = 'none';
        
        // Fermer la modale
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        } else {
            modal.style.display = 'none';
            modal.classList.remove('show');
            // Retirer le backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
    }
};

// Initialisation quand le document est prêt
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('pdfPreviewModal');
    if (modal) {
        // Gérer la fermeture de la modale avec Bootstrap
        modal.addEventListener('hidden.bs.modal', function () {
            window.closePdfModal();
        });
        
        // Gérer le clic sur le bouton de fermeture (X)
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.closePdfModal();
            });
        }
        
        // Gérer le clic sur le bouton "Fermer"
        const closeButton = modal.querySelector('button[data-dismiss="modal"]');
        if (closeButton) {
            closeButton.addEventListener('click', function(e) {
                e.preventDefault();
                window.closePdfModal();
            });
        }
        
        // Gérer le clic sur le backdrop (arrière-plan)
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                window.closePdfModal();
            }
        });
        
        // Gérer la touche Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                window.closePdfModal();
            }
        });
    }
});
</script>
