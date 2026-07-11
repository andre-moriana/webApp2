<div class="dropdown app-topbar-theme">
    <button type="button"
            class="app-topbar-link app-topbar-theme-btn"
            id="themeToggleBtn"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Changer le thème"
            title="Thème d'affichage">
        <i class="fas fa-circle-half-stroke" data-theme-icon aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow app-theme-menu" aria-labelledby="themeToggleBtn">
        <li><h6 class="dropdown-header">Apparence</h6></li>
        <li>
            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="system">
                <i class="fas fa-circle-half-stroke me-2"></i>Système
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="light">
                <i class="fas fa-sun me-2"></i>Clair
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-set="dark">
                <i class="fas fa-moon me-2"></i>Sombre
            </button>
        </li>
    </ul>
</div>
