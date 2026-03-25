<?php
$isAdmin = (bool)($_SESSION['user']['is_admin'] ?? false);
$clubId = $_SESSION['user']['clubId'] ?? $_SESSION['user']['club_id'] ?? null;
if (!class_exists('PermissionHelper')) {
    require_once __DIR__ . '/../../Config/PermissionHelper.php';
}
$canAccessUsersList = $isAdmin || PermissionHelper::can('users_list', 'view', $clubId);
?>
<div class="nav-menu-sections">
    <!-- Rubrique Club -->
    <div class="nav-menu-section">
        <div class="nav-menu-section-header" data-bs-toggle="collapse" data-bs-target="#nav-section-club" aria-expanded="true">
            <i class="fas fa-shield-alt nav-menu-section-icon"></i>
            <span>Club</span>
            <i class="fas fa-chevron-down nav-menu-section-chevron"></i>
        </div>
        <div class="collapse show" id="nav-section-club">
            <ul class="nav-menu-links">
                <li><a href="/club-feed"><i class="fas fa-info-circle me-2"></i>Infos du club</a></li>
                <?php if ($canAccessUsersList): ?>
                <li><a href="/users"><i class="fas fa-users me-2"></i>Liste des membres</a></li>
                <?php endif; ?>
                <li><a href="/groups"><i class="fas fa-layer-group me-2"></i>Forum Groupe</a></li>
                <li><a href="/events"><i class="fas fa-calendar-alt me-2"></i>Événements</a></li>
            </ul>
        </div>
    </div>

    <!-- Rubrique Utilisateurs -->
    <div class="nav-menu-section">
        <div class="nav-menu-section-header" data-bs-toggle="collapse" data-bs-target="#nav-section-users" aria-expanded="true">
            <i class="fas fa-user-friends nav-menu-section-icon"></i>
            <span>Utilisateurs</span>
            <i class="fas fa-chevron-down nav-menu-section-chevron"></i>
        </div>
        <div class="collapse show" id="nav-section-users">
            <ul class="nav-menu-links">
                <li><a href="/trainings"><i class="fas fa-chart-line me-2"></i>Entraînements</a></li>
                <li><a href="/exercises"><i class="fas fa-clipboard-list me-2"></i>Exercices</a></li>
                <li><a href="/scored-trainings"><i class="fas fa-bullseye me-2"></i>Tir comptés</a></li>
                <li><a href="/private-messages"><i class="fas fa-envelope me-2"></i>Messages</a></li>
            </ul>
        </div>
    </div>

    <!-- Rubrique Concours -->
    <div class="nav-menu-section">
        <div class="nav-menu-section-header" data-bs-toggle="collapse" data-bs-target="#nav-section-concours" aria-expanded="true">
            <i class="fas fa-trophy nav-menu-section-icon"></i>
            <span>Concours</span>
            <i class="fas fa-chevron-down nav-menu-section-chevron"></i>
        </div>
        <div class="collapse show" id="nav-section-concours">
            <ul class="nav-menu-links">
                <li><a href="/concours"><i class="fas fa-list me-2"></i>Liste des concours</a></li>
                <li><a href="/score-sheet"><i class="fas fa-clipboard-list me-2"></i>Feuilles de marque</a></li>
            </ul>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Administration -->
    <div class="nav-menu-section">
        <div class="nav-menu-section-header" data-bs-toggle="collapse" data-bs-target="#nav-section-admin" aria-expanded="true">
            <i class="fas fa-cog nav-menu-section-icon"></i>
            <span>Administration</span>
            <i class="fas fa-chevron-down nav-menu-section-chevron"></i>
        </div>
        <div class="collapse show" id="nav-section-admin">
            <ul class="nav-menu-links">
                <li><a href="/clubs"><i class="fas fa-info-circle me-2"></i>Fiche club</a></li>
                <li><a href="/user-validation"><i class="fas fa-user-check me-2"></i>Validation des comptes</a></li>
                <li><a href="/themes"><i class="fas fa-palette me-2"></i>Thèmes</a></li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>
