<?php
$title = "Gestion des événements - Portail Archers de Gémenos";
?>

<!-- Inclusion des styles -->
<link rel="stylesheet" href="/public/assets/css/events.css">

<div class="container-fluid">
    <div class="row">
        <!-- Liste des événements -->
        <div class="col-md-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Événements</h1>
                <a href="/events/create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-2"></i>Nouvel événement
                </a>
            </div>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="list-group">
                <?php if (empty($events)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun événement à venir</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $index => $event): ?>
                        <?php 
                        // Debug: vérifier les données de l'événement
                        error_log("Événement " . $index . ": " . json_encode($event));
                        ?>
                        <div class="list-group-item list-group-item-action event-item <?php echo $index === 0 ? "active" : ""; ?>" 
                             data-event-id="<?php echo $event["_id"] ?? "null"; ?>"
                             style="cursor: pointer;">
                            <div class="d-flex">
                                <div class="avatar-sm bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($event["name"] ?? "Nom inconnu"); ?></h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary members-count" data-event-id="<?php echo $event["_id"] ?? "null"; ?>">
                                                <i class="fas fa-users me-1"></i>
                                                <?php 
                                                $membersCount = count($event["members"] ?? []);
                                                echo $membersCount;
                                                ?>
                                                <?php if (isset($event["max_participants"]) && $event["max_participants"] > 0): ?>
                                                    / <?php echo $event["max_participants"]; ?>
                                                <?php endif; ?>
                                            </span>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/events/<?php echo $event["_id"] ?? "null"; ?>/edit" class="btn btn-outline-primary btn-sm" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete-event-btn" 
                                                        data-event-id="<?php echo $event["_id"] ?? "null"; ?>" 
                                                        data-event-name="<?php echo htmlspecialchars($event["name"] ?? "Événement", ENT_QUOTES); ?>" 
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>   
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-1 small">
                                        <?php echo htmlspecialchars($event["description"] ?? "Aucune description"); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php 
                                            if (!empty($event["date"])) {
                                                $eventDate = new DateTime($event["date"]);
                                                echo $eventDate->format("d/m/Y H:i");
                                            } else {
                                                echo "Date inconnue";
                                            }
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section chat -->
        <div class="col-md-8">
            <?php if (!empty($events) && isset($events[0]) && isset($events[0]["_id"])): ?>
                <!-- Chat du premier événement (affiché par défaut) -->
                <div id="chat-container" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="chat-title"><?php echo htmlspecialchars($events[0]["name"]); ?></h5>
                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-outline-primary btn-sm" id="view-details-btn">
                                <i class="fas fa-eye me-1"></i>Voir détails
                            </a>
                        </div>
                    </div>
                    <div class="card-body" style="height: 500px;">
                        <div class="chat-container">
                            <div id="messages-container" class="messages-container mb-3">
                                <!-- Messages chargés dynamiquement -->
                            </div>
                            <div class="message-input-container">
                                <form id="message-form" class="d-flex gap-2">
                                    <input type="hidden" id="current-user-id" value="<?php echo $_SESSION["user"]["id"]; ?>">
                                    <input type="hidden" id="current-event-id" value="<?php echo (isset($events[0]) && isset($events[0]["_id"])) ? $events[0]["_id"] : "null"; ?>">
                                    <div class="flex-grow-1">
                                        <input type="text" id="message-input" class="form-control" placeholder="Votre message...">
                                    </div>
                                    <div class="d-flex gap-2">
                                        <div class="btn btn-outline-secondary position-relative">
                                            <i class="fas fa-paperclip"></i>
                                            <input type="file" id="message-attachment" class="position-absolute top-0 start-0 opacity-0" style="width:100%; height:100%; cursor:pointer;">
                                        </div>
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
                Êtes-vous sûr de vouloir supprimer l'événement "<span id="eventName"></span>" ? Cette action est irréversible.
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
const initialEventId = <?php echo (!empty($events) && isset($events[0]) && isset($events[0]["_id"])) ? json_encode($events[0]["_id"]) : "null"; ?>;
// backendUrl supprimé - tous les appels passent maintenant par le backend WebApp2
const isAdmin = <?php echo $_SESSION["user"]["is_admin"] ? "true" : "false"; ?>;
const authToken = "<?php echo $_SESSION["token"] ?? ""; ?>";
</script>

<!-- Inclusion du JavaScript -->
<script src="/public/assets/js/events-chat.js"></script>
<script src="/public/assets/js/events.js"></script>
















