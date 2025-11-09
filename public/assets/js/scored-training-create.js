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

// Fonction pour convertir la catégorie vers le format de la base de données
function convertCategoryToDBFormat(category) {
    // Mapping des catégories du formulaire vers le format de la base de données
    const categoryMap = {
        'grands_gibiers': 'Grands Gibiers',
        'moyens_gibiers': 'Moyens Gibiers',
        'petits_gibiers': 'Petits Gibiers',
        'petits_animaux': 'Petits Animaux',
        'doubles_birdies': 'Doubles Birdies'
    };
    
    if (categoryMap[category]) {
        return categoryMap[category];
    }
    
    // Sinon, convertir en format standard (première lettre en majuscule)
    return category.charAt(0).toUpperCase() + category.slice(1);
}

// Fonction pour charger les images nature (blasons) par catégorie
// Définie globalement pour être accessible depuis l'attribut onchange
window.loadNatureImagesByCategory = async function() {
    console.log('loadNatureImagesByCategory appelée');
    const category = document.getElementById('nature_category')?.value;
    const wrapper = document.getElementById('nature_blason_wrapper');
    const select = document.getElementById('nature_blason');
    const loading = document.getElementById('nature_blason_loading');
    
    console.log('Catégorie sélectionnée:', category);
    console.log('Wrapper trouvé:', !!wrapper);
    console.log('Select trouvé:', !!select);
    
    if (!wrapper || !select) {
        console.error('Éléments non trouvés: wrapper=' + !!wrapper + ', select=' + !!select);
        return;
    }
    
    // Masquer le select si aucune catégorie n'est sélectionnée
    if (!category) {
        wrapper.style.display = 'none';
        select.innerHTML = '<option value="">Sélectionner un blason</option>';
        return;
    }
    
    // Afficher le wrapper
    console.log('Affichage du wrapper du blason');
    wrapper.style.display = 'block';
    
    try {
        loading.style.display = 'block';
        select.innerHTML = '<option value="">Sélectionner un blason</option>';
        
        // Convertir la catégorie vers le format de la base de données
        const dbCategory = convertCategoryToDBFormat(category);
        
        // Récupérer les images via le backend de l'application web
        let response = await fetch(`/scored-trainings/images-nature?type=${encodeURIComponent(dbCategory)}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        let result = null;
        
        // Si pas de résultats avec le type normalisé, essayer la recherche par label
        if (response.ok) {
            result = await response.json();
            if (!result.success || !result.data || result.count === 0) {
                // Essayer la recherche par label
                response = await fetch(`/scored-trainings/images-nature?label=${encodeURIComponent(category)}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                if (response.ok) {
                    result = await response.json();
                }
            }
        } else {
            // Si erreur, essayer la recherche par label
            response = await fetch(`/scored-trainings/images-nature?label=${encodeURIComponent(category)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            if (response.ok) {
                result = await response.json();
            }
        }
        
        if (response.ok) {
            if (result && result.success && result.data && Array.isArray(result.data) && result.data.length > 0) {
                // Trier les images par ordre alphabétique du label
                const sortedImages = [...result.data].sort((a, b) => {
                    const labelA = (a.label || a.nom_fichier || '').toLowerCase();
                    const labelB = (b.label || b.nom_fichier || '').toLowerCase();
                    return labelA.localeCompare(labelB, 'fr', { sensitivity: 'base' });
                });
                
                // Ajouter les options au select
                sortedImages.forEach(image => {
                    const option = document.createElement('option');
                    option.value = image.id;
                    const baseLabel = image.label || image.nom_fichier || `Image ${image.id}`;
                    // Afficher ref_blason - label (comme dans l'app mobile ligne 1646)
                    // Format exact: `${image.ref_blason} - ${baseLabel}`
                    // Si ref_blason n'est pas présent, utiliser l'ID de l'image
                    const refBlason = (image.ref_blason !== undefined && image.ref_blason !== null && image.ref_blason !== '') 
                        ? image.ref_blason 
                        : image.id;
                    const displayLabel = `${refBlason} - ${baseLabel}`;
                    option.textContent = displayLabel;
                    select.appendChild(option);
                });
                
                console.log('Blasons chargés:', sortedImages.length);
            } else {
                // Réponse OK mais pas de données
                console.warn('Aucune image nature trouvée pour la catégorie:', category);
                console.log('Réponse complète:', result);
                if (result && result.message) {
                    console.warn('Message:', result.message);
                }
            }
        } else {
            // Erreur HTTP
            console.error('Erreur HTTP lors du chargement des images nature:', response.status);
            const errorText = await response.text();
            console.error('Détails de l\'erreur:', errorText);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des images nature:', error);
    } finally {
        loading.style.display = 'none';
    }
};

// Fonction pour mettre à jour la configuration selon le type de tir
// Définie globalement pour être accessible depuis l'attribut onchange
window.updateShootingConfiguration = function() {
    const shootingType = document.getElementById('shooting_type').value;
    const natureContainer = document.getElementById('nature_fields_container');
    const natureCategory = document.getElementById('nature_category');
    const natureBlason = document.getElementById('nature_blason');
    
    // Afficher/masquer les champs Nature selon le type de tir
    if (shootingType === 'Nature') {
        if (natureContainer) {
            natureContainer.style.display = 'block';
        }
    } else {
        if (natureContainer) {
            natureContainer.style.display = 'none';
        }
        if (natureCategory) {
            natureCategory.value = '';
        }
        if (natureBlason) {
            natureBlason.value = '';
        }
        // Masquer aussi le wrapper du blason
        const wrapper = document.getElementById('nature_blason_wrapper');
        if (wrapper) {
            wrapper.style.display = 'none';
        }
    }
};

// Gestion de la soumission du formulaire
function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const shootingType = formData.get('shooting_type') || null;
    const natureBlason = formData.get('nature_blason');
    
    const data = {
        title: formData.get('title'),
        total_ends: parseInt(formData.get('total_ends')),
        arrows_per_end: parseInt(formData.get('arrows_per_end')),
        exercise_sheet_id: formData.get('exercise_sheet_id') || null,
        notes: formData.get('notes'),
        shooting_type: shootingType
    };
    
    // Ajouter ref_blason si le type est Nature et qu'un blason est sélectionné
    if (shootingType === 'Nature' && natureBlason) {
        data.ref_blason = parseInt(natureBlason);
    }
    
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
    console.log('DOMContentLoaded - Initialisation du formulaire de création');
    
    // Vérifier que les fonctions globales sont bien définies
    console.log('updateShootingConfiguration définie:', typeof window.updateShootingConfiguration);
    console.log('loadNatureImagesByCategory définie:', typeof window.loadNatureImagesByCategory);
    
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
    
    // Vérifier que le select de type de tir existe et ajouter un listener si nécessaire
    const shootingTypeSelect = document.getElementById('shooting_type');
    if (shootingTypeSelect) {
        // Si l'attribut onchange n'est pas exécuté, ajouter un listener
        shootingTypeSelect.addEventListener('change', function() {
            console.log('Changement de type de tir détecté:', this.value);
            if (window.updateShootingConfiguration) {
                window.updateShootingConfiguration();
            }
        });
    }
    
    // Vérifier que le select de catégorie nature existe et ajouter un listener si nécessaire
    const natureCategorySelect = document.getElementById('nature_category');
    if (natureCategorySelect) {
        // Si l'attribut onchange n'est pas exécuté, ajouter un listener
        natureCategorySelect.addEventListener('change', function() {
            console.log('Changement de catégorie nature détecté:', this.value);
            if (window.loadNatureImagesByCategory) {
                window.loadNatureImagesByCategory();
            }
        });
    }
    
    // Initialiser l'aperçu
    updatePreview();
});
