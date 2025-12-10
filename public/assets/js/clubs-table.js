// Fonction de tri pour le tableau des clubs
function sortClubsTable(column) {
    const table = document.getElementById('clubsTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelector(`th[data-column="${column}"]`);
    if (!header) return;
    
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
        let aValue = getCellValue(a, column);
        let bValue = getCellValue(b, column);
        
        // Traitement spécial pour les valeurs vides ou "-"
        if (aValue === '-' || aValue === '') {
            aValue = '';
        }
        if (bValue === '-' || bValue === '') {
            bValue = '';
        }
        
        // Comparaison insensible à la casse pour les chaînes
        if (typeof aValue === 'string' && typeof bValue === 'string') {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }
        
        if (isAscending) {
            return aValue > bValue ? 1 : (aValue < bValue ? -1 : 0);
        } else {
            return aValue < bValue ? 1 : (aValue > bValue ? -1 : 0);
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

// Obtenir la valeur d'une cellule selon la colonne
function getCellValue(row, column) {
    const columnIndex = getClubsColumnIndex(column);
    const cell = row.querySelector(`td:nth-child(${columnIndex})`);
    
    if (!cell) return '';
    
    // Pour les colonnes avec des liens (email), récupérer le texte du lien ou le texte de la cellule
    const link = cell.querySelector('a');
    if (link) {
        return link.textContent.trim();
    }
    
    return cell.textContent.trim();
}

// Obtenir l'index de la colonne
function getClubsColumnIndex(column) {
    const columns = ['name', 'nameShort', 'city', 'email', 'president', 'actions'];
    return columns.indexOf(column) + 1;
}

// Fonction d'initialisation
function initClubsTable() {
    // Gérer le tri
    const sortableHeaders = document.querySelectorAll('#clubsTable .sortable');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            sortClubsTable(column);
        });
    });
}

// Initialiser le tableau au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initClubsTable();
});

