/**
 * Scripts JavaScript pour la gestion des tirs comptés
 */

class ScoredTrainingManager {
    constructor() {
        this.currentTraining = null;
        this.currentEnd = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeComponents();
    }

    bindEvents() {
        // Gestion des modals
        document.addEventListener('shown.bs.modal', (e) => {
            if (e.target.id === 'addEndModal') {
                this.initializeScoreFields();
            }
        });

        // Gestion des formulaires
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'createForm') {
                e.preventDefault();
                this.createTraining();
            }
            if (e.target.id === 'addEndForm') {
                e.preventDefault();
                this.addEnd();
            }
            if (e.target.id === 'endTrainingForm') {
                e.preventDefault();
                this.endTraining();
            }
        });

        // Gestion des changements de valeurs
        document.addEventListener('input', (e) => {
            if (e.target.id === 'total_ends' || e.target.id === 'arrows_per_end') {
                this.updatePreview();
            }
        });
        
    }

    initializeComponents() {
        // Initialiser les tooltips Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialiser l'aperçu si on est sur la page de création
        if (document.getElementById('createForm')) {
            this.updatePreview();
        }
    }

    // Gestion des tirs comptés
    async createTraining() {
        if (this.isLoading) return;

        const form = document.getElementById('createForm');
        const formData = new FormData(form);
        
        const data = {
            title: formData.get('title'),
            total_ends: parseInt(formData.get('total_ends')),
            arrows_per_end: parseInt(formData.get('arrows_per_end')),
            exercise_sheet_id: formData.get('exercise_sheet_id') || null,
            notes: formData.get('notes'),
            shooting_type: formData.get('shooting_type') || null
        };

        // Validation
        if (!this.validateTrainingData(data)) {
            return;
        }

        this.setLoading(true);
        
        try {
            const response = await fetch('/scored-trainings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Tir compté créé avec succès');
                
                // Gérer la structure imbriquée de la réponse
                let trainingId = null;
                if (result.data && result.data.id) {
                    // Structure directe
                    trainingId = result.data.id;
                } else if (result.data && result.data.data && result.data.data.id) {
                    // Structure imbriquée
                    trainingId = result.data.data.id;
                }
                
                if (trainingId) {
                    window.location.href = '/scored-trainings/' + trainingId;
                } else {
                    this.showError('Erreur: ID du tir compté manquant dans la réponse');
                }
            } else {
                this.showError('Erreur: ' + (result.message || 'Erreur inconnue'));
            }
        } catch (error) {
            this.showError('Erreur lors de la création du tir compté');
        } finally {
            this.setLoading(false);
        }
    }

    async addEnd() {
        if (this.isLoading) return;

        const form = document.getElementById('addEndForm');
        const formData = new FormData(form);
        
        const scores = [];
        const scoreInputs = form.querySelectorAll('input[name="scores[]"]');
        scoreInputs.forEach(input => {
            scores.push(parseInt(input.value) || 0);
        });

        const endData = {
            end_number: parseInt(formData.get('end_number')),
            target_category: formData.get('target_category'),
            shooting_position: formData.get('shooting_position'),
            comment: formData.get('comment'),
            scores: scores
        };

        // Validation
        if (!this.validateEndData(endData)) {
            return;
        }

        this.setLoading(true);

        try {
            const response = await fetch(`/scored-trainings/${this.getTrainingId()}/ends`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ end_data: endData })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess('Volée ajoutée avec succès');
                location.reload();
            } else {
                this.showError('Erreur: ' + (result.message || 'Erreur inconnue'));
            }
        } catch (error) {
            this.showError('Erreur lors de l\'ajout de la volée');
        } finally {
            this.setLoading(false);
        }
    }

    async endTraining() {
        if (this.isLoading) return;

        const form = document.getElementById('endTrainingForm');
        const formData = new FormData(form);
        
        const data = {
            notes: formData.get('final_notes'),
            shooting_type: formData.get('final_shooting_type')
        };

        this.setLoading(true);

        try {
            const response = await fetch(`/scored-trainings/${this.getTrainingId()}/end`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess('Tir compté finalisé avec succès');
                location.reload();
            } else {
                this.showError('Erreur: ' + (result.message || 'Erreur inconnue'));
            }
        } catch (error) {
            this.showError('Erreur lors de la finalisation');
        } finally {
            this.setLoading(false);
        }
    }

    async deleteTraining(trainingId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce tir compté ?')) {
            return;
        }

        this.setLoading(true);

        try {
            const response = await fetch(`/scored-trainings/${trainingId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess('Tir compté supprimé avec succès');
                window.location.href = '/scored-trainings';
            } else {
                this.showError('Erreur: ' + (result.message || 'Erreur inconnue'));
            }
        } catch (error) {
            this.showError('Erreur lors de la suppression');
        } finally {
            this.setLoading(false);
        }
    }

    async deleteEnd(endId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette volée ?')) {
            return;
        }

        this.setLoading(true);

        try {
            const response = await fetch(`/scored-trainings/end/${endId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess('Volée supprimée avec succès');
                location.reload();
            } else {
                this.showError('Erreur: ' + (result.message || 'Erreur inconnue'));
            }
        } catch (error) {
            this.showError('Erreur lors de la suppression');
        } finally {
            this.setLoading(false);
        }
    }

    // Fonctions utilitaires
    validateTrainingData(data) {
        if (!data.title.trim()) {
            this.showError('Le titre est requis');
            return false;
        }

        if (data.total_ends < 1 || data.total_ends > 50) {
            this.showError('Le nombre de volées doit être entre 1 et 50');
            return false;
        }

        if (data.arrows_per_end < 1 || data.arrows_per_end > 12) {
            this.showError('Le nombre de flèches par volée doit être entre 1 et 12');
            return false;
        }

        return true;
    }

    validateEndData(data) {
        if (data.end_number < 1) {
            this.showError('Le numéro de volée doit être supérieur à 0');
            return false;
        }

        if (data.scores.length === 0) {
            this.showError('Veuillez saisir au moins un score');
            return false;
        }

        for (let score of data.scores) {
            if (score < 0 || score > 10) {
                this.showError('Les scores doivent être entre 0 et 10');
                return false;
            }
        }

        return true;
    }

    initializeScoreFields() {
        const container = document.getElementById('scoresContainer');
        if (!container) return;

        const arrowsPerEnd = this.getArrowsPerEnd();
        container.innerHTML = '';

        for (let i = 1; i <= arrowsPerEnd; i++) {
            const col = document.createElement('div');
            col.className = 'col-md-2 mb-2';
            col.innerHTML = `
                <label class="form-label">Flèche ${i}</label>
                <input type="number" class="form-control" name="scores[]" min="0" max="10" required>
            `;
            container.appendChild(col);
        }
    }

    updatePreview() {
        const totalEnds = parseInt(document.getElementById('total_ends')?.value) || 0;
        const arrowsPerEnd = parseInt(document.getElementById('arrows_per_end')?.value) || 0;
        const totalArrows = totalEnds * arrowsPerEnd;
        const maxScore = totalArrows * 10;

        const previewEnds = document.getElementById('preview_ends');
        const previewArrows = document.getElementById('preview_arrows');
        const previewTotal = document.getElementById('preview_total');
        const previewMax = document.getElementById('preview_max');

        if (previewEnds) previewEnds.textContent = totalEnds;
        if (previewArrows) previewArrows.textContent = arrowsPerEnd;
        if (previewTotal) previewTotal.textContent = totalArrows;
        if (previewMax) previewMax.textContent = maxScore;
    }

    getTrainingId() {
        const path = window.location.pathname;
        const matches = path.match(/\/scored-trainings\/(\d+)/);
        return matches ? matches[1] : null;
    }

    getArrowsPerEnd() {
        // Essayer de récupérer depuis les données globales du tir compté
        if (window.scoredTrainingData && window.scoredTrainingData.arrows_per_end) {
            return parseInt(window.scoredTrainingData.arrows_per_end);
        }
        
        // Essayer de récupérer depuis l'élément hidden ou depuis les données globales
        const hiddenInput = document.getElementById('arrows_per_end');
        if (hiddenInput) {
            return parseInt(hiddenInput.value) || 6;
        }
        
        // Fallback
        return 6;
    }

    setLoading(loading) {
        this.isLoading = loading;
        const buttons = document.querySelectorAll('button[type="submit"], .btn-primary, .btn-success');
        
        buttons.forEach(button => {
            if (loading) {
                button.disabled = true;
                const originalText = button.innerHTML;
                button.setAttribute('data-original-text', originalText);
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
            } else {
                button.disabled = false;
                const originalText = button.getAttribute('data-original-text');
                if (originalText) {
                    button.innerHTML = originalText;
                    button.removeAttribute('data-original-text');
                }
            }
        });
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showError(message) {
        this.showAlert(message, 'danger');
    }

    showAlert(message, type) {
        // Créer l'alerte
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Ajouter à la page
        document.body.appendChild(alertDiv);

        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Fonctions de filtrage
    filterTrainings() {
        const exerciseFilter = document.getElementById('exerciseFilter')?.value || '';
        const shootingTypeFilter = document.getElementById('shootingTypeFilter')?.value || '';
        const statusFilter = document.getElementById('statusFilter')?.value || '';
        
        const rows = document.querySelectorAll('#scoredTrainingsTable tbody tr');
        
        rows.forEach(row => {
            const exerciseId = row.dataset.exerciseId || '';
            const shootingType = row.dataset.shootingType || '';
            const status = row.dataset.status || '';
            
            let show = true;
            
            if (exerciseFilter && exerciseId !== exerciseFilter) {
                show = false;
            }
            
            if (shootingTypeFilter && shootingType !== shootingTypeFilter) {
                show = false;
            }
            
            if (statusFilter && status !== statusFilter) {
                show = false;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    // Fonctions de gestion des modals
    openCreateModal() {
        const modal = new bootstrap.Modal(document.getElementById('createModal'));
        modal.show();
    }

    openAddEndModal() {
        this.initializeScoreFields();
        const modal = new bootstrap.Modal(document.getElementById('addEndModal'));
        modal.show();
    }

    openEndTrainingModal() {
        const modal = new bootstrap.Modal(document.getElementById('endTrainingModal'));
        modal.show();
    }
    
}

// Fonctions globales pour compatibilité avec les vues existantes
let scoredTrainingManager;

// Initialiser le gestionnaire
document.addEventListener('DOMContentLoaded', function() {
    scoredTrainingManager = new ScoredTrainingManager();
});

// Fonctions globales
function openCreateModal() {
    if (scoredTrainingManager) {
        scoredTrainingManager.openCreateModal();
    }
}

function createTraining() {
    if (scoredTrainingManager) {
        scoredTrainingManager.createTraining();
    }
}

function addEnd() {
    if (scoredTrainingManager) {
        scoredTrainingManager.openAddEndModal();
    }
}

function saveEnd() {
    if (scoredTrainingManager) {
        scoredTrainingManager.addEnd();
    }
}

function endTraining() {
    if (scoredTrainingManager) {
        scoredTrainingManager.openEndTrainingModal();
    }
}

function confirmEndTraining() {
    if (scoredTrainingManager) {
        scoredTrainingManager.endTraining();
    }
}

function deleteTraining(trainingId) {
    if (scoredTrainingManager) {
        scoredTrainingManager.deleteTraining(trainingId);
    }
}

function deleteEnd(endId) {
    if (scoredTrainingManager) {
        scoredTrainingManager.deleteEnd(endId);
    }
}

function viewTraining(trainingId) {
    // Convertir en nombre si c'est une chaîne
    const id = parseInt(trainingId);
    
    if (id && id > 0) {
        // Récupérer l'user_id sélectionné depuis l'URL actuelle
        const urlParams = new URLSearchParams(window.location.search);
        const selectedUserId = urlParams.get('user_id');
        
        let url = '/scored-trainings/' + id;
        if (selectedUserId) {
            url += '?user_id=' + selectedUserId;
        }
        window.location.href = url;
    } else {
        alert('Erreur: ID du tir compté invalide');
    }
}

function continueTraining(trainingId) {
    // Convertir en nombre si c'est une chaîne
    const id = parseInt(trainingId);
    
    if (id && id > 0) {
        const url = '/scored-trainings/' + id;
        window.location.href = url;
    } else {
        alert('Erreur: ID du tir compté invalide');
    }
}

function filterTrainings() {
    if (scoredTrainingManager) {
        scoredTrainingManager.filterTrainings();
    }
}

// Configuration automatique selon le type de tir (fonction globale)
function updateShootingConfiguration() {
    const shootingType = document.getElementById('shooting_type').value;
    const totalEndsInput = document.getElementById('total_ends');
    const arrowsPerEndInput = document.getElementById('arrows_per_end');
    const configurations = {
        'TAE': { totalEnds: 12, arrowsPerEnd: 6 },
        'Salle': { totalEnds: 20, arrowsPerEnd: 3 },
        '3D': { totalEnds: 24, arrowsPerEnd: 2 },
        'Nature': { totalEnds: 21, arrowsPerEnd: 2 },
        'Campagne': { totalEnds: 24, arrowsPerEnd: 3 },
        'Libre': { totalEnds: 10, arrowsPerEnd: 6 }
    };
    
    if (shootingType && configurations[shootingType]) {
        const config = configurations[shootingType];
        totalEndsInput.value = config.totalEnds;
        arrowsPerEndInput.value = config.arrowsPerEnd;
        
        // Déclencher l'événement input pour mettre à jour l'aperçu
        totalEndsInput.dispatchEvent(new Event('input', { bubbles: true }));
        arrowsPerEndInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
}


// Fonction pour initialiser les champs de score (compatibilité)
function initializeScoreFields() {
    if (scoredTrainingManager) {
        scoredTrainingManager.initializeScoreFields();
    }
}

// Fonction pour mettre à jour l'aperçu (compatibilité)
function updatePreview() {
    if (scoredTrainingManager) {
        scoredTrainingManager.updatePreview();
    }
}
