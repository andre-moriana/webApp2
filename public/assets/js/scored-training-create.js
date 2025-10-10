/**
 * JavaScript pour la page de création des tirs comptés
 * Gestion du formulaire et de l'aperçu en temps réel
 */

// Mise à jour de l'aperçu en temps réel
function updatePreview() {
    const totalEnds = parseInt(document.getElementById('total_ends').value) || 0;
    const arrowsPerEnd = parseInt(document.getElementById('arrows_per_end').value) || 0;
    const totalArrows = totalEnds * arrowsPerEnd;
    const maxScore = totalArrows * 10;
    
    document.getElementById('preview_ends').textContent = totalEnds;
    document.getElementById('preview_arrows').textContent = arrowsPerEnd;
    document.getElementById('preview_total').textContent = totalArrows;
    document.getElementById('preview_max').textContent = maxScore;
}

// Gestion de la soumission du formulaire
function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const data = {
        title: formData.get('title'),
        total_ends: parseInt(formData.get('total_ends')),
        arrows_per_end: parseInt(formData.get('arrows_per_end')),
        exercise_sheet_id: formData.get('exercise_sheet_id') || null,
        notes: formData.get('notes'),
        shooting_type: formData.get('shooting_type') || null
    };
    
    // Validation
    if (!data.title.trim()) {
        alert('Le titre est requis');
        return;
    }
    
    if (data.total_ends < 1 || data.total_ends > 50) {
        alert('Le nombre de volées doit être entre 1 et 50');
        return;
    }
    
    if (data.arrows_per_end < 1 || data.arrows_per_end > 12) {
        alert('Le nombre de flèches par volée doit être entre 1 et 12');
        return;
    }
    
    // Désactiver le bouton de soumission
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
    
    // Envoyer la requête
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
            const redirectUrl = '/scored-trainings/' + result.data.id + '?add_end=true';
            // Rediriger vers la page de détail du tir compté créé avec paramètre pour ouvrir la modale
            window.location.href = redirectUrl;
        } else {
            console.error('❌ Erreur lors de la création:', result);
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la création du tir compté');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Initialiser l'application quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    
    // Écouter les changements dans les champs
    const totalEndsField = document.getElementById('total_ends');
    const arrowsPerEndField = document.getElementById('arrows_per_end');
    
    if (totalEndsField) {
        totalEndsField.addEventListener('input', updatePreview);
    }
    
    if (arrowsPerEndField) {
        arrowsPerEndField.addEventListener('input', updatePreview);
    }
    
    // Gestion de la soumission du formulaire
    const createForm = document.getElementById('createForm');
    if (createForm) {
        createForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Initialiser l'aperçu
    updatePreview();
});
