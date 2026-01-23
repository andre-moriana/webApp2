// Gestion de la carte pour sélectionner le lieu du concours
let map = null;
let marker = null;
let selectedLocation = null;
let geocoder = null;

// Fonction pour ouvrir la modale
function openLieuModal() {
    const modalElement = document.getElementById('lieuModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Attendre que la modale soit complètement affichée avant d'initialiser la carte
    modalElement.addEventListener('shown.bs.modal', function onShown() {
        // Initialiser la carte si elle n'existe pas encore
        if (!map) {
            initMap();
        } else {
            // Si la carte existe déjà, recentrer sur la position actuelle ou par défaut
            const lieuLatInput = document.getElementById('lieu_latitude');
            const lieuLngInput = document.getElementById('lieu_longitude');
            const currentLat = lieuLatInput && lieuLatInput.value ? parseFloat(lieuLatInput.value) : 43.2965;
            const currentLng = lieuLngInput && lieuLngInput.value ? parseFloat(lieuLngInput.value) : 5.6288;
            
            map.invalidateSize(); // Forcer le recalcul de la taille
            map.setView([currentLat, currentLng], (lieuLatInput && lieuLatInput.value && lieuLngInput && lieuLngInput.value) ? 15 : 10);
            
            if (marker) {
                marker.setLatLng([currentLat, currentLng]);
            } else if (lieuLatInput && lieuLatInput.value && lieuLngInput && lieuLngInput.value) {
                marker = L.marker([currentLat, currentLng], { draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const lat = e.target.getLatLng().lat;
                    const lng = e.target.getLatLng().lng;
                    updateAddressFromCoords(lat, lng);
                });
            }
        }
        // Retirer l'écouteur pour éviter les multiples initialisations
        modalElement.removeEventListener('shown.bs.modal', onShown);
    }, { once: true });
    
    modal.show();
}

    // Initialiser la carte Leaflet
function initMap() {
    // Coordonnées par défaut (Gémenos, France)
    const defaultLat = 43.2965;
    const defaultLng = 5.6288;
    
    // Récupérer les coordonnées existantes si disponibles
    const lieuLatInput = document.getElementById('lieu_latitude');
    const lieuLngInput = document.getElementById('lieu_longitude');
    const existingLat = (lieuLatInput && lieuLatInput.value && !isNaN(parseFloat(lieuLatInput.value))) 
        ? parseFloat(lieuLatInput.value) : null;
    const existingLng = (lieuLngInput && lieuLngInput.value && !isNaN(parseFloat(lieuLngInput.value))) 
        ? parseFloat(lieuLngInput.value) : null;
    
    const initialLat = existingLat || defaultLat;
    const initialLng = existingLng || defaultLng;
    const initialZoom = (existingLat && existingLng) ? 15 : 10;
    
    // Créer la carte
    map = L.map('map-container').setView([initialLat, initialLng], initialZoom);
    
    // Ajouter la couche de tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Note: Le contrôle de géocodage Leaflet sera ajouté via la fonction de recherche manuelle
    // pour plus de contrôle sur l'affichage des résultats
    
    // Si des coordonnées existent, placer un marqueur
    if (existingLat && existingLng) {
        marker = L.marker([existingLat, existingLng], { draggable: true }).addTo(map);
        marker.on('dragend', function(e) {
            const lat = e.target.getLatLng().lat;
            const lng = e.target.getLatLng().lng;
            updateAddressFromCoords(lat, lng);
        });
        
        // Mettre à jour selectedLocation avec les données existantes
        const lieuInput = document.getElementById('lieu_competition');
        selectedLocation = {
            lat: existingLat,
            lng: existingLng,
            address: (lieuInput && lieuInput.value) ? lieuInput.value : ''
        };
        
        // Afficher les informations
        document.getElementById('selected-address').textContent = selectedLocation.address || 'Adresse en cours de chargement...';
        document.getElementById('selected-coords').textContent = `${existingLat.toFixed(6)}, ${existingLng.toFixed(6)}`;
        
        // Récupérer l'adresse complète si elle n'est pas déjà définie
        if (!selectedLocation.address || selectedLocation.address.trim() === '') {
            updateAddressFromCoords(existingLat, existingLng);
        }
    }
    
    // Gérer le clic sur la carte
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Placer ou déplacer le marqueur
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        }
        
        // Mettre à jour l'adresse depuis les coordonnées (géocodage inverse)
        updateAddressFromCoords(lat, lng);
    });
    
    // Gérer le déplacement du marqueur
    if (marker) {
        marker.on('dragend', function(e) {
            const lat = e.target.getLatLng().lat;
            const lng = e.target.getLatLng().lng;
            updateAddressFromCoords(lat, lng);
        });
    }
}

// Mettre à jour l'adresse depuis les coordonnées (géocodage inverse)
function updateAddressFromCoords(lat, lng) {
    selectedLocation = { lat: lat, lng: lng };
    
    // Afficher les coordonnées
    document.getElementById('selected-coords').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    
    // Récupérer l'adresse via Nominatim (géocodage inverse)
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
        headers: {
            'User-Agent': 'ArcTraining/1.0'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.display_name) {
            selectedLocation.address = data.display_name;
            document.getElementById('selected-address').textContent = data.display_name;
        } else {
            document.getElementById('selected-address').textContent = 'Adresse non trouvée';
        }
    })
    .catch(error => {
        console.error('Erreur lors de la récupération de l\'adresse:', error);
        document.getElementById('selected-address').textContent = 'Erreur lors de la récupération de l\'adresse';
    });
}

// Recherche d'adresse
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('lieu-search');
    const searchButton = document.getElementById('btn-search-lieu');
    
    if (searchInput && searchButton) {
        // Recherche au clic sur le bouton
        searchButton.addEventListener('click', function() {
            performSearch();
        });
        
        // Recherche avec Entrée
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
    
    // Bouton de validation
    const confirmButton = document.getElementById('btn-confirm-lieu');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            if (selectedLocation && selectedLocation.address) {
                // Mettre à jour les champs du formulaire
                document.getElementById('lieu_competition').value = selectedLocation.address;
                document.getElementById('lieu_latitude').value = selectedLocation.lat;
                document.getElementById('lieu_longitude').value = selectedLocation.lng;
                
                // Fermer la modale
                const modal = bootstrap.Modal.getInstance(document.getElementById('lieuModal'));
                if (modal) {
                    modal.hide();
                }
            } else {
                alert('Veuillez sélectionner un lieu sur la carte ou rechercher une adresse');
            }
        });
    }
    
    // Initialiser l'adresse si des coordonnées existent déjà
    const lieuLatInput = document.getElementById('lieu_latitude');
    const lieuLngInput = document.getElementById('lieu_longitude');
    const lieuInput = document.getElementById('lieu_competition');
    
    if (lieuLatInput && lieuLngInput && lieuInput) {
        const existingLat = parseFloat(lieuLatInput.value);
        const existingLng = parseFloat(lieuLngInput.value);
        if (existingLat && existingLng) {
            selectedLocation = {
                lat: existingLat,
                lng: existingLng,
                address: lieuInput.value || ''
            };
        }
    }
});

// Fonction de recherche d'adresse
function performSearch() {
    const query = document.getElementById('lieu-search').value.trim();
    
    if (!query) {
        alert('Veuillez entrer une adresse à rechercher');
        return;
    }
    
    if (!map) {
        initMap();
    }
    
    // Rechercher l'adresse via Nominatim
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&addressdetails=1&countrycodes=fr`, {
        headers: {
            'User-Agent': 'ArcTraining/1.0'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.length > 0) {
            const result = data[0];
            const lat = parseFloat(result.lat);
            const lng = parseFloat(result.lon);
            
            // Centrer la carte sur le résultat
            map.setView([lat, lng], 15);
            
            // Placer ou déplacer le marqueur
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const lat = e.target.getLatLng().lat;
                    const lng = e.target.getLatLng().lng;
                    updateAddressFromCoords(lat, lng);
                });
            }
            
            // Mettre à jour l'adresse
            selectedLocation = {
                lat: lat,
                lng: lng,
                address: result.display_name
            };
            
            document.getElementById('selected-address').textContent = result.display_name;
            document.getElementById('selected-coords').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        } else {
            alert('Aucun résultat trouvé pour cette adresse');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la recherche:', error);
        alert('Erreur lors de la recherche de l\'adresse');
    });
}
