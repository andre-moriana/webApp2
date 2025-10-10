// JavaScript pour la page de création des tirs comptés
// Séparé du fichier principal pour respecter l'architecture

document.addEventListener('DOMContentLoaded', function() {
    // Attacher l'événement de changement de type de tir
    const shootingTypeSelect = document.getElementById('shooting_type');
    if (shootingTypeSelect) {
        shootingTypeSelect.addEventListener('change', function(e) {
            updateShootingConfiguration();
        });
    }
});

// Configuration automatique selon le type de tir
function updateShootingConfiguration() {
    const shootingType = document.getElementById('shooting_type').value;
    const totalEndsInput = document.getElementById('total_ends');
    const arrowsPerEndInput = document.getElementById('arrows_per_end');
    
    // Vérifier que les éléments existent
    if (!totalEndsInput || !arrowsPerEndInput) {
        return;
    }
    
    // Configurations par défaut pour chaque type de tir
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
