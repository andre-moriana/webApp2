// CSS pour afficher/masquer la liste au survol
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter le style CSS dynamiquement
    const style = document.createElement('style');
    style.textContent = `
        .club-card-hover .club-list {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.4s ease, opacity 0.3s ease;
        }
        
        .club-card-hover:hover .club-list {
            max-height: 500px;
            opacity: 1;
        }
        
        .club-card-hover {
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        
        .club-card-hover:hover {
            transform: translateY(-5px);
        }
    `;
    document.head.appendChild(style);
});
