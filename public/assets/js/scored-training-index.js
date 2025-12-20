/**
 * JavaScript pour la page d'index des tirs comptés
 * Gestion des filtres, modales et actions
 */

// Variables globales
let scoredTrainings = [];
let exercises = [];

// Initialiser les données depuis PHP
function initializeData() {
    if (window.scoredTrainingsData) {
        scoredTrainings = window.scoredTrainingsData;
    }
    if (window.exercisesData) {
        exercises = window.exercisesData;
    }
}

// Fonctions de filtrage
function filterTrainings() {
    const exerciseFilter = document.getElementById('exerciseFilter').value;
    const shootingTypeFilter = document.getElementById('shootingTypeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    const rows = document.querySelectorAll('#scoredTrainingsTable tbody tr');
    
    rows.forEach(row => {
        const exerciseId = row.dataset.exerciseId;
        const shootingType = row.dataset.shootingType;
        const status = row.dataset.status;
        
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

// Fonctions de gestion des tirs comptés
function openCreateModal() {
    const modal = new bootstrap.Modal(document.getElementById('createModal'));
    modal.show();
}

function createTraining() {
    const form = document.getElementById('createForm');
    if (!form) return;
    
    const formData = new FormData(form);
    
    const data = {
        title: formData.get('title'),
        total_ends: parseInt(formData.get('total_ends')),
        arrows_per_end: parseInt(formData.get('arrows_per_end')),
        exercise_sheet_id: formData.get('exercise_sheet_id') || null,
        notes: formData.get('notes'),
        shooting_type: formData.get('shooting_type') || null
    };
    
    fetch('/scored-trainings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // L'ID est dans result.data.data.id (structure imbriquée)
            const trainingId = result.data?.data?.id;
            
            if (trainingId) {
                const redirectUrl = '/scored-trainings/' + trainingId + '?add_end=true';
                // Rediriger vers la page de détail du tir compté créé avec paramètre pour ouvrir la modale
                window.location.href = redirectUrl;
            } else {
                alert('Erreur: ID du tir compté non trouvé dans la réponse du serveur');
            }
        } else {
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        alert('Erreur lors de la création du tir compté');
    });
}

function continueTraining(trainingId) {
    window.location.href = '/scored-trainings/' + trainingId;
}

function deleteTraining(trainingId) {
    // Vérifier si l'utilisateur est connecté
    if (typeof window.isLoggedIn !== 'undefined' && !window.isLoggedIn) {
        alert('Vous devez être connecté pour effectuer cette action.\n\nVeuillez vous reconnecter.');
        window.location.href = '/login';
        return;
    }
    
    if (confirm('Êtes-vous sûr de vouloir supprimer ce tir compté ?')) {
        // Faire la requête vers le contrôleur frontend
        fetch('/scored-trainings/delete/' + trainingId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                console.error('❌ Erreur HTTP:', response.status, response.statusText);
                throw new Error('Erreur HTTP: ' + response.status + ' ' + response.statusText);
            }
            return response.text();
        })
        .then(text => {
            // Nettoyer la réponse des caractères BOM et autres caractères invisibles
            let cleanText = text.replace(/^\uFEFF/, '').replace(/^\s+/, '').replace(/\s+$/, '');
            
            // Supprimer les warnings PHP qui peuvent apparaître avant le JSON
            cleanText = cleanText.replace(/^.*?(Warning:.*?\n)*/g, '');
            
            // Extraire seulement le JSON si il y a du contenu avant
            const jsonMatch = cleanText.match(/\{.*\}/s);
            if (jsonMatch) {
                cleanText = jsonMatch[0];
            }
            
            try {
                const result = JSON.parse(cleanText);
               
                if (result.success) {
                    // Préserver les paramètres de l'URL lors du rechargement
                    const currentUrl = new URL(window.location);
                    window.location.href = currentUrl.toString();
                } else {
                    
                    // Vérifier si c'est un problème d'authentification
                    if (result.message && (
                        result.message.includes('connecté') || 
                        result.message.includes('Token') ||
                        result.message.includes('authentification') ||
                        result.status_code === 401
                    )) {
                        alert('Erreur d\'authentification: ' + result.message + '\n\nVeuillez vous reconnecter.');
                        window.location.href = '/login';
                    } else if (result.status_code === 400) {
                        alert('Erreur de requête (400): ' + (result.message || 'Données invalides'));
                    } else {
                        alert('Erreur: ' + (result.message || 'Erreur inconnue'));
                    }
                }
            } catch (parseError) {
                alert('Erreur de décodage de la réponse du serveur:\n' + parseError.message + '\n\nTexte reçu: ' + cleanText.substring(0, 200));
            }
        })
        .catch(error => {
            alert('Erreur lors de la suppression: ' + error.message);
        });
    }
}

// Initialiser l'application quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    initializeData();
    
    // Écouter les changements dans les filtres
    const exerciseFilter = document.getElementById('exerciseFilter');
    const shootingTypeFilter = document.getElementById('shootingTypeFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (exerciseFilter) {
        exerciseFilter.addEventListener('change', filterTrainings);
    }
    
    if (shootingTypeFilter) {
        shootingTypeFilter.addEventListener('change', filterTrainings);
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', filterTrainings);
    }
});

// Exposer les fonctions globalement pour les appels depuis HTML
window.openCreateModal = openCreateModal;
window.createTraining = createTraining;
window.continueTraining = continueTraining;
window.deleteTraining = deleteTraining;
