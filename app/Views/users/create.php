<?php if (isset($_SESSION['errors'])): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['errors']); ?>
<?php endif; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2>Créer un nouvel utilisateur</h2>
                </div>
                <div class="card-body">
                    <form action="/users" method="POST" id="createUserForm">
                        <div class="form-group mb-3">
                            <label for="licenceNumber">Numéro de licence</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="licenceNumber" 
                                       name="licenceNumber" 
                                       value="<?php echo htmlspecialchars($_SESSION['old_input']['licenceNumber'] ?? ''); ?>"
                                       placeholder="Entrez le numéro de licence pour rechercher l'utilisateur">
                                <button type="button" class="btn btn-outline-secondary" id="searchByLicence" title="Rechercher l'utilisateur">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Si l'utilisateur existe déjà, le formulaire sera automatiquement rempli.</small>
                            <div id="licenceSearchResult" class="mt-2"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name">Nom *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($_SESSION['old_input']['name'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name">Prenom *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="<?php echo htmlspecialchars($_SESSION['old_input']['first_name'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="username">Nom d'utilisateur *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($_SESSION['old_input']['username'] ?? ''); ?>" 
                                   required>
                            <small class="form-text text-muted">Ce nom sera utilisé pour la connexion.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="email">Email *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($_SESSION['old_input']['email'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="password">Mot de passe *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                            <small class="form-text text-muted">Le mot de passe doit contenir au moins 6 caractères.</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                            <a href="/users" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Nettoyage des données de session après affichage
unset($_SESSION['old_input']);
?>
