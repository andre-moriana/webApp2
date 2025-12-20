<?php
$title = "Import de clubs depuis XML - Portail Archers de Gémenos";
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">
                        <i class="fas fa-file-upload me-2"></i>Import de clubs depuis XML
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
                                <li>Total de clubs traités : <?php echo $_SESSION['import_results']['total']; ?></li>
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
                            <li>Les clubs seront importés avec les champs suivants :</li>
                            <ul>
                                <li><strong>AGREENUM</strong> → Identifiant du club et nom court (name_short)</li>
                                <li><strong>INntituleclub</strong> → Nom du club</li>
                                <li><strong>Localite</strong> → Localité/Ville du club</li>
                            </ul>
                            <li>Si un club existe déjà (même nom), il sera mis à jour</li>
                            <li><strong>Note :</strong> Les fichiers volumineux peuvent prendre plusieurs minutes à traiter. Ne fermez pas la page pendant l'import.</li>
                        </ul>
                    </div>
                    
                    <form action="/clubs/import/process" method="POST" enctype="multipart/form-data">
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
                        
                        <div class="d-flex justify-content-between">
                            <a href="/clubs" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Importer les clubs
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <h5><i class="fas fa-question-circle me-2"></i>Format du fichier XML attendu</h5>
                        <p>Le fichier XML doit contenir une structure similaire à :</p>
                        <pre class="bg-light p-3 rounded"><code>&lt;?xml-stylesheet type="text/xsl" href="Export2.xsl"?&gt;
&lt;WINDEV_TABLE&gt;
  &lt;TABLE_CONTENU&gt;
    &lt;IDFICLUB&gt;1&lt;/IDFICLUB&gt;
    &lt;AGREENUM&gt;0000000&lt;/AGREENUM&gt;
    &lt;INntituleclub&gt;WORLD ARCHERY&lt;/INntituleclub&gt;
    &lt;Localite&gt;ETRANGER&lt;/Localite&gt;
    &lt;numligue&gt;00&lt;/numligue&gt;
    &lt;numero&gt;000000007&lt;/numero&gt;
    &lt;Rangclub/&gt;
    &lt;fg_ffta&gt;0&lt;/fg_ffta&gt;
    &lt;ligue_unique&gt;00&lt;/ligue_unique&gt;
    &lt;departement_unique&gt;00000&lt;/departement_unique&gt;
  &lt;/TABLE_CONTENU&gt;
  &lt;TABLE_CONTENU&gt;
    &lt;IDFICLUB&gt;2&lt;/IDFICLUB&gt;
    &lt;AGREENUM&gt;0100000&lt;/AGREENUM&gt;
    &lt;INntituleclub&gt;COMITE REGIONAL AUVERGNE-RHONE ALPES&lt;/INntituleclub&gt;
    &lt;Localite&gt;CR AUVERGNE-RHONE ALPES&lt;/Localite&gt;
    ...
  &lt;/TABLE_CONTENU&gt;
&lt;/WINDEV_TABLE&gt;</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

