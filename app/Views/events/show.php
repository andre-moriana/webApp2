<?php
$title = "Portail Archers de Gémenos - Détails de l'événement";
?>
<!-- Inclusion des styles -->
<link rel="stylesheet" href="/public/assets/css/events.css">
<link rel="stylesheet" href="/public/assets/css/groups-chat.css">
<div class="container-fluid">
    <div class="row">
        <!-- Détails de l"événement -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Détails de l'événement</h1>
            </div>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($event): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($event["name"] ?? "Nom inconnu"); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Description</h6>
                            <p class="text-muted"><?php echo htmlspecialchars($event["description"] ?? "Aucune description"); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-calendar me-2"></i>Date</h6>
                            <p class="text-muted">
                                <?php 
                                if (!empty($event["date"])) {
                                    $eventDate = new DateTime($event["date"]);
                                    echo $eventDate->format("d/m/Y");
                                } else {
                                    echo "Date non précisée";
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-clock me-2"></i>Heure</h6>
                            <p class="text-muted">
                                <?php 
                                if (!empty($event["time"])) {
                                    $eventTime = new DateTime($event["time"]);
                                    echo $eventTime->format("H:i");
                                } else {
                                    echo "Heure non précisée";
                                }
                                ?>
                            </p>
                        </div>

                        
                        <div class="mb-3">
                            <h6><i class="fas fa-users me-2"></i>Participants</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="text-muted mb-0" id="detail-members-count">
                                    <?php 
                                    $membersCount = count($event["members"] ?? []);
                                    echo $membersCount;
                                    ?>
                                    <?php if (isset($event["max_participants"]) && $event["max_participants"] > 0): ?>
                                        / <?php echo $event["max_participants"]; ?> places
                                    <?php else: ?>
                                        inscrit<?php echo $membersCount > 1 ? 's' : ''; ?>
                                    <?php endif; ?>
                                </p>
                                <button onclick="fetchParticipants(<?php echo $event["_id"] ?? "null"; ?>)" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Voir la liste
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="/events/<?php echo $event["_id"] ?? "null"; ?>/edit" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <button type="button" class="btn btn-outline-danger delete-event-btn" 
                                    data-event-id="<?php echo $event["_id"] ?? "null"; ?>" 
                                    data-event-name="<?php echo htmlspecialchars($event["name"] ?? "Événement", ENT_QUOTES); ?>">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Section inscription/désinscription -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Inscription</h6>
                    </div>
                    <div class="card-body">
                        <div class="registration-status">
                            <div class="alert alert-info" id="registration-status">
                                <i class="fas fa-info-circle me-2"></i>
                                Cliquez sur "Rejoindre" pour vous inscrire à cet événement
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <!-- Bouton pour s'inscrire -->
                            <button class="btn btn-success registration-button" 
                                    onclick="registerToEvent(<?php echo $event["_id"] ?? "null"; ?>)"
                                    id="register-btn">
                                <i class="fas fa-user-plus me-1"></i>
                                Rejoindre
                            </button>
                            
                            <!-- Bouton pour se désinscrire -->
                            <button class="btn btn-warning registration-button" 
                                    onclick="unregisterFromEvent(<?php echo $event["_id"] ?? "null"; ?>)"
                                    id="unregister-btn"
                                    style="display: none;">
                                <i class="fas fa-user-minus me-1"></i>
                                Quitter
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Événement non trouvé
                </div>
            <?php endif; ?>
        </div>

        <!-- Section chat -->
        <div class="col-md-8">
            <?php if ($event && !$error): ?>
                <!-- Chat de l'événement -->
                <div id="chat-container" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="chat-title"><?php echo htmlspecialchars($event["name"]); ?></h5>
                        <div class="d-flex gap-2">
                            <a href="/events" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                            </a>
                        </div>
                    </div>
                    <div class="card-body" style="height: 500px;">
                        <div class="chat-container">
                            <div id="messages-container" class="messages-container mb-3">
                                <!-- Messages seront chargés dynamiquement par JavaScript -->
                            </div>
                            <div class="message-input-container">
                                <form id="message-form" class="d-flex gap-2">
                                    <input type="hidden" id="current-user-id" value="<?php echo $_SESSION["user"]["id"]; ?>">
                                    <input type="hidden" id="current-event-id" value="<?php echo $event["_id"]; ?>">
                                    <div class="flex-grow-1">
                                        <input type="text" id="message-input" class="form-control" placeholder="Votre message...">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <div class="btn btn-outline-secondary position-relative">
                                            <i class="fas fa-paperclip"></i>
                                            <input type="file" id="message-attachment" class="position-absolute top-0 start-0 opacity-0" style="width:100%; height:100%; cursor:pointer;">
                                        </div>
                                        <?php if ($_SESSION["user"]["is_admin"]): ?>
                                        <button type="button" class="btn btn-outline-secondary" onclick="openEventFormBuilder()" title="Créer un formulaire">
                                            <i class="fas fa-table"></i> 📊
                                        </button>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Aucun événement disponible -->
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Aucun événement disponible</h4>
                    <p class="text-muted">Sélectionnez un événement pour voir le chat</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer l"événement "<span id="eventName"></span>" ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Variables PHP pour JavaScript -->
<script>
const currentUserId = <?php echo $_SESSION["user"]["id"]; ?>;
const initialEventId = <?php echo $event ? $event["_id"] : "null"; ?>;
// backendUrl supprimé - tous les appels passent maintenant par le backend WebApp2
const isAdmin = <?php echo $_SESSION["user"]["is_admin"] ? "true" : "false"; ?>;
const authToken = "<?php echo $_SESSION["token"] ?? ""; ?>";
</script>

<!-- Inclusion du JavaScript -->
<script src="/public/assets/js/events-chat.js"></script>
<script src="/public/assets/js/events.js"></script>








