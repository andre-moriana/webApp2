
// Gérer la sélection d un groupe
document.querySelectorAll(".group-item").forEach(item => {
    item.addEventListener("click", async (e) => {
        e.preventDefault();
        
        console.log("Clic sur le groupe:", item.dataset.groupId);
        
        document.querySelectorAll(".group-item").forEach(i => i.classList.remove("active"));
        item.classList.add("active");
        
        const groupName = item.querySelector("h6").textContent;
        chatTitle.textContent = groupName;
        
        currentGroupId = item.dataset.groupId;
        
        const currentGroupIdInput = document.getElementById("current-group-id");
        if (currentGroupIdInput) {
            currentGroupIdInput.value = currentGroupId;
        }
        
        await loadGroupMessages(currentGroupId);
    });
});
