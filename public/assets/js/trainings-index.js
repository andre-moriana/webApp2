// Gestion de la sélection d'utilisateur dans la page des entraînements
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('userSelect');
    if (userSelect) {
        userSelect.addEventListener('change', function() {
            const userId = this.value;
            if (userId) {
                window.location.href = '/trainings?user_id=' + userId;
            } else {
                window.location.href = '/trainings';
            }
        });
    }
});
