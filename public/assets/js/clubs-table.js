// Fonction de tri pour les clubs
function sortClubsTable(column) {
    const table = document.getElementById('clubsTable');
    if (!table) {
        console.error('Table clubsTable non trouvée');
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        console.error('Tbody non trouvé');
        return;
    }
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    if (rows.length === 0) {
        return;
    }
    
    const header = table.querySelector(`th[data-column="${column}"]`);
    if (!header) {
        console.error('Header non trouvé pour la colonne:', column);
        return;
    }
    
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
        let aValue = '';
        let bValue = '';
        
        const aCell = a.querySelector(`td[data-column="${column}"]`);
        const bCell = b.querySelector(`td[data-column="${column}"]`);
        
        if (aCell) {
            // Pour les emails, prendre le texte du lien ou le texte de la cellule
            const link = aCell.querySelector('a');
            if (link) {
                aValue = link.textContent.trim();
            } else {
                // Pour les cellules avec du texte en gras, prendre le texte complet
                const strong = aCell.querySelector('strong');
                aValue = strong ? strong.textContent.trim() : aCell.textContent.trim();
            }
        }
        if (bCell) {
            const link = bCell.querySelector('a');
            if (link) {
                bValue = link.textContent.trim();
            } else {
                const strong = bCell.querySelector('strong');
                bValue = strong ? strong.textContent.trim() : bCell.textContent.trim();
            }
        }
        
        // Gérer les valeurs vides ou "-"
        if (aValue === '-' || aValue === '') {
            aValue = '';
        }
        if (bValue === '-' || bValue === '') {
            bValue = '';
        }
        
        // Pour les emails, convertir en minuscules pour un tri insensible à la casse
        if (column === 'email') {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }
        
        // Comparaison
        let comparison = 0;
        if (aValue < bValue) {
            comparison = -1;
        } else if (aValue > bValue) {
            comparison = 1;
        }
        
        // Inverser si tri décroissant
        if (isAscending) {
            return comparison * -1;
        }
        return comparison;
    });
    
    // Réorganiser les lignes dans le DOM
    rows.forEach(row => tbody.appendChild(row));
    
    // Mettre à jour l'icône de tri
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    const icon = header.querySelector('i');
    if (icon) {
        icon.className = isAscending ? 'fas fa-sort-down ms-1' : 'fas fa-sort-up ms-1';
    }
    
    // Réappliquer les filtres après le tri
    filterClubsTable();
}

// Fonction de filtrage des clubs
function filterClubsTable() {
    const table = document.getElementById('clubsTable');
    if (!table) {
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }
    
    const showRegional = document.getElementById('filterRegional').checked;
    const showDepartmental = document.getElementById('filterDepartmental').checked;
    const showClubs = document.getElementById('filterClubs').checked;
    
    // Récupérer le terme de recherche
    const searchInput = document.getElementById('searchClubs');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    
    const rows = tbody.querySelectorAll('tr[data-club-type]');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const clubType = row.getAttribute('data-club-type');
        let shouldShow = false;
        
        // Vérifier le type de club
        if (clubType === 'regional' && showRegional) {
            shouldShow = true;
        } else if (clubType === 'departmental' && showDepartmental) {
            shouldShow = true;
        } else if (clubType === 'club' && showClubs) {
            shouldShow = true;
        }
        
        // Si le club correspond au type, vérifier la recherche
        if (shouldShow && searchTerm) {
            // Récupérer le texte de toutes les cellules de données (sauf Actions)
            const cells = row.querySelectorAll('td[data-column]');
            let matchesSearch = false;
            
            cells.forEach(cell => {
                const cellText = cell.textContent.toLowerCase().trim();
                if (cellText.includes(searchTerm)) {
                    matchesSearch = true;
                }
            });
            
            shouldShow = matchesSearch;
        }
        
        if (shouldShow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Afficher un message si aucun résultat
    let noResultsRow = tbody.querySelector('tr.no-results-filter');
    if (visibleCount === 0) {
        if (!noResultsRow) {
            noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results-filter';
            noResultsRow.innerHTML = '<td colspan="6" class="text-center py-4 text-muted">Aucun club ne correspond aux critères de recherche</td>';
            tbody.appendChild(noResultsRow);
        }
        noResultsRow.style.display = '';
    } else {
        if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
}

// Fonction d'initialisation
function initClubsTable() {
    console.log('Initialisation de la table des clubs');
    
    const table = document.getElementById('clubsTable');
    if (!table) {
        console.error('Table clubsTable non trouvée');
        return;
    }
    
    // Gérer le tri
    const sortableHeaders = table.querySelectorAll('th.sortable');
    console.log('En-têtes triables trouvés:', sortableHeaders.length);
    
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function(e) {
            e.preventDefault();
            const column = this.getAttribute('data-column');
            console.log('Tri de la colonne:', column);
            if (column) {
                sortClubsTable(column);
            }
        });
    });
    
    // Gérer les filtres
    const filterRegional = document.getElementById('filterRegional');
    const filterDepartmental = document.getElementById('filterDepartmental');
    const filterClubs = document.getElementById('filterClubs');
    
    if (filterRegional && filterDepartmental && filterClubs) {
        filterRegional.addEventListener('change', filterClubsTable);
        filterDepartmental.addEventListener('change', filterClubsTable);
        filterClubs.addEventListener('change', filterClubsTable);
    }
    
    // Gérer le champ de recherche
    const searchInput = document.getElementById('searchClubs');
    if (searchInput) {
        searchInput.addEventListener('input', filterClubsTable);
        searchInput.addEventListener('keyup', filterClubsTable);
    }
    
    // Appliquer le filtre initial
    filterClubsTable();
}

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initClubsTable);
} else {
    // Le DOM est déjà chargé
    initClubsTable();
}

