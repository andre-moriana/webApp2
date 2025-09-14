// Fonction de tri
function sortTable(column) {
    const table = document.getElementById('usersTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelector(`th[data-column="${column}"]`);
    const isAscending = header.classList.contains('sort-asc');
    
    // Supprimer les classes de tri de tous les headers
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        const icon = th.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-sort ms-1';
        }
    });
    
    // Trier les lignes
    rows.sort((a, b) => {
        let aValue = a.querySelector(`td:nth-child(${getColumnIndex(column)})`).textContent.trim();
        let bValue = b.querySelector(`td:nth-child(${getColumnIndex(column)})`).textContent.trim();
        
        // Conversion spéciale pour les colonnes numériques
        if (column === 'id') {
            aValue = parseInt(aValue) || 0;
            bValue = parseInt(bValue) || 0;
        }
        
        // Conversion spéciale pour les dates
        if (column === 'lastLogin') {
            if (aValue === 'Jamais') {
                aValue = new Date('1900-01-01'); // Date très ancienne pour "Jamais"
            } else {
                // Convertir la date du format dd/mm/yyyy hh:mm vers un objet Date
                const dateParts = aValue.split(' ');
                if (dateParts.length === 2) {
                    const [dateStr, timeStr] = dateParts;
                    const [day, month, year] = dateStr.split('/');
                    const [hour, minute] = timeStr.split(':');
                    aValue = new Date(year, month - 1, day, hour, minute);
                } else {
                    aValue = new Date('1900-01-01');
                }
            }
            
            if (bValue === 'Jamais') {
                bValue = new Date('1900-01-01'); // Date très ancienne pour "Jamais"
            } else {
                // Convertir la date du format dd/mm/yyyy hh:mm vers un objet Date
                const dateParts = bValue.split(' ');
                if (dateParts.length === 2) {
                    const [dateStr, timeStr] = dateParts;
                    const [day, month, year] = dateStr.split('/');
                    const [hour, minute] = timeStr.split(':');
                    bValue = new Date(year, month - 1, day, hour, minute);
                } else {
                    bValue = new Date('1900-01-01');
                }
            }
        }
        
        if (isAscending) {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    // Réorganiser les lignes dans le DOM
    rows.forEach(row => tbody.appendChild(row));
    
    // Mettre à jour l'icône de tri
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    const icon = header.querySelector('i');
    if (icon) {
        icon.className = isAscending ? 'fas fa-sort-down ms-1' : 'fas fa-sort-up ms-1';
    }
}

// Obtenir l'index de la colonne
function getColumnIndex(column) {
    const columns = ['id', 'name', 'email', 'role', 'status', 'lastLogin', 'actions'];
    return columns.indexOf(column) + 1;
}

// Gérer l'affichage des avatars
function handleAvatarDisplay() {
    const profileImages = document.querySelectorAll('.profile-img');
    
    profileImages.forEach(img => {
        // Cacher l'initial par défaut
        const initial = img.nextElementSibling;
        if (initial) {
            initial.style.display = 'flex';
        }
        
        // Tester si l'image se charge
        const testImg = new Image();
        testImg.onload = function() {
            // Image chargée avec succès
            img.style.display = 'block';
            if (initial) {
                initial.style.display = 'none';
            }
        };
        testImg.onerror = function() {
            // Image échouée, garder l'initial
            img.style.display = 'none';
            if (initial) {
                initial.style.display = 'flex';
            }
        };
        testImg.src = img.src;
    });
}

// Ajouter les événements de clic sur les headers
document.addEventListener('DOMContentLoaded', function() {
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            sortTable(column);
        });
    });
    
    // Gérer l'affichage des avatars
    handleAvatarDisplay();
});

function confirmDelete(userId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
        // Créer un formulaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/users/' + userId + '/delete';
        
        // Ajouter un champ pour confirmer la suppression
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'confirm';
        input.value = 'yes';
        form.appendChild(input);
        
        document.body.appendChild(form);
        form.submit();
    }
}
