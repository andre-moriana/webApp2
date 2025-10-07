<?php
// Variables disponibles depuis le contrôleur :
// $exercises, $shootingConfigurations, $selectedUser, $isAdmin, $isCoach

// Inclure les fichiers CSS et JS spécifiques
$additionalCSS = [
    '/public/assets/css/scored-trainings.css',
    '/public/assets/css/scored-training-create.css'
];
$additionalJS = [
    '/public/assets/js/scored-trainings-simple.js?v=' . time(),
    '/public/assets/js/scored-training-create.js?v=' . time()
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Nouveau tir compté</h1>
                <a href="/scored-trainings" class="btn btn-outline-secondary btn-back">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card create-card">
                        <div class="card-header">
                            <h5 class="mb-0">Configuration du tir compté</h5>
                        </div>
                        <div class="card-body">
                            <form id="createForm" class="create-form">
                                <div class="form-section">
                                    <h6>Informations générales</h6>
                                    <div class="row field-group">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="title" class="form-label required-field">Titre du tir compté</label>
                                                <input type="text" class="form-control" id="title" name="title" 
                                                       placeholder="Ex: Entraînement TAE du 15/10/2024" required>
                                                <div class="form-text">Donnez un nom descriptif à votre tir compté</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="exercise_sheet_id" class="form-label">Exercice associé</label>
                                                <select class="form-select" id="exercise_sheet_id" name="exercise_sheet_id">
                                                    <option value="">Aucun exercice (tir libre)</option>
                                                    <?php foreach ($exercises as $exercise): ?>
                                                    <option value="<?= $exercise['id'] ?>">
                                                        <?= htmlspecialchars($exercise['title']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">Optionnel - associer à un exercice existant</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="total_ends" class="form-label">Nombre de volées *</label>
                                            <input type="number" class="form-control" id="total_ends" name="total_ends" 
                                                   min="1" max="50" value="6" required>
                                            <div class="form-text">Nombre total de volées prévues</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="arrows_per_end" class="form-label">Flèches par volée *</label>
                                            <input type="number" class="form-control" id="arrows_per_end" name="arrows_per_end" 
                                                   min="1" max="12" value="6" required>
                                            <div class="form-text">Nombre de flèches par volée</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="shooting_type" class="form-label">Type de tir</label>
                                            <select class="form-select" id="shooting_type" name="shooting_type" onchange="updateShootingConfiguration()">
                                                <option value="">Sélectionner un type</option>
                                                <option value="TAE">TAE (Tir à l'Arc en Extérieur)</option>
                                                <option value="Salle">Salle</option>
                                                <option value="3D">3D</option>
                                                <option value="Nature">Nature</option>
                                                <option value="Campagne">Campagne</option>
                                                <option value="Libre">Libre</option>
                                            </select>
                                            <div class="form-text">Type de tir pratiqué</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Conditions météo, objectifs, remarques..."></textarea>
                                    <div class="form-text">Notes optionnelles sur le tir compté</div>
                                </div>

                                <!-- Aperçu de la configuration -->
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">Aperçu de la configuration</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Volées:</strong> <span id="preview_ends">6</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Flèches/volée:</strong> <span id="preview_arrows">6</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Total flèches:</strong> <span id="preview_total">36</span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Score max possible:</strong> <span id="preview_max">360</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <a href="/scored-trainings" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Démarrer le tir compté
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

