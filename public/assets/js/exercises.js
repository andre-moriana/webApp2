// JavaScript pour la gestion des exercices
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les fonctionnalités des exercices
    initializeExerciseFeatures();
});

function initializeExerciseFeatures() {
    // Gestion des filtres de catégorie
    const categoryFilters = document.querySelectorAll('.exercise-category-filter .btn');
    categoryFilters.forEach(btn => {
        btn.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            categoryFilters.forEach(b => b.classList.remove('active'));
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Filtrer les exercices par catégorie
            const category = this.dataset.category;
            filterExercisesByCategory(category);
        });
    });
    
    // Gestion de la recherche
    const searchInput = document.getElementById('exercise-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterExercisesBySearch(searchTerm);
        });
    }
    
    // Gestion de la suppression avec confirmation
    const deleteButtons = document.querySelectorAll('[data-action="delete"]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const exerciseId = this.dataset.exerciseId;
            const exerciseTitle = this.dataset.exerciseTitle;
            
            if (confirm(`Êtes-vous sûr de vouloir supprimer l'exercice "${exerciseTitle}" ?`)) {
                deleteExercise(exerciseId);
            }
        });
    });
    
    // Gestion de l'upload de fichier PDF
    const pdfFileInput = document.getElementById('pdf_file');
    if (pdfFileInput) {
        pdfFileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Vérifier la taille du fichier (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    alert('Le fichier est trop volumineux. Taille maximale : 10MB');
                    this.value = '';
                    return;
                }
                
                // Vérifier le type de fichier
                if (file.type !== 'application/pdf') {
                    alert('Veuillez sélectionner un fichier PDF valide.');
                    this.value = '';
                    return;
                }
                
                // Afficher le nom du fichier
                const fileName = document.getElementById('file-name');
                if (fileName) {
                    fileName.textContent = file.name;
                }
            }
        });
    }
    
    // Gestion de la validation du formulaire
    const exerciseForm = document.querySelector('form[action*="/exercises"]');
    if (exerciseForm) {
        exerciseForm.addEventListener('submit', function(e) {
            if (!validateExerciseForm()) {
                e.preventDefault();
            }
        });
    }
}

function filterExercisesByCategory(category) {
    const exerciseCards = document.querySelectorAll('.exercise-card');
    
    exerciseCards.forEach(card => {
        const cardCategory = card.dataset.category;
        
        if (category === 'all' || cardCategory === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Mettre à jour le compteur d'exercices visibles
    updateExerciseCount();
}

function filterExercisesBySearch(searchTerm) {
    const exerciseCards = document.querySelectorAll('.exercise-card');
    
    exerciseCards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const description = card.querySelector('.card-text').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Mettre à jour le compteur d'exercices visibles
    updateExerciseCount();
}

function updateExerciseCount() {
    const visibleCards = document.querySelectorAll('.exercise-card[style*="block"], .exercise-card:not([style*="none"])');
    const countElement = document.getElementById('exercise-count');
    
    if (countElement) {
        countElement.textContent = visibleCards.length;
    }
}

function deleteExercise(exerciseId) {
    // Afficher un indicateur de chargement
    const deleteBtn = document.querySelector(`[data-exercise-id="${exerciseId}"]`);
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
    deleteBtn.disabled = true;
    
    // Faire la requête de suppression
    fetch(`/exercises/${exerciseId}`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (response.ok) {
            // Supprimer la carte de l'exercice
            const exerciseCard = deleteBtn.closest('.exercise-card');
            if (exerciseCard) {
                exerciseCard.remove();
            }
            
            // Afficher un message de succès
            showNotification('Exercice supprimé avec succès', 'success');
        } else {
            throw new Error('Erreur lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la suppression de l\'exercice', 'error');
        
        // Restaurer le bouton
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

function validateExerciseForm() {
    const title = document.getElementById('title').value.trim();
    const category = document.getElementById('category').value;
    
    if (!title) {
        showNotification('Le titre est obligatoire', 'error');
        document.getElementById('title').focus();
        return false;
    }
    
    if (!category) {
        showNotification('La catégorie est obligatoire', 'error');
        document.getElementById('category').focus();
        return false;
    }
    
    return true;
}

function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    // Insérer la notification en haut de la page
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(notification, container.firstChild);
        
        // Supprimer automatiquement la notification après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Fonction pour prévisualiser le fichier PDF
function previewPDF(file) {
    if (file && file.type === 'application/pdf') {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('pdf-preview');
            if (preview) {
                preview.innerHTML = `
                    <embed src="${e.target.result}" type="application/pdf" width="100%" height="400px">
                `;
            }
        };
        reader.readAsDataURL(file);
    }
}

// Fonction pour exporter les exercices
function exportExercises(format = 'json') {
    const exercises = Array.from(document.querySelectorAll('.exercise-card')).map(card => ({
        title: card.querySelector('.card-title').textContent,
        description: card.querySelector('.card-text').textContent,
        category: card.dataset.category,
        targetType: card.dataset.targetType,
        shootingType: card.dataset.shootingType,
        distance: card.dataset.distance
    }));
    
    if (format === 'json') {
        const dataStr = JSON.stringify(exercises, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        downloadFile(dataBlob, 'exercises.json');
    } else if (format === 'csv') {
        const csv = convertToCSV(exercises);
        const dataBlob = new Blob([csv], {type: 'text/csv'});
        downloadFile(dataBlob, 'exercises.csv');
    }
}

function convertToCSV(data) {
    const headers = ['Titre', 'Description', 'Catégorie', 'Type de cible', 'Type de tir', 'Distance'];
    const csvContent = [
        headers.join(','),
        ...data.map(row => [
            `"${row.title}"`,
            `"${row.description}"`,
            `"${row.category}"`,
            `"${row.targetType}"`,
            `"${row.shootingType}"`,
            `"${row.distance}"`
        ].join(','))
    ].join('\n');
    
    return csvContent;
}

function downloadFile(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
} 