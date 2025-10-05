<?php
// Variables disponibles depuis le contrôleur :
// $scoredTrainings, $exercises, $shootingConfigurations, $users, $stats, $selectedUser, $isAdmin, $isCoach

// S'assurer que $scoredTrainings est un array AVANT toute utilisation
if (!is_array($scoredTrainings)) {
    $scoredTrainings = [];
}

// Inclure les fichiers CSS et JS spécifiques
$additionalCSS = ['/public/assets/css/scored-trainings.css'];
$additionalJS = ['/public/assets/js/scored-trainings.js?v=' . time() . '&debug=2'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Tirs comptés</h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Nouveau tir compté
                    </button>
                    <?php if ($isAdmin || $isCoach): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($selectedUser['name'] ?? 'Utilisateur') ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($users as $user): ?>
                            <li>
                                <a class="dropdown-item" href="?user_id=<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['name']) ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= $stats['total_trainings'] ?></h4>
                                    <p class="card-text">Tirs comptés</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-bullseye fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= $stats['total_arrows'] ?></h4>
                                    <p class="card-text">Flèches tirées</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-right fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= number_format($stats['average_score'], 1) ?></h4>
                                    <p class="card-text">Score moyen</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title"><?= $stats['best_training_score'] ?></h4>
                                    <p class="card-text">Meilleur score</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-trophy fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="exerciseFilter" class="form-label">Exercice</label>
                            <select class="form-select" id="exerciseFilter" onchange="filterTrainings()">
                                <option value="">Tous les exercices</option>
                                <?php foreach ($exercises as $exercise): ?>
                                <option value="<?= $exercise['id'] ?>">
                                    <?= htmlspecialchars($exercise['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="shootingTypeFilter" class="form-label">Type de tir</label>
                            <select class="form-select" id="shootingTypeFilter" onchange="filterTrainings()">
                                <option value="">Tous les types</option>
                                <option value="TAE">TAE</option>
                                <option value="Salle">Salle</option>
                                <option value="3D">3D</option>
                                <option value="Nature">Nature</option>
                                <option value="Campagne">Campagne</option>
                                <option value="Libre">Libre</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="statusFilter" class="form-label">Statut</label>
                            <select class="form-select" id="statusFilter" onchange="filterTrainings()">
                                <option value="">Tous les statuts</option>
                                <option value="en_cours">En cours</option>
                                <option value="terminé">Terminé</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des tirs comptés -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="scoredTrainingsTable">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Exercice</th>
                                    <th>Type de tir</th>
                                    <th>Statut</th>
                                    <th>Score</th>
                                    <th>Flèches</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // S'assurer que $scoredTrainings est un array AVANT toute vérification
                                if (!is_array($scoredTrainings)) {
                                    $scoredTrainings = [];
                                }
                                
                                if (empty($scoredTrainings)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        Aucun tir compté trouvé
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($scoredTrainings as $training): ?>
                                <?php if (!is_array($training)) continue; ?>
                                <tr data-exercise-id="<?= $training['exercise_sheet_id'] ?? '' ?>" 
                                    data-shooting-type="<?= $training['shooting_type'] ?? '' ?>"
                                    data-status="<?= $training['status'] ?? '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($training['title'] ?? 'Sans titre') ?></strong>
                                        <?php if (!empty($training['notes'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($training['notes'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($training['exercise_sheet_id'])): ?>
                                            <?php
                                            $exercise = array_filter($exercises, function($e) use ($training) {
                                                return $e['id'] == $training['exercise_sheet_id'];
                                            });
                                            $exercise = reset($exercise);
                                            ?>
                                            <?= $exercise ? htmlspecialchars($exercise['title']) : 'Exercice supprimé' ?>
                                        <?php else: ?>
                                            <span class="text-muted">Aucun exercice</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($training['shooting_type'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($training['shooting_type']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($training['status'] ?? '') === 'en_cours'): ?>
                                            <span class="badge bg-warning">En cours</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Terminé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= $training['total_score'] ?? 0 ?></strong>
                                        <?php if (!empty($training['average_score'])): ?>
                                        <br><small class="text-muted">Moy: <?= number_format($training['average_score'], 1) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $training['total_arrows'] ?? 0 ?>
                                        <br><small class="text-muted"><?= $training['total_ends'] ?? 0 ?> volées</small>
                                    </td>
                                    <td>
                                        <?php if (!empty($training['start_date'])): ?>
                                            <?= date('d/m/Y H:i', strtotime($training['start_date'])) ?>
                                            <?php if (!empty($training['end_date'])): ?>
                                            <br><small class="text-muted">Fin: <?= date('d/m/Y H:i', strtotime($training['end_date'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" data-training-id="<?= $training['id'] ?? 0 ?>" onclick="viewTraining(this.dataset.trainingId)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (($training['status'] ?? '') === 'en_cours'): ?>
                                            <button class="btn btn-sm btn-outline-success" data-training-id="<?= $training['id'] ?? 0 ?>" onclick="continueTraining(this.dataset.trainingId)">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger" data-training-id="<?= $training['id'] ?? 0 ?>" onclick="deleteTraining(this.dataset.trainingId)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de création -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau tir compté</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Titre *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="exercise_sheet_id" class="form-label">Exercice</label>
                                <select class="form-select" id="exercise_sheet_id" name="exercise_sheet_id">
                                    <option value="">Aucun exercice</option>
                                    <?php foreach ($exercises as $exercise): ?>
                                    <option value="<?= $exercise['id'] ?>">
                                        <?= htmlspecialchars($exercise['title']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="total_ends" class="form-label">Nombre de volées *</label>
                                <input type="number" class="form-control" id="total_ends" name="total_ends" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="arrows_per_end" class="form-label">Flèches par volée *</label>
                                <input type="number" class="form-control" id="arrows_per_end" name="arrows_per_end" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="shooting_type" class="form-label">Type de tir</label>
                                <select class="form-select" id="shooting_type" name="shooting_type" onchange="updateShootingConfiguration()">
                                    <option value="">Sélectionner un type</option>
                                    <option value="TAE">TAE</option>
                                    <option value="Salle">Salle</option>
                                    <option value="3D">3D</option>
                                    <option value="Nature">Nature</option>
                                    <option value="Campagne">Campagne</option>
                                    <option value="Libre">Libre</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="createTraining()">Créer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
const scoredTrainings = <?= json_encode($scoredTrainings) ?>;
const exercises = <?= json_encode($exercises) ?>;

// Fonctions de filtrage
function filterTrainings() {
    const exerciseFilter = document.getElementById('exerciseFilter').value;
    const shootingTypeFilter = document.getElementById('shootingTypeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    const rows = document.querySelectorAll('#scoredTrainingsTable tbody tr');
    
    rows.forEach(row => {
        const exerciseId = row.dataset.exerciseId;
        const shootingType = row.dataset.shootingType;
        const status = row.dataset.status;
        
        let show = true;
        
        if (exerciseFilter && exerciseId !== exerciseFilter) {
            show = false;
        }
        
        if (shootingTypeFilter && shootingType !== shootingTypeFilter) {
            show = false;
        }
        
        if (statusFilter && status !== statusFilter) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// Fonctions de gestion des tirs comptés
function openCreateModal() {
    const modal = new bootstrap.Modal(document.getElementById('createModal'));
    modal.show();
}

function createTraining() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    const data = {
        title: formData.get('title'),
        total_ends: parseInt(formData.get('total_ends')),
        arrows_per_end: parseInt(formData.get('arrows_per_end')),
        exercise_sheet_id: formData.get('exercise_sheet_id') || null,
        notes: formData.get('notes'),
        shooting_type: formData.get('shooting_type') || null
    };
    
    fetch('/scored-trainings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Erreur: ' + (result.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la création du tir compté');
    });
}

function continueTraining(trainingId) {
    window.location.href = '/scored-trainings/' + trainingId;
}

function deleteTraining(trainingId) {
    console.log('🗑️ Tentative de suppression du tir compté ID:', trainingId);
    
    // Vérifier si l'utilisateur est connecté
    <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
        alert('Vous devez être connecté pour effectuer cette action.\n\nVeuillez vous reconnecter.');
        window.location.href = '/login';
        return;
    <?php endif; ?>
    
    if (confirm('Êtes-vous sûr de vouloir supprimer ce tir compté ?')) {
        console.log('✅ Confirmation reçue, envoi de la requête...');
        
        // Faire la requête vers le contrôleur frontend
        fetch('/scored-trainings/delete/' + trainingId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('📡 Réponse reçue:', response.status, response.statusText);
            console.log('📡 Headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                console.error('❌ Erreur HTTP:', response.status, response.statusText);
                throw new Error('Erreur HTTP: ' + response.status + ' ' + response.statusText);
            }
            return response.text();
        })
        .then(text => {
            console.log('📄 Réponse brute reçue:', text);
            
            // Nettoyer la réponse des caractères BOM et autres caractères invisibles
            let cleanText = text.replace(/^\uFEFF/, '').replace(/^\s+/, '').replace(/\s+$/, '');
            
            // Supprimer les warnings PHP qui peuvent apparaître avant le JSON
            cleanText = cleanText.replace(/^.*?(Warning:.*?\n)*/g, '');
            
            // Extraire seulement le JSON si il y a du contenu avant
            const jsonMatch = cleanText.match(/\{.*\}/s);
            if (jsonMatch) {
                cleanText = jsonMatch[0];
            }
            
            console.log('🧹 Texte nettoyé:', cleanText);
            
            try {
                const result = JSON.parse(cleanText);
                console.log('📊 JSON parsé:', result);
                
                if (result.success) {
                    console.log('✅ Suppression réussie, rechargement de la page...');
                    // Préserver les paramètres de l'URL lors du rechargement
                    const currentUrl = new URL(window.location);
                    window.location.href = currentUrl.toString();
                } else {
                    console.error('❌ Suppression échouée:', result.message);
                    
                    // Vérifier si c'est un problème d'authentification
                    if (result.message && (
                        result.message.includes('connecté') || 
                        result.message.includes('Token') ||
                        result.message.includes('authentification') ||
                        result.status_code === 401
                    )) {
                        alert('Erreur d\'authentification: ' + result.message + '\n\nVeuillez vous reconnecter.');
                        console.log('🔄 Redirection vers la page de connexion...');
                        window.location.href = '/login';
                    } else if (result.status_code === 400) {
                        alert('Erreur de requête (400): ' + (result.message || 'Données invalides'));
                    } else {
                        alert('Erreur: ' + (result.message || 'Erreur inconnue'));
                    }
                }
            } catch (parseError) {
                console.error('❌ Erreur de parsing JSON:', parseError);
                console.error('❌ Texte reçu:', cleanText);
                console.error('❌ Longueur du texte:', cleanText.length);
                console.error('❌ Premiers caractères:', cleanText.substring(0, 100));
                alert('Erreur de décodage de la réponse du serveur:\n' + parseError.message + '\n\nTexte reçu: ' + cleanText.substring(0, 200));
            }
        })
        .catch(error => {
            console.error('❌ Erreur dans la requête:', error);
            alert('Erreur lors de la suppression: ' + error.message);
        });
    } else {
        console.log('❌ Suppression annulée par l\'utilisateur');
    }
}
</script>
