// Gestion des départs dans le formulaire concours

document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('departs-tbody');
    const btnAdd = document.getElementById('btn-add-depart');
    const form = document.getElementById('concoursForm');
    const departsJsonInput = document.getElementById('departs_json');

    if (!tbody || !btnAdd) return;

    function getNextNumero() {
        const rows = tbody.querySelectorAll('tr');
        let max = 0;
        rows.forEach(function(row) {
            const numCell = row.querySelector('td:first-child');
            if (numCell) {
                const n = parseInt(numCell.textContent, 10);
                if (!isNaN(n) && n > max) max = n;
            }
        });
        return max + 1;
    }

    function addDepartRow(dateVal, heureGreffeVal, heureDepartVal, numeroVal) {
        const numero = numeroVal != null ? numeroVal : getNextNumero();
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="depart-numero">' + numero + '</td>' +
            '<td><input type="date" class="form-control form-control-sm depart-date" value="' + (dateVal || '') + '"></td>' +
            '<td><input type="time" class="form-control form-control-sm depart-heure-greffe" value="' + (heureGreffeVal || '') + '"></td>' +
            '<td><input type="time" class="form-control form-control-sm depart-heure-depart" value="' + (heureDepartVal || '') + '"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-depart"><i class="fas fa-trash"></i></button></td>';
        tbody.appendChild(tr);

        tr.querySelector('.btn-remove-depart').addEventListener('click', function() {
            tr.remove();
            updateNumeros();
        });
    }

    function updateNumeros() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(function(row, i) {
            const numCell = row.querySelector('.depart-numero');
            if (numCell) numCell.textContent = i + 1;
        });
    }

    function collectDeparts() {
        const departs = [];
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(function(row, i) {
            const dateInput = row.querySelector('.depart-date');
            const heureGreffeInput = row.querySelector('.depart-heure-greffe');
            const heureDepartInput = row.querySelector('.depart-heure-depart');
            const numeroCell = row.querySelector('.depart-numero');
            const date = dateInput ? dateInput.value.trim() : '';
            if (!date) return;
            departs.push({
                numero_depart: parseInt(numeroCell ? numeroCell.textContent : i + 1, 10),
                date_depart: date,
                heure_greffe: heureGreffeInput ? heureGreffeInput.value : null,
                heure_depart: heureDepartInput ? heureDepartInput.value : null
            });
        });
        return departs;
    }

    btnAdd.addEventListener('click', function() {
        const dateDebut = document.querySelector('input[name="date_debut"]');
        const dateDefaut = dateDebut ? dateDebut.value : '';
        addDepartRow(dateDefaut, '', '', null);
    });

    tbody.querySelectorAll('.btn-remove-depart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tr = btn.closest('tr');
            if (tr) {
                tr.remove();
                updateNumeros();
            }
        });
    });

    if (form && departsJsonInput) {
        form.addEventListener('submit', function(e) {
            const departs = collectDeparts();
            departsJsonInput.value = JSON.stringify(departs);
        }, true);
    }
});
