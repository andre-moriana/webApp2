<?php
// Variables disponibles depuis le contrôleur
?>

<div class="container-fluid score-sheet-container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Feuille de marque
                </h1>
                <a href="/scored-trainings" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>

            <!-- Sélection du type de tir -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Type de tir</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="shootingType" class="form-label">Sélectionner le type de tir</label>
                            <select class="form-select" id="shootingType" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="Salle">Salle (2 x 10 volées de 3 flèches)</option>
                                <option value="TAE">TAE (2 x 6 volées de 6 flèches)</option>
                                <option value="Nature">Nature (21 volées de 2 flèches)</option>
                                <option value="3D">3D (24 volées de 2 flèches)</option>
                                <option value="Campagne">Campagne (24 volées de 3 flèches)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="trainingTitle" class="form-label">Titre de la feuille de marque</label>
                            <input type="text" class="form-control" id="trainingTitle" placeholder="Ex: Compétition du 15/11/2024">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation entre les archers -->
            <div id="archerNavigation" class="mb-3" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-outline-primary" id="prevArcherBtn" onclick="navigateArcher(-1)">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </button>
                        <span class="mx-3">
                            Archer <span id="currentArcherNumber">1</span> / <span id="totalArchers">6</span>
                        </span>
                        <button class="btn btn-outline-primary" id="nextArcherBtn" onclick="navigateArcher(1)">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-info me-2" id="signaturesBtn" onclick="openSignatureModal()" style="display: none;">
                            <i class="fas fa-signature"></i> Signatures
                        </button>
                        <button class="btn btn-primary me-2" id="exportPdfBtn" onclick="exportToPDF()" style="display: none;">
                            <i class="fas fa-file-pdf"></i> Exporter PDF
                        </button>
                        <button class="btn btn-success" id="saveScoreSheetBtn" onclick="saveScoreSheet()" style="display: none;">
                            <i class="fas fa-save"></i> Sauvegarder les feuilles de marque
                        </button>
                    </div>
                </div>
            </div>

            <!-- Informations de l'archer -->
            <div id="archerInfoSection" class="card mb-4" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">Informations de l'archer <span id="archerHeaderNumber">1</span></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="archerName" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="archerName" placeholder="Nom de l'archer">
                        </div>
                        <div class="col-md-3">
                            <label for="archerLicense" class="form-label">Numéro de licence</label>
                            <input type="text" class="form-control" id="archerLicense" placeholder="Ex: 660035U">
                        </div>
                        <div class="col-md-2">
                            <label for="archerCategory" class="form-label">Catégorie</label>
                            <select class="form-select" id="archerCategory">
                                <option value="">--</option>
                                <option value="U11">U11</option>
                                <option value="U13">U13</option>
                                <option value="U15">U15</option>
                                <option value="U18">U18</option>
                                <option value="U21">U21</option>
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="archerWeapon" class="form-label">Arme</label>
                            <select class="form-select" id="archerWeapon">
                                <option value="">--</option>
                                <option value="Arc classique">Arc classique</option>
                                <option value="Arc à poulies">Arc à poulies</option>
                                <option value="Arc nu (barebow)">Arc nu (barebow)</option>
                                <option value="Longbow">Longbow</option>
                                <option value="Arc de chasse">Arc de chasse</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="archerGender" class="form-label">Genre</label>
                            <select class="form-select" id="archerGender">
                                <option value="">--</option>
                                <option value="H">Homme</option>
                                <option value="F">Femme</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des scores -->
            <div id="scoreTableSection" class="card mb-4" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">Scores</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm score-table" id="scoreTable">
                            <thead>
                                <tr>
                                    <th class="volley-col">Volée</th>
                                    <th id="arrowHeaders" colspan="3"></th>
                                    <th class="total-col">Total</th>
                                    <th id="cumulativeHeader" class="cumulative-col" style="display: none;">Cumul</th>
                                </tr>
                            </thead>
                            <tbody id="scoreTableBody">
                                <!-- Les lignes seront générées par JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>TOTAL</th>
                                    <td id="footerArrows" colspan="3"></td>
                                    <th id="grandTotal">0</th>
                                    <th id="footerCumulative" style="display: none;"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Message d'état -->
            <div id="statusMessage" class="alert" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Modal pour saisir les scores d'une volée -->
<div class="modal fade" id="scoreModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Volée <span id="modalVolleyNumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="scoreInputTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#tableMode" type="button" role="tab">
                            <i class="fas fa-table"></i> Tableau
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="target-tab" data-bs-toggle="tab" data-bs-target="#targetMode" type="button" role="tab">
                            <i class="fas fa-bullseye"></i> Cible interactive
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="scoreInputContent">
                    <div class="tab-pane fade show active" id="tableMode" role="tabpanel">
                        <div id="scoreInputs">
                            <!-- Les inputs seront générés par JavaScript -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="targetMode" role="tabpanel">
                        <div id="targetContainer" class="text-center">
                            <canvas id="targetCanvas" width="400" height="400" style="border: 1px solid #ccc; cursor: crosshair;"></canvas>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearTarget()">
                                    <i class="fas fa-undo"></i> Réinitialiser
                                </button>
                            </div>
                            <div id="targetScores" class="mt-3">
                                <h6>Scores sélectionnés :</h6>
                                <div id="targetScoresList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveVolleyScores()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les signatures -->
<div class="modal fade" id="signatureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Signatures</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="signatureTabs" class="mb-3">
                    <ul class="nav nav-tabs" id="signatureTabList" role="tablist">
                        <!-- Les onglets seront générés par JavaScript -->
                    </ul>
                    <div class="tab-content" id="signatureTabContent">
                        <!-- Le contenu sera généré par JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-success" onclick="saveSignatures()">
                    <i class="fas fa-save"></i> Enregistrer les signatures
                </button>
            </div>
        </div>
    </div>
</div>

