function confirmDelete(clubId) {
    document.getElementById('deleteClubId').value = clubId;
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = '/clubs/' + clubId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

window.confirmDelete = confirmDelete;
