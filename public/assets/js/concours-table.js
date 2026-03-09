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
    const columns = ['id', 'name', 'email', 'role', 'club', 'status', 'lastLogin', 'actions'];
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

// Fonction de recherche rapide (combine recherche texte + filtre validation)
function filterUsersTable(searchTerm) {
    applyFilters();
}

// Applique le filtre validation et la recherche texte
function applyFilters() {
    const table = document.getElementById('usersTable');
    if (!table) {
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }
    
    const userRows = tbody.querySelectorAll('tr.user-row');
    const noResultsRow = tbody.querySelector('tr.no-results-row');
    const searchInput = document.getElementById('userSearchInput');
    const validationFilter = document.getElementById('validationFilter');
    
    const searchTermTrimmed = (searchInput && searchInput.value) ? searchInput.value.trim().toLowerCase() : '';
    const validationValue = (validationFilter && validationFilter.value) ? validationFilter.value : '';
    
    let visibleCount = 0;
    
    userRows.forEach(row => {
        const rowStatus = row.getAttribute('data-status') || '';
        const matchValidation = !validationValue || rowStatus === validationValue;
        
        let matchSearch = true;
        if (searchTermTrimmed) {
            const cells = row.querySelectorAll('td');
            let rowText = '';
            for (let i = 0; i < cells.length - 1; i++) {
                const cellText = cells[i].textContent || cells[i].innerText || '';
                rowText += cellText.toLowerCase() + ' ';
            }
            const dataSearchable = (row.getAttribute('data-searchable') || '').toLowerCase();
            rowText += dataSearchable;
            matchSearch = rowText.includes(searchTermTrimmed);
        }
        
        const show = matchValidation && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    // Message "Aucun résultat"
    if (visibleCount === 0 && userRows.length > 0) {
        let searchNoResultsRow = tbody.querySelector('tr.search-no-results');
        if (!searchNoResultsRow) {
            searchNoResultsRow = document.createElement('tr');
            searchNoResultsRow.className = 'search-no-results';
            const colCount = table.querySelectorAll('thead th').length;
            searchNoResultsRow.innerHTML = `<td colspan="${colCount}" class="text-center py-4"><i class="fas fa-search fa-2x text-muted mb-2"></i><p class="text-muted mb-0">Aucun utilisateur ne correspond aux filtres</p></td>`;
            tbody.appendChild(searchNoResultsRow);
        }
        searchNoResultsRow.style.display = '';
    } else {
        const searchNoResultsRow = tbody.querySelector('tr.search-no-results');
        if (searchNoResultsRow) {
            searchNoResultsRow.style.display = 'none';
        }
    }
    
    if (noResultsRow) {
        noResultsRow.style.display = 'none';
    }
    
    updateResultsCount(visibleCount, userRows.length);
}

// Mettre à jour le compteur de résultats
function updateResultsCount(visible, total) {
    let counter = document.getElementById('resultsCounter');
    if (!counter) {
        // Créer le compteur s'il n'existe pas
        const cardHeader = document.querySelector('.card-header');
        if (cardHeader) {
            counter = document.createElement('div');
            counter.id = 'resultsCounter';
            counter.className = 'text-muted small mt-2';
            counter.style.width = '100%';
            // Insérer après le d-flex dans le card-header
            const flexContainer = cardHeader.querySelector('.d-flex');
            if (flexContainer && flexContainer.parentElement) {
                flexContainer.parentElement.appendChild(counter);
            } else {
                cardHeader.appendChild(counter);
            }
        }
    }
    
    if (counter) {
        if (visible < total) {
            counter.textContent = `${visible} utilisateur(s) trouvé(s) sur ${total}`;
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }
}

// Fonction d'initialisation
function initUsersTable() {
    // Gérer le tri
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
    
    // Gérer la recherche rapide
    const searchInput = document.getElementById('userSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    
    if (!searchInput) {
        console.warn('Champ de recherche userSearchInput non trouvé');
        return;
    }
    
    // Recherche en temps réel lors de la saisie
    searchInput.addEventListener('input', function(e) {
        const searchTerm = this.value;
        filterUsersTable(searchTerm);
        
        // Afficher/masquer le bouton de réinitialisation
        if (clearSearchBtn) {
            if (searchTerm.trim() !== '') {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
        }
    });
    
    // Recherche au clavier (Escape pour effacer)
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            applyFilters();
            if (clearSearchBtn) {
                clearSearchBtn.style.display = 'none';
            }
            this.focus();
        }
    });
    
    // Bouton pour effacer la recherche
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                applyFilters();
                this.style.display = 'none';
                searchInput.focus();
            }
        });
    }
    
    // Filtre par validation
    const validationFilter = document.getElementById('validationFilter');
    if (validationFilter) {
        validationFilter.addEventListener('change', applyFilters);
    }
    
    // Test initial pour vérifier que tout fonctionne
    const userRows = document.querySelectorAll('#usersTable tbody tr.user-row');
    //console.log('Initialisation de la recherche - Lignes utilisateur trouvées:', userRows.length);
}

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUsersTable);
} else {
    // Le DOM est déjà chargé
    initUsersTable();
}

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
