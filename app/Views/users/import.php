<?php
$title = "Import d'utilisateurs depuis XML - Portail Archers de Gémenos";
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-file-upload me-2"></i>Import d'utilisateurs depuis XML
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['success']);
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['warning'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['warning']);
                            unset($_SESSION['warning']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['import_results'])): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Résultats de l'import</h5>
                            <ul class="mb-0">
                                <li>Total d'utilisateurs traités : <?php echo $_SESSION['import_results']['total']; ?></li>
                                <li>Succès : <span class="text-success"><?php echo $_SESSION['import_results']['success']; ?></span></li>
                                <li>Erreurs : <span class="text-danger"><?php echo $_SESSION['import_results']['errors']; ?></span></li>
                            </ul>
                            <?php if (!empty($_SESSION['import_results']['error_messages'])): ?>
                                <details class="mt-3">
                                    <summary class="text-danger">Voir les erreurs</summary>
                                    <ul class="mt-2">
                                        <?php foreach ($_SESSION['import_results']['error_messages'] as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </div>
                        <?php unset($_SESSION['import_results']); ?>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instructions :</strong>
                        <ul class="mb-0 mt-2">
                            <li>Sélectionnez un fichier XML au format WINDEV_TABLE</li>
                            <li>Optionnellement, sélectionnez un club pour filtrer les utilisateurs</li>
                            <li>Les utilisateurs seront créés avec un statut "en attente" et devront être validés par un administrateur</li>
                            <li>Les mots de passe temporaires seront générés automatiquement au format : <code>Temp[6 derniers chiffres de l'ID licence]!</code></li>
                            <li><strong>Important :</strong> Les utilisateurs importés ne pourront se connecter qu'après validation de leur compte dans la liste des utilisateurs</li>
                            <li><strong>Note :</strong> Les fichiers volumineux (100+ MB) peuvent prendre plusieurs minutes à traiter. Ne fermez pas la page pendant l'import.</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Identifiants générés :</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Username :</strong> Généré à partir du prénom + nom + 4 derniers chiffres de l'ID licence (ex: jeanmartin6142)</li>
                            <li><strong>Email :</strong> Généré au format prénom.nom@archers-gemenos.fr (ex: jean.martin@archers-gemenos.fr)</li>
                            <li><strong>Mot de passe :</strong> Temp[6 derniers chiffres de l'ID licence]! (ex: Temp6142Y!)</li>
                            <li>Pour se connecter, utilisez le <strong>username</strong> ou l'<strong>email complet</strong> avec le mot de passe généré</li>
                        </ul>
                    </div>
                    
                    <form action="/users/import/process" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="xml_file" class="form-label">
                                <i class="fas fa-file-code me-2"></i>Fichier XML <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   class="form-control" 
                                   id="xml_file" 
                                   name="xml_file" 
                                   accept=".xml,application/xml,text/xml"
                                   required>
                            <small class="form-text text-muted">
                                Format attendu : WINDEV_TABLE avec des éléments TABLE_CONTENU. Taille maximale : 200 MB.
                                <?php 
                                $phpMaxUpload = ini_get('upload_max_filesize');
                                $phpMaxPost = ini_get('post_max_size');
                                if ($phpMaxUpload || $phpMaxPost) {
                                    echo '<br><strong>Limite serveur PHP :</strong> upload_max_filesize = ' . htmlspecialchars($phpMaxUpload) . 
                                         ', post_max_size = ' . htmlspecialchars($phpMaxPost);
                                }
                                ?>
                            </small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="club_name" class="form-label">
                                <i class="fas fa-users me-2"></i>Club (optionnel)
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="club_name" 
                                   name="club_name" 
                                   placeholder="Laissez vide pour importer tous les clubs, ou saisissez le nom exact du club">
                            <small class="form-text text-muted">
                                Si un club est spécifié, seuls les utilisateurs de ce club seront importés. 
                                Le nom doit correspondre exactement au champ CIE du fichier XML.
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/users" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Importer les utilisateurs
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <h5><i class="fas fa-question-circle me-2"></i>Format du fichier XML attendu</h5>
                        <p>Le fichier XML doit contenir une structure similaire à :</p>
                        <pre class="bg-light p-3 rounded"><code>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;WINDEV_TABLE&gt;
  &lt;TABLE_CONTENU&gt;
    &lt;NOM&gt;ABAAJAN&lt;/NOM&gt;
    &lt;PRENOM&gt;SOUAD&lt;/PRENOM&gt;
    &lt;IDLicence&gt;1046142Y&lt;/IDLicence&gt;
    &lt;CIE&gt;SAINT CLOUD&lt;/CIE&gt;
    &lt;DATENAISSANCE&gt;14/08/1977&lt;/DATENAISSANCE&gt;
    &lt;SEXE&gt;2&lt;/SEXE&gt;
    &lt;CATEGORIE&gt;CLS2D&lt;/CATEGORIE&gt;
    ...
  &lt;/TABLE_CONTENU&gt;
&lt;/WINDEV_TABLE&gt;</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

