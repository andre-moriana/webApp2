/**
 * Liste des concours - inscription rapide
 */
(function() {
    'use strict';

    var userId = null;
    var el = document.querySelector('[data-concours-index]');
    if (el) {
        var uid = el.getAttribute('data-user-id');
        if (uid) userId = parseInt(uid, 10);
    }

    window.inscrireConcours = function(concoursId) {
        if (!concoursId) {
            alert('ID du concours manquant');
            return;
        }
        if (!confirm('Voulez-vous vous inscrire à ce concours ?')) {
            return;
        }
        fetch('/api/concours/' + concoursId + '/inscription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ user_id: userId })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Inscription réussie !');
                location.reload();
            } else {
                alert('Erreur lors de l\'inscription: ' + (data.error || data.message || 'Erreur inconnue'));
            }
        })
        .catch(function(error) {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'inscription');
        });
    };
})();
