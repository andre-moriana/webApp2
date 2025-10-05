// JavaScript pour la page de création des tirs comptés
// Séparé du fichier principal pour respecter l'architecture

console.log('🚀 scored-trainings-create.js chargé (avant DOMContentLoaded)');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOMContentLoaded - scored-trainings-create.js initialisé');
    
    // Attacher l'événement de changement de type de tir
    const shootingTypeSelect = document.getElementById('shooting_type');
    console.log('🔍 Élément shooting_type trouvé:', shootingTypeSelect);
    
    if (shootingTypeSelect) {
        shootingTypeSelect.addEventListener('change', function(e) {
            console.log('🎯 Événement change déclenché sur shooting_type:', e.target.value);
            updateShootingConfiguration();
        });
        console.log('✅ Event listener attaché au select shooting_type');
        
        // Test immédiat
        console.log('🧪 Test: valeur actuelle du select:', shootingTypeSelect.value);
    } else {
        console.warn('⚠️ Élément shooting_type non trouvé');
        console.log('🔍 Éléments disponibles avec "shooting":', document.querySelectorAll('[id*="shooting"]'));
    }
});

// Configuration automatique selon le type de tir
function updateShootingConfiguration() {
    const shootingType = document.getElementById('shooting_type').value;
    const totalEndsInput = document.getElementById('total_ends');
    const arrowsPerEndInput = document.getElementById('arrows_per_end');
    
    console.log('🎯 updateShootingConfiguration appelée pour:', shootingType);
    
    // Vérifier que les éléments existent
    if (!totalEndsInput || !arrowsPerEndInput) {
        console.error('❌ Éléments total_ends ou arrows_per_end non trouvés');
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
        
        console.log(`✅ Configuration automatique pour ${shootingType}: ${config.totalEnds} volées, ${config.arrowsPerEnd} flèches par volée`);
    } else {
        console.log('❌ Type de tir non reconnu ou vide:', shootingType);
    }
}
