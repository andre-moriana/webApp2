<?php
$title = "Modifier l'utilisateur - Portail Archers de Gémenos";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Modifier l'utilisateur</h1>
                <a href="/users/<?php echo $user['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux détails
                </a>
            </div>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-edit me-2"></i>Modifier les informations
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/users/<?php echo $user['id']; ?>/update">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="firstName" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" id="firstName" name="firstName" 
                                                   value="<?php echo htmlspecialchars($user['firstName'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Téléphone</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Rôle</label>
                                            <select class="form-select" id="role" name="role">
                                                <option value="Archer" <?php echo ($user['role'] ?? '') === 'Archer' ? 'selected' : ''; ?>>Archer</option>
                                                <option value="Coach" <?php echo ($user['role'] ?? '') === 'Coach' ? 'selected' : ''; ?>>Coach</option>
                                                <option value="Dirigeant" <?php echo ($user['role'] ?? '') === 'Dirigeant' ? 'selected' : ''; ?>>Dirigeant</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="is_admin" class="form-label">Administrateur</label>
                                            <?php
                                            $isAdmin = ($user['is_admin'] ?? $user['isAdmin'] ?? false);
                                            error_log("DEBUG edit form - is_admin: " . json_encode($user['is_admin'] ?? null));
                                            error_log("DEBUG edit form - isAdmin: " . json_encode($user['isAdmin'] ?? null));
                                            error_log("DEBUG edit form - final isAdmin value: " . json_encode($isAdmin));
                                            ?>
                                            <select class="form-select" id="is_admin" name="is_admin">
                                                <option value="0" <?php echo !$isAdmin ? 'selected' : ''; ?>>Non</option>
                                                <option value="1" <?php echo $isAdmin ? 'selected' : ''; ?>>Oui</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="is_banned" class="form-label">Bannissement</label>
                                            <select class="form-select" id="is_banned" name="is_banned">
                                                <option value="0" <?php echo !($user['is_banned'] ?? false) ? 'selected' : ''; ?>>Actif</option>
                                                <option value="1" <?php echo ($user['is_banned'] ?? false) ? 'selected' : ''; ?>>Banni</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Validation</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="active" <?php echo ($user['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Validé</option>
                                                <option value="rejected" <?php echo ($user['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-trophy me-2"></i>Informations sportives
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="licenceNumber" class="form-label">Numéro de licence</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="licenceNumber" name="licenceNumber" 
                                                       value="<?php echo htmlspecialchars($user['licenceNumber'] ?? ''); ?>"
                                                       placeholder="Entrez le numéro de licence pour rechercher l'utilisateur">
                                                <button type="button" class="btn btn-outline-secondary" id="searchByLicence" title="Rechercher l'utilisateur">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Si l'utilisateur existe déjà, le formulaire sera automatiquement rempli.</small>
                                            <div id="licenceSearchResult" class="mt-2"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="birthDate" class="form-label">Date de naissance</label>
                                            <input type="date" class="form-control" id="birthDate" name="birthDate" 
                                                   value="<?php echo htmlspecialchars($user['birthDate'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="gender" class="form-label">Genre</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="">Sélectionner...</option>
                                                <option value="H" <?php echo ($user['gender'] ?? '') === 'H' ? 'selected' : ''; ?>>Homme</option>
                                                <option value="F" <?php echo ($user['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Femme</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ageCategory" class="form-label">Catégorie d'âge</label>
                                            <select class="form-select" id="ageCategory" name="ageCategory">
                                                <option value="">Sélectionner...</option>
                                                <?php
                                                // Liste des catégories d'âge selon les choix prédéfinis dans l'application mobile
                                                $ageCategories = [
                                                    'DECOUVERTE',
                                                    'U11 - POUSSINS',
                                                    'U13 - BENJAMINS',
                                                    'U13 - BENJAMINS (N)',
                                                    'U15 - MINIMES',
                                                    'U15 - MINIMES (N)',
                                                    'U18 - CADETS',
                                                    'U18 - CADETS (N)',
                                                    'U21 - JUNIORS',
                                                    'U21 - JUNIORS (N)',
                                                    'SENIORS1 (S1)',
                                                    'SENIORS1 (S1) (N)',
                                                    'SENIORS2 (S2)',
                                                    'SENIORS2 (S2) (N)',
                                                    'SENIORS3 (S3)',
                                                    'SENIORS3 (S3) (N)',
                                                    'SENIORS1 (T1)',
                                                    'SENIORS1 (T1) (N)',
                                                    'SENIORS2 (T2)',
                                                    'SENIORS2 (T2) (N)',
                                                    'SENIORS3 (T3)',
                                                    'SENIORS3 (T3) (N)',
                                                    'DEBUTANTS',
                                                    'W1',
                                                    'W1 U18',
                                                    'W1 NATIONAL',
                                                    'OPEN',
                                                    'OPEN U18',
                                                    'OPEN VETERAN',
                                                    'OPEN NATIONAL',
                                                    'FEDERAL',
                                                    'FEDERAL U18',
                                                    'FEDERAL VETERAN',
                                                    'FEDERAL NATIONAL',
                                                    'CHALLENGE',
                                                    'CHALLENGE U18',
                                                    'CRITERIUM',
                                                    'CRITERIUM U18',
                                                    'POTENCE',
                                                    'HV1',
                                                    'HV2-3',
                                                    'HV U18',
                                                    'HV LIBRE',
                                                    'SUPPORT 1',
                                                    'SUPPORT 2',
                                                ];
                                                
                                                $currentAgeCategory = $user['ageCategory'] ?? '';
                                                foreach ($ageCategories as $category) {
                                                    $selected = ($currentAgeCategory === $category) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($category) . '" ' . $selected . '>' . htmlspecialchars($category) . '</option>';
                                                }
                                                
                                                // Si la catégorie actuelle n'est pas dans la liste, l'ajouter quand même
                                                if (!empty($currentAgeCategory) && !in_array($currentAgeCategory, $ageCategories)) {
                                                    echo '<option value="' . htmlspecialchars($currentAgeCategory) . '" selected>' . htmlspecialchars($currentAgeCategory) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bowType" class="form-label">Type d'arc</label>
                                            <select class="form-select" id="bowType" name="bowType">
                                                <option value="">Sélectionner...</option>
                                                <?php
                                                // Liste des types d'arc selon les choix prédéfinis dans l'application mobile
                                                $bowTypes = [
                                                    'Arc Classique',
                                                    'Arc à poulies',
                                                    'Arc droit',
                                                    'Arc de chasse',
                                                    'Arc Nu',
                                                    'Arc Libre',
                                                ];
                                                
                                                $currentBowType = $user['bowType'] ?? '';
                                                foreach ($bowTypes as $bowType) {
                                                    $selected = ($currentBowType === $bowType) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($bowType) . '" ' . $selected . '>' . htmlspecialchars($bowType) . '</option>';
                                                }
                                                
                                                // Si le type d'arc actuel n'est pas dans la liste, l'ajouter quand même
                                                if (!empty($currentBowType) && !in_array($currentBowType, $bowTypes)) {
                                                    echo '<option value="' . htmlspecialchars($currentBowType) . '" selected>' . htmlspecialchars($currentBowType) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="arrivalYear" class="form-label">Année d'arrivée</label>
                                            <input type="number" class="form-control" id="arrivalYear" name="arrivalYear" 
                                                   value="<?php echo htmlspecialchars($user['arrivalYear'] ?? ''); ?>" 
                                                   min="2000" max="<?php echo date('Y'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="clubId" class="form-label">Club</label>
                                            <select class="form-select" id="clubId" name="clubId">
                                                <option value="">Sélectionner un club...</option>
                                                <?php
                                                $currentClubId = $user['club'] ?? $user['clubId'] ?? '';
                                                if (!empty($clubs)) {
                                                    foreach ($clubs as $club) {
                                                        $selected = ($currentClubId === $club['nameShort']) ? 'selected' : '';
                                                        $displayName = !empty($club['name']) ? $club['name'] . ' (' . $club['nameShort'] . ')' : $club['nameShort'];
                                                        echo '<option value="' . htmlspecialchars($club['nameShort']) . '" ' . $selected . '>' . htmlspecialchars($displayName) . '</option>';
                                                    }
                                                }
                                                // Si le club actuel n'est pas dans la liste, l'ajouter quand même
                                                if (!empty($currentClubId)) {
                                                    $found = false;
                                                    foreach ($clubs as $club) {
                                                        if ($club['nameShort'] === $currentClubId) {
                                                            $found = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$found) {
                                                        echo '<option value="' . htmlspecialchars($currentClubId) . '" selected>' . htmlspecialchars($currentClubId) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <a href="/users/<?php echo $user['id']; ?>" class="btn btn-secondary me-2">Annuler</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Informations actuelles
                            </h5>
                        </div>
                        <div class="card-body">
                            <p><strong>ID :</strong> <?php echo htmlspecialchars($user['id']); ?></p>
                            <p><strong>Créé le :</strong> 
                                <?php 
                                if (!empty($user['createdAt'])) {
                                    $createdAt = new DateTime($user['createdAt']);
                                    echo $createdAt->format('d/m/Y à H:i');
                                } else {
                                    echo 'Non renseigné';
                                }
                                ?>
                            </p>
                            <p><strong>Dernière connexion :</strong> 
                                <?php 
                                if (!empty($user['lastLogin'])) {
                                    $lastLogin = new DateTime($user['lastLogin']);
                                    echo $lastLogin->format('d/m/Y à H:i');
                                } else {
                                    echo 'Jamais';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
